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
  <link rel="stylesheet" href="../assets/css/admin.css?v=4">
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
            <div><div class="stat-value" id="statHoy">–</div><div class="stat-label">Reservas Hoy</div></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div><div class="stat-value" id="statSemana">–</div><div class="stat-label">Esta Semana</div></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chair"></i></div>
            <div><div class="stat-value" id="statMesas">–</div><div class="stat-label">Mesas Libres</div></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-robot"></i></div>
            <div><div class="stat-value" id="statConv">–</div><div class="stat-label">Chat IA Hoy</div></div>
          </div>
        </div>
      </div>

      <!-- Gráfico + próximas reservas -->
      <div class="row g-3 mb-4">
        <div class="col-lg-7">
          <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-line me-2"></i>Reservas últimos 7 días</div>
            <canvas id="chartReservas" height="90"></canvas>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="admin-table-wrapper h-100">
            <div class="admin-table-header">
              <span class="admin-table-title">Próximas Reservas</span>
              <a href="reservas.php" class="btn-admin-sm">Ver todas</a>
            </div>
            <div style="overflow-x:auto;">
              <table class="table mb-0" id="tablaProximas">
                <thead><tr>
                  <th>Hora</th><th>Cliente</th><th>Pers.</th><th>Estado</th>
                </tr></thead>
                <tbody><tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem;">Cargando...</td></tr></tbody>
              </table>
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

    // Próximas reservas
    const tbody = document.querySelector('#tablaProximas tbody');
    if (data.proximas_reservas?.length) {
      const estadoClass = { pendiente:'badge-pendiente', confirmada:'badge-confirmada', cancelada:'badge-cancelada', completada:'badge-completada' };
      tbody.innerHTML = data.proximas_reservas.map(r => `
        <tr>
          <td>${r.hora?.slice(0,5)}</td>
          <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r.nombre_cliente}</td>
          <td>${r.num_personas}</td>
          <td><span class="badge-estado ${estadoClass[r.estado]||''}">${r.estado}</span></td>
        </tr>
      `).join('');
    } else {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);">Sin reservas próximas</td></tr>';
    }
  } catch(e) { console.error(e); }
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
