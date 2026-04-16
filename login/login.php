<?php
session_start();

// ── Solo acepta POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Configuración ───────────────────────────────────────────────
define('RECAPTCHA_SECRET', 'TU_SECRET_KEY_AQUI');   // ← pega tu Secret Key aquí

// ── Usuarios registrados ────────────────────────────────────────
// Clave del array: "nombre|apellido" en minúsculas
// Contraseña: Admin@2024!
$users = [
    'admin|garcia' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    // Para agregar más: 'nombre|apellido' => password_hash('Pass', PASSWORD_BCRYPT)
];

// ── 1. Validar token CSRF ───────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    $_SESSION['login_error'] = 'Token de seguridad inválido. Recarga la página.';
    header('Location: index.php');
    exit;
}
// Regenerar token tras cada uso
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── 2. Sanitizar entradas ───────────────────────────────────────
$nombre   = trim(filter_input(INPUT_POST, 'nombre',   FILTER_SANITIZE_SPECIAL_CHARS));
$apellido = trim(filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_SPECIAL_CHARS));
$password = trim($_POST['password'] ?? '');
$remember = isset($_POST['remember']);
$captcha  = trim($_POST['g-recaptcha-response'] ?? '');

// ── 3. Validaciones básicas ─────────────────────────────────────
if (empty($nombre) || !preg_match('/^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s\'-]{2,50}$/u', $nombre)) {
    $_SESSION['login_error'] = 'Nombre no válido (solo letras, mínimo 2 caracteres).';
    header('Location: index.php');
    exit;
}

if (empty($apellido) || !preg_match('/^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s\'-]{2,50}$/u', $apellido)) {
    $_SESSION['login_error'] = 'Apellido no válido (solo letras, mínimo 2 caracteres).';
    header('Location: index.php');
    exit;
}

// Clave de búsqueda: nombre|apellido en minúsculas
$user_key = mb_strtolower($nombre) . '|' . mb_strtolower($apellido);

// Contraseña: mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número, 1 símbolo
$pwd_errors = [];
if (strlen($password) < 8)                          $pwd_errors[] = 'maximo 8 caracteres';
if (!preg_match('/[A-Z]/', $password))              $pwd_errors[] = 'una mayúscula';
if (!preg_match('/[a-z]/', $password))              $pwd_errors[] = 'una minúscula';
if (!preg_match('/[0-9]/', $password))              $pwd_errors[] = 'un número';
if (!preg_match('/[\W_]/', $password))              $pwd_errors[] = 'un símbolo (!@#$...)';

if (!empty($pwd_errors)) {
    $_SESSION['login_error'] = 'La contraseña debe tener: ' . implode(', ', $pwd_errors) . '.';
    header('Location: index.php');
    exit;
}

// ── 4. Verificar reCAPTCHA con Google ──────────────────────────
if (empty($captcha)) {
    $_SESSION['login_error'] = 'Por favor completa la verificación reCAPTCHA.';
    header('Location: index.php');
    exit;
}

$recaptcha_url  = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = http_build_query([
    'secret'   => RECAPTCHA_SECRET,
    'response' => $captcha,
    'remoteip' => $_SERVER['REMOTE_ADDR'],
]);

$context  = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $recaptcha_data,
        'timeout' => 5,
    ],
]);

$recaptcha_result = file_get_contents($recaptcha_url, false, $context);
$recaptcha_json   = json_decode($recaptcha_result, true);

if (empty($recaptcha_json['success'])) {
    $_SESSION['login_error'] = 'Verificación reCAPTCHA fallida. Inténtalo de nuevo.';
    header('Location: index.php');
    exit;
}

// ── 5. Verificar credenciales ───────────────────────────────────
if (!isset($users[$user_key]) || !password_verify($password, $users[$user_key])) {
    sleep(1);
    $_SESSION['login_error'] = 'Nombre, apellido o contraseña incorrectos.';
    header('Location: index.php');
    exit;
}

// ── 6. Login exitoso ────────────────────────────────────────────
session_regenerate_id(true);

$_SESSION['user_nombre']  = ucfirst($nombre);
$_SESSION['user_apellido'] = ucfirst($apellido);
$_SESSION['logged_in']    = true;
$_SESSION['login_time']   = time();

// Recordar nombre y apellido en cookie (30 días)
if ($remember) {
    setcookie('remember_nombre',   $nombre,   time() + (30 * 24 * 3600), '/', '', true, true);
    setcookie('remember_apellido', $apellido, time() + (30 * 24 * 3600), '/', '', true, true);
} else {
    setcookie('remember_nombre',   '', time() - 3600, '/');
    setcookie('remember_apellido', '', time() - 3600, '/');
}

$_SESSION['login_success'] = '¡Bienvenido, ' . htmlspecialchars(ucfirst($nombre) . ' ' . ucfirst($apellido)) . '!';
header('Location: index.php');
exit;
