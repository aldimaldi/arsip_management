<?php 
include "koneksi.php";

// Mengambil data log book dari database. 
$sql = "SELECT log_book.*, arsip.kode_arsip, arsip.perihal 
        FROM log_book 
        LEFT JOIN arsip ON log_book.arsip_id = arsip.id 
        ORDER BY log_book.id DESC"; 

$result = mysqli_query($koneksi, $sql);

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
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
        
        <div class="card" style="background-color: #f8f9fa; margin-bottom: 15px; border-top: 3px solid #0c3e68;">
            <h4 style="margin: 0 0 10px 0; color: #0c3e68; font-size: 14px;">Filter Data Spesifik</h4>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                
                <div style="flex: 1; min-width: 130px;">
                    <span style="font-size: 11px; color: #666; font-weight: bold;">Dari Tanggal:</span>
                    <input type="date" id="minDate" class="form-control" style="padding: 6px; font-size: 12px;">
                </div>
                
                <div style="flex: 1; min-width: 130px;">
                    <span style="font-size: 11px; color: #666; font-weight: bold;">Sampai Tanggal:</span>
                    <input type="date" id="maxDate" class="form-control" style="padding: 6px; font-size: 12px;">
                </div>
                
                <div style="flex: 1; min-width: 100px;">
                    <span style="font-size: 11px; color: #666; font-weight: bold;">Jenis Aktivitas:</span>
                    <select id="filterAktivitas" class="form-control" style="padding: 6px; font-size: 12px;">
                        <option value="">Semua Aktivitas</option>
                        <option value="Pengecekan">Pengecekan</option>
                        <option value="Menambahkan">Menambahkan</option>
                        <option value="Meminjam">Meminjam</option>
                        <option value="Memindahkan">Memindahkan</option>
                        <option value="Pengembalian">Pengembalian</option>
                    </select>
                </div>
                
            </div>
        </div>

        <div class="card table-card">
            <div class="table-responsive">
                <table class="table-logbook" id="tabelLogBook" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th> <th>Aktivitas</th>
                            <th>Jam Kunjungan</th>
                            <th>Nama</th>
                            <th>Keterangan</th>
                            <th>Dokumen</th>
                            <th>TTD</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                
                                // Format (Y-m-d) sangat penting agar filter tanggal DataTables mudah membaca
                                $tanggal_input = date('Y-m-d', strtotime($row['created_at']));
                                echo "<td>" . $tanggal_input . "</td>";

                                $aktivitas = $row['jenis_aktivitas'];
                                $class_warna = "";
                                
                                switch ($aktivitas) {
                                    case 'Menambahkan':
                                        $class_warna = "badge-menambahkan";
                                        break;
                                    case 'Meminjam':
                                        $class_warna = "badge-meminjam";
                                        break;
                                    case 'Pengembalian':
                                        $class_warna = "badge-pengembalian";
                                        break;
                                    case 'Memindahkan':
                                        $class_warna = "badge-memindahkan";
                                        break;
                                    case 'Pengecekan':
                                        $class_warna = "badge-pengecekan";
                                        break;
                                    default:
                                        $class_warna = ""; // Default jika ada aktivitas lain
                                        break;
                                }
                                
                                echo "<td><span class='badge-aktivitas " . $class_warna . "'>" . $aktivitas . "</span></td>";
                                
                                if (!empty($row['jam_masuk']) && !empty($row['jam_keluar'])) {
                                    $jam_m = date('H:i', strtotime($row['jam_masuk']));
                                    $jam_k = date('H:i', strtotime($row['jam_keluar']));
                                    echo "<td>" . $jam_m . " - " . $jam_k . "</td>";
                                } else {
                                    echo "<td style='text-align: center;'>-</td>";
                                }
                                
                                if ($row['jenis_aktivitas'] == 'Pengecekan') {
                                    $jabatan = !empty($row['jabatan']) ? " (" . $row['jabatan'] . ")" : "";
                                    echo "<td>" . $row['nama'] . $jabatan . "</td>";
                                } else {
                                    echo "<td>" . $row['nama'] . "</td>";
                                }
                                
                                $ket_aman = htmlspecialchars($row['keterangan_aktivitas'], ENT_QUOTES);
                                echo "<td><span class='keterangan-tooltip' title='" . $ket_aman . "' onclick='Swal.fire({ title: \"Detail Keterangan\", html: \"" . $ket_aman . "\", icon: \"info\", confirmButtonColor: \"#0c3e68\", confirmButtonText: \"Tutup\" })'>" . $row['keterangan_aktivitas'] . "</span></td>";
                                
                                if ($row['jenis_aktivitas'] == 'Pengecekan') {
                                    echo "<td style='text-align: center;'>-</td>";
                                } else {
                                    if (!empty($row['kode_arsip'])) {
                                        echo "<td>" . $row['kode_arsip'] . " - " . $row['perihal'] . "</td>";
                                    } else {
                                        echo "<td style='color:red; font-style:italic;'>Dokumen tidak ditemukan</td>";
                                    }
                                }
                                
                                if (!empty($row['ttd'])) {
                                    echo "<td><img src='" . $row['ttd'] . "' alt='Tanda Tangan' class='ttd-img' style='height: 30px;'></td>";
                                } else {
                                    echo "<td style='text-align: center;'>-</td>";
                                }

                                echo "<td style='text-align: center;'>";
                                if ($row['jenis_aktivitas'] == 'Meminjam') {
                                    if ($row['status_peminjaman'] == 'Dikembalikan') {
                                        echo "<span class='status-selesai'>Dikembalikan</span>";
                                    } else {
                                        echo "<a href='pengembalian.php?id=" . $row['id'] . "' class='btn-kembali'>Kembalikan</a>";
                                    }
                                } else {
                                    echo "-";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                        // Tag <tr> Else (kosong) sudah tidak perlu, karena DataTables akan memunculkan teks otomatis jika tabel kosong.
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.css"></script> <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // 1. Membuat Fungsi Pencarian Custom DataTables
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var minTanggal = $('#minDate').val();
            var maxTanggal = $('#maxDate').val();
            var aktivitasFilter = $('#filterAktivitas').val();
            
            // Mengambil data dari Kolom Index 1 (Tanggal) dan Index 2 (Aktivitas)
            // Catatan: Array di DataTables dimulai dari 0.
            // 0:No | 1:Tanggal | 2:Aktivitas
            var tanggalKolom = data[1] || ""; 
            var aktivitasKolom = data[2] || ""; 
            
            // Logika Cek Aktivitas
            var matchAktivitas = true;
            if (aktivitasFilter !== "") {
                // Jika kata di dropdown tidak ada di dalam kolom tabel, maka false
                if (aktivitasKolom.indexOf(aktivitasFilter) === -1) {
                    matchAktivitas = false;
                }
            }
            
            // Logika Cek Rentang Tanggal
            var matchTanggal = true;
            if (minTanggal !== "" || maxTanggal !== "") {
                if (minTanggal !== "" && tanggalKolom < minTanggal) {
                    matchTanggal = false;
                }
                if (maxTanggal !== "" && tanggalKolom > maxTanggal) {
                    matchTanggal = false;
                }
            }
            
            // Tampilkan baris HANYA JIKA lolos filter aktivitas DAN lolos filter tanggal
            return matchAktivitas && matchTanggal;
        }
    );

    // 2. Inisialisasi Tabel Saat Halaman Dimuat
    $(document).ready(function() {
        var table = $('#tabelLogBook').DataTable({
            "language": {
                "url": "id.json" // Translate ke Bahasa Indonesia
            },
            "pageLength": 10, // Menampilkan 10 baris per halaman secara default
            "ordering": false, // Mematikan panah sorting bawaan (karena data kita sudah berurutan DESC dari SQL)
            "scrollX": true
        });
        
        // 3. Trigger / Eksekusi ulang filter ketika user mengganti tanggal atau dropdown
        $('#minDate, #maxDate, #filterAktivitas').on('change', function () {
            table.draw();
        });
    });
</script>

</body>
</html>