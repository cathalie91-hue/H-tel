/* =========================================
   HOTEL SYSTEM — JavaScript Global
   ========================================= */

// ── Modal Functions ──
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// ── Delete Confirmation Modal ──
var deleteUrl = '';

function confirmDelete(url, message) {
    deleteUrl = url;
    var msgEl = document.getElementById('deleteMessage');
    if (msgEl) {
        msgEl.textContent = message || 'Cette action est irréversible.';
    }
    openModal('modalDelete');
}

function executeDelete() {
    if (deleteUrl) {
        window.location.href = deleteUrl;
    }
}
