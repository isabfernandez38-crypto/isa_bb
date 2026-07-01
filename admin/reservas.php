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
  <link rel="stylesheet" href="../assets/css/admin.css?v=9">
  <link rel="shortcut icon" href="../assets/images/logo-maicelo.png" type="image/png">
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
      <div class="admin-table-wrapper" style="border:none; box-shadow:none; background:transparent;">
        <div class="admin-table-header" style="border-radius: var(--radius-md); margin-bottom: 1rem; background: var(--bg-card); backdrop-filter: blur(12px); border: 1px solid var(--border-color);">
          <span class="admin-table-title"><i class="fas fa-layer-group me-2" style="color:var(--accent);"></i>Kanban de Reservas</span>
          <div class="admin-filters">
            <input type="date" id="filtroFecha" class="admin-search" style="min-width:140px;">
            <input type="text" id="filtroBusqueda" class="admin-search" placeholder="Buscar cliente, código...">
            <button class="btn-admin-gold" id="btnFiltrar"><i class="fas fa-search"></i></button>
            <button class="btn-admin-outline" id="btnExportarCSV"><i class="fas fa-download me-1"></i>CSV</button>
          </div>
        </div>
        
        <div class="kanban-board" id="kanbanBoard">
            <!-- Pendientes -->
            <div class="kanban-column">
                <div class="kanban-header">
                    <span class="kanban-title"><i class="fas fa-inbox" style="color:var(--info);"></i> Nuevas</span>
                    <span class="kanban-count" id="count-pendiente">0</span>
                </div>
                <div class="kanban-body" id="col-pendiente" data-estado="pendiente"></div>
            </div>

            <!-- Confirmadas -->
            <div class="kanban-column">
                <div class="kanban-header">
                    <span class="kanban-title"><i class="fas fa-check-circle" style="color:var(--success);"></i> Confirmadas</span>
                    <span class="kanban-count" id="count-confirmada">0</span>
                </div>
                <div class="kanban-body" id="col-confirmada" data-estado="confirmada"></div>
            </div>

            <!-- Canceladas / Rechazadas -->
            <div class="kanban-column">
                <div class="kanban-header">
                    <span class="kanban-title"><i class="fas fa-ban" style="color:var(--danger);"></i> Canceladas</span>
                    <span class="kanban-count" id="count-cancelada">0</span>
                </div>
                <div class="kanban-body" id="col-cancelada" data-estado="cancelada"></div>
            </div>

            <!-- Historico (Completadas) -->
            <div class="kanban-column">
                <div class="kanban-header">
                    <span class="kanban-title"><i class="fas fa-history" style="color:var(--text-muted);"></i> Historial</span>
                    <span class="kanban-count" id="count-historial">0</span>
                </div>
                <div class="kanban-body" id="col-historial" data-estado="completada"></div>
            </div>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="../assets/js/admin-ui.js"></script>
<script>
const BASE = '<?= APP_URL ?>';
let paginaActual = 1;
let datosActuales = [];
const codigosConocidos = new Set();
const estadoClass = { pendiente:'badge-pendiente',confirmada:'badge-confirmada',cancelada:'badge-cancelada',completada:'badge-completada',no_show:'badge-no_show' };

function playNotificationSound() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    
    osc.type = 'sine';
    osc.frequency.setValueAtTime(523.25, ctx.currentTime);
    osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.15);
    osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.3);
    
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.6);
    
    osc.start();
    osc.stop(ctx.currentTime + 0.6);
  } catch (e) {
    console.error('AudioContext fail:', e);
  }
}

let kanbanSortables = [];

async function cargarReservas(silencioso = false) {
  if (!silencioso) {
    document.getElementById('col-pendiente').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-info spinner-border-sm"></div></div>';
    document.getElementById('col-confirmada').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-success spinner-border-sm"></div></div>';
    document.getElementById('col-cancelada').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-danger spinner-border-sm"></div></div>';
    document.getElementById('col-historial').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-secondary spinner-border-sm"></div></div>';
  }
  
  const fecha    = document.getElementById('filtroFecha').value;
  const busqueda = document.getElementById('filtroBusqueda').value;
  // Para el kanban traemos más registros para no paginar en exceso
  const params   = new URLSearchParams({ pagina: 1, por_pagina: 100 });
  if (fecha)    params.set('fecha', fecha);
  if (busqueda) params.set('busqueda', busqueda);

  const res  = await fetch(BASE + '/api/admin/reservas.php?' + params);
  const data = await res.json();
  
  const nuevasReservas = data.data || [];
  const esPrimeraCarga = (codigosConocidos.size === 0);
  let hayNueva = false;

  nuevasReservas.forEach(r => {
    if (!codigosConocidos.has(r.codigo)) {
      codigosConocidos.add(r.codigo);
      if (!esPrimeraCarga) {
        hayNueva = true;
      }
    }
  });

  if (hayNueva && silencioso) {
    playNotificationSound();
    toast('¡Nueva reserva recibida! 📅', 'success');
  }

  datosActuales = nuevasReservas;
  renderKanban(datosActuales);
}

