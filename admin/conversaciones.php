<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat IA – Maicelo Admin</title>
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
        <h1 class="topbar-title">Historial Chat IA</h1>
      </div>
    </div>
    <div class="admin-content">
      <div class="row g-3">
        <div class="col-lg-5">
          <div class="admin-table-wrapper">
            <div class="admin-table-header"><span class="admin-table-title">Conversaciones</span></div>
            <div id="listaConversaciones" class="chat-session-list">
              <div style="padding:1rem;">
                <div class="skeleton-block"></div>
                <div class="skeleton-block"></div>
                <div class="skeleton-block"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="admin-table-wrapper" style="height:100%;">
            <div class="admin-table-header"><span class="admin-table-title" id="chatTitulo">Selecciona una conversación</span></div>
            <div id="chatViewer" style="padding:1rem;overflow-y:auto;max-height:550px;display:flex;flex-direction:column;gap:0.75rem;">
              <div class="empty-state">
                <i class="fas fa-robot"></i>
                <div class="empty-title">Selecciona una conversación</div>
                <div class="empty-sub">Elige un chat de la lista para ver los mensajes</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-ui.js"></script>
<script>
const BASE = '<?= APP_URL ?>';

let conversacionActivaId = null;

async function cargarConversaciones(pagina = 1) {
  const lista = document.getElementById('listaConversaciones');
  lista.innerHTML = `
    <div style="padding:1rem;">
      <div class="skeleton-block"></div>
      <div class="skeleton-block"></div>
      <div class="skeleton-block"></div>
    </div>
  `;
  try {
    const res  = await fetch(BASE + '/api/admin/conversaciones.php?pagina=' + pagina);
    const data = await res.json();
    const items = data.data || [];

    if (!items.length) {
      lista.innerHTML = '<div class="empty-state"><i class="fas fa-comment-slash"></i><div class="empty-title">Sin conversaciones</div><div class="empty-sub">Aún no hay chats con el asistente IA</div></div>';
      return;
    }

    lista.innerHTML = items.map(c => `
      <div class="chat-session-item ${c.id === conversacionActivaId ? 'active' : ''}" onclick="verConversacion(${c.id},'${escapeHTML(c.session_id.slice(0,8))}...')">
        <div class="d-flex justify-content-between mb-1">
          <span style="font-size:0.8rem;color:var(--text-primary);font-family:monospace;">${escapeHTML(c.session_id.slice(0,12))}...</span>
          <span style="font-size:0.7rem;color:var(--text-muted);">${escapeHTML(c.total_mensajes)} msgs</span>
        </div>
        <div class="d-flex justify-content-between">
          <span style="font-size:0.72rem;color:var(--text-muted);">${new Date(c.iniciada_at).toLocaleDateString('es-PE')}</span>
          ${c.reserva_generada ? '<span style="font-size:0.65rem;color:var(--success);"><i class="fas fa-circle-check"></i> Reserva</span>' : ''}
        </div>
      </div>
    `).join('');
  } catch (e) {
    console.error(e);
    toast('No se pudieron cargar las conversaciones 😕', 'error');
    lista.innerHTML = '<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><div class="empty-title">Error al cargar</div></div>';
  }
}

async function verConversacion(id, titulo) {
  conversacionActivaId = id;
  document.querySelectorAll('.chat-session-item').forEach(el => el.classList.remove('active'));
  event?.currentTarget?.classList.add('active');

  document.getElementById('chatTitulo').textContent = 'Conversación ' + titulo;
  const viewer = document.getElementById('chatViewer');
  viewer.innerHTML = '<div class="skeleton-block"></div><div class="skeleton-block"></div><div class="skeleton-block"></div>';

  try {
    const res  = await fetch(BASE + '/api/admin/conversaciones.php?id=' + id);
    const data = await res.json();
    const mensajes = data.mensajes || [];

    if (!mensajes.length) {
      viewer.innerHTML = '<div class="empty-state"><i class="fas fa-comment-dots"></i><div class="empty-title">Sin mensajes</div></div>';
      return;
    }

    viewer.innerHTML = mensajes.map(m => `
      <div class="chat-bubble-admin ${m.rol === 'user' ? 'user' : 'assistant'}">
        ${escapeHTML(m.contenido).replace(/\n/g,'<br>')}
        <span style="display:block;font-size:0.62rem;opacity:0.6;margin-top:0.3rem;text-align:${m.rol==='user'?'right':'left'};">
          ${new Date(m.created_at).toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'})}
        </span>
      </div>
    `).join('');
    viewer.scrollTop = viewer.scrollHeight;
  } catch (e) {
    console.error(e);
    toast('No se pudo cargar la conversación 😕', 'error');
  }
}

document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.querySelector('.admin-sidebar')?.classList.toggle('open');
  document.getElementById('sidebarOverlay')?.classList.toggle('active');
});

cargarConversaciones();
</script>
</body>
</html>
