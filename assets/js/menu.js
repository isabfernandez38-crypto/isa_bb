/* ============================================================
   MAICELO RESTOBAR — menu.js
   ============================================================ */

// escapeHTML() ahora está en utils.js

let todosLosPlatos = [];
let todasLasCategorias = [];
let categoriaIndex = 0;

// Imágenes placeholder por slug de categoría
const imgPorCategoria = {
  'entradas':      'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=400&q=80',
  'bajada-criolla':'https://images.unsplash.com/photo-1585325701956-60dd9c8399b6?w=400&q=80',
  'bajada-marina': 'https://images.unsplash.com/photo-1611143669185-af224c5e3252?w=400&q=80',
  'bajada-night':  'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80',
  'pastas':        'https://images.unsplash.com/photo-1555949258-eb67b1ef0ceb?w=400&q=80',
  'parrillas':     'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&q=80',
  'piscos':        'https://images.unsplash.com/photo-1609951651556-5334e2706168?w=400&q=80',
  'tragos':        'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=400&q=80',
  'refrescantes':  'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&q=80',
  'chelas':        'https://images.unsplash.com/photo-1608270586620-248524c67de9?w=400&q=80',
  'aguayu':        'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&q=80',
};

function generarImagenPlato(plato) {
  if (plato.imagen_url) return plato.imagen_url;
  return imgPorCategoria[plato.categoria_slug] || imgPorCategoria['entradas'];
}

function renderMenuCard(plato) {
  const img     = generarImagenPlato(plato);
  const precio  = parseFloat(plato.precio).toFixed(2);
  const badges  = [];
  if (plato.es_destacado) badges.push('<span class="badge-destacado">⭐ DESTACADO</span>');
  if (plato.precio_alt)   badges.push('<span class="badge-doble-precio">Vaso / Jarra</span>');

  const precioAlt = plato.precio_alt
    ? `<span class="precio-alt">${plato.unidad_alt}: S/ ${parseFloat(plato.precio_alt).toFixed(2)}</span>`
    : '';

  // C4: Escapar todos los datos de la BD
  const slugSafe = escapeHTML(plato.categoria_slug);
  const nomSafe  = escapeHTML(plato.nombre);
  const descSafe = plato.descripcion ? `<p class="menu-card-desc">${escapeHTML(plato.descripcion)}</p>` : '';

  return `
    <div class="menu-card animate-fade-up" data-categoria="${slugSafe}">
      <div class="menu-card-img-wrapper">
        <img src="${img}" alt="${nomSafe}" loading="lazy" onerror="this.src='${imgPorCategoria['entradas']}'">
        ${badges.join('')}
      </div>
      <div class="menu-card-body">
        <h4 class="menu-card-nombre">${nomSafe}</h4>
        ${descSafe}
        <div class="menu-card-precio">
          <span class="precio-principal">S/ ${precio}</span>
          ${precioAlt}
        </div>
      </div>
    </div>
  `;
}

function renderMenuCards(platos, contenedor) {
  if (!contenedor) return;
  if (platos.length === 0) {
    contenedor.innerHTML = '<div class="col-12 text-center" style="color:var(--text-muted);padding:3rem;">No hay platos en esta categoría.</div>';
    return;
  }
  contenedor.innerHTML = platos.map(renderMenuCard).join('');
  // Re-observar para animaciones
  contenedor.querySelectorAll('.animate-fade-up').forEach(el => {
    el.classList.remove('visible');
    setTimeout(() => el.classList.add('visible'), 50);
  });
}

function filtrarPorCategoria(slug) {
  const contenedor = document.getElementById('menuGrid');
  if (!contenedor) return;

  const platos = slug === 'todos'
    ? todosLosPlatos
    : todosLosPlatos.filter(p => p.categoria_slug === slug);

  contenedor.style.opacity = '0';
  contenedor.style.transition = 'opacity 0.2s';
  setTimeout(() => {
    renderMenuCards(platos, contenedor);
    contenedor.style.opacity = '1';
  }, 200);
}

function actualizarNavegacion() {
  const total = todasLasCategorias.length;
  const cat   = todasLasCategorias[categoriaIndex];
  const indicador = document.getElementById('indicadorMenu');
  const btnAnt    = document.getElementById('btnAnterior');
  const btnSig    = document.getElementById('btnSiguiente');
  const titulo    = document.getElementById('menuCategoriaTitulo');
  if (indicador) indicador.textContent = `Sección ${categoriaIndex + 1} de ${total} — ${cat?.nombre || ''}`;
  if (btnAnt)    btnAnt.disabled    = categoriaIndex === 0;
  if (btnSig)    btnSig.disabled    = categoriaIndex === total - 1;
  if (titulo)    titulo.textContent = cat?.nombre || '';
}

function navegarCategoria(direccion) {
  const total = todasLasCategorias.length;
  categoriaIndex = Math.max(0, Math.min(total - 1, categoriaIndex + direccion));
  const slug = todasLasCategorias[categoriaIndex]?.slug;
  if (!slug) return;
  activarTab(slug);
  const seccion = document.getElementById('menu');
  if (seccion) seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function activarTab(slug) {
  document.querySelectorAll('.menu-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.slug === slug);
  });
  // Sincronizar categoriaIndex si se activó desde un tab
  const idx = todasLasCategorias.findIndex(c => c.slug === slug);
  if (idx !== -1) categoriaIndex = idx;
  filtrarPorCategoria(slug);
  actualizarNavegacion();
}

async function cargarMenu() {
  const contenedor = document.getElementById('menuGrid');
  const tabsContainer = document.getElementById('menuTabs');
  if (!contenedor) return;

  try {
    const res  = await fetch(`${BASE_URL}/api/menu.php`);
    const data = await res.json();
    if (!data.success) throw new Error('Error en API');

    // Extraer todos los platos y categorías
    todasLasCategorias = data.categorias;
    todosLosPlatos     = data.platos;

    // A5: Generar tabs con event delegation (un solo listener en el contenedor)
    if (tabsContainer) {
      tabsContainer.innerHTML = data.categorias.map(cat =>
        `<button class="menu-tab" data-slug="${escapeHTML(cat.slug)}">${escapeHTML(cat.icono || '')} ${escapeHTML(cat.nombre)}</button>`
      ).join('');

      // Un único listener delegado — evita memory leaks al regenerar el DOM
      tabsContainer.addEventListener('click', (e) => {
        const tab = e.target.closest('.menu-tab');
        if (tab) activarTab(tab.dataset.slug);
      });
    }

    // Arrancar en la primera categoría
    categoriaIndex = 0;
    const primeraSlug = todasLasCategorias[0]?.slug;
    if (primeraSlug) {
      activarTab(primeraSlug);
    } else {
      renderMenuCards(todosLosPlatos, contenedor);
    }

  } catch (e) {
    console.error('Error cargando menú:', e);
    contenedor.innerHTML = `
      <div class="col-12 text-center" style="padding:3rem;color:var(--text-muted);">
        <i class="fas fa-utensils" style="font-size:2rem;margin-bottom:1rem;color:var(--border-gold);"></i>
        <p>No se pudo cargar el menú. Por favor, intenta más tarde.</p>
      </div>
    `;
  }
}

// Swipe mobile para cambiar sección
(function initSwipe() {
  let startX = 0;
  document.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
  document.addEventListener('touchend', e => {
    const grid = document.getElementById('menuGrid');
    if (!grid) return;
    const diff = startX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 60) navegarCategoria(diff > 0 ? 1 : -1);
  }, { passive: true });
})();

document.addEventListener('DOMContentLoaded', cargarMenu);
