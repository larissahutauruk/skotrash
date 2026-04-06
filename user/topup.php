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
$tanggal = date('Y-m-d');

$error = ''; // Variabel untuk menyimpan pesan error
$success = ''; // Variabel untuk menyimpan pesan sukses

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jenis_id'])) {
    $jenis_id = (int)$_POST['jenis_id'];
    $quantity = (float)$_POST['quantity'];

    // VALIDASI SERVER-SIDE: Pastikan quantity lebih besar dari 0
    if ($quantity <= 0) {
        $error = "Jumlah sampah tidak boleh 0 atau kurang dari 0. Mohon masukkan jumlah yang valid.";
    } else {
        $jenis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM jenis_sampah WHERE id = $jenis_id"));

        // Pastikan jenis sampah ditemukan
        if ($jenis) {
            $poin_per_satuan = $jenis['poin_per_satuan'];
            $total_poin = $poin_per_satuan * $quantity;

            // Memulai transaksi
            // GANTI: mysqli_begin_trans($conn);
            mysqli_autocommit($conn, FALSE); // Mengatur autocommit menjadi FALSE

            try {
                // Insert ke tabel penyetoran
                $stmt_penyetoran = $conn->prepare("INSERT INTO penyetoran (user_id, tanggal, status, total_poin) VALUES (?, ?, 'pending', ?)");
                $stmt_penyetoran->bind_param("isd", $user_id, $tanggal, $total_poin);
                if (!$stmt_penyetoran->execute()) {
                    throw new Exception("Error saat memasukkan data penyetoran: " . $stmt_penyetoran->error);
                }
                $penyetoran_id = mysqli_insert_id($conn);
                $stmt_penyetoran->close();

                // Insert ke tabel detail_penyetoran
                $stmt_detail = $conn->prepare("INSERT INTO detail_penyetoran (penyetoran_id, jenis_id, jumlah, subtotal_poin) VALUES (?, ?, ?, ?)");
                $stmt_detail->bind_param("iidd", $penyetoran_id, $jenis_id, $quantity, $total_poin);
                if (!$stmt_detail->execute()) {
                    throw new Exception("Error saat memasukkan data detail penyetoran: " . $stmt_detail->error);
                }
                $stmt_detail->close();

                // Poin di tabel users akan diupdate saat status penyetoran menjadi 'approved' oleh admin,
                // jadi baris ini dihapus agar total_poin user hanya bertambah jika sudah disetujui.
                // mysqli_query($conn, "UPDATE users SET total_poin = total_poin + $total_poin WHERE id = $user_id");

                mysqli_commit($conn); // Commit transaksi
                $success = "Setor berhasil untuk jenis '{$jenis['nama']}' sebanyak " . number_format($quantity, 2, ',', '.') . " kg. Menunggu persetujuan admin. (Poin Estimasi: " . number_format($total_poin) . ")";

            } catch (Exception $e) {
                mysqli_rollback($conn); // Rollback transaksi jika ada kesalahan
                $error = "Terjadi kesalahan: " . $e->getMessage();
            } finally {
                mysqli_autocommit($conn, TRUE); // Mengatur autocommit kembali ke TRUE setelah transaksi selesai
            }
        } else {
            $error = "Jenis sampah tidak ditemukan.";
        }
    }
}

