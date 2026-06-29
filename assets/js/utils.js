/* ============================================================
   MAICELO RESTOBAR — utils.js
   Utilidades compartidas para todo el frontend
   ============================================================ */

// ── Configuración Base ────────────────────────────────────────────────────
// La ruta base se define en index.html antes de cargar este script.
// Fallback a '/maicelo' si no está definida.
const BASE_URL = window.__BASE_URL || '/maicelo';

// ── Escape HTML ───────────────────────────────────────────────────────────
// Previene XSS al insertar datos del servidor en el DOM
function escapeHTML(str) {
  const d = document.createElement('div');
  d.textContent = String(str ?? '');
  return d.innerHTML;
}

// ── CSRF Token Manager ───────────────────────────────────────────────────
window.csrf = {
  token: null,

  async getToken() {
    try {
      const res = await fetch(`${BASE_URL}/api/csrf.php`, {
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (data.token) {
        this.token = data.token;
      }
    } catch (e) {
      console.error('Error obteniendo token CSRF:', e);
    }
    return this.token;
  },

  getHeader() {
    return this.token ? { 'X-CSRF-Token': this.token } : {};
  },
};

// Obtener token CSRF al cargar la página
document.addEventListener('DOMContentLoaded', () => {
  window.csrf.getToken();
});

// ── Toast Notifications ──────────────────────────────────────────────────
function showToast(message, type = 'info') {
  // Eliminar toast anterior si existe
  const existing = document.getElementById('appToast');
  if (existing) existing.remove();

  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
  };

  const colors = {
    success: '#10b981',
    error: '#ef4444',
    warning: '#f59e0b',
    info: '#3b82f6',
  };

  const toast = document.createElement('div');
  toast.id = 'appToast';
  toast.style.cssText = `
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1a1a2e;
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    border-left: 4px solid ${colors[type] || colors.info};
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    max-width: 90vw;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
    opacity: 0;
  `;

  toast.innerHTML = `<span style="font-size:1.3rem;">${icons[type] || icons.info}</span><span>${escapeHTML(message)}</span>`;
  document.body.appendChild(toast);

  // Animación de entrada
  requestAnimationFrame(() => {
    toast.style.transform = 'translateX(-50%) translateY(0)';
    toast.style.opacity = '1';
  });

  // Auto-cerrar después de 4 segundos
  setTimeout(() => {
    toast.style.transform = 'translateX(-50%) translateY(100px)';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

// ── Button Loading State ─────────────────────────────────────────────────
function setButtonLoading(btn, loading) {
  if (!btn) return;
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="display:inline-block;width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span> Procesando...';
  } else {
    btn.disabled = false;
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
  }
}

// Agregar animación de spinner si no existe
if (!document.getElementById('spinnerStyle')) {
  const style = document.createElement('style');
  style.id = 'spinnerStyle';
  style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
  document.head.appendChild(style);
}
