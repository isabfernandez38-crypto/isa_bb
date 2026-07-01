<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
$menuRepo   = new MenuRepository();
$categorias = $menuRepo->obtenerCategorias();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menú – Maicelo Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css?v=4">
  <link rel="shortcut icon" href="../assets/images/logo-maicelo.png" type="image/png">
</head>
<body>
<div class="admin-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h1 class="topbar-title">Gestión de Menú</h1>
      </div>
      <button class="btn-admin-gold" data-bs-toggle="modal" data-bs-target="#modalPlato">
        <i class="fas fa-plus"></i> Agregar Plato
      </button>
    </div>
    <div class="admin-content">
      <div class="accordion" id="menuAccordion" style="display:flex;flex-direction:column;gap:0.75rem;">
        <div style="text-align:center;padding:2rem;">
          <div class="skeleton-block" style="margin-bottom:0.75rem;"></div>
          <div class="skeleton-block" style="margin-bottom:0.75rem;"></div>
          <div class="skeleton-block"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal agregar/editar plato -->
<div class="modal fade" id="modalPlato" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPlatoTitulo">Agregar Plato</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formPlato">
          <input type="hidden" id="platoId">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="admin-label">Categoría *</label>
              <select id="platoCategoria" class="admin-input" required>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="admin-label">Nombre *</label>
              <input type="text" id="platoNombre" class="admin-input" required>
            </div>
            <div class="col-12">
              <label class="admin-label">Descripción</label>
              <textarea id="platoDescripcion" class="admin-textarea" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="admin-label">Precio (S/) *</label>
              <input type="number" id="platoPrecio" class="admin-input" step="0.01" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="admin-label">Precio Alt (S/)</label>
              <input type="number" id="platoPrecioAlt" class="admin-input" step="0.01" min="0" placeholder="Ej: jarra">
            </div>
            <div class="col-md-4">
              <label class="admin-label">Etiqueta Alt</label>
              <input type="text" id="platoUnidadAlt" class="admin-input" placeholder="jarra, grande...">
            </div>
            <div class="col-md-3 d-flex align-items-center gap-2 pt-3">
              <label class="toggle-switch"><input type="checkbox" id="platoDisponible" checked><span class="toggle-slider"></span></label>
              <span class="admin-label mb-0">Disponible</span>
            </div>
            <div class="col-md-3 d-flex align-items-center gap-2 pt-3">
              <label class="toggle-switch"><input type="checkbox" id="platoDestacado"><span class="toggle-slider"></span></label>
              <span class="admin-label mb-0">Destacado</span>
            </div>
            <div class="col-md-3 d-flex align-items-center gap-2 pt-3">
              <label class="toggle-switch"><input type="checkbox" id="platoNuevo"><span class="toggle-slider"></span></label>
              <span class="admin-label mb-0">Nuevo</span>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-admin-gold" id="btnGuardarPlato">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-ui.js"></script>
<script>
const BASE = '<?= APP_URL ?>';

