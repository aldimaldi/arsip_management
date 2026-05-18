<?php include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $unit_pengolahan = mysqli_real_escape_string($koneksi, $_POST['unit_pengolahan']);
    $kode_arsip     = mysqli_real_escape_string($koneksi, $_POST['kode_arsip']);
    $perihal        = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    $periode        = mysqli_real_escape_string($koneksi, $_POST['periode']);
    $peta_lokasi    = mysqli_real_escape_string($koneksi, $_POST['peta_lokasi']); 
    $ruangan        = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
    $no_rak         = mysqli_real_escape_string($koneksi, $_POST['no_rak']);
    $tingkatan_rak  = mysqli_real_escape_string($koneksi, $_POST['tingkatan_rak']);
    $ttd            = mysqli_real_escape_string($koneksi, $_POST['ttd']); 
    
    $sql = "INSERT INTO arsip (unit_pengolahan, kode_arsip, perihal, periode, peta_lokasi, ruangan, no_rak, tingkatan_rak, ttd) 
            VALUES ('$unit_pengolahan', '$kode_arsip', '$perihal', '$periode', '$peta_lokasi', '$ruangan', '$no_rak', '$tingkatan_rak', '$ttd')";
    
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>
                alert('Data arsip sukses disimpan ke database!'); 
                window.location='.';
              </script>";
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($koneksi);
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
        <a href="menambahkan.php" class="tab active">Menambahkan</a>
        <a href="meminjam.php" class="tab">Meminjam</a>
        <a href="memindahkan.php" class="tab">Memindahkan</a>
    </div>

    <form action="" method="POST" class="form-area" id="formTambahArsip">
        
        <div class="section-title">Informasi Dokumen</div>
        <div class="card card-grid">
            <div class="form-group">
                <label>Unit Pengolahan</label>
                <select name="unit_pengolahan" class="form-control" required>
                    <option value="">Pilih Unit Pengolahan</option>
                    <option value="customer service">Customer Service</option>
                    <option value="teller">Teller</option>
                    <option value="kredit">Kredit</option>
                    <option value="-">-Lainnya-</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kode Arsip</label>
                <input type="text" name="kode_arsip" class="form-control" placeholder="Masukan Kode Arsip" required>
            </div>
            <div class="form-group">
                <label>Perihal</label>
                <input type="text" name="perihal" class="form-control" placeholder="Masukan Judul Dokumen" required>
            </div>
            <div class="form-group">
                <label>Periode</label>
                <input type="text" name="periode" class="form-control" placeholder="Masukan Periode" required>
            </div>
        </div>

        <div class="section-title">Informasi Ruangan</div>
        <div class="card card-grid">
            <div class="form-group">
                <label>Peta Lokasi</label>
                <select name="peta_lokasi" class="form-control" required>
                    <option value="">Pilih Lokasi</option>
                    <option value="Lantai 2">Lantai 2</option>
                    <option value="Lantai 3">Lantai 3</option>
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
                <label>No Rak</label>
                <select name="no_rak" class="form-control" required>
                    <option value="">Pilih No Rak</option>
                    <option value="Rak 1">Rak 1</option>
                    <option value="Rak 2">Rak 2</option>
                    <option value="Rak 3">Rak 3</option>
                    <option value="Rak 4">Rak 4</option>
                    <option value="Rak 5">Rak 5</option>
                    <option value="Rak 6">Rak 6</option>
                    <option value="Rak 7">Rak 7</option>
                    <option value="Rak 8">Rak 8</option>
                    <option value="Rak 9">Rak 9</option>
                    <option value="Rak 10">Rak 10</option>
                    <option value="Rak 11">Rak 11</option>
                    <option value="Rak 12">Rak 12</option>
                    <option value="Rak 13">Rak 13</option>
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

<script>
    // 1. Inisialisasi Canvas dan SignaturePad
    const canvas = document.getElementById('canvasTtd');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)', 
        penColor: 'rgb(12, 62, 104)' // Menggunakan warna pulpen biru gelap khas Mandiri Taspen
    });

    // 2. Fungsi menyesuaikan ukuran canvas dengan pembungkusnya
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

    // 5. PERBAIKAN 5: Validasi diubah mencari id "formTambahArsip"
    document.getElementById('formTambahArsip').addEventListener('submit', function(e) {
        const inputTtdValue = document.getElementById('inputTtd').value;
        if (!inputTtdValue || signaturePad.isEmpty()) {
            e.preventDefault(); 
            alert("Anda belum menekan tombol 'Konfirmasi' pada tanda tangan!");
        }
    });
</script>

</body>
</html>