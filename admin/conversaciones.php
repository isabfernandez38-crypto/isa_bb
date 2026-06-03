<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat IA – Maicelo Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css">
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
            <div id="listaConversaciones" style="overflow-y:auto;max-height:600px;">
              <div style="text-align:center;padding:2rem;color:var(--text-muted);">Cargando...</div>
            </div>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="admin-table-wrapper" style="height:100%;">
            <div class="admin-table-header"><span class="admin-table-title" id="chatTitulo">Selecciona una conversación</span></div>
            <div id="chatViewer" style="padding:1rem;overflow-y:auto;max-height:550px;display:flex;flex-direction:column;gap:0.75rem;">
              <div style="text-align:center;color:var(--text-muted);padding:3rem;">
                <i class="fas fa-robot" style="font-size:2rem;margin-bottom:0.75rem;display:block;opacity:0.3;"></i>
                Selecciona una conversación para ver los mensajes
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
<script>
const BASE = '<?= APP_URL ?>';

async function cargarConversaciones(pagina = 1) {
  const res  = await fetch(BASE + '/api/admin/conversaciones.php?pagina=' + pagina);
  const data = await res.json();
  const lista = document.getElementById('listaConversaciones');

  lista.innerHTML = (data.data || []).map(c => `
    <div onclick="verConversacion(${c.id},'${c.session_id.slice(0,8)}...')"
         style="padding:0.9rem 1.25rem;border-bottom:1px solid var(--border-gold);cursor:pointer;transition:background 0.2s;"
         onmouseover="this.style.background='rgba(201,168,76,0.05)'"
         onmouseout="this.style.background='transparent'">
      <div style="display:flex;justify-content:space-between;margin-bottom:0.2rem;">
        <span style="font-size:0.8rem;color:var(--text-primary);font-family:monospace;">${c.session_id.slice(0,12)}...</span>
        <span style="font-size:0.7rem;color:var(--text-muted);">${c.total_mensajes} msgs</span>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span style="font-size:0.72rem;color:var(--text-muted);">${new Date(c.iniciada_at).toLocaleDateString('es-PE')}</span>
        ${c.reserva_generada ? '<span style="font-size:0.65rem;color:#4caf50;">✅ Reserva</span>' : ''}
      </div>
    </div>
  `).join('') || '<div style="text-align:center;padding:2rem;color:var(--text-muted);">Sin conversaciones</div>';
}

async function verConversacion(id, titulo) {
  document.getElementById('chatTitulo').textContent = 'Conversación ' + titulo;
  const res  = await fetch(BASE + '/api/admin/conversaciones.php?id=' + id);
  const data = await res.json();
  const viewer = document.getElementById('chatViewer');

  viewer.innerHTML = (data.mensajes || []).map(m => `
    <div style="max-width:80%;padding:0.65rem 0.9rem;border-radius:12px;font-size:0.82rem;line-height:1.5;
                ${m.rol==='user'
                  ? 'background:var(--gold);color:#000;margin-left:auto;border-radius:12px 12px 4px 12px;'
                  : 'background:#2a2a2a;color:var(--text-primary);border:1px solid rgba(201,168,76,0.1);'}">
      ${m.contenido.replace(/\n/g,'<br>')}
      <span style="display:block;font-size:0.62rem;opacity:0.6;margin-top:0.3rem;text-align:${m.rol==='user'?'right':'left'};">
        ${new Date(m.created_at).toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'})}
      </span>
    </div>
  `).join('');
  viewer.scrollTop = viewer.scrollHeight;
}

document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.querySelector('.admin-sidebar')?.classList.toggle('open');
  document.getElementById('sidebarOverlay')?.classList.toggle('active');
});

cargarConversaciones();
</script>
</body>
</html>
