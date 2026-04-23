<?php
session_start();
session_unset();
session_destroy();

// Limpiar cookie de recordar usuario
setcookie('remember_usuario', '', time() - 3600, '/');

header('Location: index.php');
exit;
