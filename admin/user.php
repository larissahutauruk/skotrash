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

// Query untuk mendapatkan semua penyetoran
$queryPenyetoran = "
    SELECT
        p.id AS penyetoran_id,
        p.tanggal,
        p.status,
        u.id AS user_id,
        u.nama AS nama_user,
        p.total_poin
    FROM
        penyetoran p
    JOIN
        users u ON p.user_id = u.id
    ORDER BY
        p.tanggal DESC, p.id DESC;
";

$resultPenyetoran = mysqli_query($conn, $queryPenyetoran);
$all_transactions = [];
while ($row = mysqli_fetch_assoc($resultPenyetoran)) {
    $penyetoran_id = $row['penyetoran_id'];
    $all_transactions[$penyetoran_id] = [
        'id' => $row['penyetoran_id'],
        'tanggal' => $row['tanggal'],
        'status' => $row['status'],
        'user_id' => $row['user_id'],
        'nama_user' => $row['nama_user'],
        'total_poin_transaksi' => $row['total_poin'],
        'detail_sampah' => []
    ];
}

// Ambil detail sampah
$queryDetailSampah = "
    SELECT
        dp.penyetoran_id,
        js.nama AS nama_jenis_sampah,
        js.satuan,
        dp.jumlah,
        dp.subtotal_poin
    FROM
        detail_penyetoran dp
    JOIN
        jenis_sampah js ON dp.jenis_id = js.id
    ORDER BY
        dp.penyetoran_id ASC;
";

$resultDetailSampah = mysqli_query($conn, $queryDetailSampah);
while ($row = mysqli_fetch_assoc($resultDetailSampah)) {
    $penyetoran_id = $row['penyetoran_id'];
    if (isset($all_transactions[$penyetoran_id])) {
        $all_transactions[$penyetoran_id]['detail_sampah'][] = [
            'nama_jenis_sampah' => $row['nama_jenis_sampah'],
            'satuan' => $row['satuan'],
            'jumlah' => $row['jumlah'],
            'subtotal_poin' => $row['subtotal_poin']
        ];
    }
}

