<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesas – Maicelo Admin</title>
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
        <h1 class="topbar-title">Gestión de Mesas</h1>
      </div>
    </div>
    <div class="admin-content">
      <div style="margin-bottom:1rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
        <span style="font-size:0.78rem;color:var(--text-muted);">Haz clic en una mesa para cambiar su estado</span>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
          <span style="font-size:0.75rem;"><span style="color:#4caf50;">●</span> Disponible</span>
          <span style="font-size:0.75rem;"><span style="color:#ff6b6b;">●</span> Ocupada</span>
          <span style="font-size:0.75rem;"><span style="color:#f7b731;">●</span> Reservada</span>
          <span style="font-size:0.75rem;"><span style="color:var(--text-muted);">●</span> Mantenimiento</span>
        </div>
      </div>
      <div class="restaurant-floor-plan" id="mesasGrid">
        <div class="skeleton-block"></div><div class="skeleton-block"></div>
        <div class="skeleton-block"></div><div class="skeleton-block"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal cambiar estado -->
<div class="modal fade" id="modalMesa" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="modalMesaTitulo">Mesa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="admin-label">Cambiar estado</label>
        <select id="selectEstadoMesa" class="admin-input">
          <option value="disponible">Disponible</option>
          <option value="ocupada">Ocupada</option>
          <option value="reservada">Reservada</option>
          <option value="mantenimiento">Mantenimiento</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-admin-gold" id="btnActualizarMesa">Actualizar</button>
      </div>
    </div>
  </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-ui.js"></script>
<script>
const BASE = '<?= APP_URL ?>';
let mesaSeleccionada = null;

const ZONES_ORDER = {
  'bar': { title: '🍸 Bar', order: 1 },
  'vip': { title: '👑 Zona VIP', order: 2 },
  'interior': { title: '🍽️ Comedor Interior', order: 3 },
  'exterior': { title: '🌴 Terraza / Exterior', order: 4 }
};

async function cargarMesas() {
  const res   = await fetch(BASE + '/api/admin/mesas.php');
  const data  = await res.json();
  const grid  = document.getElementById('mesasGrid');
  
  if (!data.mesas || data.mesas.length === 0) {
      grid.innerHTML = '<p style="color:var(--text-muted)">No hay mesas configuradas.</p>';
      return;
  }

  // Agrupar por zonas
  const zonas = {};
  data.mesas.forEach(m => {
      const z = m.zona || 'interior';
      if (!zonas[z]) zonas[z] = [];
      zonas[z].push(m);
  });

  // Ordenar y construir HTML
  let html = '';
  const sortedZones = Object.keys(zonas).sort((a, b) => {
      const orderA = ZONES_ORDER[a]?.order || 99;
      const orderB = ZONES_ORDER[b]?.order || 99;
      return orderA - orderB;
  });

  sortedZones.forEach(z => {
      const title = ZONES_ORDER[z]?.title || z.toUpperCase();
      
      let mesasHtml = zonas[z].map(m => `
        <div class="mesa-card ${m.estado}" data-cap="${m.capacidad}" onclick="abrirModalMesa(${m.id}, ${m.numero}, '${m.estado}')">
          <div class="mesa-numero">${m.numero}</div>
          <div class="mesa-cap">${m.capacidad} pax</div>
          <div class="mesa-estado-dot"></div>
        </div>
      `).join('');

      html += `
        <div class="zone-container">
            <div class="zone-header">${title}</div>
            <div class="zone-grid">${mesasHtml}</div>
        </div>
      `;
  });

  grid.innerHTML = html;
}

function abrirModalMesa(id, numero, estado) {
  mesaSeleccionada = id;
  document.getElementById('modalMesaTitulo').textContent = `Mesa ${numero}`;
  document.getElementById('selectEstadoMesa').value = estado;
  new bootstrap.Modal(document.getElementById('modalMesa')).show();
}

document.getElementById('btnActualizarMesa')?.addEventListener('click', async () => {
  const estado = document.getElementById('selectEstadoMesa').value;
  await fetch(BASE + '/api/admin/mesas.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: mesaSeleccionada, estado }),
  });
  bootstrap.Modal.getInstance(document.getElementById('modalMesa'))?.hide();
  cargarMesas();
});


cargarMesas();
</script>
</body>
</html>
