<?php 
// 1. Menghubungkan ke file konfigurasi database
include "koneksi.php";

// 2. Logika untuk memproses data ketika form dikirim (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $arsip_id             = mysqli_real_escape_string($koneksi, $_POST['arsip_id']);
    $nama                 = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $keterangan_aktivitas = mysqli_real_escape_string($koneksi, $_POST['keterangan_aktivitas']);
    $jenis_aktivitas      = "Meminjam";
    $ttd                  = mysqli_real_escape_string($koneksi, $_POST['ttd']); 
    
    mysqli_begin_transaction($koneksi);
    
    try {
        $sql_log = "INSERT INTO log_book (arsip_id, nama, jenis_aktivitas, keterangan_aktivitas, ttd) 
                    VALUES ('$arsip_id', '$nama', '$jenis_aktivitas', '$keterangan_aktivitas', '$ttd')";
        mysqli_query($koneksi, $sql_log);
        
        $sql_update = "UPDATE arsip SET status = 'Dipinjam' WHERE id = '$arsip_id'";
        mysqli_query($koneksi, $sql_update);
        
        mysqli_commit($koneksi);
        
        echo "<script>
                alert('Data peminjaman sukses dicatat!'); 
                window.location='index.php';
              </script>";
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($koneksi);
        echo "Gagal menyimpan data: " . $exception->getMessage();
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
        <a href="menambahkan.php" class="tab">Menambahkan</a>
        <a href="meminjam.php" class="tab active">Meminjam</a>
        <a href="memindahkan.php" class="tab">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formPeminjaman">
        
        <div class="section-title">Informasi Dokumen</div>
        <div class="card">
            <div class="form-group">
                <label>Pilih Dokumen</label>
                <select name="arsip_id" class="form-control" required>
                    <option value="">Pilih Dokumen (Unit Pengolahan)</option>
                    <option value="customer service">Customer Service</option>
                    <option value="teller">Teller</option>
                    <option value="kredit">Kredit</option>
                    <option value="-">-Lainnya-</option>
                    <?php
                    $query_arsip = mysqli_query($koneksi, "SELECT id, kode_arsip, perihal, unit_pengolahan FROM arsip WHERE status = 'Tersedia'");
                    while($row = mysqli_fetch_assoc($query_arsip)) {
                        echo "<option value='".$row['id']."'>".$row['kode_arsip']." - ".$row['perihal']." (".$row['unit_pengolahan'].")</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Peminjam</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan Nama Peminjam" required>
            </div>
            <div class="form-group">
                <label>Keterangan Peminjam</label>
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