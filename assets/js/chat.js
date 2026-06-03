/* ============================================================
   MAICELO RESTOBAR — chat.js
   ============================================================ */

// ── UUID v4 ───────────────────────────────────────────────────────────────
function generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random() * 16 | 0;
    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
  });
}

const sessionId = sessionStorage.getItem('chat_session_id') || generateUUID();
sessionStorage.setItem('chat_session_id', sessionId);

let chatAbierto    = false;
let bienvenidaVisto = false;
let enviando       = false;

// ── Inyectar HTML del chat ────────────────────────────────────────────────
function inyectarChatHTML() {
  const html = `
    <div id="chatBubble" class="chat-bubble" role="button" aria-label="Abrir chat">
      <div class="chat-unread-badge" id="chatBadge">1</div>
      <i class="fas fa-robot"></i>
    </div>

    <div id="chatPanel" class="chat-panel" style="display:none;" role="dialog" aria-label="Chat Maicelo IA">
      <div class="chat-header">
        <div class="chat-header-info">
          <div class="chat-avatar">🍽️</div>
          <div>
            <div class="chat-name">Maicelo IA</div>
            <div class="chat-status"><span class="status-dot"></span> En línea</div>
          </div>
        </div>
        <button class="chat-close" id="chatClose" aria-label="Cerrar chat">✕</button>
      </div>

      <div class="chat-messages" id="chatMessages"></div>

      <div class="chat-suggestions" id="chatSuggestions">
        <button class="suggestion-chip">📋 Ver Menú</button>
        <button class="suggestion-chip">📅 Hacer Reserva</button>
        <button class="suggestion-chip">⏰ Horarios</button>
        <button class="suggestion-chip">📍 Ubicación</button>
        <button class="suggestion-chip">🍹 Tragos</button>
      </div>

      <div class="chat-input-area">
        <textarea id="chatInput" placeholder="Escribe tu mensaje..." rows="1" maxlength="1000" aria-label="Mensaje"></textarea>
        <button id="chatSend" class="chat-send-btn" aria-label="Enviar">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', html);
}

// ── Abrir / cerrar chat ───────────────────────────────────────────────────
function toggleChat() {
  const panel  = document.getElementById('chatPanel');
  const bubble = document.getElementById('chatBubble');
  const badge  = document.getElementById('chatBadge');

  chatAbierto = !chatAbierto;
  panel.style.display = chatAbierto ? 'flex' : 'none';

  if (chatAbierto) {
    badge.style.display = 'none';
    if (!bienvenidaVisto) {
      bienvenidaVisto = true;
      setTimeout(() => {
        addMessage(
          '¡Hola! 👋 Soy Maicelo IA, tu asistente virtual.\n\n' +
          'Puedo ayudarte a:\n🍽️ Hacer o consultar reservas\n' +
          '📋 Conocer nuestra carta\n⏰ Informarte sobre horarios\n\n' +
          '¿En qué puedo ayudarte hoy?',
          'assistant'
        );
      }, 300);
    }
    // Enfocar input
    setTimeout(() => document.getElementById('chatInput')?.focus(), 100);
  }
}

// ── Agregar mensaje ───────────────────────────────────────────────────────
function addMessage(text, role, timestamp = null) {
  const container = document.getElementById('chatMessages');
  if (!container) return;

  const ts   = timestamp || new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
  const div  = document.createElement('div');
  div.className = `chat-msg ${role}`;

  // Convertir saltos de línea en <br>
  const html = text
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/\n/g,'<br>')
    .replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g,'<em>$1</em>');

  div.innerHTML = `${html}<span class="chat-msg-time">${ts}</span>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

// ── Typing indicator ──────────────────────────────────────────────────────
function showTypingIndicator() {
  const container = document.getElementById('chatMessages');
  if (!container) return null;
  const el = document.createElement('div');
  el.className = 'typing-indicator';
  el.id        = 'typingIndicator';
  el.innerHTML = '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
  container.appendChild(el);
  container.scrollTop = container.scrollHeight;
  return el;
}

function removeTypingIndicator(el) {
  if (el && el.parentElement) el.remove();
  document.getElementById('typingIndicator')?.remove();
}

// ── Enviar mensaje ────────────────────────────────────────────────────────
async function sendMessage(text) {
  text = text.trim();
  if (!text || enviando) return;

  const input   = document.getElementById('chatInput');
  const sendBtn = document.getElementById('chatSend');
  const sugs    = document.getElementById('chatSuggestions');

  enviando = true;
  if (input)   { input.value = ''; input.style.height = 'auto'; }
  if (sendBtn)  sendBtn.disabled = true;
  if (sugs)     sugs.style.display = 'none';

  addMessage(text, 'user');

  const typing = showTypingIndicator();

  try {
    const res = await fetch('/PRACTICAS/api/chat.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ session_id: sessionId, message: text }),
    });

    removeTypingIndicator(typing);

    const data = await res.json();

    if (res.ok && data.success) {
      addMessage(data.message, 'assistant');

      // Si se creó una reserva, mostrar tarjeta especial
      if (data.reserva_creada && data.codigo_reserva) {
        const container = document.getElementById('chatMessages');
        const card = document.createElement('div');
        card.className = 'chat-reserva-card';
        // C2: usar textContent para dato del servidor, no innerHTML directo
        const strong = document.createElement('strong');
        strong.textContent = '✅ Reserva creada: ' + data.codigo_reserva;
        const br = document.createElement('br');
        const small = document.createElement('small');
        small.style.color = 'var(--text-secondary)';
        small.textContent = 'Recibirás confirmación por WhatsApp 📱';
        card.appendChild(strong);
        card.appendChild(br);
        card.appendChild(small);
        container?.appendChild(card);
        if (container) container.scrollTop = container.scrollHeight;
      }
    } else {
      addMessage(data.error || 'Hubo un problema. Intenta de nuevo o contáctanos por WhatsApp. 📱', 'assistant');
    }

  } catch (err) {
    removeTypingIndicator(typing);
    addMessage('No pude conectarme. Por favor escríbenos por WhatsApp: +51 991 917 732 🍽️', 'assistant');
  } finally {
    enviando = false;
    if (sendBtn) sendBtn.disabled = false;
    input?.focus();
  }
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  inyectarChatHTML();

  // Mostrar badge después de 5 segundos si no se ha abierto
  setTimeout(() => {
    const badge = document.getElementById('chatBadge');
    if (!chatAbierto && badge) badge.style.display = 'flex';
  }, 5000);

  // Eventos principales
  document.getElementById('chatBubble')?.addEventListener('click', toggleChat);
  document.getElementById('chatClose')?.addEventListener('click', toggleChat);

  // Envío con botón
  document.getElementById('chatSend')?.addEventListener('click', () => {
    const input = document.getElementById('chatInput');
    sendMessage(input?.value || '');
  });

  // Envío con Enter (Shift+Enter = nueva línea)
  document.getElementById('chatInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage(e.target.value);
    }
  });

  // Auto-resize del textarea
  document.getElementById('chatInput')?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
  });

  // Suggestion chips (delegación de eventos)
  document.getElementById('chatSuggestions')?.addEventListener('click', e => {
    const chip = e.target.closest('.suggestion-chip');
    if (chip) sendMessage(chip.textContent.trim());
  });
});
