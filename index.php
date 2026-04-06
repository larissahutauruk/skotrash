<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skotrash - Bank Sampah Digital SMK Telkom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .gradient-bg-header {
            background: linear-gradient(to right, var(--tw-gradient-stops));
            --tw-gradient-stops: var(--tw-gradient-from, #059669) /* emerald-600 */, var(--tw-gradient-to, #0d9488) /* teal-600 */;
        }
        .gradient-bg-main {
            background: linear-gradient(to bottom, var(--tw-gradient-stops));
            --tw-gradient-stops: var(--tw-gradient-from, #f0fdf4) /* emerald-50 */ , var(--tw-gradient-to, #e0f2f7) /* cyan-50 */;
        }
        .btn-primary {
            @apply bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold py-3 px-8 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98];
        }
        .btn-secondary {
            @apply border border-emerald-600 text-emerald-700 hover:bg-emerald-600 hover:text-white font-semibold py-3 px-8 rounded-xl shadow-md transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98];
        }
    </style>
</head>
<body class="gradient-bg-main min-h-screen">

<?php 
// Sertakan file koneksi Anda
include 'koneksi.php'; 
?>

<header class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600"></div>
    <div class="absolute inset-0 bg-black opacity-10"></div>
    
    <div class="relative px-6 py-6 sm:px-8 lg:px-12">
        <div class="flex items-center justify-between">
            <a href="index.php" class="text-3xl font-extrabold text-white tracking-tight">Skotrash</a>
            
            <a href="home.php" class="bg-white/20 hover:bg-white/30 text-white font-medium py-2 px-6 rounded-full transition duration-300">
                Login
            </a>
        </div>
    </div>
</header>

<section class="relative z-10 px-6 py-16 sm:py-24 text-center overflow-hidden">
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="w-80 h-80 sm:w-96 sm:h-96 bg-emerald-300/10 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="w-80 h-80 sm:w-96 sm:h-96 bg-teal-300/10 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="w-80 h-80 sm:w-96 sm:h-96 bg-cyan-300/10 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <div class="container mx-auto max-w-4xl relative">
        <span class="inline-block bg-emerald-100 text-emerald-800 px-4 py-1.5 rounded-full font-semibold text-sm mb-4 animate-fade-in-down">🌱 Program Lingkungan SMK Telkom</span>
        <h2 class="text-4xl md:text-6xl font-extrabold text-gray-900 leading-tight mb-6 animate-fade-in-up">
            Bank Sampah Digital <span class="block text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">SMK Telkom</span>
        </h2>
        <p class="text-gray-600 text-lg md:text-xl mb-10 max-w-2xl mx-auto animate-fade-in-up delay-200">
            Kelola sampah dengan cerdas, raih poin, dan wujudkan sekolah yang lebih hijau bersama teknologi digital.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12 animate-fade-in-up delay-400">
            <a href="login_user.php" class="btn-primary">
                Mulai Setor Sampah
            </a>
            <a href="login_user.php" class="btn-secondary">
                Lihat Poin Saya
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8 max-w-2xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-8 border border-gray-100 animate-fade-in-up delay-600">
            <div class="text-center">
                <div class="text-4xl font-bold text-emerald-600 mb-1">
                    <?php
                    $sampah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM detail_penyetoran"))['total'] ?? 0;
                    echo number_format(round($sampah, 2), 2, ',', '.'); // Format to 2 decimal places with comma as decimal separator
                    ?>
                </div>
                <div class="text-sm text-gray-500 font-medium">Kg Sampah Terkumpul</div>
            </div>
            <div class="text-center border-t sm:border-t-0 sm:border-l sm:border-r border-gray-200 pt-8 sm:pt-0">
                <div class="text-4xl font-bold text-teal-600 mb-1">
                    <?php
                    $siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;
                    echo number_format($siswa);
                    ?>
                </div>
                <div class="text-sm text-gray-500 font-medium">Siswa Aktif</div>
            </div>
            <div class="text-center border-t sm:border-t-0 border-gray-200 pt-8 sm:pt-0">
                <div class="text-4xl font-bold text-cyan-600 mb-1">
                    <?php
                    $hari = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT tanggal) as total FROM penyetoran WHERE status = 'approved'"))['total'] ?? 0;
                    echo number_format($hari);
                    ?>
                </div>
                <div class="text-sm text-gray-500 font-medium">Hari Penyetoran Aktif</div>
            </div>
        </div>
    </div>
</section>

<footer class="bg-gray-900 text-white px-6 py-12">
    <div class="container mx-auto text-center max-w-4xl">
        <h1 class="text-3xl font-extrabold text-emerald-400 mb-4">Skotrash</h1>
        <p class="text-gray-400 text-lg mb-8 max-w-lg mx-auto">
            Bank Sampah Digital untuk masa depan yang lebih hijau dan berkelanjutan.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div>
                <h4 class="text-xl font-semibold text-white mb-4">Kontak Kami</h4>
                <ul class="space-y-3 text-gray-400">
                    <li class="flex items-center justify-center gap-2">
                        <span>📍</span><span>SMK Telkom Jakarta</span>
                    </li>
                    <li class="flex items-center justify-center gap-2">
                        <span>📞</span><span>(021) 1234-5678</span>
                    </li>
                    <li class="flex items-center justify-center gap-2">
                        <span>✉️</span><span>ecobank@smktelkom.sch.id</span>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="text-xl font-semibold text-white mb-4">Tautan Cepat</h4>
                <ul class="space-y-3">
                    <li><a href="login_user.php" class="text-gray-400 hover:text-emerald-300 transition-colors">Login User</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-emerald-300 transition-colors">Tentang Kami</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-emerald-300 transition-colors">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xl font-semibold text-white mb-4">Ikuti Kami</h4>
                <div class="flex justify-center space-x-4">
                    <a href="#" class="text-gray-400 hover:text-emerald-300 transition-colors">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.776-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-emerald-300 transition-colors">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.488 2.052a.75.75 0 01.077.514V7.5H18a.75.75 0 010 1.5h-5.435V14a.75.75 0 01-1.5 0v-4.986L5.5 8.998a.75.75 0 010-1.5h5.435V2.052a.75.75 0 01.077-.514zM2.05 12.488a.75.75 0 01.514-.077H7.5V18a.75.75 0 01-1.5 0v-5.435l-4.986.535a.75.75 0 01-1.5 0h5.435V2.05a.75.75 0 01-.077.514zM12.488 2.052a.75.75 0 01-.514.077v5.435H6a.75.75 0 010-1.5h5.435V2.052a.75.75 0 01.514-.077zm-.077 19.896a.75.75 0 01-.514.077H6v-5.435a.75.75 0 011.5 0v4.986l4.986-.535a.75.75 0 011.5 0h-5.435V18a.75.75 0 010 1.5z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-6">
            <p class="text-sm text-gray-500">© <?= date('Y') ?> Skotrash SMK Telkom. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
    /* Blob animation for Hero Section */
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob {
        animation: blob 7s infinite cubic-bezier(0.6, 0.4, 0.4, 0.8);
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }

    /* Fade-in animations for content */
    @keyframes fade-in-down {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-down {
        animation: fade-in-down 0.8s ease-out forwards;
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.8s ease-out forwards;
        opacity: 0; /* ensure it's hidden before animation starts */
    }
    .animate-fade-in-up.delay-200 { animation-delay: 0.2s; }
    .animate-fade-in-up.delay-400 { animation-delay: 0.4s; }
    .animate-fade-in-up.delay-600 { animation-delay: 0.6s; }
</style>

</body>
</html>