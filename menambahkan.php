<?php include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $unit_pengolahan      = mysqli_real_escape_string($koneksi, $_POST['unit_pengolahan']);
    $kode_arsip           = mysqli_real_escape_string($koneksi, $_POST['kode_arsip']);
    $perihal              = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    $periode              = mysqli_real_escape_string($koneksi, $_POST['periode']);
    $nomer_dokumen        = mysqli_real_escape_string($koneksi, $_POST['nomer_dokumen']);
    $peta_lokasi          = mysqli_real_escape_string($koneksi, $_POST['peta_lokasi']); 
    $ruangan              = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
    $no_rak               = mysqli_real_escape_string($koneksi, $_POST['no_rak']);
    $tingkatan_rak        = mysqli_real_escape_string($koneksi, $_POST['tingkatan_rak']);
    $nama                 = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $keterangan_aktivitas = 'Menambahkan arsip baru ke sistem';
    $jenis_aktivitas      = "Menambahkan";
    $ttd                  = mysqli_real_escape_string($koneksi, $_POST['ttd']); 
    $jam_masuk            = mysqli_real_escape_string($koneksi, $_POST['jam_masuk']);
    $jam_keluar           = mysqli_real_escape_string($koneksi, $_POST['jam_keluar']);
    $waktu_sekarang = date('Y-m-d H:i:s');
    // var_dump($_POST); die;


    mysqli_begin_transaction($koneksi);

    try {
        $sql_arsip = "INSERT INTO arsip (unit_pengolahan, kode_arsip, perihal, periode, nomer_dokumen, peta_lokasi, ruangan, no_rak, tingkatan_rak, created_at) 
            VALUES ('$unit_pengolahan', '$kode_arsip', '$perihal', '$periode', '$nomer_dokumen', '$peta_lokasi', '$ruangan', '$no_rak', '$tingkatan_rak', '$waktu_sekarang')";

        if (mysqli_query($koneksi, $sql_arsip)){
            $arsip_id = mysqli_insert_id($koneksi);

            $sql_log = "INSERT INTO log_book (arsip_id, nama, jenis_aktivitas, keterangan_aktivitas, ttd, created_at, jam_masuk, jam_keluar) 
                VALUES ('$arsip_id', '$nama', '$jenis_aktivitas', '$keterangan_aktivitas', '$ttd', '$waktu_sekarang', '$jam_masuk', '$jam_keluar')";

            if (mysqli_query($koneksi, $sql_log)) {
                mysqli_commit($koneksi); 

                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                toast: true,
                                position: 'top-end', // Ubah ke 'bottom-end' jika ingin di bawah
                                icon: 'success',
                                title: 'Data arsip sukses disimpan!',
                                showConfirmButton: false,
                                timer: 1500, // Waktu tampil 1.5 detik
                                timerProgressBar: true
                            }).then(function() {
                                // Pindah halaman setelah animasi Toast selesai
                                window.location = 'index.php';
                            });
                        });
                    </script>";
            } else {
                mysqli_rollback($koneksi);
                $pesan_error = addslashes(mysqli_error($koneksi)); // Amankan tanda kutip
                
                echo "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Gagal Menyimpan Log!',
                            text: '$pesan_error',
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true
                        });
                    });
                </script>";
            }
        } else {
            mysqli_rollback($koneksi);
            $pesan_error = addslashes(mysqli_error($koneksi));
            
            echo "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Gagal Menyimpan Arsip!',
                        text: '$pesan_error',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                });
            </script>";
        }
            
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

        <div class="main-actions">
            <button type="submit" class="btn-lg btn-primary">Kirim</button>
            <button type="button" class="btn-lg btn-outline" onclick="window.location.reload();">Batal</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="script.js"></script>

</body>
</html>