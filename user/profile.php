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
$nis = $user['nis'];

// Ambil total poin user yang sudah disetujui (approved)
$stmtPoin = $conn->prepare("SELECT SUM(dp.subtotal_poin) as skor FROM detail_penyetoran dp JOIN penyetoran p ON dp.penyetoran_id = p.id WHERE p.user_id = ? AND p.status = 'approved'");
$stmtPoin->bind_param("i", $user_id);
$stmtPoin->execute();
$skor = $stmtPoin->get_result()->fetch_assoc()['skor'] ?? 0;

// Ambil jumlah total sampah disetor (jumlah)
$stmtJumlah = $conn->prepare("SELECT SUM(dp.jumlah) as total_jumlah FROM detail_penyetoran dp JOIN penyetoran p ON dp.penyetoran_id = p.id WHERE p.user_id = ?");
$stmtJumlah->bind_param("i", $user_id);
$stmtJumlah->execute();
$jumlah_setoran = $stmtJumlah->get_result()->fetch_assoc()['total_jumlah'] ?? 0;

// --- Ambil Ranking User ---
$ranking_query = "
    SELECT
        u.id,
        u.nama,
        SUM(CASE WHEN p.status = 'approved' THEN dp.subtotal_poin ELSE 0 END) AS total_approved_poin
    FROM
        users u
    LEFT JOIN
        penyetoran p ON u.id = p.user_id
    LEFT JOIN
        detail_penyetoran dp ON p.id = dp.penyetoran_id
    GROUP BY
        u.id, u.nama
    ORDER BY
        total_approved_poin DESC, u.nama ASC;
";
$result_ranking = mysqli_query($conn, $ranking_query);

$user_rank = 0;
$current_rank_pos = 0;
$prev_total_poin = -1; // Use -1 to ensure the first unique score gets rank 1
$processed_users = 0;

if ($result_ranking) {
    while ($row_rank = mysqli_fetch_assoc($result_ranking)) {
        $processed_users++;
        if ($row_rank['total_approved_poin'] !== $prev_total_poin) {
            $current_rank_pos = $processed_users;
        }
        
        if ($row_rank['id'] == $user_id) {
            $user_rank = $current_rank_pos;
            break; // Found the user, no need to continue
        }
        $prev_total_poin = $row_rank['total_approved_poin'];
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
    <title>Profil Saya - Skotrash</title>
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
    
    <div class="relative px-6 pt-16 pb-24">
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
            </div>
        </div>

        <div class="text-center">
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-4xl">👤</span>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Profil Saya</h1>
            <p class="text-white/80 text-sm">Kelola informasi akun Anda</p>
        </div>
    </div>
</div>

<div class="px-6 -mt-16 relative z-10 pb-24">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-4 text-center card-hover">
            <p class="text-sm text-gray-500 mb-1">Poin Disetujui</p>
            <p class="text-2xl font-bold text-emerald-600"><?= number_format($skor) ?></p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-4 text-center card-hover">
            <p class="text-sm text-gray-500 mb-1">Peringkat Anda</p>
            <p class="text-2xl font-bold text-blue-600">
                #<?= $user_rank > 0 ? $user_rank : 'N/A' ?>
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-4 text-center card-hover">
            <p class="text-sm text-gray-500 mb-1">Total Setoran</p>
            <p class="text-2xl font-bold text-emerald-600"><?= number_format($jumlah_setoran, 2, ',', '.') ?> kg</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-6 mb-6 card-hover">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Informasi Pribadi
        </h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nama Lengkap</label>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($nama) ?></p>
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-gray-500 mb-1">NIS</label>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($nis) ?></p>
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-gray-500 mb-1">Kelas</label>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($kelas) ?></p>
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sekolah</label>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="font-medium text-gray-800">SMK Telkom</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <a href="../logout.php" class="block bg-white rounded-2xl shadow-xl p-4 card-hover flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <span class="font-medium text-gray-800">Logout</span>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
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
        
        <a href="riwayat.php" class="flex flex-col items-center p-2 text-gray-500 hover:text-emerald-600 transition-colors">
            <div class="w-8 h-8 flex items-center justify-center mb-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <span class="text-xs">Riwayat</span>
        </a>
        
        <a href="profile.php" class="flex flex-col items-center p-2 text-emerald-600 font-semibold">
            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center mb-1">
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