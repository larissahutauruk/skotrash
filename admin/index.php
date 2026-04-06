<?php
session_start();
include '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../login_admin.php");
    exit;
}

// Ambil data admin
$admin_id = $_SESSION['admin']['id'];
$query_admin = "SELECT * FROM admin WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $query_admin);
$admin = mysqli_fetch_assoc($result_admin);

// Query data statistik
$query_total_users = "SELECT COUNT(*) as total FROM users";
$result_total_users = mysqli_query($conn, $query_total_users);
$total_users = mysqli_fetch_assoc($result_total_users)['total'];

$query_total_penyetoran = "SELECT COUNT(*) as total FROM penyetoran WHERE DATE(tanggal) = CURDATE()";
$result_total_penyetoran = mysqli_query($conn, $query_total_penyetoran);
$total_penyetoran = mysqli_fetch_assoc($result_total_penyetoran)['total'];

$query_total_poin = "SELECT SUM(total_poin) as total FROM penyetoran WHERE status = 'approved'";
$result_total_poin = mysqli_query($conn, $query_total_poin);
$total_poin = mysqli_fetch_assoc($result_total_poin)['total'] ?? 0;

// Query aktivitas terkini
$query_aktivitas = "SELECT p.id, u.nama, p.status, p.tanggal 
                  FROM penyetoran p
                  JOIN users u ON p.user_id = u.id
                  ORDER BY p.tanggal DESC LIMIT 5";
$result_aktivitas = mysqli_query($conn, $query_aktivitas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Skotrash</title>
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
    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg flex flex-col">
        <div class="p-4 border-b flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                ♻️
            </div>
            <h1 class="text-lg font-bold text-green-700">Skotrash Admin</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-4">
            <a href="index.php" class="sidebar-link active block py-3 px-6 flex items-center">
                <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
            </a>
            <a href="user.php" class="sidebar-link block py-3 px-6 flex items-center">
                <i class="fas fa-users mr-3"></i> Approval User
            </a>
            <a href="setor.php" class="sidebar-link block py-3 px-6 flex items-center">
                <i class="fas fa-truck mr-3"></i> Setor ke Pihak Ketiga
            </a>
        </nav>
        <div class="p-4 border-t">
            <a href="../logout.php" class="flex items-center text-gray-600 hover:text-red-600">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Dashboard Admin</h2>
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

        <!-- Content Area -->
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-500 mb-2">Total User</h3>
                    <p class="text-3xl font-bold"><?= number_format($total_users) ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-500 mb-2">Penyetoran Hari Ini</h3>
                    <p class="text-3xl font-bold"><?= number_format($total_penyetoran) ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-500 mb-2">Total Poin Disetujui</h3>
                    <p class="text-3xl font-bold"><?= number_format($total_poin) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Aktivitas Terkini</h3>
                <div class="space-y-4">
                    <?php while ($row = mysqli_fetch_assoc($result_aktivitas)): ?>
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-<?= $row['status'] == 'approved' ? 'check' : ($row['status'] == 'rejected' ? 'times' : 'clock') ?> text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">
                                <?= htmlspecialchars($row['nama']) ?> - 
                                Status: <span class="<?= $row['status'] == 'approved' ? 'text-green-600' : ($row['status'] == 'rejected' ? 'text-red-600' : 'text-yellow-600') ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </p>
                            <p class="text-sm text-gray-500"><?= date('d M Y H:i', strtotime($row['tanggal'])) ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>