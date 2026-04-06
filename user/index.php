<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

$user = $_SESSION['user'];
$nama = $user['nama'];
$kelas = $user['kelas'];
$userId = $user['id'];

// Saldo poin approved
$querySaldoApproved = "
    SELECT SUM(dp.subtotal_poin) AS saldo_approved
    FROM penyetoran p
    JOIN detail_penyetoran dp ON p.id = dp.penyetoran_id
    WHERE p.user_id = ? AND p.status = 'approved';
";
$stmtSaldo = $conn->prepare($querySaldoApproved);
$stmtSaldo->bind_param("i", $userId);
$stmtSaldo->execute();
$resSaldo = $stmtSaldo->get_result();
$rowSaldo = $resSaldo->fetch_assoc();
$saldo = $rowSaldo['saldo_approved'] ?? 0;

// Top 3 jenis sampah
$topSampah = [];
$top = mysqli_query($conn, "SELECT nama, poin_per_satuan FROM jenis_sampah ORDER BY poin_per_satuan DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($top)) {
    $topSampah[] = $row;
}

// Total berat sampah minggu ini
$total_mingguan = 0;
$hari_ini = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('monday this week', strtotime($hari_ini)));
$endDate = date('Y-m-d', strtotime('sunday this week', strtotime($hari_ini)));

$queryMingguan = "
    SELECT SUM(dp.jumlah) AS total_mingguan
    FROM penyetoran p
    JOIN detail_penyetoran dp ON p.id = dp.penyetoran_id
    WHERE p.user_id = ? AND p.tanggal BETWEEN ? AND ?
";
$stmtMingguan = $conn->prepare($queryMingguan);
$stmtMingguan->bind_param("iss", $userId, $startDate, $endDate);
$stmtMingguan->execute();
$resultMingguan = $stmtMingguan->get_result();
$rowMingguan = $resultMingguan->fetch_assoc();
$total_mingguan = $rowMingguan['total_mingguan'] ?? 0;

// Salam waktu
$hour = date('H');
if ($hour < 12) $greeting = "Selamat pagi";
elseif ($hour < 17) $greeting = "Selamat siang";
else $greeting = "Selamat malam";

// Grafik Senin–Jumat
$labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$total_daily_data = [0, 0, 0, 0, 0];

$queryChart = "
    SELECT DATE_FORMAT(p.tanggal, '%w') AS hari_angka, SUM(dp.jumlah) AS total
    FROM penyetoran p
    JOIN detail_penyetoran dp ON p.id = dp.penyetoran_id
    WHERE p.user_id = ? AND p.tanggal BETWEEN ? AND ?
    AND DATE_FORMAT(p.tanggal, '%w') BETWEEN 1 AND 5
    GROUP BY hari_angka
";
$stmtChart = $conn->prepare($queryChart);
$stmtChart->bind_param("iss", $userId, $startDate, $endDate);
$stmtChart->execute();
$resultChart = $stmtChart->get_result();
while ($row = mysqli_fetch_assoc($resultChart)) {
    $index = (int)$row['hari_angka'] - 1;
    if (isset($total_daily_data[$index])) {
        $total_daily_data[$index] = (float)$row['total'];
    }
}