$data_jenis = mysqli_query($conn, "SELECT * FROM jenis_sampah");

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
    <title>Setor Sampah - Skotrash</title>
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
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .quantity-btn {
            transition: all 0.2s ease;
        }

        .quantity-btn:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .quantity-btn:active {
            transform: scale(0.95);
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen p-0">

<div class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600"></div>
    <div class="absolute inset-0 bg-black opacity-10"></div>

    <div class="relative px-4 sm:px-6 pt-16 pb-12">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-3 sm:space-x-4 flex-grow min-w-0">
                <a href="index.php" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <p class="text-white/80 text-sm whitespace-nowrap overflow-hidden text-ellipsis max-w-[150px] sm:max-w-xs"><?= $greeting ?>,</p>
                    <p class="text-white font-bold text-xl whitespace-nowrap overflow-hidden text-ellipsis max-w-[150px] sm:max-w-xs"><?= htmlspecialchars($nama) ?></p>
                    <p class="text-white/70 text-xs whitespace-nowrap overflow-hidden text-ellipsis max-w-[150px] sm:max-w-xs"><?= htmlspecialchars($kelas) ?> • SMK Telkom</p>
                </div>
            </div>

            <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0">
                <div class="relative flex-shrink-0">
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

        <div class="text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-4xl">♻️</span>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Setor Sampah</h1>
            <p class="text-white/80 text-sm">Pilih jenis sampah dan masukkan jumlahnya</p>
        </div>
    </div>
</div>

<div class="px-4 sm:px-6 -mt-8 relative z-10 pb-24">

    <?php if (isset($success) && $success): ?>
        <div class="bg-white rounded-3xl shadow-2xl p-4 sm:p-6 mb-6 border-l-4 border-emerald-500">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-emerald-800 font-semibold text-lg">Setor Berhasil!</p>
                    <p class="text-emerald-600 text-sm break-words"><?= $success ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error) && $error): ?>
        <div class="bg-white rounded-3xl shadow-2xl p-4 sm:p-6 mb-6 border-l-4 border-red-500">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-red-800 font-semibold text-lg">Setor Gagal!</p>
                    <p class="text-red-600 text-sm break-words"><?= $error ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="section-fade-in mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php mysqli_data_seek($data_jenis, 0); // Reset pointer for loop ?>
            <?php while ($row = mysqli_fetch_assoc($data_jenis)): ?>
                <form method="POST" class="bg-white rounded-3xl shadow-xl p-4 sm:p-6 card-hover border border-gray-100">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-2xl">
                                <?php
                                    $icons = ['🥤', '📰', '🥫', '🧴', '📦', '🗞️', '🥛', '🍃'];
                                    echo $icons[$row['id'] % count($icons)];
                                ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($row['nama']) ?></h3>
                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-3 border border-emerald-100">
                            <p class="text-sm text-emerald-700 font-medium">
                                <span class="text-2xl font-bold text-emerald-800"><?= $row['poin_per_satuan'] ?></span>
                                poin per 1 kg
                            </p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Jumlah/kg</label>
                        <div class="flex items-center bg-gray-50 rounded-2xl p-2">
                            <button type="button" onclick="changeQuantity(<?= $row['id'] ?>, -1)" class="quantity-btn w-10 h-10 sm:w-12 sm:h-12 bg-red-500 hover:bg-red-600 text-white rounded-xl flex items-center justify-center font-bold text-xl flex-shrink-0">
                                −
                            </button>
                            <input type="number" name="quantity" id="qty-<?= $row['id'] ?>" value="0" step="1" min="0" class="flex-1 text-center text-xl font-bold text-gray-800 bg-transparent border-none outline-none min-w-0">
                            <button type="button" onclick="changeQuantity(<?= $row['id'] ?>, 1)" class="quantity-btn w-10 h-10 sm:w-12 sm:h-12 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl flex items-center justify-center font-bold text-xl flex-shrink-0">
                                +
                            </button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-4 border border-blue-100">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-blue-700">Total Poin:</span>
                                <span id="total-poin-<?= $row['id'] ?>" class="text-2xl font-bold text-blue-800">0</span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="jenis_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white py-3 sm:py-4 px-6 rounded-2xl font-bold text-lg shadow-lg transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]" disabled>
                        <span class="flex items-center justify-center space-x-2">
                            <span>Setor Sekarang</span>
                            <span class="text-xl">🚀</span>
                        </span>
                    </button>
                </form>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-2xl p-4 sm:p-6 mb-8">
        <div class="flex items-center space-x-4 mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-2xl">💡</span>
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-gray-800">Tips Setor Sampah</h3>
                <p class="text-sm text-gray-600">Pastikan sampah dalam kondisi bersih dan kering</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center space-x-3">
                <span class="text-green-600">✅</span>
                <span class="text-sm text-gray-700">Pisahkan sampah berdasarkan jenisnya</span>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-green-600">✅</span>
                <span class="text-sm text-gray-700">Bersihkan dari sisa makanan</span>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-green-600">✅</span>
                <span class="text-sm text-gray-700">Keringkan sebelum disetor</span>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-green-600">✅</span>
                <span class="text-sm text-gray-700">Timbang dengan akurat</span>
            </div>
        </div>
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

        <a href="topup.php" class="flex flex-col items-center p-2 text-emerald-600 font-semibold">
            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center mb-1">
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
// Fungsi untuk mengupdate total poin dan status tombol submit
function updateSetorButton(id) {
    const input = document.getElementById('qty-' + id);
    const totalPoinEl = document.getElementById('total-poin-' + id);
    const submitButton = input.closest('form').querySelector('button[type="submit"]');

    let val = parseFloat(input.value) || 0;

    // Pastikan nilai tidak negatif
    if (val < 0) {
        val = 0;
        input.value = 0;
    }

    const poinPerSatuanElement = input.closest('form').querySelector('.text-emerald-800');
    let poinPerSatuan = 0;
    if (poinPerSatuanElement) {
        poinPerSatuan = parseFloat(poinPerSatuanElement.textContent);
    }
    const totalPoin = val * poinPerSatuan;

    totalPoinEl.textContent = Math.round(totalPoin); // Menampilkan poin bulat

    // Nonaktifkan tombol jika quantity 0, aktifkan jika > 0
    if (val <= 0) {
        submitButton.disabled = true;
        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        submitButton.classList.remove('hover:from-emerald-600', 'hover:to-teal-600', 'hover:scale-[1.02]');
    } else {
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        submitButton.classList.add('hover:from-emerald-600', 'hover:to-teal-600', 'hover:scale-[1.02]');
    }
}

