<?php 
// 1. Menghubungkan ke file konfigurasi database
include "koneksi.php";

// 2. Logika untuk memproses data ketika form dikirim (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Tangkap respon dari kotak reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    // // 2. Cek jika pengguna lupa mencentang kotak
    // if (empty($recaptcha_response)) {
    //     // Hentikan proses dan beri pesan peringatan (Bisa Anda ganti dengan SweetAlert)
    //     echo "<script>
    //         document.addEventListener('DOMContentLoaded', function() {
    //             Swal.fire({
    //                 toast: true,
    //                 position: 'top-end',
    //                 icon: 'error',
    //                 title: 'Error Sistem Database!',
    //                 text: 'Centang Captca Terlebih Dahulu !',
    //                 showConfirmButton: false,
    //                 timer: 5000,
    //                 timerProgressBar: true
    //             });
    //         });
    //     </script>";
    //     exit;
    // }
    
    // 3. Proses verifikasi respon ke server Google
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret_key . "&response=" . $recaptcha_response;
    
    // Kirim request ke Google dan baca hasilnya
    $verify_response = file_get_contents($verify_url);
    $response_data = json_decode($verify_response);
    
    // 4. Jika Google mendeteksi itu adalah Bot atau verifikasi gagal
    if (!$response_data->success) {
        echo "<script>alert('Verifikasi CAPTCHA gagal. Anda terdeteksi sebagai robot.'); window.history.back();</script>";
        exit;
    }
    
    $nama                 = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jabatan              = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $jenis_aktivitas      = "Pengecekan";
    $ttd                  = mysqli_real_escape_string($koneksi, $_POST['ttd']);
    $jam_masuk            = mysqli_real_escape_string($koneksi, $_POST['jam_masuk']);
    $jam_keluar            = mysqli_real_escape_string($koneksi, $_POST['jam_keluar']);

    if (isset($_POST['pengecekan']) && is_array($_POST['pengecekan'])) {
        
        // 2. Gabungkan array menjadi satu kalimat dengan koma
        $pengecekan_gabungan = implode(', ', $_POST['pengecekan']);
        
        // 3. BARU KITA AMANKAN string yang sudah digabung tersebut
        $pengecekan_aman = mysqli_real_escape_string($koneksi, $pengecekan_gabungan);
        
        // 4. Masukkan ke dalam format keterangan aktivitas
        $keterangan_aktivitas = "melakukan: " . $pengecekan_aman;
        
    } else {
        // Jika tidak ada satu pun yang dicentang
        $keterangan_aktivitas = "Syarat terpenuhi: Tidak ada";
    }

    $waktu_sekarang = date('Y-m-d H:i:s');
    
    mysqli_begin_transaction($koneksi);
    
    try {
        $sql_log = "INSERT INTO log_book (nama, jabatan, jenis_aktivitas, keterangan_aktivitas, ttd, created_at, jam_masuk, jam_keluar) 
                    VALUES ('$nama', '$jabatan', '$jenis_aktivitas', '$keterangan_aktivitas', '$ttd', '$waktu_sekarang', '$jam_masuk', '$jam_keluar')";
        mysqli_query($koneksi, $sql_log);
        
        mysqli_commit($koneksi);
        
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end', // Ubah ke 'bottom-end' jika ingin di bawah
                        icon: 'success',
                        title: 'Data pengecekan arsip berhasil disimpan!',
                        showConfirmButton: false,
                        timer: 1500, // Waktu tampil 1.5 detik
                        timerProgressBar: true
                    }).then(function() {
                        // Pindah halaman setelah animasi Toast selesai
                        window.location = 'index.php';
                    });
                });
              </script>";
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($koneksi);
        $pesan_error = addslashes($exception->getMessage());
        
        echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Error Sistem Database!',
                    text: '$pesan_error',
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
        <a href="pengecekan.php" class="tab active">pengecekan</a>
        <a href="menambahkan.php" class="tab">Menambahkan</a>
        <a href="meminjam.php" class="tab">Meminjam</a>
        <a href="memindahkan.php" class="tab">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formPengecekan">

        <div class="section-title">Informasi Karyawan</div>
        <div class="card">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan Nama Pemindah" required>
            </div>
            <div class="form-group">
                <label>Jabatan</label>
                <select name="jabatan" class="form-control" id="search" required>
                    <option value="">Pilih atau ketik jabatan...</option>
                    <option value="Kepala KCP">Kepala KCP</option>
                    <option value="BM">BM</option>
                    <option value="PCO">PCO</option>
                    <option value="Teller">Teller</option>
                    <option value="CS">CS</option>
                    <option value="BS / BSA">BS / BSA</option>
                    <option value="CUP / RBC">CUP / RBC</option>
                    <option value="TAD">TAD</option>
                </select>
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
        
        <div class="section-title">Pengecekan</div>
        <div class="card">
            <div class="form-group">
                <label>Pengecekan Arsip</label>
                <div class="checkbox-wrapper">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="pengecekan[]" value="Cek Dokumen / Pemeriksaan">
                        <span class="checkmark"></span>
                        Cek Dokumen / Pemeriksaan
                    </label>
                    
                    <label class="custom-checkbox">
                        <input type="checkbox" name="pengecekan[]" value="Cek Kebersihan">
                        <span class="checkmark"></span>
                        Cek Kebersihan
                    </label>

                    <label class="custom-checkbox">
                        <input type="checkbox" name="pengecekan[]" value="Cek Suhu, Alarm, dan Kelengkapan">
                        <span class="checkmark"></span>
                        Cek Suhu, Alarm, dan Kelengkapan
                    </label>
                </div>
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

    // Menangkap form berdasarkan ID yang kita buat tadi
    document.getElementById('formPengecekan').addEventListener('submit', function(e) {
        
        // Mengambil respon dari widget reCAPTCHA Google
        let recaptchaResponse = grecaptcha.getResponse();
        
        // Jika responnya kosong (belum dicentang)
        if (recaptchaResponse.length === 0) {
            
            // 1. TAHAN FORM! Jangan biarkan loading/pindah halaman
            e.preventDefault(); 
            
            // 2. Munculkan SweetAlert Toast Anda yang keren itu
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning', // Saya ganti ke warning agar lebih pas untuk peringatan
                title: 'Validasi Diperlukan',
                text: 'Silakan centang Captcha terlebih dahulu!',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        }
    });

    $("#search").select2({
        tags: true,
        placeholder: "Pilih atau ketik jabatan...",
        allowClear: true,
        width: '100%'
    });
</script>

</body>
</html>