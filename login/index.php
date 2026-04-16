<?php
session_start();

// Mensajes desde login.php
$error   = $_SESSION['login_error']   ?? '';
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_success']);

// Recordar nombre
$remembered_nombre   = $_COOKIE['remember_nombre']   ?? '';
$remembered_apellido = $_COOKIE['remember_apellido'] ?? '';
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

        <!-- Nombre y Apellido -->
        <div class="form-row">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <div class="input-wrapper">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2a5 5 0 1 0 0 10A5 5 0 0 0 12 2z"/>
                <path d="M20 21a8 8 0 1 0-16 0"/>
              </svg>
              <input
                type="text"
                id="nombre"
                name="nombre"
                placeholder="Juan"
                autocomplete="given-name"
                value="<?= htmlspecialchars($remembered_nombre) ?>"
                required
              />
            </div>
            <span class="error-msg" id="nombreError"></span>
          </div>

          <div class="form-group">
            <label for="apellido">Apellido</label>
            <div class="input-wrapper">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
              <input
                type="text"
                id="apellido"
                name="apellido"
                placeholder="Pérez"
                autocomplete="family-name"
                value="<?= htmlspecialchars($remembered_apellido) ?>"
                required
              />
            </div>
            <span class="error-msg" id="apellidoError"></span>
          </div>
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
            <li id="r-len">Maximo 8 caracteres</li>
            <li id="r-upper">Una mayúscula</li>
            <li id="r-lower">Una minúscula</li>
            <li id="r-num">Un número</li>
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
              <?= $remembered_nombre ? 'checked' : '' ?> />
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
</body>
</html>
