<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservas – Maicelo Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css?v=4">
</head>
<body>
<div class="admin-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h1 class="topbar-title">Gestión de Reservas</h1>
      </div>
    </div>
    <div class="admin-content">
      <div class="admin-table-wrapper">
        <div class="admin-table-header">
          <span class="admin-table-title">Reservas</span>
          <div class="admin-filters">
            <input type="date" id="filtroFecha" class="admin-search" style="min-width:140px;">
            <select id="filtroEstado" class="admin-select">
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="confirmada">Confirmada</option>
              <option value="cancelada">Cancelada</option>
              <option value="completada">Completada</option>
              <option value="no_show">No Show</option>
            </select>
            <input type="text" id="filtroBusqueda" class="admin-search" placeholder="Buscar cliente, código...">
            <button class="btn-admin-gold" id="btnFiltrar"><i class="fas fa-search"></i></button>
            <button class="btn-admin-outline" id="btnExportarCSV"><i class="fas fa-download me-1"></i>CSV</button>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="table" id="tablaReservas">
            <thead><tr>
              <th>Código</th><th>Cliente</th><th>Teléfono</th>
              <th>Fecha</th><th>Hora</th><th>Pers.</th>
              <th>Mesa</th><th>Estado</th><th>Origen</th><th>Acciones</th>
            </tr></thead>
            <tbody id="tbodyReservas">
              <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted);">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center p-3">
          <span id="totalReservas" style="font-size:0.8rem;color:var(--text-muted);"></span>
          <nav><ul class="pagination pagination-sm mb-0" id="paginacion"></ul></nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detalle de Reserva</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="detalleContenido"></div>
      <div class="modal-footer"><button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= APP_URL ?>';
let paginaActual = 1;
let datosActuales = [];
const estadoClass = { pendiente:'badge-pendiente',confirmada:'badge-confirmada',cancelada:'badge-cancelada',completada:'badge-completada',no_show:'badge-no_show' };

async function cargarReservas(pagina = 1) {
  paginaActual = pagina;
  const fecha    = document.getElementById('filtroFecha').value;
  const estado   = document.getElementById('filtroEstado').value;
  const busqueda = document.getElementById('filtroBusqueda').value;
  const params   = new URLSearchParams({ pagina, por_pagina:20 });
  if (fecha)    params.set('fecha', fecha);
  if (estado)   params.set('estado', estado);
  if (busqueda) params.set('busqueda', busqueda);

  const res  = await fetch(BASE + '/api/admin/reservas.php?' + params);
  const data = await res.json();
  datosActuales = data.data || [];

  const tbody = document.getElementById('tbodyReservas');
  if (!datosActuales.length) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted);">Sin resultados</td></tr>';
    document.getElementById('totalReservas').textContent = '';
    document.getElementById('paginacion').innerHTML = '';
    return;
  }

  tbody.innerHTML = datosActuales.map(r => `
    <tr>
      <td style="font-family:monospace;font-size:0.78rem;color:var(--gold);">${r.codigo}</td>
      <td>${r.nombre_cliente}</td>
      <td>${r.telefono}</td>
      <td>${r.fecha}</td>
      <td>${r.hora?.slice(0,5)}</td>
      <td>${r.num_personas}</td>
      <td>${r.mesa_numero ? 'Mesa '+r.mesa_numero : '–'}</td>
      <td><span class="badge-estado ${estadoClass[r.estado]||''}">${r.estado}</span></td>
      <td style="font-size:0.75rem;color:var(--text-muted);">${r.origen}</td>
      <td>
        <div class="d-flex gap-1 flex-wrap">
          ${r.estado==='pendiente'?`<button class="btn-admin-sm" onclick="cambiarEstado(${r.id},'confirmada')"><i class="fas fa-check"></i></button>`:''}
          ${r.estado!=='cancelada'&&r.estado!=='completada'?`<button class="btn-admin-danger" onclick="cambiarEstado(${r.id},'cancelada')"><i class="fas fa-times"></i></button>`:''}
          ${r.estado==='confirmada'?`<button class="btn-admin-sm" onclick="cambiarEstado(${r.id},'completada')"><i class="fas fa-check-double"></i></button>`:''}
          <button class="btn-admin-sm" onclick="verDetalle(${r.id})"><i class="fas fa-eye"></i></button>
        </div>
      </td>
    </tr>
  `).join('');

  document.getElementById('totalReservas').textContent = `Total: ${data.total} reservas`;

  // Paginación
  const totalPags = Math.ceil(data.total / 20);
  let pags = '';
  for (let i = 1; i <= totalPags; i++) {
    pags += `<li class="page-item ${i===pagina?'active':''}"><a class="page-link" href="#" onclick="cargarReservas(${i});return false;">${i}</a></li>`;
  }
  document.getElementById('paginacion').innerHTML = pags;
}

