<?php
// Wajib memanggil koneksi di paling atas
require_once 'koneksi.php';

// Mencegah akses langsung tanpa ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_peminjaman = intval($_GET['id']);

// =========================================================================
// 1. TARIK DATA DARI PEMINJAMAN SEBELUMNYA
// =========================================================================
$sql_get = "SELECT * FROM log_book WHERE id = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get);
mysqli_stmt_bind_param($stmt_get, "i", $id_peminjaman);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$data_pinjam = mysqli_fetch_assoc($result_get);

// Jika data tidak ada atau bukan aktivitas meminjam
if (!$data_pinjam || $data_pinjam['jenis_aktivitas'] != 'Meminjam') {
    echo "<script>alert('Data peminjaman tidak valid!'); window.location='index.php';</script>";
    exit;
}

// =========================================================================
// 2. MEMBEDAH KETERANGAN LAMA UNTUK MENGAMBIL NAMA DOKUMEN
// =========================================================================
$keterangan_lama = $data_pinjam['keterangan_aktivitas'];
$nama_dokumen_bersih = $keterangan_lama; // Fallback jika format teks tidak standar

// Mengecek apakah teksnya sesuai format "Dokumen: ... | Alasan: ..."
if (strpos($keterangan_lama, 'Dokumen: ') !== false && strpos($keterangan_lama, ' | Alasan:') !== false) {
    // Memotong string berdasarkan pembatas " | Alasan:"
    $potongan = explode(" | Alasan:", $keterangan_lama);
    
    // Hasilnya adalah "Dokumen: Nama Dokumennya". Kita buang kata "Dokumen: "
    $nama_dokumen_bersih = trim(str_replace("Dokumen: ", "", $potongan[0]));
}

// =========================================================================
// 3. SIAPKAN DATA OTOMATIS BARU
// =========================================================================
$arsip_id             = $data_pinjam['arsip_id'];
$nama                 = $data_pinjam['nama']; 
$ttd                  = $data_pinjam['ttd'];  
$jenis_aktivitas      = "Pengembalian";

// Format teks baru sesuai permintaan Anda: Mengembalikan "Nama Dokumen"
$keterangan_aktivitas = "Mengembalikan Dokumen \"" . $nama_dokumen_bersih . "\"";

$waktu_sekarang       = date('Y-m-d H:i:s');
$jam_sekarang         = date('H:i'); 
$status_baru          = "Dikembalikan";

$status_proses = ""; 

// Mulai Transaksi Database
mysqli_begin_transaction($koneksi);

try {
    // =========================================================================
    // PERINTAH 1: UPDATE STATUS LAMA MENJADI 'Dikembalikan'
    // =========================================================================
    $sql_update = "UPDATE log_book SET status_peminjaman = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($koneksi, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "si", $status_baru, $id_peminjaman);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Gagal memperbarui status peminjaman.");
    }

    // =========================================================================
    // PERINTAH 2: INSERT RIWAYAT PENGEMBALIAN BARU
    // =========================================================================
    $sql_insert = "INSERT INTO log_book (arsip_id, nama, jenis_aktivitas, keterangan_aktivitas, ttd, created_at, jam_masuk, jam_keluar) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($koneksi, $sql_insert);
    mysqli_stmt_bind_param($stmt_insert, "isssssss", $arsip_id, $nama, $jenis_aktivitas, $keterangan_aktivitas, $ttd, $waktu_sekarang, $jam_sekarang, $jam_sekarang);
    
    if (!mysqli_stmt_execute($stmt_insert)) {
        throw new Exception("Gagal mencatat riwayat pengembalian.");
    }

    // Kunci permanen perubahan ke database
    mysqli_commit($koneksi);
    $status_proses = "sukses";

} catch (Exception $exception) {
    mysqli_rollback($koneksi);
    error_log("Pengembalian Otomatis Error: " . $exception->getMessage());
    $status_proses = "gagal";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memproses Pengembalian...</title>
    <style> body { background-color: #f4f7f6; font-family: sans-serif; } </style>
</head>
<body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($status_proses == 'sukses') { ?>
            Swal.fire({
                toast: true,
                position: 'center',
                icon: 'success',
                title: 'Dokumen berhasil dikembalikan!',
                text: 'Proses otomatis selesai.',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            }).then(function() {
                window.location = 'index.php';
            });
            
        <?php } else { ?>
            Swal.fire({
                toast: true,
                position: 'center',
                icon: 'error',
                title: 'Gagal Memproses!',
                text: 'Terjadi kendala sistem. Silakan coba lagi.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            }).then(function() {
                window.location = 'index.php';
            });
        <?php } ?>
    });
</script>

</body>
</html>