async function cargarMenu() {
  const res   = await fetch(BASE + '/api/admin/menu.php');
  const data  = await res.json();
  const platos = data.platos || [];

  // Agrupar por categoría
  const cats = {};
  platos.forEach(p => {
    if (!cats[p.categoria_id]) cats[p.categoria_id] = { nombre: p.categoria_nombre, slug: p.categoria_slug, platos: [] };
    cats[p.categoria_id].platos.push(p);
  });

  const accordion = document.getElementById('menuAccordion');
  if (!platos.length) {
    accordion.innerHTML = `<div class="empty-state"><i class="fas fa-utensils"></i><div class="empty-title">Aún no hay platos en la carta</div>Agrega el primero con el botón de arriba.</div>`;
    return;
  }
  accordion.innerHTML = '';
  Object.entries(cats).forEach(([catId, cat]) => {
    const id = 'cat' + catId;
    const filas = cat.platos.map(p => `
      <tr>
        <td>${escapeHTML(p.nombre)}</td>
        <td>S/ ${parseFloat(p.precio).toFixed(2)}</td>
        <td>
          <label class="toggle-switch">
            <input type="checkbox" ${p.es_disponible?'checked':''} onchange="togglePlato(${p.id},'disponible')">
            <span class="toggle-slider"></span>
          </label>
        </td>
        <td>
          <label class="toggle-switch">
            <input type="checkbox" ${p.es_destacado?'checked':''} onchange="togglePlato(${p.id},'destacado')">
            <span class="toggle-slider"></span>
          </label>
        </td>
        <td>
          <div class="d-flex gap-1">
            <button class="btn-admin-sm" onclick="editarPlato(${JSON.stringify(p).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i></button>
            <button class="btn-admin-danger" onclick="eliminarPlato(${p.id},'${p.nombre.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');

    accordion.innerHTML += `
      <div style="background:var(--bg-card);border:1px solid var(--border-gold);border-radius:8px;overflow:hidden;">
        <div style="padding:1rem 1.5rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
             onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
          <span style="font-family:var(--font-display);color:var(--gold);">${cat.nombre}</span>
          <span style="color:var(--text-muted);font-size:0.8rem;">${cat.platos.length} platos</span>
        </div>
        <div style="display:none;overflow-x:auto;">
          <table class="table mb-0">
            <thead><tr><th>Nombre</th><th>Precio</th><th>Disponible</th><th>Destacado</th><th>Acciones</th></tr></thead>
            <tbody>${filas}</tbody>
          </table>
        </div>
      </div>
    `;
  });
}

async function togglePlato(id, tipo) {
  await fetch(BASE + '/api/admin/menu.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, toggle: tipo }),
  });
  toast(`${tipo === 'disponible' ? 'Disponibilidad' : 'Destacado'} actualizado`, 'success', 2000);
}

function editarPlato(p) {
  document.getElementById('platoId').value           = p.id;
  document.getElementById('platoCategoria').value    = p.categoria_id;
  document.getElementById('platoNombre').value       = p.nombre;
  document.getElementById('platoDescripcion').value  = p.descripcion || '';
  document.getElementById('platoPrecio').value       = p.precio;
  document.getElementById('platoPrecioAlt').value    = p.precio_alt || '';
  document.getElementById('platoUnidadAlt').value    = p.unidad_alt || '';
  document.getElementById('platoDisponible').checked = p.es_disponible;
  document.getElementById('platoDestacado').checked  = p.es_destacado;
  document.getElementById('platoNuevo').checked      = p.es_nuevo;
  document.getElementById('modalPlatoTitulo').textContent = 'Editar Plato';
  new bootstrap.Modal(document.getElementById('modalPlato')).show();
}

async function eliminarPlato(id, nombre) {
  const ok = await confirmDialog({
    titulo: `¿Eliminar "${nombre}"?`,
    mensaje: 'Este plato desaparecerá de la carta digital al toque.',
    icono: 'fa-trash',
    textoConfirmar: 'Sí, eliminar',
  });
  if (!ok) return;
  await fetch(BASE + '/api/admin/menu.php?id=' + id, { method: 'DELETE' });
  toast(`"${nombre}" fue retirado de la carta`, 'success');
  cargarMenu();
}

document.getElementById('btnGuardarPlato')?.addEventListener('click', async () => {
  const id = document.getElementById('platoId').value;
  const payload = {
    categoria_id: document.getElementById('platoCategoria').value,
    nombre:       document.getElementById('platoNombre').value,
    descripcion:  document.getElementById('platoDescripcion').value,
    precio:       document.getElementById('platoPrecio').value,
    precio_alt:   document.getElementById('platoPrecioAlt').value || null,
    unidad_alt:   document.getElementById('platoUnidadAlt').value || null,
    es_disponible: document.getElementById('platoDisponible').checked ? 1 : 0,
    es_destacado:  document.getElementById('platoDestacado').checked  ? 1 : 0,
    es_nuevo:      document.getElementById('platoNuevo').checked       ? 1 : 0,
  };
  if (id) payload.id = id;

  const method = id ? 'PUT' : 'POST';
  const res    = await fetch(BASE + '/api/admin/menu.php', {
    method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (data.success) {
    bootstrap.Modal.getInstance(document.getElementById('modalPlato'))?.hide();
    document.getElementById('platoId').value = '';
    document.getElementById('formPlato').reset();
    document.getElementById('modalPlatoTitulo').textContent = 'Agregar Plato';
    toast(id ? 'Plato actualizado con sazón 👨‍🍳' : 'Plato agregado a la carta 🔥', 'success');
    cargarMenu();
  } else {
    toast(data.error || 'No se pudo guardar el plato', 'error');
  }
});


cargarMenu();
</script>
</body>
</html>