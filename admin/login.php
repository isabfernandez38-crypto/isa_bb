<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – Maicelo Restobar</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css?v=4">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-name">MAICELO</div>
      <div class="login-logo-sub">RESTOBAR — ADMINISTRACIÓN</div>
    </div>
    <p class="login-title">Acceso al Panel</p>

    <div class="login-error" id="loginError"></div>

    <form id="loginForm">
      <div class="admin-form-group">
        <label class="admin-label">Correo electrónico</label>
        <input type="email" id="loginEmail" class="admin-input" placeholder="admin@maicelorestbar.com" required autocomplete="email">
      </div>
      <div class="admin-form-group">
        <label class="admin-label">Contraseña</label>
        <div class="password-wrapper">
          <input type="password" id="loginPass" class="admin-input" placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="password-toggle" id="togglePass">
            <i class="fas fa-eye" id="toggleIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-login mt-3" id="btnLogin">
        <i class="fas fa-sign-in-alt"></i> INGRESAR
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;font-size:0.75rem;color:var(--text-muted);">
      <a href="../index.html" style="color:var(--gold);">← Volver al sitio</a>
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= APP_URL ?>';

document.getElementById('togglePass')?.addEventListener('click', () => {
  const pass = document.getElementById('loginPass');
  const icon = document.getElementById('toggleIcon');
  const isPassword = pass.type === 'password';
  pass.type = isPassword ? 'text' : 'password';
  icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
});

document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn   = document.getElementById('btnLogin');
  const error = document.getElementById('loginError');
  const email = document.getElementById('loginEmail').value;
  const pass  = document.getElementById('loginPass').value;

  error.style.display = 'none';
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-gold"></span> Verificando...';

  try {
    const res  = await fetch(BASE + '/api/admin/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ email, password: pass }),
    });
    const data = await res.json();

    if (res.ok && data.success) {
      window.location.href = BASE + '/admin/dashboard.php';
    } else {
      error.textContent    = data.error || 'Credenciales incorrectas';
      error.style.display  = 'block';
    }
  } catch {
    error.textContent   = 'Error de conexión';
    error.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> INGRESAR';
  }
});
</script>
</body>
</html>
