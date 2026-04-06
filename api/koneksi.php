<?php
$host     = "localhost";
$user     = "root";         // Ganti kalau username DB kamu bukan root
$password = "";             // Isi kalau ada password MySQL kamu
$database = "skotrash";     // Nama database kamu

$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
