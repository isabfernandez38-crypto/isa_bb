<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$links = [
  'dashboard'     => ['icon'=>'fa-tachometer-alt', 'label'=>'Dashboard'],
  'reservas'      => ['icon'=>'fa-calendar-check', 'label'=>'Reservas'],
  'menu'          => ['icon'=>'fa-utensils',        'label'=>'Menú'],
  'mesas'         => ['icon'=>'fa-chair',           'label'=>'Mesas'],
  'conversaciones'=> ['icon'=>'fa-robot',           'label'=>'Chat IA'],
  'promociones'   => ['icon'=>'fa-tags',            'label'=>'Promociones'],
];
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <img src="../assets/images/logo-maicelo.png" alt="Maicelo Logo" style="max-width: 100%; max-height: 65px; object-fit: contain; margin-bottom: 0.5rem; filter: drop-shadow(0 0 12px rgba(240,75,15,0.5));">
    <div class="sidebar-brand-sub">Administración</div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-nav-label">Menú Principal</div>
    <?php foreach ($links as $page => $info): ?>
    <a href="<?= $page ?>.php" class="<?= $currentPage === $page ? 'active' : '' ?>">
      <i class="fas <?= $info['icon'] ?>"></i>
      <?= $info['label'] ?>
    </a>
    <?php endforeach; ?>
    <div class="sidebar-nav-label" style="margin-top:1rem;">Sistema</div>
    <a href="../index.html" target="_blank">
      <i class="fas fa-external-link-alt"></i>
      Ver Sitio
    </a>
  </nav>
  <div class="sidebar-footer">
    <button class="btn-logout" id="btnLogout">
      <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </button>
  </div>
</aside>
<script>
document.getElementById('btnLogout')?.addEventListener('click', async () => {
  const ok = await (window.confirmDialog ? confirmDialog({
    titulo: '¿Cerrar sesión?',
    mensaje: 'Tendrás que volver a ingresar tus credenciales para acceder al panel.',
    icono: 'fa-sign-out-alt',
    textoConfirmar: 'Sí, salir',
    peligroso: true,
  }) : Promise.resolve(true));
  if (!ok) return;
  await fetch('<?= APP_URL ?>/api/admin/auth.php?action=logout', { method: 'POST' });
  window.location.href = '<?= APP_URL ?>/admin/login.php';
});
</script>
