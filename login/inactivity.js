/**
 * inactivity.js — Cierra sesión tras 20 segundos sin actividad de mouse o teclado
 */

const TIMEOUT_SEC = 20;

let timer        = null;
let remaining    = TIMEOUT_SEC;
let countdownEl  = null;
let overlayEl    = null;

// ── Crear overlay de advertencia ────────────────────────────────
function buildOverlay() {
  overlayEl = document.createElement('div');
  overlayEl.id = 'inactivity-overlay';
  overlayEl.innerHTML = `
    <div class="inact-box">
      <div class="inact-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>
      <h3>¿Sigues ahí?</h3>
      <p>Tu sesión se cerrará por inactividad en</p>
      <div class="inact-countdown" id="inactCountdown">${TIMEOUT_SEC}</div>
      <p class="inact-sub">segundos</p>
      <button id="inactStay">Continuar sesión</button>
    </div>
  `;

  // Estilos inline para no depender del CSS
  const style = document.createElement('style');
  style.textContent = `
    #inactivity-overlay {
      position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,.75);
      display: flex; align-items: center; justify-content: center;
      animation: fadeIn .3s ease;
    }
    .inact-box {
      background: #181818;
      border: 1px solid #c0392b;
      border-radius: 14px;
      padding: 2rem 2.5rem;
      text-align: center;
      max-width: 320px;
      width: 90%;
      box-shadow: 0 0 40px rgba(192,57,43,.4);
      animation: slideUp .3s ease;
    }
    .inact-icon svg {
      width: 48px; height: 48px;
      color: #c0392b;
      margin-bottom: .75rem;
    }
    .inact-box h3 {
      color: #f0f0f0; font-size: 1.3rem;
      margin-bottom: .4rem;
    }
    .inact-box p {
      color: #9a9a9a; font-size: .9rem;
      margin-bottom: .5rem;
    }
    .inact-countdown {
      font-size: 3.5rem; font-weight: 700;
      color: #c0392b;
      line-height: 1;
      margin: .25rem 0;
      transition: color .3s ease;
    }
    .inact-countdown.urgent { color: #e74c3c; }
    .inact-sub {
      color: #9a9a9a; font-size: .85rem;
      margin-bottom: 1.25rem !important;
    }
    #inactStay {
      width: 100%; padding: .75rem;
      background: #7dbb8a; color: #0d0d0d;
      border: none; border-radius: 8px;
      font-size: 1rem; font-weight: 700;
      cursor: pointer;
      transition: background .2s ease;
    }
    #inactStay:hover { background: #6aaa77; }
    @keyframes fadeIn  { from { opacity:0 } to { opacity:1 } }
    @keyframes slideUp { from { opacity:0; transform:translateY(20px) } to { opacity:1; transform:translateY(0) } }
  `;

  document.head.appendChild(style);
  document.body.appendChild(overlayEl);

  countdownEl = document.getElementById('inactCountdown');

  document.getElementById('inactStay').addEventListener('click', resetTimer);
}

// ── Mostrar overlay y cuenta regresiva ──────────────────────────
function showWarning() {
  if (!overlayEl) buildOverlay();
  overlayEl.style.display = 'flex';
  remaining = TIMEOUT_SEC;
  updateCountdown();

  const tick = setInterval(() => {
    remaining--;
    updateCountdown();
    if (remaining <= 0) {
      clearInterval(tick);
      logout();
    }
  }, 1000);

  // Guardar referencia para cancelarlo si el usuario reacciona
  overlayEl._tick = tick;
}

function updateCountdown() {
  if (!countdownEl) return;
  countdownEl.textContent = remaining;
  countdownEl.classList.toggle('urgent', remaining <= 5);
}

// ── Cerrar sesión ───────────────────────────────────────────────
function logout() {
  window.location.href = 'logout.php';
}

// ── Resetear timer al detectar actividad ───────────────────────
function resetTimer() {
  // Ocultar overlay si estaba visible
  if (overlayEl) {
    if (overlayEl._tick) clearInterval(overlayEl._tick);
    overlayEl.style.display = 'none';
  }

  clearTimeout(timer);
  timer = setTimeout(showWarning, TIMEOUT_SEC * 1000);
}

// ── Eventos de actividad ────────────────────────────────────────
['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(evt => {
  document.addEventListener(evt, resetTimer, { passive: true });
});

// ── Arrancar ────────────────────────────────────────────────────
resetTimer();
