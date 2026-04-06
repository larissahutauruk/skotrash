<?php
// login_admin.php (baru: pakai tabel admin, bukan NIS)
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = mysqli_real_escape_string($conn, $_POST['password']);

  $query = mysqli_query($conn, "SELECT * FROM admin WHERE username = '$username'");
  $admin = mysqli_fetch_assoc($query);

  if ($admin && $password === $admin['password']) { // NOTE: tambahkan hash check jika sudah di-hash
    $_SESSION['admin'] = $admin; // Pastikan semua data admin disimpan di session
    header("Location: index.php");
    exit;
  } else {
    $error = "Username atau password salah.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Admin - Skotrash</title>
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
<body class="animated-gradient-bg flex flex-col items-center justify-center min-h-screen p-4">
  <div class="bg-white/90 backdrop-blur-md shadow-2xl rounded-3xl p-8 md:p-10 max-w-md w-full transform transition-all duration-500 hover:scale-[1.02]">
    <div class="text-center mb-6">
        <h1 class="text-4xl font-bold text-green-700 mb-2">Skotrash Admin</h1>
        <p class="text-lg text-gray-600">Selamat Datang</p>
    </div>

    <p class="text-center text-gray-600 mb-6 text-sm">Silakan login untuk mengakses halaman admin Skotrash</p>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-lg text-sm"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
      <div>
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input type="text" id="username" name="username" required class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 ease-in-out">
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" id="password" name="password" required class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 ease-in-out">
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
        Masuk
      </button>
    </form>
  </div>
</body>
</html>