// Fungsi untuk mengubah quantity
function changeQuantity(id, step) {
    const input = document.getElementById('qty-' + id);
    let val = parseFloat(input.value) || 0;

    val += step;

    // Pastikan nilai tidak negatif
    if (val < 0) {
        val = 0;
    }

    // Pembulatan ke 2 angka desimal untuk input
    val = Math.round(val * 100) / 100;
    input.value = val;

    updateSetorButton(id); // Panggil updateSetorButton setelah mengubah quantity
}

document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('input[name="quantity"]');

    quantityInputs.forEach(input => {
        // Panggil updateSetorButton saat halaman dimuat untuk menginisialisasi status tombol
        const id = input.id.replace('qty-', '');
        updateSetorButton(id);

        input.addEventListener('input', function() {
            // Validasi input langsung agar tidak bisa diisi negatif
            if (this.value < 0) {
                this.value = 0;
            }
            const id = this.id.replace('qty-', '');
            updateSetorButton(id);
        });
    });

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const quantityInput = this.querySelector('input[name="quantity"]');
            const quantity = parseFloat(quantityInput.value) || 0;

            // Tambahan validasi di sisi klien sebelum submit
            if (quantity <= 0) {
                e.preventDefault(); // Mencegah form disubmit
                alert("Jumlah sampah tidak boleh 0 atau kurang dari 0. Mohon masukkan jumlah yang valid.");
                return;
            }

            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;

            button.innerHTML = `
                <span class="flex items-center justify-center space-x-2">
                    <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>Memproses...</span>
                </span>
            `;
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed'); // Tambahkan efek nonaktif saat memproses
            button.classList.remove('hover:from-emerald-600', 'hover:to-teal-600', 'hover:scale-[1.02]');

            // Simulasi loading atau animasi (opsional, akan hilang setelah submit)
            // setTimeout(() => {
            //     button.innerHTML = originalText;
            //     button.disabled = false;
            //     button.classList.remove('opacity-50', 'cursor-not-allowed');
            //     button.classList.add('hover:from-emerald-600', 'hover:to-teal-600', 'hover:scale-[1.02]');
            // }, 2000);
        });
    });
});
</script>

</body>
</html>