// Logika POST untuk Accept/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $setoran_id_to_update = $_POST['penyetoran_id'];
    $action = $_POST['action'];
    $new_status = ($action === 'accept') ? 'approved' : 'rejected';

    mysqli_autocommit($conn, FALSE);
    $success = true;

    // Ambil status lama dan user_id
    $get_prev_data = "SELECT status, user_id FROM penyetoran WHERE id = '$setoran_id_to_update'";
    $prev_data_result = mysqli_query($conn, $get_prev_data);
    $prev_data_row = mysqli_fetch_assoc($prev_data_result);
    $previous_status = $prev_data_row['status'];
    $target_user_id = $prev_data_row['user_id'];

    // Ambil total poin
    $get_total_poin = "SELECT total_poin FROM penyetoran WHERE id = '$setoran_id_to_update'";
    $total_poin_result = mysqli_query($conn, $get_total_poin);
    $total_poin_row = mysqli_fetch_assoc($total_poin_result);
    $poin_amount = $total_poin_row['total_poin'] ?? 0;

    // Update status penyetoran
    $update_status = "UPDATE penyetoran SET status = '$new_status' WHERE id = '$setoran_id_to_update'";
    if (!mysqli_query($conn, $update_status)) {
        $success = false;
    }

    // Sesuaikan poin user
    if ($success) {
        if ($new_status === 'approved' && $previous_status !== 'approved') {
            $update_user = "UPDATE users SET total_poin = total_poin + '$poin_amount' WHERE id = '$target_user_id'";
            if (!mysqli_query($conn, $update_user)) {
                $success = false;
            }
        } elseif ($new_status === 'rejected' && $previous_status === 'approved') {
            $update_user = "UPDATE users SET total_poin = total_poin - '$poin_amount' WHERE id = '$target_user_id'";
            if (!mysqli_query($conn, $update_user)) {
                $success = false;
            }
        }
    }

    if ($success) {
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Status setoran berhasil diupdate menjadi " . $new_status . "!";
    } else {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Gagal mengupdate status setoran: " . mysqli_error($conn);
    }

    header("Location: user.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval User - Skotrash</title>
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
        .card-status-approved { border-left: 4px solid #10B981; }
        .card-status-rejected { border-left: 4px solid #EF4444; }
        .card-status-pending { border-left: 4px solid #F59E0B; }
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
            <a href="index.php" class="sidebar-link block py-3 px-6 flex items-center">
                <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
            </a>
            <a href="user.php" class="sidebar-link active block py-3 px-6 flex items-center">
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
            <h2 class="text-xl font-semibold text-gray-800">Approval Penyetoran User</h2>
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

            <div class="mb-6 flex justify-between items-center">
                <h3 class="text-xl font-semibold">Daftar Penyetoran User</h3>
                <div class="relative">
                    <input type="text" placeholder="Cari penyetoran..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>

            <?php if (!empty($all_transactions)): ?>
                <div class="space-y-4">
                    <?php foreach ($all_transactions as $setoran): ?>
                        <div class="bg-white rounded-lg shadow p-6 <?= $setoran['status'] == 'approved' ? 'card-status-approved' : ($setoran['status'] == 'rejected' ? 'card-status-rejected' : 'card-status-pending') ?>">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Penyetoran oleh: <span class="text-blue-600"><?= htmlspecialchars($setoran['nama_user']) ?></span>
                                </h3>
                                <span class="text-sm text-gray-500">
                                    Tanggal: <?= htmlspecialchars(date('d F Y, H:i', strtotime($setoran['tanggal']))) ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-4">
                                <p class="font-bold">Total Poin: <span class="text-green-600"><?= number_format($setoran['total_poin_transaksi']) ?></span></p>
                                <p class="text-xs text-gray-500">Status: 
                                    <span class="font-semibold <?= $setoran['status'] === 'approved' ? 'text-green-700' : ($setoran['status'] === 'rejected' ? 'text-red-700' : 'text-yellow-700') ?>">
                                        <?= ucfirst($setoran['status']) ?>
                                    </span>
                                </p>
                            </div>

                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <p class="text-md font-medium text-gray-700 mb-2">Detail Sampah:</p>
                                <?php if (!empty($setoran['detail_sampah'])): ?>
                                    <ul class="list-disc list-inside space-y-1 text-gray-700">
                                        <?php foreach ($setoran['detail_sampah'] as $detail): ?>
                                            <li>
                                                <span class="font-semibold"><?= htmlspecialchars($detail['nama_jenis_sampah']) ?></span>
                                                <span class="text-gray-500"> (<?= number_format($detail['jumlah'], 2, ',', '.') ?> <?= $detail['satuan'] ?>)</span>
                                                <span class="float-right text-green-600 font-medium"><?= number_format($detail['subtotal_poin']) ?> poin</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm">Tidak ada detail sampah.</p>
                                <?php endif; ?>
                            </div>

                            <div class="flex justify-end gap-2">
                                <?php if ($setoran['status'] === 'pending'): ?>
                                    <form method="POST" action="user.php" class="inline-block">
                                        <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 flex items-center">
                                            <i class="fas fa-check mr-2"></i> Terima
                                        </button>
                                    </form>
                                    <form method="POST" action="user.php" class="inline-block">
                                        <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 flex items-center">
                                            <i class="fas fa-times mr-2"></i> Tolak
                                        </button>
                                    </form>
                                <?php elseif ($setoran['status'] === 'approved'): ?>
                                    <button class="bg-green-600 text-white px-4 py-2 rounded opacity-80 cursor-not-allowed flex items-center">
                                        <i class="fas fa-check-double mr-2"></i> Disetujui
                                    </button>
                                    <form method="POST" action="user.php" class="inline-block">
                                        <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 flex items-center">
                                            <i class="fas fa-times mr-2"></i> Batalkan
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="bg-red-600 text-white px-4 py-2 rounded opacity-80 cursor-not-allowed flex items-center">
                                        <i class="fas fa-ban mr-2"></i> Ditolak
                                    </button>
                                    <form method="POST" action="user.php" class="inline-block">
                                        <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 flex items-center">
                                            <i class="fas fa-check mr-2"></i> Terima
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Belum ada transaksi penyetoran</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>