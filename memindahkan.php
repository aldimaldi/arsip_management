<?php 
// 1. Menghubungkan ke file konfigurasi database
include "koneksi.php";

// 2. Logika untuk memproses data ketika form dikirim (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $arsip_id             = mysqli_real_escape_string($koneksi, $_POST['arsip_id']);
    $nama                 = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $no_rak               = mysqli_real_escape_string($koneksi, $_POST['no_rak']);
    $tingkatan_rak        = mysqli_real_escape_string($koneksi, $_POST['tingkatan_rak']);
    $ruangan              = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
    $keterangan_aktivitas = mysqli_real_escape_string($koneksi, $_POST['keterangan_aktivitas']);
    $jenis_aktivitas      = "Memindahkan";
    
    // Proses decode tanda tangan dari Base64 ke File Gambar PNG
    $ttd_base64 = $_POST['ttd_base64'];
    $nama_file_ttd = NULL;

    if (!empty($ttd_base64) && $ttd_base64 != "empty") {
        // Hapus metadata base64
        $filteredData = explode(',', $ttd_base64);
        if(isset($filteredData[1])) {
            $unencodedData = base64_decode($filteredData[1]);
            
            // Nama file unik untuk tanda tangan
            $nama_file_ttd = "ttd_" . time() . "_" . uniqid() . ".png";
            $target_path = "uploads/" . $nama_file_ttd;
            
            // Simpan gambar ke folder uploads
            file_put_contents($target_path, $unencodedData);
        }
    }

    // Mulai proses transaksi database
    mysqli_begin_transaction($koneksi);
    
    try {
        // a. Update lokasi rak dan ruangan baru di tabel arsip
        $sql_update_arsip = "UPDATE arsip SET 
                                ruangan = '$ruangan', 
                                no_rak = '$no_rak', 
                                tingkatan_rak = '$tingkatan_rak' 
                             WHERE id = '$arsip_id'";
        mysqli_query($koneksi, $sql_update_arsip);
        
        // b. Catat riwayat pemindahan ke tabel log_book beserta nama file tanda tangan
        $sql_insert_log = "INSERT INTO log_book (arsip_id, nama, ttd, jenis_aktivitas, keterangan_aktivitas) 
                           VALUES ('$arsip_id', '$nama', '$nama_file_ttd', '$jenis_aktivitas', '$keterangan_aktivitas')";
        mysqli_query($koneksi, $sql_insert_log);
        
        // Komit transaksi
        mysqli_commit($koneksi);
        
        echo "<script>
                alert('Dokumen berhasil dipindahkan dan riwayat telah dicatat!'); 
                window.location='memindahkan.php';
              </script>";
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($koneksi);
        echo "Gagal memproses pemindahan: " . $exception->getMessage();
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body>

<div class="mobile-container">
    <div class="header">
        <img src="img/Logo.png" alt="Logo Mandiri Taspen">
    </div>

    <div class="tabs-container">
        <a href="index.php" class="tab ">Log Book</a>
        <a href="menambahkan.php" class="tab">Menambahkan</a>
        <a href="meminjam.php" class="tab">Meminjam</a>
        <a href="memindahkan.php" class="tab active">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formTambahArsip">
        
        <div class="section-title">Informasi Dokumen</div>
        <div class="card">
            <div class="form-group">
                <label>Pilih Dokumen</label>
                <select name="arsip_id" class="form-control" required>
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
                    $query_arsip = mysqli_query($koneksi, "SELECT id, kode_arsip, perihal, ruangan, no_rak, periode FROM arsip");
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

                        echo "<option value='".$row['id']."'>".$row['kode_arsip']." - ".$row['perihal']. " - " . $periode_format ." - "." (Posisi: ".$row['ruangan']."/".$row['no_rak'].")</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nama Pemindah</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan Nama Pemindah" required>
            </div>

            <div class="form-group">
                <label>No Rak</label>
                <select name="no_rak" class="form-control" required>
                    <option value="">Pilih No Rak</option>
                    <?php for($i=1; $i<=13; $i++) { echo "<option value='Rak $i'>Rak $i</option>"; } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tingkatan Rak</label>
                <select name="tingkatan_rak" class="form-control" required>
                    <option value="">Pilih Tingkatan Rak</option>
                    <?php for($i=1; $i<=5; $i++) { echo "<option value='Tingkat $i'>Tingkat $i</option>"; } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Ruangan</label>
                <select name="ruangan" class="form-control" required>
                    <option value="">Pilih Ruangan</option>
                    <option value="Ruangan Arsip">Ruangan Arsip</option>
                </select>
            </div>

            <div class="form-group">
                <label>Keterangan Pemindahan</label>
                <textarea name="keterangan_aktivitas" class="form-control" placeholder="Masukkan Keterangan Peminjam"></textarea>
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

<script>
    // 1. Inisialisasi Canvas dan SignaturePad
    const canvas = document.getElementById('canvasTtd');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)', 
        penColor: 'rgb(12, 62, 104)' // Mengubah warna goresan pulpen menjadi Biru Mandiri Taspen
    });

    // 2. Fungsi menyesuaikan ukuran canvas agar presisi
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear(); 
    }

    window.addEventListener("resize", resizeCanvas);
    document.addEventListener("DOMContentLoaded", resizeCanvas);

    // 3. Tombol Ulang / Hapus Coretan
    document.getElementById('btnHapus').addEventListener('click', function() {
        signaturePad.clear();
        document.getElementById('inputTtd').value = ""; 
        alert('Coretan tanda tangan dibersihkan.');
    });

    // 4. Tombol Konfirmasi Tanda Tangan
    document.getElementById('btnKonfirmasi').addEventListener('click', function() {
        if (signaturePad.isEmpty()) {
            alert("Silakan buat tanda tangan terlebih dahulu!");
        } else {
            const dataDataUrl = signaturePad.toDataURL('image/png');
            document.getElementById('inputTtd').value = dataDataUrl;
            alert("Tanda tangan berhasil dikonfirmasi!");
        }
    });

    // 5. Validasi saat form dikirim
    document.getElementById('formPeminjaman').addEventListener('submit', function(e) {
        const inputTtdValue = document.getElementById('inputTtd').value;
        if (!inputTtdValue || signaturePad.isEmpty()) {
            e.preventDefault(); 
            alert("Anda belum menekan tombol 'Konfirmasi' pada tanda tangan!");
        }
    });
</script>

</body>
</html>