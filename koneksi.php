<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_arsip_mandiri_taspen"; 

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal : " . mysqli_connect_errno());
}
?>