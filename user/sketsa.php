<?php
// sketsa.php
session_start();
include '../koneksi.php';

// Pastikan user sudah login (bagian ini tetap diperlukan untuk akses halaman)
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

// Dapatkan tanggal mulai dan akhir untuk minggu ini (Senin-Jumat)
$today = new DateTime();
$dayOfWeek = (int)$today->format('w'); // 0 (Minggu) sampai 6 (Sabtu)

// Hitung mundur ke hari Senin minggu ini
if ($dayOfWeek == 0) { // Jika hari ini Minggu, mundur 6 hari untuk Senin lalu
    $startOfWeek = (clone $today)->modify('-6 days');
} else { // Senin-Sabtu
    $startOfWeek = (clone $today)->modify('-' . ($dayOfWeek - 1) . ' days');
}
$startOfWeek->setTime(0, 0, 0);

// Hitung maju ke hari Jumat minggu ini
$endOfWeek = (clone $startOfWeek)->modify('+4 days'); // Senin + 4 hari = Jumat
$endOfWeek->setTime(23, 59, 59);

$startDate = $startOfWeek->format('Y-m-d H:i:s');
$endDate = $endOfWeek->format('Y-m-d H:i:s');


// --- KOREKSI PENTING DI SINI ---
// Query untuk mendapatkan data penyetoran semua user per hari
// dan juga detail user serta jumlahnya untuk tooltip
$queryDetailSetoran = "
    SELECT
        DATE_FORMAT(p.tanggal, '%w') AS hari_angka, -- 0=Minggu, 1=Senin, ..., 6=Sabtu
        u.nama AS nama_user,
        dp.jumlah
    FROM
        penyetoran p
    JOIN
        detail_penyetoran dp ON p.id = dp.penyetoran_id
    JOIN
        users u ON p.user_id = u.id
    WHERE
        p.tanggal BETWEEN '$startDate' AND '$endDate'
        AND DATE_FORMAT(p.tanggal, '%w') BETWEEN 1 AND 5 -- Hanya hari Senin (1) sampai Jumat (5)
    ORDER BY
        hari_angka ASC, p.tanggal ASC, u.nama ASC;
";

$resultDetailSetoran = mysqli_query($conn, $queryDetailSetoran);

// Struktur data untuk Chart.js:
// total_daily_data akan menyimpan SUM(jumlah) per hari
// detailed_daily_data akan menyimpan array detail user per hari untuk tooltip
$total_daily_data = array_fill(0, 5, 0); // Index 0=Senin, 4=Jumat
$detailed_daily_data = [ // Untuk menyimpan detail user per hari
    '1' => [], // Senin
    '2' => [], // Selasa
    '3' => [], // Rabu
    '4' => [], // Kamis
    '5' => []  // Jumat
];

if ($resultDetailSetoran) {
    while ($row = mysqli_fetch_assoc($resultDetailSetoran)) {
        $hari_angka = (int)$row['hari_angka'];
        $index_hari = $hari_angka - 1; // Konversi hari_angka (1-5) ke index array (0-4)

        // Akumulasi total jumlah untuk grafik
        if (isset($total_daily_data[$index_hari])) {
            $total_daily_data[$index_hari] += (float)$row['jumlah'];
        }

        // Simpan detail user dan jumlah untuk tooltip
        if (isset($detailed_daily_data[$hari_angka])) {
            $detailed_daily_data[$hari_angka][] = [
                'nama_user' => $row['nama_user'],
                'jumlah' => (float)$row['jumlah']
            ];
        }
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Labels akan selalu dari Senin sampai Jumat
$labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$jsonDataPoints = json_encode($total_daily_data); // Ini adalah total per hari untuk grafik

// Kita perlu mengirimkan detailed_daily_data ke JavaScript juga untuk tooltip
$jsonDetailedData = json_encode($detailed_daily_data);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sketsa Board - Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<body class="bg-green-50 min-h-screen">

    <nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-green-700">Skotrash</h1>
        <ul class="flex space-x-6 text-sm font-medium text-gray-700">
            <li><a href="index.php" class="hover:text-green-600">Home</a></li>
            <li><a href="aboutus.php" class="hover:text-green-600">About Us</a></li>
            <li><a href="profile.php" class="hover:text-green-600">Profile</a></li>
        </ul>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Sketsa Board - Total Penyetoran Minggu Ini</h2>

        <div class="bg-white shadow rounded-lg p-6">
            <div style="width: 100%; height: 500px;">
                <canvas id="penyetoranChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        Chart.register(ChartDataLabels);

        const labels = <?= json_encode($labels) ?>; // Selalu Senin-Jumat
        const dataPoints = <?= $jsonDataPoints ?>; // Total setoran per hari
        const detailedDailyData = <?= $jsonDetailedData ?>; // Detail setoran per user per hari

        const ctx = document.getElementById('penyetoranChart').getContext('2d');
        const penyetoranChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Sampah Disetor (Kg/Satuan)',
                    data: dataPoints,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    fill: false,
                    stepped: false,
                    pointRadius: 6,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: 'rgb(16, 185, 129)',
                    pointHoverBorderColor: 'rgba(255,255,255,1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        mode: 'index', // Aktifkan mode index agar bisa menampilkan data dari semua user di titik yang sama
                        intersect: false, // Penting agar tooltip muncul saat hover di area garis, tidak harus tepat di titik
                        callbacks: {
                            title: function(context) {
                                // context[0].label akan menjadi "Senin", "Selasa", dst.
                                return 'Hari ' + context[0].label;
                            },
                            label: function(context) {
                                // Ini akan menampilkan label dataset (Total Sampah Disetor)
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(context.raw) + ' Kg/Satuan';
                                return label;
                            },
                            footer: function(context) {
                                // --- LOGIKA BARU UNTUK DETAIL USER ---
                                const hari_label = context[0].label; // Contoh: "Senin"
                                // Konversi label hari ke angka (1=Senin, 2=Selasa, dst.)
                                const hari_map = {'Senin': 1, 'Selasa': 2, 'Rabu': 3, 'Kamis': 4, 'Jumat': 5};
                                const hari_angka_untuk_data = hari_map[hari_label];

                                let footer_text = ['--- Detail Penyetoran ---']; // Judul untuk detail
                                const details = detailedDailyData[hari_angka_untuk_data];

                                if (details && details.length > 0) {
                                    // Urutkan detail berdasarkan nama user untuk konsistensi
                                    details.sort((a, b) => a.nama_user.localeCompare(b.nama_user));
                                    details.forEach(item => {
                                        footer_text.push(
                                            `${item.nama_user}: ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(item.jumlah)} Kg/Satuan`
                                        );
                                    });
                                } else {
                                    footer_text.push('Belum ada penyetoran pada hari ini.');
                                }
                                return footer_text;
                            }
                        }
                    },
                    datalabels: {
                        display: true,
                        align: 'end',
                        anchor: 'end',
                        color: '#6B7280',
                        font: {
                            weight: 'bold'
                        },
                        formatter: function(value, context) {
                            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(value);
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hari'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Sampah (Kg/Satuan)'
                        },
                        ticks: {
                            stepSize: 5,
                            callback: function(value) {
                                if (value % 5 === 0) {
                                    return value;
                                }
                                return null;
                            }
                        },
                        grid: {
                            color: function(context) {
                                return context.tick.value % 5 === 0 ? 'rgba(0, 0, 0, 0.15)' : 'rgba(0, 0, 0, 0.05)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>