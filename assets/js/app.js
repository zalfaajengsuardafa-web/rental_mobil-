/**
 * RentWheels - Main JavaScript
 * UAS Praktikum Pemrograman Web 1
 *
 * Fitur:
 * - Form validation (minimal 2 field)
 * - confirm() sebelum hapus data
 * - Manipulasi DOM (pesan error inline)
 * - addEventListener (selain onclick)
 */

'use strict';

// ============================================
// SCROLL TO TOP BUTTON
// addEventListener: 'scroll' (bukan onclick)
// ============================================
document.addEventListener('DOMContentLoaded', function () {

    // --- Scroll to top button ---
    const scrollBtn = document.getElementById('scrollTopBtn');
    if (scrollBtn) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 300) {
                scrollBtn.style.display = 'flex';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- Auto-dismiss flash alert setelah 4 detik ---
    const flashEl = document.querySelector('#flash-container .alert');
    if (flashEl) {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(flashEl);
            bsAlert.close();
        }, 4000);
    }

    // --- Inisialisasi tooltip Bootstrap ---
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

    // --- Search realtime: tambahkan delay supaya tidak terlalu sering fire ---
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const icon = document.getElementById('searchIcon');
            if (icon) icon.className = 'bi bi-hourglass-split text-muted';

            searchTimeout = setTimeout(() => {
                if (icon) icon.className = 'bi bi-search text-muted';
                filterTable(this.value.toLowerCase());
            }, 300);
        });
    }

    // --- Hitung total harga di form transaksi secara real-time ---
    initTransaksiCalculator();

    // --- Validasi form sebelum submit ---
    initFormValidation();
});

// ============================================
// TABEL FILTER (Search di client-side)
// ============================================
function filterTable(keyword) {
    const rows = document.querySelectorAll('#dataTable tbody tr');
    let found = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(keyword)) {
            row.style.display = '';
            found++;
        } else {
            row.style.display = 'none';
        }
    });

    // Tampilkan pesan jika tidak ada hasil (DOM manipulation)
    const emptyMsg = document.getElementById('noResultMsg');
    if (emptyMsg) {
        emptyMsg.style.display = found === 0 ? 'block' : 'none';
    }

    // Update counter
    const counter = document.getElementById('rowCounter');
    if (counter) {
        counter.textContent = `Menampilkan ${found} data`;
    }
}

// ============================================
// KONFIRMASI HAPUS DATA
// ============================================
function confirmDelete(nama, url) {
    if (confirm(`⚠️ Hapus "${nama}"?\n\nData yang dihapus tidak dapat dikembalikan.`)) {
        window.location.href = url;
    }
}

// ============================================
// KONFIRMASI PERUBAHAN (Edit)
// ============================================
function confirmEdit(formId) {
    if (confirm('Simpan perubahan data ini?')) {
        document.getElementById(formId).submit();
    }
}

