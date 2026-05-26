// ====================================================
// FUNGSI TANDA TANGAN DIGITAL (SIGNATURE PAD)
// ====================================================

// 1. Cari elemen canvas
const canvas = document.getElementById('canvasTtd');

// Hanya jalankan semua script tanda tangan JIKA canvas ditemukan di halaman tersebut
if (canvas) {
    
    // Konfigurasi template dasar SweetAlert Toast untuk dipakai berulang kali
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    // Inisialisasi SignaturePad
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)', 
        penColor: 'rgb(12, 62, 104)' 
    });

    // Fungsi menyesuaikan ukuran canvas
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear(); 
    }

    window.addEventListener("resize", resizeCanvas);
    
    // Panggil sekali saat halaman selesai dimuat agar ukurannya langsung pas
    setTimeout(resizeCanvas, 100); 

    // Tombol Ulang / Hapus Coretan
    const btnHapus = document.getElementById('btnHapus');
    if (btnHapus) {
        btnHapus.addEventListener('click', function() {
            signaturePad.clear();
            document.getElementById('inputTtd').value = ""; 
            
            // Menggunakan SweetAlert info
            Toast.fire({ icon: 'info', title: 'Coretan dibersihkan.' });
        });
    }

    // Tombol Konfirmasi Tanda Tangan
    const btnKonfirmasi = document.getElementById('btnKonfirmasi');
    if (btnKonfirmasi) {
        btnKonfirmasi.addEventListener('click', function() {
            if (signaturePad.isEmpty()) {
                // Menggunakan SweetAlert warning
                Toast.fire({ icon: 'warning', title: 'Silakan buat tanda tangan terlebih dahulu!' });
            } else {
                const dataDataUrl = signaturePad.toDataURL('image/png');
                document.getElementById('inputTtd').value = dataDataUrl;
                
                // Menggunakan SweetAlert success
                Toast.fire({ icon: 'success', title: 'Tanda tangan berhasil dikonfirmasi!' });
            }
        });
    }

    // Validasi saat form di-submit
    const formYangMembungkus = canvas.closest('form');
    if (formYangMembungkus) {
        formYangMembungkus.addEventListener('submit', function(e) {
            const inputTtdValue = document.getElementById('inputTtd').value;
            if (!inputTtdValue || signaturePad.isEmpty()) {
                e.preventDefault(); // Cegah form terkirim
                
                // Menggunakan SweetAlert error
                Toast.fire({ 
                    icon: 'error', 
                    title: 'Tanda Tangan Belum Dikonfirmasi!',
                    text: 'Silakan klik tombol konfirmasi setelah menandatangani.' 
                });
            }

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
    }
}

// Fungsi kode arsip otomatis

// 1. Ambil elemen-elemen dari HTML berdasarkan ID
const inputPerihal = document.getElementById('perihal');
const inputPeriode = document.getElementById('periode');
const inputKodeArsip = document.getElementById('kode_arsip');

// 2. Buat fungsi untuk merangkai kode
function generateKodeArsip() {
    const perihal = inputPerihal.value;
    const periode = inputPeriode.value; // Format bawaan input type="date" adalah YYYY-MM-DD

    let baseKode = "";
    let dateKode = "";

    // Logika untuk menentukan kode depan berdasarkan perihal
    if (perihal === "teller") {
        baseKode = "1204.01";
    } else if (perihal === "customer service") {
        baseKode = "1204.02";
    } else if (perihal === "kredit") {
        baseKode = "1204.03";
    } else if (perihal === "-") {
        baseKode = "1204.99"; // Contoh jika memilih lainnya, sesuaikan jika perlu
    }

    // Logika untuk memotong dan mengambil Bulan serta 2 digit Tahun
    if (periode !== "") {
        const parts = periode.split("-"); // Memecah "YYYY-MM-DD" jadi array ["YYYY", "MM", "DD"]
        const tahunLengkap = parts[0]; 
        const bulan = parts[1];
        const tahunDuaDigit = tahunLengkap.substring(2, 4); // Ambil 2 digit terakhir tahun

        dateKode = "." + bulan + tahunDuaDigit;
    }

    // Gabungkan keduanya lalu masukkan ke input form Kode Arsip
    if (baseKode !== "" && dateKode !== "") {
        inputKodeArsip.value = baseKode + dateKode;
    } else if (baseKode !== "") {
        inputKodeArsip.value = baseKode; // Tampilkan kode depan dulu jika tanggal belum diisi
    } else {
        inputKodeArsip.value = ""; // Kosongkan jika belum ada yang dipilih
    }
}

// 3. Pasang "Pendengar" agar fungsi di atas berjalan tiap kali form diubah
if (inputPerihal && inputPeriode) {
    inputPerihal.addEventListener('change', generateKodeArsip);
    inputPeriode.addEventListener('change', generateKodeArsip);
}