<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gradient-to-br from-emerald-200 via-teal-200 to-sky-200">
    <div class="bg-white rounded-3xl shadow-xl p-8 max-w-sm w-full text-center">
        <h1 class="text-3xl font-bold text-emerald-600 mb-4">Skotrash</h1>

        <div class="flex justify-center mb-6">
            <button id="user-tab" class="px-6 py-2 rounded-l-full bg-emerald-600 text-white font-semibold transition-colors">Pengguna</button>
            <button id="admin-tab" class="px-6 py-2 rounded-r-full bg-gray-200 text-gray-700 font-semibold transition-colors">Admin</button>
        </div>

        <div id="user-content">
            <p class="text-gray-600 mb-6">Masuk untuk mengelola setoran sampah Anda dan melihat poin.</p>
            <a href="user/login.php" class="block w-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white py-3 px-6 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all">
                Login Sebagai Manusia
            </a>
            <div class="text-6xl mt-6">👨‍👩‍👧‍👦</div>
        </div>

        <div id="admin-content" class="hidden">
            <p class="text-gray-600 mb-6">Masuk untuk mengelola sistem bank sampah.</p>
            <a href="admin/login.php" class="block w-full bg-purple-600 text-white py-3 px-6 rounded-full font-semibold shadow-lg hover:bg-purple-700 transition-all">
                Login Sebagai Admin
            </a>
            <div class="text-6xl mt-6">💼</div>
        </div>

        <p class="text-gray-400 text-sm mt-8">&copy; 2025 Skotrash Digital.</p>
    </div>

    <script>
        const userTab = document.getElementById('user-tab');
        const adminTab = document.getElementById('admin-tab');
        const userContent = document.getElementById('user-content');
        const adminContent = document.getElementById('admin-content');

        // Initial state: User tab active
        userTab.classList.add('bg-emerald-600', 'text-white');
        userTab.classList.remove('bg-gray-200', 'text-gray-700');
        adminTab.classList.add('bg-gray-200', 'text-gray-700');


        userTab.addEventListener('click', () => {
            userContent.classList.remove('hidden');
            adminContent.classList.add('hidden');
            userTab.classList.add('bg-emerald-600', 'text-white');
            userTab.classList.remove('bg-gray-200', 'text-gray-700');
            // Pastikan warna admin tab kembali ke abu-abu saat user tab aktif
            adminTab.classList.remove('bg-purple-600', 'text-white');
            adminTab.classList.add('bg-gray-200', 'text-gray-700');
        });

        adminTab.addEventListener('click', () => {
            adminContent.classList.remove('hidden');
            userContent.classList.add('hidden');
            // Ubah warna admin tab menjadi ungu
            adminTab.classList.add('bg-purple-600', 'text-white');
            adminTab.classList.remove('bg-gray-200', 'text-gray-700');
            // Pastikan warna user tab kembali ke abu-abu saat admin tab aktif
            userTab.classList.remove('bg-emerald-600', 'text-white');
            userTab.classList.add('bg-gray-200', 'text-gray-700');
        });
    </script>
</body>
</html>