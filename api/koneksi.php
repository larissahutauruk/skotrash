<?php
$host = "b6kiqtean5gdsqwwuyvd-mysql.services.clever-cloud.com";
$user = "un0bgzneqdv8p3aq";         // Ganti kalau username DB kamu bukan root
$password = "IRXbOABk9eC56GAHtzEA";             // Isi kalau ada password MySQL kamu
$database = "b6kiqtean5gdsqwwuyvd";     // Nama database kamu
$port = "3306";

$conn = mysqli_connect($host, $user, $password, $database, $port);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>