function createCardHTML(r) {
  return `
    <div class="kanban-card" data-id="${r.id}" onclick="if(!window.isDragging) verDetalle(${r.id})">
      <div class="k-card-header">
        <span class="k-time"><i class="far fa-clock me-1"></i>${escapeHTML(r.hora?.slice(0,5))}</span>
        <span class="k-code">${escapeHTML(r.codigo)}</span>
      </div>
      <div class="k-client">${escapeHTML(r.nombre_cliente)}</div>
      <div class="k-details">
        <span><i class="fas fa-users"></i>${escapeHTML(r.num_personas)}</span>
        <span><i class="fas fa-chair"></i>${r.mesa_numero ? escapeHTML(r.mesa_numero) : '–'}</span>
        ${r.origen === 'ia' ? '<span><i class="fas fa-robot text-info"></i>IA</span>' : ''}
      </div>
      ${r.estado === 'pendiente' ? `
      <div class="k-card-actions">
        <button class="k-confirm-btn" onclick="event.stopPropagation(); confirmarReserva(${r.id})"><i class="fas fa-check"></i> Confirmar</button>
        <button class="k-reject-btn" onclick="event.stopPropagation(); rechazarReserva(${r.id})"><i class="fas fa-times"></i> Rechazar</button>
      </div>` : ''}
    </div>
  `;
}

async function cambiarEstadoReserva(id, estado, mensajeOk) {
  try {
    const res = await fetch(BASE + '/api/admin/reservas.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id, estado: estado }),
    });
    const data = await res.json();
    if (data.success) {
      toast(mensajeOk, 'success');
      cargarReservas(true);
    } else {
      toast(data.error || 'Error al actualizar la reserva', 'error');
    }
  } catch (err) {
    toast('No se pudo conectar con el servidor', 'error');
  }
}

function confirmarReserva(id) {
  cambiarEstadoReserva(id, 'confirmada', 'Reserva <strong>confirmada</strong> ✅');
}

async function rechazarReserva(id) {
  const ok = await confirmDialog({
    titulo: 'Rechazar Reserva',
    mensaje: '¿Seguro que deseas rechazar esta reserva?',
    icono: 'fa-ban',
    textoConfirmar: 'Sí, rechazar',
    textoCancelar: 'Cancelar'
  });
  if (!ok) return;
  cambiarEstadoReserva(id, 'cancelada', 'Reserva <strong>rechazada</strong> 🚫');
}

function renderKanban(reservas) {
  const pendientes = reservas.filter(r => r.estado === 'pendiente');
  const confirmadas = reservas.filter(r => r.estado === 'confirmada');
  const canceladas = reservas.filter(r => ['cancelada', 'no_show'].includes(r.estado));
  const historial = reservas.filter(r => r.estado === 'completada');

  document.getElementById('col-pendiente').innerHTML = pendientes.map(createCardHTML).join('') || '<div class="text-center text-muted p-4" style="font-size:0.8rem">No hay nuevas reservas</div>';
  document.getElementById('col-confirmada').innerHTML = confirmadas.map(createCardHTML).join('') || '<div class="text-center text-muted p-4" style="font-size:0.8rem">No hay confirmadas</div>';
  document.getElementById('col-cancelada').innerHTML = canceladas.map(createCardHTML).join('') || '<div class="text-center text-muted p-4" style="font-size:0.8rem">No hay canceladas</div>';
  document.getElementById('col-historial').innerHTML = historial.map(createCardHTML).join('') || '<div class="text-center text-muted p-4" style="font-size:0.8rem">El historial está vacío</div>';

  document.getElementById('count-pendiente').textContent = pendientes.length;
  document.getElementById('count-confirmada').textContent = confirmadas.length;
  document.getElementById('count-cancelada').textContent = canceladas.length;
  document.getElementById('count-historial').textContent = historial.length;

  initSortable();
}

