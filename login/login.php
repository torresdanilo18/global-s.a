<?php
session_start();

// ── Solo acepta POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Configuración reCAPTCHA ─────────────────────────────────────
define('RECAPTCHA_SECRET', '6LfXsbosAAAAAE3ns2VMz5IvCW4vSyebT7MKEaJN');

// ── 1. Validar token CSRF ───────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    $_SESSION['login_error'] = 'Token de seguridad inválido. Recarga la página.';
    header('Location: index.php');
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── 2. Sanitizar entradas ───────────────────────────────────────
$usuario  = trim(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS));
$password = trim($_POST['password'] ?? '');
$remember = isset($_POST['remember']);
$captcha  = trim($_POST['g-recaptcha-response'] ?? '');

// ── 3. Validar usuario ──────────────────────────────────────────
if (empty($usuario) || !preg_match('/^[a-zA-Z0-9_.\-]{3,30}$/', $usuario)) {
    $_SESSION['login_error'] = 'Usuario no válido (letras, números, _ . - entre 3 y 30 caracteres).';
    header('Location: index.php');
    exit;
}

// ── 4. Validar contraseña (requisitos) ──────────────────────────
$pwd_errors = [];
if (strlen($password) < 8)           $pwd_errors[] = 'mínimo 8 caracteres';
if (!preg_match('/[A-Z]/', $password)) $pwd_errors[] = 'una mayúscula';
if (!preg_match('/[a-z]/', $password)) $pwd_errors[] = 'una minúscula';
if (!preg_match('/[0-9]/', $password)) $pwd_errors[] = 'un número';
if (!preg_match('/[\W_]/',  $password)) $pwd_errors[] = 'un símbolo (!@#$...)';

if (!empty($pwd_errors)) {
    $_SESSION['login_error'] = 'La contraseña debe tener: ' . implode(', ', $pwd_errors) . '.';
    header('Location: index.php');
    exit;
}

// ── 5. Verificar reCAPTCHA con Google ──────────────────────────
if (empty($captcha)) {
    $_SESSION['login_error'] = 'Por favor completa la verificación reCAPTCHA.';
    header('Location: index.php');
    exit;
}

$rc_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false,
    stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => RECAPTCHA_SECRET,
            'response' => $captcha,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ]),
        'timeout' => 5,
    ]])
);

$rc_json = json_decode($rc_response, true);

if (empty($rc_json['success'])) {
    $_SESSION['login_error'] = 'Verificación reCAPTCHA fallida. Inténtalo de nuevo.';
    header('Location: index.php');
    exit;
}

// ── 6. Login exitoso — acepta cualquier usuario/contraseña válidos ──
// (sin base de datos: solo valida formato)
session_regenerate_id(true);

$_SESSION['user_usuario'] = $usuario;
$_SESSION['logged_in']    = true;
$_SESSION['login_time']   = time();

// Token de sesión con tiempo de expiración (20 segundos de inactividad)
$_SESSION['expire_at'] = time() + 20;

if ($remember) {
    setcookie('remember_usuario', $usuario, time() + (30 * 24 * 3600), '/', '', true, true);
} else {
    setcookie('remember_usuario', '', time() - 3600, '/');
}

$_SESSION['login_success'] = '¡Bienvenido, ' . htmlspecialchars($usuario) . '!';
header('Location: index.php');
exit;
