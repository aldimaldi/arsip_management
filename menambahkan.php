<?php include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Tangkap respon dari kotak reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    // Gunakan konstanta RECAPTCHA_SECRET_KEY yang sudah kita buat di koneksi.php
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret_key . "&response=" . $recaptcha_response;
    
    // Kirim request ke Google dan baca hasilnya
    $verify_response = file_get_contents($verify_url);
    $response_data = json_decode($verify_response);
    
    // Jika verifikasi gagal
    if (!$response_data->success) {
        echo "<script>alert('Verifikasi CAPTCHA gagal. Anda terdeteksi sebagai robot.'); window.history.back();</script>";
        exit;
    }
    
    // 2. Tangkap Variabel (Tidak perlu lagi mysqli_real_escape_string!)
    // Prepared statement akan mengamankan karakter berbahaya secara otomatis
    $unit_pengolahan      = $_POST['unit_pengolahan'];
    $kode_arsip           = $_POST['kode_arsip'];
    $perihal              = $_POST['perihal'];
    $periode              = $_POST['periode'];
    $nomer_dokumen        = $_POST['nomer_dokumen'];
    $peta_lokasi          = $_POST['peta_lokasi']; 
    $ruangan              = $_POST['ruangan'];
    $no_rak               = $_POST['no_rak'];
    $tingkatan_rak        = $_POST['tingkatan_rak'];
    $nama                 = $_POST['nama'];
    $ttd                  = $_POST['ttd']; 
    $jam_masuk            = $_POST['jam_masuk'];
    $jam_keluar           = $_POST['jam_keluar'];
    
    // Data Otomatis
    $keterangan_aktivitas = 'Menambahkan arsip baru ke sistem';
    $jenis_aktivitas      = "Menambahkan";
    $waktu_sekarang       = date('Y-m-d H:i:s');

    // Mulai Transaksi
    mysqli_begin_transaction($koneksi);

    try {
        // =========================================================================
        // UPGRADE 1: PREPARED STATEMENT UNTUK ARSIP (Anti SQL Injection)
        // Gunakan tanda tanya (?) sebagai pengganti variabel
        // =========================================================================
        $sql_arsip = "INSERT INTO arsip (unit_pengolahan, kode_arsip, perihal, periode, nomer_dokumen, peta_lokasi, ruangan, no_rak, tingkatan_rak, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_arsip = mysqli_prepare($koneksi, $sql_arsip);
        
        // Hubungkan variabel dengan tanda tanya. 
        // "ssssssssss" artinya ke-10 data tersebut adalah String (Teks)
        mysqli_stmt_bind_param($stmt_arsip, "ssssssssss", $unit_pengolahan, $kode_arsip, $perihal, $periode, $nomer_dokumen, $peta_lokasi, $ruangan, $no_rak, $tingkatan_rak, $waktu_sekarang);
        
        // Eksekusi
        if (!mysqli_stmt_execute($stmt_arsip)) {
            throw new Exception("Gagal menyimpan data utama arsip.");
        }

        $arsip_id = mysqli_insert_id($koneksi);

        // =========================================================================
        // UPGRADE 1 Lanjutan: PREPARED STATEMENT UNTUK LOG BOOK
        // =========================================================================
        $sql_log = "INSERT INTO log_book (arsip_id, nama, jenis_aktivitas, keterangan_aktivitas, ttd, created_at, jam_masuk, jam_keluar) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_log = mysqli_prepare($koneksi, $sql_log);
        
        // "isssssss" artinya: 'i' (Integer untuk arsip_id), 's' (String untuk sisanya)
        mysqli_stmt_bind_param($stmt_log, "isssssss", $arsip_id, $nama, $jenis_aktivitas, $keterangan_aktivitas, $ttd, $waktu_sekarang, $jam_masuk, $jam_keluar);

        // Eksekusi
        if (!mysqli_stmt_execute($stmt_log)) {
            throw new Exception("Gagal menyimpan riwayat log book.");
        }

        // Permanenkan data jika sukses semua
        mysqli_commit($koneksi); 
        mysqli_stmt_close($stmt_arsip);
        mysqli_stmt_close($stmt_log);

        // SweetAlert Sukses
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Data arsip sukses disimpan!',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(function() {
                        window.location = 'index.php';
                    });
                });
              </script>";

    } catch (Exception $e) {
        // Batalkan perubahan (Rollback)
        mysqli_rollback($koneksi);
        
        // =========================================================================
        // UPGRADE 2: MENCEGAH INFORMATION DISCLOSURE
        // Jangan pernah munculkan error asli dari MySQL ke layar user!
        // =========================================================================
        
        // Simpan error aslinya secara diam-diam di file log server (untuk Anda baca jika mau nge-debug)
        error_log("Database Error: " . $e->getMessage()); 
        
        // Buat pesan error bohongan (umum) untuk ditampilkan ke user
        $pesan_untuk_user = "Terjadi gangguan saat menyimpan ke database. Silakan hubungi admin.";
        
        // =========================================================================
        // UPGRADE 3: json_encode (Anti XSS untuk JavaScript)
        // =========================================================================
        echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Sistem Gagal Memproses!',
                    text: " . json_encode($pesan_untuk_user) . ",
                    showConfirmButton: false,
                    timer: 4000,
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body>

<div class="mobile-container">
    <div class="header">
        <img src="img/Logo.png" alt="Logo Mandiri Taspen"> 
    </div>

    <div class="tabs-container">
        <a href="index.php" class="tab">Log Book</a>
        <a href="pengecekan.php" class="tab ">pengecekan</a>
        <a href="menambahkan.php" class="tab active">Menambahkan</a>
        <a href="meminjam.php" class="tab">Meminjam</a>
        <a href="memindahkan.php" class="tab">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formTambahArsip">

        <div class="section-title">Informasi Kariyawan</div>
        <div class="card">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukan nama karyawan" required>
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
        <div class="card card-grid">
            <div class="form-group">
                <label>Unit Pengolahan</label>
                <input type="text" name="unit_pengolahan" class="form-control" value="KCP Bogor" readonly required>
            </div>
            <div class="form-group">
                <label>Perihal</label>
                <select name="perihal" id="perihal" class="form-control" required>
                    <option value="">Pilih Perihal Dokumen</option>
                    <option value="customer service">Customer Service</option>
                    <option value="teller">Teller</option>
                    <option value="kredit">Kredit</option>
                    <option value="-">-Lainnya-</option>
                </select>
            </div>
            <div class="form-group">
                <label>Periode</label>
                <input type="date" name="periode" id="periode" class="form-control" placeholder="Masukan Periode" required>
            </div>
            <div class="form-group">
                <label>Kode Arsip</label>
                <input type="text" name="kode_arsip" id="kode_arsip" class="form-control" value="" required readonly>
            </div>
            <div class="form-group">
                <label>Peta Lokasi</label>
                <input type="text" name="peta_lokasi" class="form-control" placeholder="Contoh: Bogor" required>
            </div>
            <div class="form-group">
                <label>Dokumen Ke</label>
                <input type="number" name="nomer_dokumen" class="form-control" placeholder="Masukan Dokumen ke berapa" required>
            </div>
        </div>

        <div class="section-title">Informasi Tata Letak</div>
        <div class="card card-grid">
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
                    <option value="Tingkat 1">Tingkat 1</option>
                    <option value="Tingkat 2">Tingkat 2</option>
                    <option value="Tingkat 3">Tingkat 3</option>
                    <option value="Tingkat 4">Tingkat 4</option>
                    <option value="Tingkat 5">Tingkat 5</option>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="script.js"></script>

</body>
</html>