<?php
session_start();

// ── Verificar expiración de sesión ──────────────────────────────
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (time() > ($_SESSION['expire_at'] ?? 0)) {
        // Sesión expirada
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['login_error'] = 'Tu sesión expiró. Inicia sesión de nuevo.';
    } else {
        // Renovar tiempo en cada visita
        $_SESSION['expire_at'] = time() + 20;
    }
}

// Mensajes desde login.php
$error   = $_SESSION['login_error']   ?? '';
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_success']);

// Recordar usuario
$remembered_usuario = $_COOKIE['remember_usuario'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login con reCAPTCHA</title>
  <link rel="stylesheet" href="styles.css" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <div class="container">
    <div class="login-card">

      <div class="logo">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2a5 5 0 1 0 0 10A5 5 0 0 0 12 2z"/>
          <path d="M20 21a8 8 0 1 0-16 0"/>
        </svg>
      </div>

      <h1>Iniciar Sesión</h1>
      <p class="subtitle">Ingresa tus credenciales para continuar</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form id="loginForm" method="POST" action="login.php" novalidate>

        <!-- Token CSRF -->
        <?php
          if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
          }
        ?>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Usuario -->
        <div class="form-group">
          <label for="usuario">Usuario</label>
          <div class="input-wrapper">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2a5 5 0 1 0 0 10A5 5 0 0 0 12 2z"/>
              <path d="M20 21a8 8 0 1 0-16 0"/>
            </svg>
            <input
              type="text"
              id="usuario"
              name="usuario"
              placeholder="mi_usuario"
              autocomplete="username"
              value="<?= htmlspecialchars($remembered_usuario) ?>"
              required
            />
          </div>
          <span class="error-msg" id="usuarioError"></span>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="input-wrapper">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            />
            <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar contraseña">
              <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <span class="error-msg" id="passwordError"></span>
          <!-- Indicador de fortaleza -->
          <div class="strength-bar" id="strengthBar">
            <div class="strength-track">
              <div class="strength-fill" id="strengthFill"></div>
            </div>
            <span class="strength-label" id="strengthLabel"></span>
          </div>
          <!-- Requisitos -->
          <ul class="pwd-rules" id="pwdRules">
            <li id="r-len">Mínimo 8 caracteres</li>
            <li id="r-upper">Una mayúscula (A-Z)</li>
            <li id="r-lower">Una minúscula (a-z)</li>
            <li id="r-num">Un número (0-9)</li>
            <li id="r-sym">Un símbolo (!@#$...)</li>
          </ul>
        </div>

        <!-- Google reCAPTCHA v2 -->
        <div class="form-group">
          <div
            class="g-recaptcha"
            data-sitekey="6LfXsbosAAAAANjOugW5wx9kW5Hts-5cBEXLXcWz"
            data-callback="onCaptchaSuccess"
            data-expired-callback="onCaptchaExpired"
          ></div>
          <span class="error-msg" id="captchaError"></span>
        </div>

        <!-- Recordarme -->
        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" id="remember" name="remember"
              <?= $remembered_usuario ? 'checked' : '' ?> />
            <span class="checkmark"></span>
            Recordarme
          </label>
          <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit" id="submitBtn">
          <span class="btn-text">Iniciar Sesión</span>
          <span class="btn-loader" hidden>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin">
              <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
          </span>
        </button>

      </form>

      <p class="register-link">¿No tienes cuenta? <a href="#">Regístrate aquí</a></p>
    </div>
  </div>

  <!-- Modal de éxito -->
  <?php if ($success): ?>
  <div class="modal-overlay" id="successModal">
    <div class="modal">
      <div class="modal-icon success">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M20 6 9 17l-5-5"/>
        </svg>
      </div>
      <h2>¡Bienvenido!</h2>
      <p><?= htmlspecialchars($success) ?></p>
      <button class="btn-submit" onclick="closeModal()">Continuar</button>
    </div>
  </div>
  <?php else: ?>
  <div class="modal-overlay" id="successModal" hidden>
    <div class="modal">
      <div class="modal-icon success">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M20 6 9 17l-5-5"/>
        </svg>
      </div>
      <h2>¡Bienvenido!</h2>
      <p>Has iniciado sesión correctamente.</p>
      <button class="btn-submit" onclick="closeModal()">Continuar</button>
    </div>
  </div>
  <?php endif; ?>

  <script src="login.js"></script>
  <?php if (!empty($_SESSION['logged_in'])): ?>
  <script src="inactivity.js"></script>
  <?php endif; ?>
</body>
</html>
