<?php 
// 1. Menghubungkan ke file konfigurasi database
include "koneksi.php";

// 2. Logika untuk memproses data ketika form dikirim (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Tangkap respon dari kotak reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    // Menggunakan konstanta RECAPTCHA_SECRET_KEY yang tersimpan di koneksi.php
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret_key . "&response=" . $recaptcha_response;
    
    // Kirim request ke Google dan baca hasilnya
    $verify_response = file_get_contents($verify_url);
    $response_data = json_decode($verify_response);
    
    // Jika Google mendeteksi itu adalah Bot atau verifikasi gagal
    if (!$response_data->success) {
        echo "<script>alert('Verifikasi CAPTCHA gagal. Anda terdeteksi sebagai robot.'); window.history.back();</script>";
        exit;
    }
    
    // 2. Tangkap Variabel Langsung (Tidak perlu mysqli_real_escape_string lagi)
    $arsip_id        = $_POST['arsip_id'];
    $nama            = $_POST['nama'];
    $nama_dokumen    = $_POST['nama_dokumen'];
    $keterangan      = $_POST['keterangan'];
    $ttd             = $_POST['ttd'];
    $jam_masuk       = $_POST['jam_masuk'];
    $jam_keluar      = $_POST['jam_keluar'];
    
    // Data Otomatis Internal Sistem
    $jenis_aktivitas      = "Meminjam";
    $keterangan_aktivitas = "Dokumen: " . $nama_dokumen . " | Alasan: " . $keterangan; 
    $waktu_sekarang       = date('Y-m-d H:i:s');
    
    // Mulai Transaksi Database
    mysqli_begin_transaction($koneksi);
    
    try {
        // =========================================================================
        // IMPLEMENTASI PREPARED STATEMENT (Kebal SQL Injection)
        // =========================================================================
        $sql_log = "INSERT INTO log_book (arsip_id, nama, jenis_aktivitas, keterangan_aktivitas, ttd, created_at, jam_masuk, jam_keluar) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_log = mysqli_prepare($koneksi, $sql_log);
        
        // "isssssss" -> 'i' untuk arsip_id (Integer), 7 's' berikutnya untuk String (Teks)
        mysqli_stmt_bind_param($stmt_log, "isssssss", $arsip_id, $nama, $jenis_aktivitas, $keterangan_aktivitas, $ttd, $waktu_sekarang, $jam_masuk, $jam_keluar);
        
        // Eksekusi query
        if (!mysqli_stmt_execute($stmt_log)) {
            throw new Exception("Gagal mengeksekusi penyimpanan data log peminjaman.");
        }
        
        // Jika berhasil, kunci perubahan secara permanen
        mysqli_commit($koneksi);
        mysqli_stmt_close($stmt_log);
        
        // SweetAlert Sukses
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Data arsip berhasil di pinjam!',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(function() {
                        window.location = 'index.php';
                    });
                });
              </script>";
              
    } catch (Exception $exception) {
        // Batalkan semua transaksi jika ada satu saja yang gagal
        mysqli_rollback($koneksi);
        
        // Catat error asli di log server secara rahasia (aman dari mata hacker)
        error_log("Peminjaman Error: " . $exception->getMessage());
        
        // Buat pesan standar yang aman untuk ditampilkan ke pengguna
        $pesan_aman_user = "Terjadi kendala pada sistem database. Silakan coba kembali atau hubungi admin.";
        
        // SweetAlert Gagal (Menggunakan json_encode untuk keamanan ekstra dari XSS)
        echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Error Sistem Database!',
                    text: " . json_encode($pesan_aman_user) . ",
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Mandiri Taspen</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>

<div class="mobile-container">
    <div class="header">
        <img src="img/Logo.png" alt="Logo Mandiri Taspen"> 
    </div>

    <div class="tabs-container">
        <a href="index.php" class="tab ">Log Book</a>
        <a href="pengecekan.php" class="tab ">pengecekan</a>
        <a href="menambahkan.php" class="tab">Menambahkan</a>
        <a href="meminjam.php" class="tab active">Meminjam</a>
        <a href="memindahkan.php" class="tab">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formPeminjaman">
        
        <div class="section-title">Informasi Karyawan</div>
        <div class="card">
            <div class="form-group">
                <label>Nama Peminjam</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan Nama Peminjam" required>
            </div>
            <div class="form-group">
                <label>Waktu Kunjungan ke Ruang Arsip</label>
                <div style="display: flex; gap: 15px; margin-top: 5px;">
                    <div style="flex: 1;">
                        <span style="font-size: 10px; color: #666; display: block; margin-bottom: 6px;">Jam Masuk</span>
                        <input type="time" name="jam_masuk" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <span style="font-size: 10px; color: #666; display: block; margin-bottom: 6px;">Jam Keluar</span>
                        <input type="time" name="jam_keluar" class="form-control" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title">Informasi Dokumen</div>
        <div class="card">
            <div class="form-group">
                <label>Pilih Kardus Arsip</label>
                <select name="arsip_id" class="form-control" id="search" required>
                    <option value="">Pilih Kardus / unit..</option>
                    <?php

                    $bulan_indo = array(
                        '01' => 'Januari',
                        '02' => 'Februari',
                        '03' => 'Maret',
                        '04' => 'April',
                        '05' => 'Mei',
                        '06' => 'Juni',
                        '07' => 'Juli',
                        '08' => 'Agustus',
                        '09' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember'
                    );

                    $query_arsip = mysqli_query($koneksi, "SELECT id, kode_arsip, perihal, ruangan, no_rak, periode, nomer_dokumen FROM arsip ");
                    while($row = mysqli_fetch_assoc($query_arsip)) {

                        $tanggal_mentah = $row['periode'];
            
                        if (!empty($tanggal_mentah) && $tanggal_mentah != '0000-00-00') {
                            $timestamp = strtotime($tanggal_mentah);
                            $hari = date('d', $timestamp);
                            $bulan_angka = date('m', $timestamp); 
                            $tahun = date('Y', $timestamp);
                            
                            $periode_format = $hari . ' ' . $bulan_indo[$bulan_angka] . ' ' . $tahun;
                        } else {
                            $periode_format = "-";
                        }

                        echo "<option value='".$row['id']."'>".$row['kode_arsip']." - ".$row['perihal']. " - " . $periode_format . " - " ." Dokumen ke " .$row['nomer_dokumen'] . " - " . " (Posisi: ".$row['ruangan']."/".$row['no_rak'].")</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Dokumen yang Dipinjam</label>
                <input type="text" name="nama_dokumen" class="form-control" placeholder="Ketik nama surat/dokumen" required>
            </div>
            <div class="form-group">
                <label>Tujuan / Alasan Peminjaman</label>
                <textarea name="keterangan" class="form-control" placeholder="Contoh: Keperluan audit" required></textarea>
            </div>
        </div>

        <div class="section-title">Verifikasi</div>
        <div class="card">
            <div class="form-group">
                <label>Tanda Tangan</label>
                <div class="signature-box">
                    <canvas id="canvasTtd"></canvas>
                </div>
                
                <input type="hidden" name="ttd" id="inputTtd" required>

                <div class="signature-actions">
                    <button type="button" class="btn-sm btn-gray" id="btnHapus">Ulang</button>
                    <button type="button" class="btn-sm btn-dark" id="btnKonfirmasi">Konfirmasi</button>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <div class="g-recaptcha" data-sitekey="<?php echo $site_key; ?>"></div>
        </div>

        <div class="main-actions">
            <button type="submit" class="btn-lg btn-primary">Kirim</button>
            <button type="button" class="btn-lg btn-outline" onclick="window.location.reload();">Batal</button>
        </div>
    </form>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="script.js"></script>
<script>
    $("#search").select2({
        tags: true,
        width: '100%'
    });
</script>

</body>
</html>