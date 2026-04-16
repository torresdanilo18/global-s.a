/**
 * login.js — Validación del lado del cliente
 * El envío real lo procesa login.php en el servidor.
 */

// ── Estado reCAPTCHA ────────────────────────────────────────────
let captchaToken = null;

function onCaptchaSuccess(token) {
  captchaToken = token;
  document.getElementById('captchaError').textContent = '';
}

function onCaptchaExpired() {
  captchaToken = null;
}

// ── DOM ─────────────────────────────────────────────────────────
const form          = document.getElementById('loginForm');
const nombreInput   = document.getElementById('nombre');
const apellidoInput = document.getElementById('apellido');
const passwordInput = document.getElementById('password');
const toggleBtn     = document.getElementById('togglePassword');
const eyeIcon       = document.getElementById('eyeIcon');
const submitBtn     = document.getElementById('submitBtn');
const btnText       = submitBtn.querySelector('.btn-text');
const btnLoader     = submitBtn.querySelector('.btn-loader');

// ── Toggle contraseña ───────────────────────────────────────────
toggleBtn.addEventListener('click', () => {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';

  eyeIcon.innerHTML = isPassword
    ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
       <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
       <line x1="1" y1="1" x2="23" y2="23"/>`
    : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
       <circle cx="12" cy="12" r="3"/>`;

  toggleBtn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
});

// ── Helpers de validación ───────────────────────────────────────
function setError(input, errorEl, msg) {
  input.classList.add('invalid');
  input.classList.remove('valid');
  errorEl.textContent = msg;
}

function setValid(input, errorEl) {
  input.classList.remove('invalid');
  input.classList.add('valid');
  errorEl.textContent = '';
}

function clearState(input, errorEl) {
  input.classList.remove('invalid', 'valid');
  errorEl.textContent = '';
}

function validateNombre() {
  const errorEl = document.getElementById('nombreError');
  const value   = nombreInput.value.trim();
  if (!value) { setError(nombreInput, errorEl, 'El nombre es obligatorio.'); return false; }
  if (value.length < 2) { setError(nombreInput, errorEl, 'Mínimo 2 caracteres.'); return false; }
  if (!/^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s'\-]+$/u.test(value)) {
    setError(nombreInput, errorEl, 'Solo se permiten letras.');
    return false;
  }
  setValid(nombreInput, errorEl);
  return true;
}

function validateApellido() {
  const errorEl = document.getElementById('apellidoError');
  const value   = apellidoInput.value.trim();
  if (!value) { setError(apellidoInput, errorEl, 'El apellido es obligatorio.'); return false; }
  if (value.length < 2) { setError(apellidoInput, errorEl, 'Mínimo 2 caracteres.'); return false; }
  if (!/^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s'\-]+$/u.test(value)) {
    setError(apellidoInput, errorEl, 'Solo se permiten letras.');
    return false;
  }
  setValid(apellidoInput, errorEl);
  return true;
}

function validatePassword() {
  const errorEl = document.getElementById('passwordError');
  const value   = passwordInput.value;
  if (!value) { setError(passwordInput, errorEl, 'La contraseña es obligatoria.'); return false; }

  const rules = [
    { test: v => v.length >= 8,        msg: 'mínimo 8 caracteres' },
    { test: v => /[A-Z]/.test(v),      msg: 'una mayúscula' },
    { test: v => /[a-z]/.test(v),      msg: 'una minúscula' },
    { test: v => /[0-9]/.test(v),      msg: 'un número' },
    { test: v => /[\W_]/.test(v),      msg: 'un símbolo (!@#$...)' },
  ];

  const failed = rules.filter(r => !r.test(value)).map(r => r.msg);
  if (failed.length) {
    setError(passwordInput, errorEl, 'Falta: ' + failed.join(', ') + '.');
    return false;
  }
  setValid(passwordInput, errorEl);
  return true;
}

function validateCaptcha() {
  const errorEl = document.getElementById('captchaError');
  if (!captchaToken) {
    errorEl.textContent = 'Por favor completa la verificación reCAPTCHA.';
    return false;
  }
  errorEl.textContent = '';
  return true;
}

// ── Indicador de fortaleza en tiempo real ──────────────────────
const strengthBar   = document.getElementById('strengthBar');
const strengthFill  = document.getElementById('strengthFill');
const strengthLabel = document.getElementById('strengthLabel');

const RULES = [
  { id: 'r-len',   test: v => v.length >= 8        },
  { id: 'r-upper', test: v => /[A-Z]/.test(v)      },
  { id: 'r-lower', test: v => /[a-z]/.test(v)      },
  { id: 'r-num',   test: v => /[0-9]/.test(v)      },
  { id: 'r-sym',   test: v => /[\W_]/.test(v)      },
];

const LEVELS = [
  { cls: 's1', label: 'Muy débil'  },
  { cls: 's2', label: 'Débil'      },
  { cls: 's3', label: 'Regular'    },
  { cls: 's4', label: 'Fuerte'     },
  { cls: 's5', label: 'Muy fuerte' },
];

passwordInput.addEventListener('input', () => {
  const v     = passwordInput.value;
  const score = RULES.filter(r => r.test(v)).length;

  // Actualizar cada requisito
  RULES.forEach(r => {
    const li = document.getElementById(r.id);
    if (li) li.classList.toggle('ok', r.test(v));
  });

  // Mostrar / ocultar barra
  if (v.length === 0) {
    strengthBar.classList.remove('visible');
    strengthFill.className = 'strength-fill';
    strengthLabel.className = 'strength-label';
    strengthLabel.textContent = '';
    return;
  }

  strengthBar.classList.add('visible');
  const lvl = LEVELS[score - 1] || LEVELS[0];
  strengthFill.className  = `strength-fill ${lvl.cls}`;
  strengthLabel.className = `strength-label ${lvl.cls}`;
  strengthLabel.textContent = lvl.label;
});

// ── Validación en tiempo real ───────────────────────────────────
nombreInput.addEventListener('blur',   () => { if (nombreInput.value)   validateNombre(); });
apellidoInput.addEventListener('blur', () => { if (apellidoInput.value) validateApellido(); });
passwordInput.addEventListener('blur', () => { if (passwordInput.value) validatePassword(); });

[nombreInput, apellidoInput, passwordInput].forEach(input => {
  input.addEventListener('input', () => {
    const el = document.getElementById(input.id + 'Error');
    if (el && input.classList.contains('invalid')) clearState(input, el);
  });
});

// ── Envío: validar antes de dejar que PHP procese ───────────────
form.addEventListener('submit', (e) => {
  const okNombre   = validateNombre();
  const okApellido = validateApellido();
  const okPassword = validatePassword();
  const okCaptcha  = validateCaptcha();

  if (!okNombre || !okApellido || !okPassword || !okCaptcha) {
    e.preventDefault();
    return;
  }
  setLoading(true);
});

function setLoading(on) {
  submitBtn.disabled = on;
  btnText.hidden     = on;
  btnLoader.hidden   = !on;
}

// ── Modal ────────────────────────────────────────────────────────
function closeModal() {
  const modal = document.getElementById('successModal');
  if (modal) {
    modal.hidden = true;
    document.body.style.overflow = '';
  }
}

// Abrir modal si viene del servidor
window.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('successModal');
  if (modal && !modal.hidden) {
    document.body.style.overflow = 'hidden';
  }
});

document.getElementById('successModal')?.addEventListener('click', (e) => {
  if (e.target === e.currentTarget) closeModal();
});

document.addEventListener('keydown', (e) => {
  const modal = document.getElementById('successModal');
  if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
});
