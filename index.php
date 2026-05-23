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
        <a href="pengecekan.php" class="tab ">pengecekan</a>
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
                            <th>Jam Kunjungan</th>
                            <th>Nama</th>
                            <th>Keterangan</th>
                            <th>Dokumen</th>
                            <th>TTD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                
                                // 1. Kolom Nomor Urut
                                echo "<td>" . $no++ . "</td>";
                                
                                // 2. Kolom tanggal
                                $tanggal_input = date('Y-m-d', strtotime($row['created_at']));
                                echo "<td>" . $tanggal_input . "</td>";

                                // kolom Waktu
                                if (!empty($row['jam_masuk']) && !empty($row['jam_keluar'])) {
                                    $jam_m = date('H:i', strtotime($row['jam_masuk']));
                                    $jam_k = date('H:i', strtotime($row['jam_keluar']));
                                    echo "<td>" . $jam_m . " - " . $jam_k . "</td>";
                                } else {
                                    echo "<td style='text-align: center;'>-</td>";
                                }
                                
                                // 3. Kolom Jenis Aktivitas (Dicetak tebal)
                                echo "<td><b>" . $row['jenis_aktivitas'] . "</b></td>";
                                
                                // 4. KOLOM NAMA (Logika Jabatan ditambahkan di sini)
                                if ($row['jenis_aktivitas'] == 'Pengecekan') {
                                    // Jika ada jabatannya, bungkus dengan kurung. Jika kosong, biarkan kosong.
                                    $jabatan = !empty($row['jabatan']) ? " (" . $row['jabatan'] . ")" : "";
                                    echo "<td>" . $row['nama'] . $jabatan . "</td>";
                                } else {
                                    echo "<td>" . $row['nama'] . "</td>";
                                }
                                
                                // 6. Kolom Keterangan Aktivitas
                                // Amankan teks dari karakter khusus seperti tanda kutip agar tidak merusak sistem klik
                                $ket_aman = htmlspecialchars($row['keterangan_aktivitas'], ENT_QUOTES);

                                echo "<td>";
                                // Memanggil Swal.fire() saat teks diklik, dan menggunakan parameter 'html' agar teks rapi
                                echo "<span class='keterangan-tooltip' title='" . $ket_aman . "' onclick='Swal.fire({
                                        title: \"Detail Keterangan\",
                                        html: \"" . $ket_aman . "\",
                                        icon: \"info\",
                                        confirmButtonColor: \"#0c3e68\",
                                        confirmButtonText: \"Tutup\"
                                    })'>";
                                echo $row['keterangan_aktivitas'];
                                echo "</span>";
                                echo "</td>";
                                
                                // 6. KOLOM DOKUMEN (Logika tanda strip ditambahkan di sini)
                                if ($row['jenis_aktivitas'] == 'Pengecekan') {
                                    // Jika Pengecekan, langsung cetak strip di tengah
                                    echo "<td style='text-align: center;'>-</td>";
                                } else {
                                    // Jika bukan Pengecekan, cek apakah ada dokumennya
                                    if (!empty($row['kode_arsip'])) {
                                        echo "<td>" . $row['kode_arsip'] . " - " . $row['perihal'] . "</td>";
                                    } else {
                                        echo "<td style='color:red; font-style:italic;'>Dokumen tidak ditemukan</td>";
                                    }
                                }
                                
                                // 7. Kolom Tanda Tangan
                                if (!empty($row['ttd'])) {
                                    echo "<td><img src='" . $row['ttd'] . "' alt='Tanda Tangan' class='ttd-img'></td>";
                                } else {
                                    echo "<td style='text-align: center;'>-</td>";
                                }
                                
                                echo "</tr>";
                            }
                        } else {
                            // Pesan jika tabel kosong (colspan disesuaikan menjadi 7 kolom)
                            echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>Belum ada data riwayat aktivitas.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>