// Ambil 3 besar pengguna berdasarkan poin terbanyak
$rankingQuery = mysqli_query($conn, "
    SELECT nama, kelas, total_poin 
    FROM users 
    ORDER BY total_poin DESC 
    LIMIT 3
");
$rankings = [];
while ($row = mysqli_fetch_assoc($rankingQuery)) {
    $rankings[] = $row;
}

// Persentase kenaikan dari minggu lalu
$lastWeekStart = date('Y-m-d', strtotime('monday last week', strtotime($hari_ini)));
$lastWeekEnd = date('Y-m-d', strtotime('sunday last week', strtotime($hari_ini)));

$queryLastWeek = "
    SELECT SUM(dp.jumlah) AS total_last_week
    FROM penyetoran p
    JOIN detail_penyetoran dp ON p.id = dp.penyetoran_id
    WHERE p.user_id = ? AND p.tanggal BETWEEN ? AND ?
";
$stmtLastWeek = $conn->prepare($queryLastWeek);
$stmtLastWeek->bind_param("iss", $userId, $lastWeekStart, $lastWeekEnd);
$stmtLastWeek->execute();
$resultLastWeek = $stmtLastWeek->get_result();
$rowLastWeek = $resultLastWeek->fetch_assoc();
$total_last_week = $rowLastWeek['total_last_week'] ?? 0;

$percentage_change = 0;
if ($total_last_week > 0) {
    $percentage_change = (($total_mingguan - $total_last_week) / $total_last_week) * 100;
}

$rankings_query = "
    SELECT 
        u.nama, 
        u.kelas, 
        SUM(dp.subtotal_poin) AS total_poin 
    FROM users u
    LEFT JOIN penyetoran p ON p.user_id = u.id AND p.status = 'approved'
    LEFT JOIN detail_penyetoran dp ON dp.penyetoran_id = p.id
    GROUP BY u.id
    ORDER BY total_poin DESC
    LIMIT 3
";

$rankings_result = mysqli_query($conn, $rankings_query);
$rankings = [];
while ($row = mysqli_fetch_assoc($rankings_result)) {
    $rankings[] = $row;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .quick-action-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
        }
        
        .quick-action-item:hover {
            transform: translateY(-2px);
        }
        
        .quick-action-item:active {
            transform: translateY(0) scale(0.98);
        }
        
        .icon-container {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .icon-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0.3));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .quick-action-item:hover .icon-container::before {
            opacity: 1;
        }
        
        .icon-container:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .action-text {
            transition: all 0.3s ease;
        }
        
        .quick-action-item:hover .action-text {
            transform: translateY(-1px);
        }
        
        /* Responsive grid improvements */
        @media (max-width: 640px) {
            .actions-grid {
                gap: 1rem;
            }
            
            .icon-container {
                width: 3.5rem;
                height: 3.5rem;
            }
            
            .action-text {
                font-size: 0.75rem;
                line-height: 1rem;
            }
        }
        
        @media (min-width: 641px) {
            .actions-grid {
                gap: 1.5rem;
            }
            
            .icon-container {
                width: 4rem;
                height: 4rem;
            }
        }
        
        /* Subtle animations */
        .section-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen p-0"> 

<div class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600"></div>
    <div class="absolute inset-0 bg-black opacity-10"></div>
    
    <div class="relative px-4 sm:px-6 pt-16 pb-12"> <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-3 sm:space-x-4 flex-grow min-w-0"> <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl">♻️</span>
                </div>
                <div class="min-w-0"> <p class="text-white/80 text-sm truncate"><?= $greeting ?>,</p>
                    <p class="text-white font-bold text-xl truncate"><?= htmlspecialchars($nama) ?></p>
                    <p class="text-white/70 text-xs truncate"><?= htmlspecialchars($kelas) ?> • SMK Telkom</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0"> <div class="relative flex-shrink-0">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="text-white text-lg">🔔</span>
                    </div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs font-bold">3</span>
                    </div>
                </div>
                <a href="profile.php" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-lg">⚙️</span>
                </a>
            </div>
        </div>

        <div class="relative mb-6">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input 
                type="text" 
                placeholder="Cari layanan bank sampah..." 
                class="w-full pl-12 pr-4 py-3 bg-white/95 backdrop-blur-sm rounded-2xl text-gray-700 placeholder-gray-500 shadow-lg border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all duration-300"
            />
        </div>
    </div>
</div>

