<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login_user.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$nama = $user['nama'];
$kelas = $user['kelas'];

// --- Filtering Logic ---
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all'; // Default to 'all'
$allowed_statuses = ['all', 'approved', 'pending', 'rejected'];

if (!in_array($filter_status, $allowed_statuses)) {
    $filter_status = 'all'; // Fallback to 'all' if invalid status is provided
}

$query = "
    SELECT p.id AS penyetoran_id, p.tanggal, p.status, js.nama AS nama_jenis_sampah, dp.jumlah, dp.subtotal_poin, dp.jenis_id
    FROM penyetoran p
    JOIN detail_penyetoran dp ON p.id = dp.penyetoran_id
    JOIN jenis_sampah js ON dp.jenis_id = js.id
    WHERE p.user_id = ?
";

// Add WHERE clause for status if not 'all'
if ($filter_status !== 'all') {
    $query .= " AND p.status = ?";
}

$query .= " ORDER BY p.tanggal DESC, p.id DESC;";

$stmt = $conn->prepare($query);

// Bind parameters based on filter_status
if ($filter_status !== 'all') {
    $stmt->bind_param("is", $user_id, $filter_status);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$grouped_riwayat = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $penyetoran_id = $row['penyetoran_id'];
        if (!isset($grouped_riwayat[$penyetoran_id])) {
            $grouped_riwayat[$penyetoran_id] = [
                'tanggal' => $row['tanggal'],
                'status' => $row['status'],
                'total_poin_penyetoran' => 0,
                'detail_sampah' => []
            ];
        }
        $grouped_riwayat[$penyetoran_id]['detail_sampah'][] = $row;
        $grouped_riwayat[$penyetoran_id]['total_poin_penyetoran'] += $row['subtotal_poin'];
    }
}

// Salam waktu
$hour = date('H');
if ($hour < 12) $greeting = "Selamat pagi";
elseif ($hour < 17) $greeting = "Selamat siang";
else $greeting = "Selamat malam";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penyetoran - Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }
        
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
    
    <div class="relative px-6 pt-16 pb-12">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <p class="text-white/80 text-sm"><?= $greeting ?>,</p>
                    <p class="text-white font-bold text-xl whitespace-nowrap overflow-hidden text-ellipsis max-w-[150px] sm:max-w-xs"><?= htmlspecialchars($nama) ?></p>
                    <p class="text-white/70 text-xs whitespace-nowrap overflow-hidden text-ellipsis max-w-[150px] sm:max-w-xs"><?= htmlspecialchars($kelas) ?> • SMK Telkom</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="text-white text-lg">🔔</span>
                    </div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs font-bold">3</span>
                    </div>
                </div>
                <a href="profile.php" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <span class="text-white text-lg">⚙️</span>
                </a>
            </div>
        </div>

        <div class="text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-4xl">📋</span>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Riwayat Penyetoran</h1>
            <p class="text-white/80 text-sm">Semua catatan penyetoran Anda</p>
        </div>
    </div>
</div>

<div class="px-6 -mt-8 relative z-10 pb-24">
    <div class="bg-white rounded-3xl shadow-xl p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex space-x-2 overflow-x-auto pb-2">
                <a href="?status=all" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                    <?= $filter_status == 'all' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' ?>">
                    Semua
                </a>
                <a href="?status=approved" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                    <?= $filter_status == 'approved' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' ?>">
                    Approved
                </a>
                <a href="?status=pending" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                    <?= $filter_status == 'pending' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' ?>">
                    Pending
                </a>
                <a href="?status=rejected" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                    <?= $filter_status == 'rejected' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' ?>">
                    Rejected
                </a>
            </div>
            <button class="p-2 bg-gray-100 rounded-full">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="section-fade-in">
        <?php if (!empty($grouped_riwayat)): ?>
            <div class="space-y-4">
                <?php foreach ($grouped_riwayat as $penyetoran_id => $data_penyetoran): ?>
                    <div class="bg-white rounded-3xl shadow-xl p-6 card-hover border-l-4 <?= 
                        $data_penyetoran['status'] == 'approved' ? 'border-emerald-500' : 
                        ($data_penyetoran['status'] == 'pending' ? 'border-yellow-500' : 'border-red-500')
                    ?>">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?= htmlspecialchars(date('d F Y', strtotime($data_penyetoran['tanggal']))) ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    <?= htmlspecialchars(date('H:i', strtotime($data_penyetoran['tanggal']))) ?>
                                </p>
                            </div>
                            <span class="status-badge <?= 
                                $data_penyetoran['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                ($data_penyetoran['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                            ?>">
                                <?= htmlspecialchars(ucfirst($data_penyetoran['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="border-t pt-4">
                            <ul class="space-y-3">
                                <?php foreach ($data_penyetoran['detail_sampah'] as $detail): ?>
                                    <li class="flex justify-between items-center">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                                <span class="text-lg">
                                                    <?php 
                                                        $icons = ['🥤', '📰', '🥫', '🧴', '📦', '🗞️', '🥛', '🍃'];
                                                        echo $icons[$detail['jenis_id'] % count($icons)];
                                                    ?>
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-gray-800 font-medium"><?= htmlspecialchars($detail['nama_jenis_sampah']) ?></p>
                                                <p class="text-sm text-gray-500"><?= number_format($detail['jumlah'], 2, ',', '.') ?> kg</p>
                                            </div>
                                        </div>
                                        <span class="text-emerald-600 font-bold">+<?= number_format($detail['subtotal_poin']) ?> poin</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="mt-4 pt-4 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 font-medium">Total Poin:</span>
                                    <span class="text-xl font-bold text-emerald-600"><?= number_format($data_penyetoran['total_poin_penyetoran']) ?> poin</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl shadow-xl p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">📭</span>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Belum ada riwayat</h3>
                <p class="text-gray-600">Anda belum melakukan penyetoran sampah.</p>
                <a href="topup.php" class="mt-4 inline-block bg-gradient-to-r from-emerald-500 to-teal-500 text-white py-2 px-6 rounded-full font-medium shadow hover:shadow-md transition">
                    Setor Sampah Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-sm border-t border-gray-200 shadow-2xl z-50">
    <div class="flex justify-around py-2">
        <a href="index.php" class="flex flex-col items-center p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <div class="w-8 h-8 flex items-center justify-center mb-1">
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
        
        <a href="riwayat.php" class="flex flex-col items-center p-2 text-emerald-600 font-semibold">
            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center mb-1">
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

</body>
</html>