<?php
session_start();
include '../koneksi.php'; // AKTIFKAN KEMBALI KONEKSI DATABASE

// Cek login admin - AKTIFKAN KEMBALI CEK SESSION ADMIN
if (!isset($_SESSION['admin'])) {
    header("Location: ../login_admin.php");
    exit;
}

// Ambil data admin dari DATABASE (bukan dummy lagi)
$admin_id = $_SESSION['admin']['id']; // Pastikan $_SESSION['admin']['id'] diset saat login
$query_admin = "SELECT * FROM admin WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $query_admin);

$admin = []; // Inisialisasi variabel admin
if ($result_admin && mysqli_num_rows($result_admin) > 0) {
    $admin = mysqli_fetch_assoc($result_admin);
} else {
    // Fallback jika data admin tidak ditemukan, bisa ke logout atau set dummy
    // Untuk pengembangan, kita bisa set dummy sementara atau redirect ke logout
    // header("Location: ../logout.php"); // Atau redirect ke logout
    // exit;
    $admin = ['id' => 0, 'nama' => 'Admin Tidak Dikenal']; // Fallback dummy
}


// --- Logika Dummy untuk Riwayat Setoran (TETAP DUMMY SESUAI PERMINTAAN) ---
// Query data setoran ke pihak ketiga (gunakan data dummy)
$dummy_setoran_data = [
    [
        'tanggal' => '2025-07-15',
        'penerima' => 'PT. Daur Ulang Mandiri',
        'berat' => 150.75,
        'catatan' => 'Sampah plastik botol dan kaleng aluminium',
        'id' => 1
    ],
    [
        'tanggal' => '2025-07-10',
        'penerima' => 'CV. Jaya Lestari',
        'berat' => 210.50,
        'catatan' => 'Kardus dan kertas bekas',
        'id' => 2
    ],
    [
        'tanggal' => '2025-07-01',
        'penerima' => 'Yayasan Peduli Lingkungan',
        'berat' => 80.20,
        'catatan' => 'Khusus botol kaca hijau',
        'id' => 3
    ],
    [
        'tanggal' => '2025-06-25',
        'penerima' => 'PT. Daur Ulang Mandiri',
        'berat' => 120.00,
        'catatan' => 'Campuran plastik dan besi',
        'id' => 4
    ],
];

// Urutkan data dummy berdasarkan tanggal secara DESC (baru ke lama)
usort($dummy_setoran_data, function($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});

$result_setoran = $dummy_setoran_data; // Variabel ini sekarang berisi array dummy

// --- Proses Form Submit (SIMULASI - TIDAK ADA INSERT KE DATABASE NYATA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $tanggal_simulasi = $_POST['tanggal'] ?? 'N/A';
    $penerima_simulasi = $_POST['penerima'] ?? 'N/A';
    $berat_simulasi = $_POST['berat'] ?? 'N/A';

    // Ini hanya simulasi, tidak ada INSERT ke database
    $_SESSION['success_message'] = "Data setoran (simulasi) berhasil disimpan untuk " . htmlspecialchars($penerima_simulasi) . " (" . htmlspecialchars($berat_simulasi) . " kg)! (Tidak tersimpan permanen)";
    
    // Tidak perlu header redirect, biarkan halaman me-refresh dan menampilkan pesan
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setor ke Pihak Ketiga - Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar-link.active {
            background-color: #f0fdf4;
            color: #047857;
            border-right: 4px solid #059669;
        }
        .sidebar-link:hover:not(.active) {
            background-color: #f0fdf4;
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen">
    <div class="w-64 bg-white shadow-lg flex flex-col">
        <div class="p-4 border-b flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                ♻️
            </div>
            <h1 class="text-lg font-bold text-green-700">Skotrash Admin</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-4">
            <a href="index.php" class="sidebar-link block py-3 px-6 flex items-center">
                <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
            </a>
            <a href="user.php" class="sidebar-link block py-3 px-6 flex items-center">
                <i class="fas fa-users mr-3"></i> Approval User
            </a>
            <a href="setor.php" class="sidebar-link active block py-3 px-6 flex items-center">
                <i class="fas fa-truck mr-3"></i> Setor ke Pihak Ketiga
            </a>
        </nav>
        <div class="p-4 border-t">
            <a href="../logout.php" class="flex items-center text-gray-600 hover:text-red-600">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Setor ke Pihak Ketiga</h2>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="p-2 rounded-full hover:bg-gray-100">
                        <i class="fas fa-bell text-gray-600"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white">
                        <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
                    </div>
                    <span class="text-sm font-medium"><?= $admin['nama'] ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                    <?= $_SESSION['success_message'] ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                    <?= $_SESSION['error_message'] ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-semibold mb-6">Setor Sampah ke Pihak Ketiga</h3>
                
                <form method="POST" action="setor.php" class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Setor</label>
                            <input type="date" name="tanggal" class="w-full p-2 border rounded-lg" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Penerima</label>
                            <input type="text" name="penerima" class="w-full p-2 border rounded-lg" placeholder="Nama Penerima" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Berat (kg)</label>
                            <input type="number" name="berat" step="0.01" class="w-full p-2 border rounded-lg" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea name="catatan" class="w-full p-2 border rounded-lg" rows="3"></textarea>
                    </div>
                    <button type="submit" name="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i> Simpan Setoran
                    </button>
                </form>

                <h4 class="font-medium mb-3">Riwayat Setoran</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-left text-gray-700">
                                <th class="py-2 px-4">Tanggal</th>
                                <th class="py-2 px-4">Penerima</th>
                                <th class="py-2 px-4">Berat (kg)</th>
                                <th class="py-2 px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Loop menggunakan data dummy
                            foreach ($result_setoran as $row): ?>
                                <tr class="border-b">
                                    <td class="py-3 px-4"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($row['penerima']) ?></td>
                                    <td class="py-3 px-4"><?= number_format($row['berat'], 2) ?></td>
                                    <td class="py-3 px-4">
                                        <button class="text-blue-600 hover:text-blue-800 mr-3" title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($result_setoran)): ?>
                                <tr>
                                    <td colspan="4" class="py-3 px-4 text-center text-gray-500">Belum ada riwayat setoran (data dummy kosong).</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>