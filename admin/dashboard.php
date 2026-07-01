<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
session_check();
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – Maicelo Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
  <link rel="shortcut icon" href="../assets/images/logo-maicelo.png" type="image/png">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-wrapper">
  <!-- Sidebar -->
  <?php include '_sidebar.php'; ?>

  <!-- Main -->
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h1 class="topbar-title">Dashboard</h1>
      </div>
      <div class="topbar-right">
        <span class="topbar-user">Bienvenido, <strong><?= $nombreUsuario ?></strong></span>
        <a href="../index.html" class="btn-admin-sm" target="_blank"><i class="fas fa-external-link-alt"></i> Ver sitio</a>
      </div>
    </div>

    <div class="admin-content">
      <!-- Stat cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div><div class="stat-value" id="statHoy"><span class="skeleton-block" style="width:40px;height:22px;margin:0;display:inline-block;"></span></div><div class="stat-label">Reservas Hoy</div></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div><div class="stat-value" id="statSemana"><span class="skeleton-block" style="width:40px;height:22px;margin:0;display:inline-block;"></span></div><div class="stat-label">Esta Semana</div></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chair"></i></div>
            <div><div class="stat-value" id="statMesas"><span class="skeleton-block" style="width:40px;height:22px;margin:0;display:inline-block;"></span></div><div class="stat-label">Mesas Libres</div></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-robot"></i></div>
            <div><div class="stat-value" id="statConv"><span class="skeleton-block" style="width:40px;height:22px;margin:0;display:inline-block;"></span></div><div class="stat-label">Chat IA Hoy</div></div>
          </div>
        </div>
      </div>

      <!-- Gráfico + Live Feed -->
      <div class="row g-4 mb-4">
        <!-- Ocupación y Gráfico -->
        <div class="col-lg-7 d-flex flex-column gap-4">
          <div class="chart-card" style="flex:1;">
            <div class="chart-title">
              <i class="fas fa-chart-line" style="color:var(--accent);"></i>
              Flujo de Reservas (7 días)
            </div>
            <div style="position:relative; height: 200px; width:100%;">
                <canvas id="chartReservas"></canvas>
            </div>
          </div>
        </div>

        <!-- Feed en Vivo -->
        <div class="col-lg-5">
          <div class="admin-table-wrapper h-100" style="background:var(--bg-card); padding:0;">
            <div class="admin-table-header" style="background:transparent;">
              <span class="admin-table-title"><i class="fas fa-bolt text-warning me-2"></i>Actividad en Vivo</span>
              <a href="reservas.php" class="btn-admin-outline" style="padding:0.4rem 0.8rem; font-size:0.75rem;">Ir al Kanban</a>
            </div>
            <div class="activity-feed" style="padding: 1.5rem; display:flex; flex-direction:column; gap:1.2rem; max-height:280px; overflow-y:auto;" id="liveFeed">
                <!-- Skeletons -->
                <div class="skeleton-row"><div class="skeleton-block" style="height:25px;margin:0;"></div></div>
                <div class="skeleton-row"><div class="skeleton-block" style="height:25px;margin:0;"></div></div>
                <div class="skeleton-row"><div class="skeleton-block" style="height:25px;margin:0;"></div></div>
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
let chart = null;

async function cargarDashboard() {
  try {
    const res  = await fetch(BASE + '/api/admin/dashboard.php');
    const data = await res.json();
    if (!data.success) return;

    document.getElementById('statHoy').textContent    = data.reservas_hoy;
    document.getElementById('statSemana').textContent  = data.reservas_semana;
    document.getElementById('statMesas').textContent   = data.mesas_disponibles;
    document.getElementById('statConv').textContent    = data.conversaciones_hoy;

    // Gráfico
    const labels = data.grafico_reservas.map(r => {
      const d = new Date(r.fecha + 'T12:00:00');
      return d.toLocaleDateString('es-PE', { weekday: 'short', day: 'numeric' });
    });
    const totales = data.grafico_reservas.map(r => r.total);

    if (chart) { chart.data.labels = labels; chart.data.datasets[0].data = totales; chart.update(); }
    else {
      chart = new Chart(document.getElementById('chartReservas'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Reservas',
            data: totales,
            borderColor: '#c9a84c',
            backgroundColor: 'rgba(201,168,76,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#c9a84c',
            pointRadius: 4,
            tension: 0.4,
            fill: true,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { color: '#9a9a8a', font: { size: 11 } }, grid: { color: 'rgba(201,168,76,0.08)' } },
            y: { ticks: { color: '#9a9a8a', font: { size: 11 }, stepSize: 1 }, grid: { color: 'rgba(201,168,76,0.08)' } },
          }
        }
      });
    }

    // Actividad en Vivo (Reemplaza las próximas reservas)
    const feedContainer = document.getElementById('liveFeed');
    if (data.proximas_reservas?.length) {
      feedContainer.innerHTML = data.proximas_reservas.map(r => {
        let icon = r.origen === 'ia' ? 'fa-robot text-info' : 'fa-user text-warning';
        let bg = r.estado === 'confirmada' ? 'rgba(52, 199, 123, 0.1)' : 'rgba(255, 255, 255, 0.03)';
        let border = r.estado === 'confirmada' ? '1px solid rgba(52, 199, 123, 0.3)' : '1px solid var(--border-color)';
        return `
          <div style="display:flex; gap:1rem; align-items:flex-start; padding: 1rem; background: ${bg}; border: ${border}; border-radius: var(--radius-sm); transition: transform 0.2s;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
              <i class="fas ${icon}"></i>
            </div>
            <div style="flex:1;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.2rem;">
                <strong style="color:var(--text-primary); font-size:0.95rem;">${r.nombre_cliente}</strong>
                <span style="font-size:0.75rem; color:var(--accent); font-weight:600;">${r.hora?.slice(0,5)}</span>
              </div>
              <div style="font-size:0.8rem; color:var(--text-secondary); display:flex; gap:1rem; align-items:center;">
                <span><i class="fas fa-users me-1 text-muted"></i>${r.num_personas} pax</span>
                <span><i class="fas fa-circle me-1" style="font-size:0.4rem; color:var(--${r.estado==='confirmada'?'success':'warning'});"></i>${r.estado}</span>
              </div>
            </div>
          </div>
        `;
      }).join('');
    } else {
      feedContainer.innerHTML = `
        <div style="text-align:center; padding: 2rem 0; color:var(--text-muted);">
          <i class="fas fa-moon mb-2" style="font-size:1.5rem; opacity:0.5;"></i>
          <div style="font-size:0.9rem;">No hay actividad reciente</div>
        </div>
      `;
    }
  } catch(e) {
    console.error(e);
    toast('No se pudo cargar el dashboard 😕', 'error');
  }
}

// Auto-refresh cada 30s
cargarDashboard();
setInterval(cargarDashboard, 30000);

// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.querySelector('.admin-sidebar')?.classList.toggle('open');
  document.getElementById('sidebarOverlay')?.classList.toggle('active');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
  document.querySelector('.admin-sidebar')?.classList.remove('open');
  document.getElementById('sidebarOverlay')?.classList.remove('active');
});
</script>
</body>
</html>
