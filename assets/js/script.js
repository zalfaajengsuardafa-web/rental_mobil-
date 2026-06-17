// ============================================
// Sistem Rental Mobil - JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    // ---- Validasi Form Mobil (Client-side) ----
    const formMobil = document.getElementById('formMobil');
    if (formMobil) {
        formMobil.addEventListener('submit', function (e) {
            let valid = true;
            clearValidation(formMobil);

            // Validasi field Merk (wajib, min 2 karakter)
            const merk = formMobil.querySelector('[name="merk"]');
            if (merk && merk.value.trim().length < 2) {
                showError(merk, 'Merk harus diisi minimal 2 karakter.');
                valid = false;
            }

            // Validasi field Harga Sewa (wajib, angka > 0)
            const harga = formMobil.querySelector('[name="harga_sewa"]');
            if (harga && (isNaN(harga.value) || parseFloat(harga.value) <= 0)) {
                showError(harga, 'Harga sewa harus berupa angka lebih dari 0.');
                valid = false;
            }

            // Validasi Nomor Polisi (wajib)
            const nopol = formMobil.querySelector('[name="nopol"]');
            if (nopol && nopol.value.trim().length < 3) {
                showError(nopol, 'Nomor polisi harus diisi minimal 3 karakter.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    }

    // ---- Validasi Form Transaksi (Client-side) ----
    const formTransaksi = document.getElementById('formTransaksi');
    if (formTransaksi) {
        formTransaksi.addEventListener('submit', function (e) {
            let valid = true;
            clearValidation(formTransaksi);

            // Validasi Nama Penyewa (wajib, min 3 karakter)
            const nama = formTransaksi.querySelector('[name="nama_penyewa"]');
            if (nama && nama.value.trim().length < 3) {
                showError(nama, 'Nama penyewa harus diisi minimal 3 karakter.');
                valid = false;
            }

            // Validasi No KTP (wajib, 16 digit)
            const ktp = formTransaksi.querySelector('[name="no_ktp"]');
            if (ktp && !/^\d{16}$/.test(ktp.value.trim())) {
                showError(ktp, 'No KTP harus 16 digit angka.');
                valid = false;
            }

            // Validasi No Telepon (wajib, min 10 digit)
            const telp = formTransaksi.querySelector('[name="no_telp"]');
            if (telp && !/^\d{10,15}$/.test(telp.value.trim())) {
                showError(telp, 'No telepon harus 10-15 digit angka.');
                valid = false;
            }

            // Validasi tanggal kembali > tanggal sewa
            const tglSewa = formTransaksi.querySelector('[name="tgl_sewa"]');
            const tglKembali = formTransaksi.querySelector('[name="tgl_kembali"]');
            if (tglSewa && tglKembali && tglSewa.value && tglKembali.value) {
                if (new Date(tglKembali.value) <= new Date(tglSewa.value)) {
                    showError(tglKembali, 'Tanggal kembali harus setelah tanggal sewa.');
                    valid = false;
                }
            }

            if (!valid) {
                e.preventDefault();
            }
        });

        // Hitung total biaya otomatis (DOM manipulation + addEventListener)
        const tglSewa = formTransaksi.querySelector('[name="tgl_sewa"]');
        const tglKembali = formTransaksi.querySelector('[name="tgl_kembali"]');
        const idMobil = formTransaksi.querySelector('[name="id_mobil"]');
        const totalBiaya = formTransaksi.querySelector('[name="total_biaya"]');
        const totalDisplay = document.getElementById('totalBiayaDisplay');

        function hitungTotal() {
            if (tglSewa && tglKembali && idMobil && tglSewa.value && tglKembali.value) {
                const start = new Date(tglSewa.value);
                const end = new Date(tglKembali.value);
                const diffTime = end - start;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 0) {
                    const selectedOption = idMobil.options[idMobil.selectedIndex];
                    const hargaPerHari = parseFloat(selectedOption.getAttribute('data-harga') || 0);
                    const total = diffDays * hargaPerHari;

                    if (totalBiaya) totalBiaya.value = total;
                    if (totalDisplay) {
                        totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
                        totalDisplay.classList.remove('d-none');
                    }
                }
            }
        }

        // addEventListener selain onclick - menggunakan 'change' dan 'input'
        if (tglSewa) tglSewa.addEventListener('change', hitungTotal);
        if (tglKembali) tglKembali.addEventListener('change', hitungTotal);
        if (idMobil) idMobil.addEventListener('change', hitungTotal);
    }

    // ---- Konfirmasi Hapus dengan confirm() ----
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const itemName = this.getAttribute('data-name') || 'data ini';
            if (!confirm('Apakah Anda yakin ingin menghapus "' + itemName + '"? Tindakan ini tidak dapat dibatalkan.')) {
                e.preventDefault();
            }
        });
    });

    // ---- Manipulasi DOM: Live search pada tabel ----
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const keyword = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#dataTable tbody tr');
            let visibleCount = 0;

            tableRows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(keyword)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update counter (DOM manipulation)
            const counter = document.getElementById('rowCounter');
            if (counter) {
                counter.textContent = visibleCount + ' data ditemukan';
            }
        });
    }

    // ---- Manipulasi DOM: Toggle dark mode sederhana ----
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function () {
            document.body.classList.toggle('bg-dark');
            document.body.classList.toggle('text-light');
        });
    }

    // ---- Auto-dismiss alerts after 5 seconds ----
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});

// ---- Helper Functions ----
function showError(input, message) {
    input.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback-js';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

function clearValidation(form) {
    form.querySelectorAll('.is-invalid').forEach(function (el) {
        el.classList.remove('is-invalid');
    });
    form.querySelectorAll('.invalid-feedback-js').forEach(function (el) {
        el.remove();
    });
}