<div class="px-6 -mt-8 relative z-10 pb-24">
    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-8 card-hover">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-gray-500 text-sm mb-1">Total Poin Approved</p>
                <div class="flex items-center space-x-3">
                    <p class="text-5xl font-black bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent"><?= number_format($saldo) ?></p>
                    <div class="flex flex-col">
                        <span class="text-2xl pulse-animation">✨</span>
                        <div class="status-badge bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                            Active
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    🏆
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Sampah Terkumpul Minggu Ini</p>
                    <h2 class="text-2xl font-bold text-emerald-800"><?= number_format($total_mingguan, 2, ',', '.') ?> Kg</h2>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-12 h-12 bg-emerald-200 rounded-full flex items-center justify-center">
                        <span class="text-emerald-700 text-xl">📊</span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs <?= $percentage_change >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $percentage_change >= 0 ? '+' : '' ?><?= number_format($percentage_change, 1) ?>%
                        </p>
                        <p class="text-xs text-gray-500">dari minggu lalu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-fade-in mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">Aksi Cepat</h3>
        </div>
    
    <div class="actions-grid grid grid-cols-4 justify-items-center">
        <a href="setor.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-emerald-600 transition-colors max-w-16">
                Setor Sampah
            </p>
        </a>
        
        <a href="tukar-poin.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-orange-600 transition-colors max-w-16">
                Tukar Poin
            </p>
        </a>
        
        <a href="riwayat.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-blue-600 transition-colors max-w-16">
                Riwayat
            </p>
        </a>
        
        <a href="statistik.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-purple-600 transition-colors max-w-16">
                Statistik
            </p>
        </a>
    </div>
    
    <div class="actions-grid grid grid-cols-4 justify-items-center mt-6">
        <a href="leaderboard.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-pink-600 transition-colors max-w-16">
                Leaderboard
            </p>
        </a>
        
        <a href="reward.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-yellow-600 transition-colors max-w-16">
                Reward
            </p>
        </a>
        
        <a href="jadwal.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-indigo-600 transition-colors max-w-16">
                Jadwal
            </p>
        </a>
        
        <a href="lokasi.php" class="quick-action-item group flex flex-col items-center">
            <div class="icon-container bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl flex items-center justify-center text-white shadow-lg mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <p class="action-text text-sm font-medium text-gray-700 text-center group-hover:text-teal-600 transition-colors max-w-16">
                Lokasi
            </p>
        </a>
    </div>
</div>

<div class="mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Top 3 Jenis Sampah</h3>
        <div class="grid grid-cols-1 gap-4">
            <?php if (count($topSampah) > 0): ?>
                <?php foreach ($topSampah as $index => $sampah): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 <?= 
                                    $index == 0 ? 'bg-blue-100' : 
                                    ($index == 1 ? 'bg-green-100' : 'bg-yellow-100') 
                                ?> rounded-full flex items-center justify-center">
                                    <span class="<?= 
                                        $index == 0 ? 'text-blue-600' : 
                                        ($index == 1 ? 'text-green-600' : 'text-yellow-600') 
                                    ?> text-xl">
                                        <?= 
                                            $index == 0 ? '🥤' : 
                                            ($index == 1 ? '📰' : '🥫')
                                        ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($sampah['nama']) ?></p>
                                    <p class="text-sm text-gray-500"><?= number_format($sampah['poin_per_satuan'], 2) ?> poin/kg</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold <?= 
                                    $index == 0 ? 'text-blue-600' : 
                                    ($index == 1 ? 'text-gray-400' : 'text-orange-400') 
                                ?>">
                                    <?= 
                                        $index == 0 ? '🥇' : 
                                        ($index == 1 ? '🥈' : '🥉')
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border border-gray-100 text-center py-8">
                    <p class="text-gray-500">Belum ada data sampah</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Grafik Penyetoran Minggu Ini</h3>
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <canvas id="chart" class="w-full h-64"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-32">
    <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-2xl mr-2">🏆</span>
        Peringkat Seluruh User
    </h2>

    <div class="space-y-4">
        <?php if (count($rankings) > 0): ?>
            <?php foreach ($rankings as $index => $r): ?>
                <div class="flex items-center justify-between p-4 <?= 
                    $index == 0 ? 'bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200' : 
                    ($index == 1 ? 'bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200' : 
                    'bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200') 
                ?> rounded-xl">
                    <div class="flex items-center space-x-4">
                        <div class="text-3xl"><?= ['🥇','🥈','🥉'][$index] ?? ($index+1) ?></div>
                        <div class="w-12 h-12 <?= 
                            $index == 0 ? 'bg-yellow-200' : 
                            ($index == 1 ? 'bg-gray-200' : 'bg-orange-200') 
                        ?> rounded-full flex items-center justify-center">
                            <span class="<?= 
                                $index == 0 ? 'text-yellow-800' : 
                                ($index == 1 ? 'text-gray-800' : 'text-orange-800') 
                            ?> font-bold">
                                <?= strtoupper(substr($r['nama'], 0, 2)) ?>
                            </span>
                        </div>
                        <div>
                            <div class="text-base font-semibold text-gray-800"><?= htmlspecialchars($r['nama']) ?></div>
                            <div class="text-sm text-gray-500">Kelas <?= htmlspecialchars($r['kelas']) ?></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold <?= 
                            $index == 0 ? 'text-yellow-600' : 
                            ($index == 1 ? 'text-gray-600' : 'text-orange-600') 
                        ?>"><?= number_format($r['total_poin'] ?? 0) ?></div>
                        <div class="text-sm text-gray-500">poin</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8">
                <p class="text-gray-500">Belum ada data peringkat</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="fixed bottom-28 right-6 z-50">   <a href="topup.php" class="w-16 h-16 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full flex items-center justify-center text-white text-2xl shadow-xl pulse-animation hover:scale-110 transition-transform">
        ➕
    </a>
