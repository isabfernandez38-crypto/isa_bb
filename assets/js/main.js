/* ============================================================
   MAICELO RESTOBAR — main.js
   ============================================================ */

const BASE_URL = '/PRACTICAS';

// ── 1. Nav scroll effect ──────────────────────────────────────────────────
const mainNav = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
  mainNav?.classList.toggle('scrolled', window.scrollY > 50);
}, { passive: true });

// ── 2. Smooth scroll ─────────────────────────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', e => {
    const target = document.querySelector(anchor.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    const offset = 80;
    const top    = target.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top, behavior: 'smooth' });
    // Cerrar navbar mobile
    const bsCollapse = document.getElementById('navMenu');
    if (bsCollapse?.classList.contains('show')) {
      bootstrap.Collapse.getInstance(bsCollapse)?.hide();
    }
  });
});

// ── 3. Active section tracking ────────────────────────────────────────────
const sections  = document.querySelectorAll('section[id]');
const navLinks  = document.querySelectorAll('.nav-link');
const sectionObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      navLinks.forEach(link => {
        link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id);
      });
    }
  });
}, { threshold: 0.3, rootMargin: '-80px 0px -40% 0px' });
sections.forEach(s => sectionObserver.observe(s));

// ── 4. Animate on scroll ─────────────────────────────────────────────────
const animObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      animObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.animate-fade-up').forEach(el => animObserver.observe(el));

// ── 5. CountUp animation ─────────────────────────────────────────────────
function animateCounter(el, target, duration = 1800) {
  const isDecimal = el.dataset.decimal;
  const decimals  = isDecimal ? parseInt(isDecimal) : 0;
  let start   = null;
  const step  = ts => {
    if (!start) start = ts;
    const progress = Math.min((ts - start) / duration, 1);
    const ease     = 1 - Math.pow(1 - progress, 3);
    const current  = parseFloat(target) * ease;
    el.textContent = decimals ? current.toFixed(decimals) : Math.floor(current);
    if (progress < 1) requestAnimationFrame(step);
    else el.textContent = target;
  };
  requestAnimationFrame(step);
}

const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.querySelectorAll('.highlight-number[data-target]').forEach(el => {
        animateCounter(el, el.dataset.target);
      });
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });
const highlightsSec = document.getElementById('highlights');
if (highlightsSec) counterObserver.observe(highlightsSec);

// ── 6. Parallax hero ─────────────────────────────────────────────────────
const hero = document.getElementById('hero');
window.addEventListener('scroll', () => {
  if (!hero) return;
  const offset = window.scrollY * 0.4;
  hero.style.backgroundPositionY = `calc(50% + ${offset}px)`;
}, { passive: true });

// ── 7. Scroll to top ─────────────────────────────────────────────────────
const scrollTopBtn = document.getElementById('scrollTop');
window.addEventListener('scroll', () => {
  scrollTopBtn?.classList.toggle('visible', window.scrollY > 500);
}, { passive: true });
scrollTopBtn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

// ── 8. Toast notification system ─────────────────────────────────────────
function showToast(message, type = 'success', duration = 4000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  // M3: Limitar a 3 toasts simultáneos para evitar stack infinito
  const existing = container.querySelectorAll('.toast-custom');
  if (existing.length >= 3) existing[0].remove();

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast-custom ${type}`;
  // Escapar mensaje antes de insertar
  const msgSpan  = document.createElement('span');
  msgSpan.className = 'toast-msg';
  msgSpan.textContent = message;
  const iconSpan = document.createElement('span');
  iconSpan.className = 'toast-icon';
  iconSpan.textContent = icons[type] || 'ℹ️';
  const closeBtn = document.createElement('button');
  closeBtn.className = 'toast-close';
  closeBtn.textContent = '×';
  closeBtn.addEventListener('click', () => toast.remove());
  toast.appendChild(iconSpan);
  toast.appendChild(msgSpan);
  toast.appendChild(closeBtn);
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 350);
  }, duration);
}
window.showToast = showToast;

// ── 9. Loading button ─────────────────────────────────────────────────────
function setButtonLoading(btn, loading = true) {
  if (!btn) return;
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span> Procesando...';
    btn.disabled  = true;
  } else {
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    btn.disabled  = false;
  }
}
window.setButtonLoading = setButtonLoading;

// ── 10. CSRF Token Manager ────────────────────────────────────────────────
const csrf = {
  token: null,
  async getToken() {
    try {
      const res  = await fetch(`${BASE_URL}/api/csrf.php`);
      const data = await res.json();
      this.token = data.token;
      const input = document.getElementById('csrfToken');
      if (input) input.value = this.token;
      return this.token;
    } catch (e) {
      console.error('CSRF token error:', e);
      return null;
    }
  },
  getHeader() {
    return this.token ? { 'X-CSRF-Token': this.token } : {};
  }
};
window.csrf = csrf;

// ── 11. Horarios dinámicos ────────────────────────────────────────────────
let horarioData = null;
async function cargarHorarios() {
  try {
    const res  = await fetch(`${BASE_URL}/api/horarios.php`);
    const data = await res.json();
    if (!data.success) return;

    horarioData = data.horarios;
    window.horarioData = horarioData;

    // Renderizar en sección reservas
    const list = document.getElementById('horariosList');
    if (list) {
      // C7: API devuelve h.dia (lunes, martes...) — mapeamos a nombre con tilde
      const diasDisplay = {
        lunes:'Lunes', martes:'Martes', miercoles:'Miércoles',
        jueves:'Jueves', viernes:'Viernes', sabado:'Sábado', domingo:'Domingo',
      };
      list.innerHTML = data.horarios.map(h => {
        const nombre = diasDisplay[h.dia] || h.dia;
        const cierreStr = h.hora_cierre === '00:00' ? '12:00am' : h.hora_cierre.slice(0, 5);
        const horaLabel = h.cerrado
          ? '<span style="color:var(--error)">Cerrado</span>'
          : `${h.hora_apertura.slice(0,5)} – ${cierreStr}`;
        return `<li class="horario-item ${h.es_hoy ? 'hoy' : ''}">
          <span class="dia">${nombre}</span>
          <span class="hora">${horaLabel}</span>
        </li>`;
      }).join('');
    }
  } catch (e) {
    console.error('Error cargando horarios:', e);
  }
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  await csrf.getToken();
  await cargarHorarios();
});
