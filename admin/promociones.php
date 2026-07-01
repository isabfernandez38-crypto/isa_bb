<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Promociones – Maicelo Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
  <link rel="shortcut icon" href="../assets/images/logo-maicelo.png" type="image/png">
</head>
<body>
<div class="admin-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h1 class="topbar-title">Gestión de Promociones</h1>
      </div>
      <button class="btn-admin-gold" data-bs-toggle="modal" data-bs-target="#modalPromo">
        <i class="fas fa-plus"></i> Nueva Promoción
      </button>
    </div>
    <div class="admin-content">
      <div class="row g-3" id="promoGrid">
        <div class="col-md-6 col-lg-4"><div class="skeleton-block"></div></div>
        <div class="col-md-6 col-lg-4"><div class="skeleton-block"></div></div>
        <div class="col-md-6 col-lg-4"><div class="skeleton-block"></div></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPromo" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="modalPromoTitulo">Nueva Promoción</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="promoId">
        <div class="admin-form-group">
          <label class="admin-label">Título *</label>
          <input type="text" id="promoTitulo" class="admin-input" required>
        </div>
        <div class="admin-form-group">
          <label class="admin-label">Descripción</label>
          <textarea id="promoDescripcion" class="admin-textarea" rows="3"></textarea>
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="admin-label">Fecha Inicio *</label>
            <input type="date" id="promoInicio" class="admin-input" required>
          </div>
          <div class="col-6">
            <label class="admin-label">Fecha Fin *</label>
            <input type="date" id="promoFin" class="admin-input" required>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3">
          <label class="toggle-switch"><input type="checkbox" id="promoActiva" checked><span class="toggle-slider"></span></label>
          <span class="admin-label mb-0">Activa</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-admin-gold" id="btnGuardarPromo">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-ui.js"></script>
<script>
const BASE = '<?= APP_URL ?>';

async function cargarPromociones() {
  const res  = await fetch(BASE + '/api/admin/promociones.php');
  const data = await res.json();
  const grid = document.getElementById('promoGrid');

  if (!data.promociones?.length) {
    grid.innerHTML = `<div class="col-12"><div class="empty-state"><i class="fas fa-tags"></i><div class="empty-title">Sin promociones activas</div>Crea una nueva con el botón "+ Nueva Promoción".</div></div>`;
    return;
  }

  grid.innerHTML = data.promociones.map(p => `
    <div class="col-md-6 col-lg-4">
      <div style="background:var(--bg-card);border:1px solid ${p.activa?'var(--border-gold)':'rgba(85,85,80,0.3)'};border-radius:10px;padding:1.5rem;opacity:${p.activa?1:0.6};">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
          <h5 style="font-family:var(--font-display);color:${p.activa?'var(--gold)':'var(--text-muted)'};font-size:1rem;margin:0;">${escapeHTML(p.titulo)}</h5>
          <span class="badge-estado ${p.activa?'badge-confirmada':'badge-cancelada'}">${escapeHTML(p.activa?'Activa':'Inactiva')}</span>
        </div>
        <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:0.75rem;">${escapeHTML(p.descripcion||'')}</p>
        <p style="font-size:0.72rem;color:var(--text-muted);margin-bottom:1rem;">${escapeHTML(p.fecha_inicio)} → ${escapeHTML(p.fecha_fin)}</p>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
          <button class="btn-admin-sm" onclick="editarPromo(${JSON.stringify(p).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i> Editar</button>
          <button class="btn-admin-sm" onclick="togglePromo(${p.id})">${p.activa?'<i class="fas fa-eye-slash"></i> Desactivar':'<i class="fas fa-eye"></i> Activar'}</button>
          <button class="btn-admin-danger" onclick="eliminarPromo(${p.id},'${p.titulo.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    </div>
  `).join('');
}

function editarPromo(p) {
  document.getElementById('promoId').value          = p.id;
  document.getElementById('promoTitulo').value      = p.titulo;
  document.getElementById('promoDescripcion').value = p.descripcion || '';
  document.getElementById('promoInicio').value      = p.fecha_inicio;
  document.getElementById('promoFin').value         = p.fecha_fin;
  document.getElementById('promoActiva').checked    = !!p.activa;
  document.getElementById('modalPromoTitulo').textContent = 'Editar Promoción';
  new bootstrap.Modal(document.getElementById('modalPromo')).show();
}

async function togglePromo(id) {
  await fetch(BASE + '/api/admin/promociones.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, toggle_activa: true }),
  });
  toast('Estado de la promoción actualizado', 'success', 2000);
  cargarPromociones();
}

async function eliminarPromo(id, titulo) {
  const ok = await confirmDialog({
    titulo: `¿Eliminar "${titulo}"?`,
    mensaje: 'La promoción dejará de mostrarse a los clientes.',
    icono: 'fa-trash',
    textoConfirmar: 'Sí, eliminar',
  });
  if (!ok) return;
  await fetch(BASE + '/api/admin/promociones.php?id=' + id, { method: 'DELETE' });
  toast(`Promoción "${titulo}" eliminada`, 'success');
  cargarPromociones();
}

document.getElementById('btnGuardarPromo')?.addEventListener('click', async () => {
  const id = document.getElementById('promoId').value;
  const payload = {
    titulo:       document.getElementById('promoTitulo').value,
    descripcion:  document.getElementById('promoDescripcion').value,
    fecha_inicio: document.getElementById('promoInicio').value,
    fecha_fin:    document.getElementById('promoFin').value,
    activa:       document.getElementById('promoActiva').checked ? 1 : 0,
  };
  if (id) payload.id = id;

  const res = await fetch(BASE + '/api/admin/promociones.php', {
    method: id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (data.success) {
    bootstrap.Modal.getInstance(document.getElementById('modalPromo'))?.hide();
    document.getElementById('promoId').value = '';
    document.getElementById('modalPromoTitulo').textContent = 'Nueva Promoción';
    toast(id ? 'Promoción actualizada 🎉' : 'Promoción creada, a venderla 🔥', 'success');
    cargarPromociones();
  } else {
    toast(data.error || 'No se pudo guardar la promoción', 'error');
  }
});


cargarPromociones();
</script>
</body>
</html>
