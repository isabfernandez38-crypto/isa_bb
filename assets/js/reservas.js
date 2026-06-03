/* ============================================================
   MAICELO RESTOBAR — reservas.js
   ============================================================ */

// C3: Escape HTML para datos del servidor antes de insertar en DOM
function escapeHTML(str) {
  const d = document.createElement('div');
  d.textContent = String(str ?? '');
  return d.innerHTML;
}

let horarioRestaurante = [];
let personasCount = 2;

// ── Helpers de validación ────────────────────────────────────────────────
function validarNombre(valor) {
  return valor.trim().length >= 2 && valor.trim().length <= 150 && !/^\d+$/.test(valor);
}
function validarTelefono(valor) {
  const limpio = valor.replace(/\D/g, '');
  // Acepta 9 dígitos (empieza con 9) o con prefijo 51
  return /^9\d{8}$/.test(limpio) || /^519\d{8}$/.test(limpio);
}
function validarFecha(valor) {
  if (!valor) return false;
  const hoy     = new Date(); hoy.setHours(0,0,0,0);
  const maxDate = new Date(); maxDate.setDate(maxDate.getDate() + 60);
  const fecha   = new Date(valor + 'T00:00:00');
  return fecha >= hoy && fecha <= maxDate;
}

function setInputState(input, valid, msg = '') {
  if (!input) return;
  input.classList.toggle('is-valid',   valid);
  input.classList.toggle('is-invalid', !valid);
  const feedback = input.parentElement?.querySelector('.invalid-feedback');
  if (feedback && msg) feedback.textContent = msg;
}