// ============================================
// KALKULATOR HARGA TRANSAKSI
// DOM manipulation: update field total_harga otomatis
// ============================================
function initTransaksiCalculator() {
    const tglMulai   = document.getElementById('tanggal_mulai');
    const tglSelesai = document.getElementById('tanggal_selesai');
    const hargaInput = document.getElementById('harga_per_hari_display');
    const totalEl    = document.getElementById('total_harga_display');
    const hariEl     = document.getElementById('jumlah_hari_display');

    if (!tglMulai || !tglSelesai) return;

    function hitungTotal() {
        const mulai   = new Date(tglMulai.value);
        const selesai = new Date(tglSelesai.value);
        const harga   = parseFloat(hargaInput ? hargaInput.dataset.harga || 0 : 0);

        if (tglMulai.value && tglSelesai.value && selesai > mulai) {
            const diffMs   = selesai - mulai;
            const diffHari = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

            if (hariEl) hariEl.textContent = diffHari + ' hari';
            if (totalEl) {
                const total = diffHari * harga;
                totalEl.textContent = formatRupiah(total);
                totalEl.dataset.value = total;
            }

            // Update hidden input
            const jumlahHariInput = document.getElementById('jumlah_hari');
            const totalHargaInput = document.getElementById('total_harga');
            if (jumlahHariInput) jumlahHariInput.value = diffHari;
            if (totalHargaInput) totalHargaInput.value = diffHari * harga;

            // Hapus error jika valid
            clearError('tanggal_selesai');
        } else if (selesai <= mulai && tglSelesai.value) {
            showError('tanggal_selesai', 'Tanggal selesai harus setelah tanggal mulai');
            if (hariEl) hariEl.textContent = '-';
            if (totalEl) totalEl.textContent = '-';
        }
    }

    // addEventListener: 'change' (bukan onclick)
    tglMulai.addEventListener('change', hitungTotal);
    tglSelesai.addEventListener('change', hitungTotal);

    // Saat pilih mobil, update harga per hari
    const mobilSelect = document.getElementById('mobil_id');
    if (mobilSelect) {
        mobilSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const harga = opt.dataset.harga || 0;
            if (hargaInput) {
                hargaInput.dataset.harga = harga;
                hargaInput.textContent  = harga > 0 ? formatRupiah(harga) + '/hari' : '-';
            }
            hitungTotal();
        });
    }
}

// ============================================
// VALIDASI FORM (minimal 2 field)
// DOM manipulation: tampilkan error inline
// ============================================
function initFormValidation() {
    // Semua form dengan class 'needs-validation'
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            let valid = true;

            // Kumpulkan semua field wajib
            const requiredFields = form.querySelectorAll('[data-validate]');

            requiredFields.forEach(field => {
                clearError(field.id);
                const rules = field.dataset.validate.split('|');

                for (let rule of rules) {
                    const [ruleName, ruleVal] = rule.split(':');

                    if (ruleName === 'required' && !field.value.trim()) {
                        showError(field.id, field.dataset.label + ' wajib diisi');
                        valid = false;
                        break;
                    }

                    if (ruleName === 'minlength' && field.value.trim().length < parseInt(ruleVal)) {
                        showError(field.id, field.dataset.label + ' minimal ' + ruleVal + ' karakter');
                        valid = false;
                        break;
                    }

                    if (ruleName === 'numeric' && !/^\d+$/.test(field.value.trim())) {
                        showError(field.id, field.dataset.label + ' harus berupa angka');
                        valid = false;
                        break;
                    }

                    if (ruleName === 'phone' && !/^[0-9+\-\s]{9,15}$/.test(field.value.trim())) {
                        showError(field.id, field.dataset.label + ' tidak valid (contoh: 081234567890)');
                        valid = false;
                        break;
                    }

                    if (ruleName === 'email' && field.value.trim() !== '') {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(field.value.trim())) {
                            showError(field.id, field.dataset.label + ' tidak valid');
                            valid = false;
                            break;
                        }
                    }

                    if (ruleName === 'min' && parseFloat(field.value) < parseFloat(ruleVal)) {
                        showError(field.id, field.dataset.label + ' minimal ' + ruleVal);
                        valid = false;
                        break;
                    }
                }
            });

            if (!valid) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll ke error pertama
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });
}

// ============================================
// HELPER: Tampilkan / Hapus Error Inline
// (DOM Manipulation)
// ============================================
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.add('is-invalid');

    let feedback = field.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.appendChild(feedback);
    }
    feedback.textContent = '⚠ ' + message;
    feedback.classList.add('show');
}

function clearError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.remove('is-invalid');
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.classList.remove('show');
        feedback.textContent = '';
    }
}

// ============================================
// HELPER: Format Rupiah
// ============================================
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);
}

// ============================================
// TOGGLE PASSWORD VISIBILITY
// addEventListener: 'click'
// ============================================
const togglePwdBtns = document.querySelectorAll('.toggle-password');
togglePwdBtns.forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        if (!input) return;

        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        this.querySelector('i').className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
});