async function cambiarEstado(id, estado) {
  if (!confirm(`¿Cambiar estado a "${estado}"?`)) return;
  const res = await fetch(BASE + '/api/admin/reservas.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, estado }),
  });
  const data = await res.json();
  if (data.success) cargarReservas(paginaActual);
  else alert('Error al actualizar');
}

function verDetalle(id) {
  const r = datosActuales.find(x => x.id == id);
  if (!r) return;
  document.getElementById('detalleContenido').innerHTML = `
    <div class="reserva-codigo-badge"><div class="codigo">${r.codigo}</div></div>
    <div class="reserva-detail-row"><span class="key">Cliente</span><span class="val">${r.nombre_cliente}</span></div>
    <div class="reserva-detail-row"><span class="key">Teléfono</span><span class="val">${r.telefono}</span></div>
    <div class="reserva-detail-row"><span class="key">Email</span><span class="val">${r.email||'–'}</span></div>
    <div class="reserva-detail-row"><span class="key">Fecha</span><span class="val">${r.fecha}</span></div>
    <div class="reserva-detail-row"><span class="key">Hora</span><span class="val">${r.hora?.slice(0,5)}</span></div>
    <div class="reserva-detail-row"><span class="key">Personas</span><span class="val">${r.num_personas}</span></div>
    <div class="reserva-detail-row"><span class="key">Mesa</span><span class="val">${r.mesa_numero?'Mesa '+r.mesa_numero:'Sin asignar'}</span></div>
    <div class="reserva-detail-row"><span class="key">Estado</span><span class="val"><span class="badge-estado ${estadoClass[r.estado]||''}">${r.estado}</span></span></div>
    <div class="reserva-detail-row"><span class="key">Origen</span><span class="val">${r.origen}</span></div>
    <div class="reserva-detail-row"><span class="key">WhatsApp</span><span class="val">${r.whatsapp_enviado?'✅ Enviado':'⏳ Pendiente'}</span></div>
    ${r.comentarios?`<div class="reserva-detail-row"><span class="key">Comentarios</span><span class="val">${r.comentarios}</span></div>`:''}
    <div class="reserva-detail-row"><span class="key">Creada</span><span class="val">${r.created_at}</span></div>
  `;
  new bootstrap.Modal(document.getElementById('modalDetalle')).show();
}

function exportarCSV() {
  const headers = ['Codigo','Cliente','Telefono','Fecha','Hora','Personas','Mesa','Estado','Origen'];
  const rows = datosActuales.map(r => [
    r.codigo, r.nombre_cliente, r.telefono, r.fecha,
    r.hora?.slice(0,5), r.num_personas,
    r.mesa_numero?'Mesa '+r.mesa_numero:'', r.estado, r.origen
  ].map(v => `"${v||''}"`).join(','));
  const csv  = [headers.join(','), ...rows].join('\n');
  const blob = new Blob(['﻿'+csv], { type:'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = 'reservas_maicelo.csv';
  a.click(); URL.revokeObjectURL(url);
}

document.getElementById('btnFiltrar')?.addEventListener('click', () => cargarReservas(1));
document.getElementById('btnExportarCSV')?.addEventListener('click', exportarCSV);
document.getElementById('filtroBusqueda')?.addEventListener('keypress', e => { if (e.key==='Enter') cargarReservas(1); });
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.querySelector('.admin-sidebar')?.classList.toggle('open');
  document.getElementById('sidebarOverlay')?.classList.toggle('active');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
  document.querySelector('.admin-sidebar')?.classList.remove('open');
  document.getElementById('sidebarOverlay')?.classList.remove('active');
});

cargarReservas();
</script>
</body>
</html>