</div>

<nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-sm border-t border-gray-200 shadow-2xl z-50">
    <div class="flex justify-around py-2">
        <a href="index.php" class="flex flex-col items-center p-2 text-emerald-600 font-semibold">
            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center mb-1">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
            </div>
            <span class="text-xs">Beranda</span>
        </a>
        
        <a href="topup.php" class="flex flex-col items-center p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <div class="w-8 h-8 flex items-center justify-center mb-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="text-xs">Setor</span>
        </a>
        
        <a href="riwayat.php" class="flex flex-col items-center p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <div class="w-8 h-8 flex items-center justify-center mb-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <span class="text-xs">Riwayat</span>
        </a>
        
        <a href="profile.php" class="flex flex-col items-center p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <div class="w-8 h-8 flex items-center justify-center mb-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span class="text-xs">Profil</span>
        </a>
    </div>
</nav>

<script>
    // Add click feedback for better UX
document.querySelectorAll('.quick-action-item').forEach(item => {
    item.addEventListener('click', function(e) {
        // Add ripple effect
        const ripple = document.createElement('div');
        ripple.className = 'absolute inset-0 bg-white opacity-30 rounded-2xl transform scale-0 transition-transform duration-300';
        ripple.style.transformOrigin = 'center';
        
        const iconContainer = this.querySelector('.icon-container');
        if (iconContainer) {
            const rect = iconContainer.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = size + 'px';
            ripple.style.height = size + 'px';
            ripple.style.left = '50%';
            ripple.style.top = '50%';
            ripple.style.marginLeft = -(size / 2) + 'px';
            ripple.style.marginTop = -(size / 2) + 'px';
            
            iconContainer.style.position = 'relative';
            iconContainer.appendChild(ripple);
            
            // Animate ripple
            setTimeout(() => {
                ripple.style.transform = 'scale(1)';
            }, 10);
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 300);
        }
    });
});

// Add loading states
document.querySelectorAll('.quick-action-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Add loading state
        const iconContainer = this.querySelector('.icon-container');
        const originalContent = iconContainer.innerHTML;
        
        iconContainer.innerHTML = `
            <div class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        `;
        
        // Simulate navigation delay
        setTimeout(() => {
            window.location.href = this.href;
        }, 300);
    });
});
// Chart.js Configuration
const ctx = document.getElementById('chart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Sampah Disetor (Kg)',
            data: <?= json_encode($total_daily_data) ?>,
            borderColor: 'rgba(16, 185, 129, 1)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    color: '#6b7280'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6b7280'
                }
            }
        },
        elements: {
            point: {
                hoverBackgroundColor: 'rgba(16, 185, 129, 1)'
            }
        }
    }
});

// Add some interactivity
document.addEventListener('DOMContentLoaded', function() {
    // Add click animations to menu items
    const menuItems = document.querySelectorAll('.menu-icon');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1.1)';
            }, 100);
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });

    // Add search functionality
    const searchInput = document.querySelector('input[type="text"]');
    searchInput.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });
    
    searchInput.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});
</script>

</body>
</html>