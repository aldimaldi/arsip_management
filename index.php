<?php 
include "koneksi.php";

// Mengambil data log book dari database. 
// Sesuaikan nama tabel dan kolom dengan yang ada di database Anda.
// Di sini saya asumsikan nama tabelnya 'log_book'
$sql = "SELECT log_book.*, arsip.kode_arsip, arsip.perihal 
        FROM log_book 
        LEFT JOIN arsip ON log_book.arsip_id = arsip.id 
        ORDER BY log_book.id DESC"; 

$result = mysqli_query($koneksi, $sql);

// Cek jika ada error pada query (sangat berguna saat development)
if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Book Arsip Mandiri Taspen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="mobile-container">
    <div class="header">
        <img src="img/Logo.png" alt="Logo Mandiri Taspen"> 
    </div>

    <div class="tabs-container">
        <a href="index.php" class="tab active">Log Book</a>
        <a href="menambahkan.php" class="tab">Menambahkan</a>
        <a href="meminjam.php" class="tab">Meminjam</a>
        <a href="memindahkan.php" class="tab">Memindahkan</a>
    </div>

    <div class="form-area">
        <div class="section-title" style="margin-top: 20px;">Riwayat Log Book</div>
        
        <div class="card table-card">
            <div class="table-responsive">
                <table class="table-logbook">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Aktivitas</th>
                            <th>Nama</th>
                            <th>Keterangan</th>
                            <th>Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                // Asumsi ada kolom created_at, jenis_aktivitas, nama, dan keterangan_aktifitas
                                echo "<td>" . $row['created_at'] . "</td>";
                                echo "<td><b>" . $row['jenis_aktivitas'] . "</b></td>";
                                echo "<td>" . $row['nama'] . "</td>";
                                echo "<td>" . $row['keterangan_aktivitas'] . "</td>";
                                $dokumen = $row['kode_arsip'] ? $row['kode_arsip'] . " - " . $row['perihal'] . "</small>" : "<i style='color:red;'>Dokumen tidak ditemukan</i>";
                                echo "<td>" . $dokumen . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>Belum ada data riwayat aktivitas.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>