// ── Generar slots de hora ────────────────────────────────────────────────
function generarSlotHora(horario) {
  const select = document.getElementById('inputHora');
  if (!select) return;
  select.innerHTML = '<option value="">Selecciona una hora</option>';

  if (!horario || horario.cerrado) {
    select.innerHTML = '<option value="">El restaurante está cerrado ese día</option>';
    return;
  }

  const [hAp, mAp] = horario.hora_apertura.split(':').map(Number);
  let cierre = horario.hora_cierre;
  const [hCi, mCi] = cierre.split(':').map(Number);
  let minutosAp = hAp * 60 + mAp;
  let minutosCi = hCi * 60 + mCi;

  // Si cierre es 00:00 = medianoche = 1440 min
  if (minutosCi === 0) minutosCi = 1440;
  // Último slot = 30 min antes del cierre
  const ultimoSlot = minutosCi - 30;

  for (let min = minutosAp; min <= ultimoSlot; min += 30) {
    const h    = Math.floor(min / 60) % 24;
    const m    = min % 60;
    const val  = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12  = h > 12 ? h - 12 : (h === 0 ? 12 : h);
    const label = `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
    const opt  = document.createElement('option');
    opt.value  = val;
    opt.textContent = label;
    select.appendChild(opt);
  }
}

// ── Inicialización ────────────────────────────────────────────────────────
async function initReservas() {
  // Cargar horarios
  try {
    const res  = await fetch('/PRACTICAS/api/horarios.php');
    const data = await res.json();
    if (data.success) {
      horarioRestaurante = data.horarios;
    }
  } catch (e) {
    console.error('Error cargando horarios para reservas:', e);
  }

  // Configurar fecha mínima y máxima
  const inputFecha = document.getElementById('inputFecha');
  if (inputFecha) {
    const hoy = new Date();
    const max = new Date(); max.setDate(max.getDate() + 60);
    inputFecha.min = hoy.toISOString().split('T')[0];
    inputFecha.max = max.toISOString().split('T')[0];

    inputFecha.addEventListener('change', () => {
      const fecha = inputFecha.value;
      if (!fecha) return;
      // Determinar día de la semana
      const diasMap = {0:'domingo',1:'lunes',2:'martes',3:'miercoles',4:'jueves',5:'viernes',6:'sabado'};
      const diaSemana = diasMap[new Date(fecha + 'T12:00:00').getDay()];
      const horario   = horarioRestaurante.find(h => h.dia === diaSemana);
      generarSlotHora(horario);
      setInputState(inputFecha, validarFecha(fecha));
    });
  }

  // Contador de personas
  const btnMenos = document.getElementById('personasMenos');
  const btnMas   = document.getElementById('personasMas');
  const display  = document.getElementById('personasDisplay');
  const hidden   = document.getElementById('inputPersonas');

  function actualizarPersonas() {
    if (display) display.textContent = personasCount;
    if (hidden)  hidden.value        = personasCount;
    if (btnMenos) btnMenos.disabled  = personasCount <= 1;
    if (btnMas)   btnMas.disabled    = personasCount >= 8;
  }

  btnMenos?.addEventListener('click', () => { if (personasCount > 1) { personasCount--; actualizarPersonas(); } });
  btnMas?.addEventListener('click',   () => { if (personasCount < 8) { personasCount++; actualizarPersonas(); } });
  actualizarPersonas();

  // Validación en tiempo real
  document.getElementById('inputNombre')?.addEventListener('blur', e => {
    setInputState(e.target, validarNombre(e.target.value), 'Mínimo 2 caracteres, sin números.');
  });
  document.getElementById('inputTelefono')?.addEventListener('blur', e => {
    setInputState(e.target, validarTelefono(e.target.value), '9 dígitos, empieza con 9.');
  });
  document.getElementById('inputHora')?.addEventListener('change', e => {
    setInputState(e.target, !!e.target.value);
  });

  // Envío del formulario
  document.getElementById('reservaForm')?.addEventListener('submit', enviarReserva);
}

// ── Enviar reserva ────────────────────────────────────────────────────────
async function enviarReserva(e) {
  e.preventDefault();

  const nombre   = document.getElementById('inputNombre');
  const telefono = document.getElementById('inputTelefono');
  const fecha    = document.getElementById('inputFecha');
  const hora     = document.getElementById('inputHora');
  const personas = document.getElementById('inputPersonas');
  const terminos = document.getElementById('chkTerminos');
  const btn      = document.getElementById('btnReservar');

  // Validar todos los campos
  let esValido = true;

  if (!validarNombre(nombre?.value || '')) {
    setInputState(nombre, false, 'Mínimo 2 caracteres.');
    esValido = false;
  } else {
    setInputState(nombre, true);
  }

  if (!validarTelefono(telefono?.value || '')) {
    setInputState(telefono, false, '9 dígitos, empieza con 9.');
    esValido = false;
  } else {
    setInputState(telefono, true);
  }

  if (!validarFecha(fecha?.value || '')) {
    setInputState(fecha, false, 'Fecha inválida.');
    esValido = false;
  } else {
    setInputState(fecha, true);
  }

  if (!hora?.value) {
    setInputState(hora, false, 'Selecciona una hora.');
    esValido = false;
  } else {
    setInputState(hora, true);
  }

  if (!terminos?.checked) {
    showToast('Debes aceptar las políticas de reserva.', 'warning');
    esValido = false;
  }

  if (!esValido) return;

  setButtonLoading(btn, true);

  const payload = {
    nombre_cliente: nombre.value.trim(),
    telefono:       telefono.value.replace(/\D/g, ''),
    email:          document.getElementById('inputEmail')?.value.trim() || null,
    fecha:          fecha.value,
    hora:           hora.value,
    num_personas:   parseInt(personas.value),
    comentarios:    document.getElementById('inputComentarios')?.value.trim() || null,
    origen:         'web',
  };

  try {
    // Obtener token CSRF fresco si no lo tenemos
    if (!window.csrf?.token) await window.csrf?.getToken();

    const res = await fetch('/PRACTICAS/api/reservas.php', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(window.csrf?.getHeader() || {}),
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (res.ok && data.success) {
      // Mostrar modal de confirmación
      document.getElementById('reservaCodigo').textContent = data.codigo;

      const r = data.reserva;
      const fechaFormatted = new Date(r.fecha + 'T12:00:00').toLocaleDateString('es-PE', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
      });
      const horaStr = r.hora.slice(0, 5);

      // C3: Escapar datos del servidor antes de insertar en innerHTML
      document.getElementById('reservaDetalles').innerHTML = `
        <div class="reserva-detail-row"><span class="key">Nombre</span><span class="val">${escapeHTML(r.nombre_cliente)}</span></div>
        <div class="reserva-detail-row"><span class="key">Fecha</span><span class="val">${escapeHTML(fechaFormatted)}</span></div>
        <div class="reserva-detail-row"><span class="key">Hora</span><span class="val">${escapeHTML(horaStr)}</span></div>
        <div class="reserva-detail-row"><span class="key">Personas</span><span class="val">${escapeHTML(r.num_personas)}</span></div>
        ${r.mesa_numero ? `<div class="reserva-detail-row"><span class="key">Mesa</span><span class="val">Mesa ${escapeHTML(r.mesa_numero)}</span></div>` : ''}
      `;

      new bootstrap.Modal(document.getElementById('modalConfirmacion')).show();
      e.target.reset();
      personasCount = 2;
      document.getElementById('personasDisplay').textContent = '2';
      document.getElementById('inputPersonas').value = '2';
      document.getElementById('inputHora').innerHTML = '<option value="">Selecciona la fecha primero</option>';

      // Renovar token CSRF
      await window.csrf?.getToken();

    } else {
      showToast(data.error || 'Error al crear la reserva.', 'error');
      // Si el token expiró, obtener uno nuevo
      if (res.status === 403) await window.csrf?.getToken();
    }

  } catch (err) {
    console.error('Error:', err);
    showToast('Error de conexión. Por favor intenta de nuevo.', 'error');
  } finally {
    setButtonLoading(btn, false);
  }
}

document.addEventListener('DOMContentLoaded', initReservas);