function initSortable() {
  if (kanbanSortables.length) {
    kanbanSortables.forEach(s => s.destroy());
    kanbanSortables = [];
  }

  const cols = [
    document.getElementById('col-pendiente'),
    document.getElementById('col-confirmada'),
    document.getElementById('col-cancelada'),
    document.getElementById('col-historial')
  ];

  cols.forEach(el => {
    kanbanSortables.push(new Sortable(el, {
      group: 'reservas',
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      delay: 100, // Ayuda a diferenciar click de drag
      delayOnTouchOnly: true,
      onStart: function() { window.isDragging = true; },
      onEnd: async function (evt) {
        setTimeout(() => { window.isDragging = false; }, 100);
        
        const itemEl = evt.item;
        const toCol = evt.to;
        const nuevoEstado = toCol.getAttribute('data-estado');
        const reservaId = itemEl.getAttribute('data-id');
        
        // Si no cambió de columna, no hacer nada
        if (evt.from === evt.to) return;

        // Si se mueve al historial, preguntar qué tipo de finalización es
        let estadoFinal = nuevoEstado;
        if (nuevoEstado === 'completada') {
            const opciones = await confirmDialog({
                titulo: 'Finalizar Reserva',
                mensaje: '¿Cómo terminó esta reserva?',
                icono: 'fa-flag-checkered',
                textoConfirmar: 'Completada',
                textoCancelar: 'No Show / Cancelada'
            });
            // Esto es básico, para un UX premium podríamos tener un modal de selección, pero reutilizamos el confirmDialog.
            estadoFinal = opciones ? 'completada' : 'no_show';
        }

        // Llamar a API
        const res = await fetch(BASE + '/api/admin/reservas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: reservaId, estado: estadoFinal }),
        });
        const data = await res.json();
        
        if (data.success) {
            toast(`Reserva actualizada a <strong>${estadoFinal}</strong>`, 'success');
            // Recargar para sincronizar conteos y posibles cambios de mesas
            cargarReservas(true);
        } else {
            toast(data.error || 'Error al mover la reserva', 'error');
            cargarReservas(true); // Revertir visualmente
        }
      }
    }));
  });
}

const ESTADO_LABEL = { confirmada:'Confirmada', cancelada:'Cancelada', completada:'Completada', pendiente:'Pendiente', no_show:'No Show' };

function verDetalle(id) {
  const r = datosActuales.find(x => x.id == id);
  if (!r) return;
  document.getElementById('detalleContenido').innerHTML = `
    <div class="reserva-codigo-badge"><div class="codigo">${escapeHTML(r.codigo)}</div></div>
    <div class="reserva-detail-row"><span class="key">Cliente</span><span class="val">${escapeHTML(r.nombre_cliente)}</span></div>
    <div class="reserva-detail-row"><span class="key">Teléfono</span><span class="val">${escapeHTML(r.telefono)}</span></div>
    <div class="reserva-detail-row"><span class="key">Email</span><span class="val">${escapeHTML(r.email||'–')}</span></div>
    <div class="reserva-detail-row"><span class="key">Fecha</span><span class="val">${escapeHTML(r.fecha)}</span></div>
    <div class="reserva-detail-row"><span class="key">Hora</span><span class="val">${escapeHTML(r.hora?.slice(0,5))}</span></div>
    <div class="reserva-detail-row"><span class="key">Personas</span><span class="val">${escapeHTML(r.num_personas)}</span></div>
    <div class="reserva-detail-row"><span class="key">Mesa</span><span class="val">${r.mesa_numero?'Mesa '+escapeHTML(r.mesa_numero):'Sin asignar'}</span></div>
    <div class="reserva-detail-row"><span class="key">Estado</span><span class="val"><span class="badge-estado ${estadoClass[r.estado]||''}">${escapeHTML(r.estado)}</span></span></div>
    <div class="reserva-detail-row"><span class="key">Origen</span><span class="val">${escapeHTML(r.origen)}</span></div>
    <div class="reserva-detail-row"><span class="key">WhatsApp</span><span class="val">${r.whatsapp_enviado?'✅ Enviado':'⏳ Pendiente'}</span></div>
    ${r.comentarios?`<div class="reserva-detail-row"><span class="key">Comentarios</span><span class="val">${escapeHTML(r.comentarios)}</span></div>`:''}
    <div class="reserva-detail-row"><span class="key">Creada</span><span class="val">${escapeHTML(r.created_at)}</span></div>
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
  toast('CSV descargado con todo el sabor 📋', 'success');
}

document.getElementById('btnFiltrar')?.addEventListener('click', () => cargarReservas(1));
document.getElementById('btnExportarCSV')?.addEventListener('click', exportarCSV);
document.getElementById('filtroBusqueda')?.addEventListener('keypress', e => { if (e.key==='Enter') cargarReservas(1); });

cargarReservas();

// Auto-refresh silencioso cada 10 segundos
setInterval(() => {
  cargarReservas(paginaActual, true);
}, 10000);
</script>
</body>
</html>
