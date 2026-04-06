<?php
// login_user.php (pakai tabel users, semua NIS termasuk 6 NIS sekarang user biasa)
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nis = mysqli_real_escape_string($conn, $_POST['nis']);
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);

  $query = mysqli_query($conn, "SELECT * FROM users WHERE nis = '$nis' AND nama = '$nama'");
  $user = mysqli_fetch_assoc($query);

  if ($user) {
    $_SESSION['user'] = $user;
    header("Location: index.php");
    exit;
  } else {
    $error = "Data tidak ditemukan. Cek kembali NIS dan nama Anda.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login User - Skotrash</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
      body {
          font-family: 'Inter', sans-serif;
      }
      /* Animasi untuk latar belakang gradien */
      @keyframes gradient-animation {
          0% { background-position: 0% 50%; }
          50% { background-position: 100% 50%; }
          100% { background-position: 0% 50%; }
      }
      .animated-gradient-bg {
          background: linear-gradient(-45deg, #a7f3d0, #d1fae5, #a7f3d0, #6ee7b7); /* Lighter green tones */
          background-size: 400% 400%;
          animation: gradient-animation 15s ease infinite;
      }
  </style>
</head>
<body class="animated-gradient-bg flex items-center justify-center min-h-screen p-4">
  <div class="bg-white/90 backdrop-blur-md shadow-2xl rounded-3xl p-8 md:p-10 max-w-md w-full transform transition-all duration-500 hover:scale-[1.02]">
    <h2 class="text-3xl font-bold text-center bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent mb-6">Login Manusia</h2>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-lg text-sm"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
      <div>
        <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" required class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 ease-in-out">
      </div>

      <div>
        <label for="nis" class="block text-sm font-medium text-gray-700 mb-1">NIS</label>
        <input type="text" id="nis" name="nis" required class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 ease-in-out">
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
        Masuk
      </button>
    </form>
  </div>
</body>
</html>