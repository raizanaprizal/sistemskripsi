// ============================================================
// script.js — Sistem Pemilihan Vendor PT Bio Farma (Persero)
// ============================================================
 
// Konfirmasi hapus
function confirmDelete(msg) {
    return confirm(msg || 'Apakah Anda yakin ingin menghapus data ini?\nData yang dihapus tidak dapat dikembalikan.');
}
 
// Hitung total nilai SAW real-time
function hitungTotal() {
    const inputs = document.querySelectorAll('input[id^="nilai_"][data-bobot]');
    let total = 0;
    let valid = true;
    inputs.forEach(function (el) {
        const val = parseFloat(el.value);
        const bobot = parseFloat(el.dataset.bobot) || 0;
        if (isNaN(val) || val < 0 || val > 100) {
            el.style.borderColor = '#993C1D';
            valid = false;
        } else {
            el.style.borderColor = '#ccc';
            total += val * bobot;
        }
    });
    const totalEl = document.getElementById('total_nilai');
    const gradeEl = document.getElementById('grade_hasil');
    if (totalEl) totalEl.textContent = total.toFixed(2);
    if (gradeEl) {
        let grade = 'E';
        if (total >= 85) grade = 'A';
        else if (total >= 70) grade = 'B';
        else if (total >= 55) grade = 'C';
        else if (total >= 40) grade = 'D';
        gradeEl.textContent = grade;
        const colors = { A:'#27500A', B:'#0C447C', C:'#633806', D:'#993C1D', E:'#666' };
        gradeEl.style.color = colors[grade];
    }
}
 
// Auto-bind nilai inputs
document.addEventListener('DOMContentLoaded', function () {
    for (let i = 1; i <= 6; i++) {
        const el = document.getElementById('nilai_' + i);
        if (el) el.addEventListener('input', hitungTotal);
    }
    hitungTotal();
});
 