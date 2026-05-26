<?php 
// 1. Menghubungkan ke file konfigurasi database
include "koneksi.php";

// 2. Logika untuk memproses data ketika form dikirim (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Tangkap respon dari kotak reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    // Menggunakan konstanta RECAPTCHA_SECRET_KEY dari koneksi.php
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret_key . "&response=" . $recaptcha_response;
    
    // Kirim request ke Google dan baca hasilnya
    $verify_response = file_get_contents($verify_url);
    $response_data = json_decode($verify_response);
    
    // 2. Jika Google mendeteksi itu adalah Bot atau verifikasi gagal
    if (!$response_data->success) {
        echo "<script>alert('Verifikasi CAPTCHA gagal. Anda terdeteksi sebagai robot.'); window.history.back();</script>";
        exit;
    }
    
    // 3. Tangkap Variabel Langsung (Tanpa mysqli_real_escape_string)
    $arsip_id             = $_POST['arsip_id'];
    $nama                 = $_POST['nama'];
    $no_rak               = $_POST['no_rak'];
    $tingkatan_rak        = $_POST['tingkatan_rak'];
    $ruangan              = $_POST['ruangan'];
    $keterangan_aktivitas = $_POST['keterangan_aktivitas'];
    $ttd                  = $_POST['ttd'];
    $jam_masuk            = $_POST['jam_masuk'];
    $jam_keluar           = $_POST['jam_keluar'];
    
    // Data Otomatis Internal Sistem
    $jenis_aktivitas      = "Memindahkan";
    $waktu_sekarang       = date('Y-m-d H:i:s');

    // Mulai Transaksi Database
    mysqli_begin_transaction($koneksi);
    
    try {
        // =========================================================================
        // PREPARED STATEMENT 1: UPDATE LOKASI ARSIP
        // =========================================================================
        $sql_update_arsip = "UPDATE arsip SET ruangan = ?, no_rak = ?, tingkatan_rak = ?, updated_at = ? WHERE id = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $sql_update_arsip);
        
        // "ssssi" -> 4 String pertama (ruangan, no_rak, tingkatan_rak, waktu), 1 Integer terakhir (id)
        mysqli_stmt_bind_param($stmt_update, "ssssi", $ruangan, $no_rak, $tingkatan_rak, $waktu_sekarang, $arsip_id);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Gagal memperbarui lokasi arsip di database.");
        }

        // =========================================================================
        // PREPARED STATEMENT 2: INSERT CATATAN KE LOG BOOK
        // =========================================================================
        $sql_insert_log = "INSERT INTO log_book (arsip_id, nama, ttd, jenis_aktivitas, keterangan_aktivitas, created_at, jam_masuk, jam_keluar) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                           
        $stmt_log = mysqli_prepare($koneksi, $sql_insert_log);
        
        // "isssssss" -> 1 Integer (arsip_id), 7 String (sisanya)
        mysqli_stmt_bind_param($stmt_log, "isssssss", $arsip_id, $nama, $ttd, $jenis_aktivitas, $keterangan_aktivitas, $waktu_sekarang, $jam_masuk, $jam_keluar);
        
        if (!mysqli_stmt_execute($stmt_log)) {
            throw new Exception("Gagal menyimpan riwayat pemindahan ke log book.");
        }
        
        // Jika kedua perintah di atas berhasil, komit (simpan permanen) transaksinya
        mysqli_commit($koneksi);
        
        // Tutup statement
        mysqli_stmt_close($stmt_update);
        mysqli_stmt_close($stmt_log);
        
        // Tampilkan Notifikasi Sukses
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Data arsip berhasil dipindahkan!',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(function() {
                        window.location = 'index.php';
                    });
                });
              </script>";
              
    } catch (Exception $exception) {
        // Batalkan (Rollback) kedua query jika salah satunya gagal
        mysqli_rollback($koneksi);
        
        // Simpan error asli ke dalam file log server secara rahasia
        error_log("Pemindahan Error: " . $exception->getMessage());
        
        // Pesan error umum yang aman untuk dilihat pengguna
        $pesan_aman_user = "Terjadi kendala pada sistem database saat memindahkan data. Silakan hubungi admin.";
        
        // Tampilkan Notifikasi Gagal (Aman dari XSS)
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
    <title>Form Memindahkan - Mandiri Taspen</title>
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
        <a href="meminjam.php" class="tab">Meminjam</a>
        <a href="memindahkan.php" class="tab active">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formMemindahkan">
        
        <div class="section-title">Informasi Karyawan</div>
        <div class="card">
            <div class="form-group">
                <label>Nama Pemindah</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan Nama Pemindah" required>
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
                <label>Pilih Dokumen</label>
                <select name="arsip_id" class="form-control" id="search" required>
                    <option value="">Pilih Dokumen (Unit Pengolahan)</option>
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

                    
                    // Mengambil semua arsip untuk dipindahkan lokasinya
                    $query_arsip = mysqli_query($koneksi, "SELECT id, kode_arsip, perihal, ruangan, no_rak, nomer_dokumen, periode FROM arsip");
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

                        echo "<option value='".$row['id']."'>".$row['kode_arsip']." - ".$row['perihal']. " - " . $periode_format ." - ". "Dokumen ke " .$row['nomer_dokumen'] . " - " ." (Posisi: ".$row['ruangan']."/".$row['no_rak'].")</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Keterangan Pemindahan</label>
                <textarea name="keterangan_aktivitas" class="form-control" placeholder="Contoh: Perapihan Ruangan"></textarea>
            </div>
        </div>

        <div class="section-title">Informasi Tata Letak Pemindahan</div>
        <div class="card">
            <div class="form-group">
                <label>Ruangan</label>
                <input type="text" name="ruangan" class="form-control" placeholder="Contoh: Ruangan Arsip Lantai 1" required>
            </div>
            <div class="form-group">
                <label>No Rak</label>
                <select name="no_rak" class="form-control" required>
                    <option value="">Pilih No Rak</option>
                    <?php for($i=1; $i<=20; $i++) { echo "<option value='Rak $i'>Rak $i</option>"; } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tingkatan Rak</label>
                <select name="tingkatan_rak" class="form-control" required>
                    <option value="">Pilih Tingkatan Rak</option>
                    <?php for($i=1; $i<=5; $i++) { echo "<option value='Tingkat $i'>Tingkat $i</option>"; } ?>
                </select>
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