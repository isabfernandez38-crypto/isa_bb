/* ============================================================
   MAICELO RESTOBAR — admin-ui.js
   Sistema compartido de notificaciones (toast), confirmaciones
   (confirmDialog) y estados de carga (skeletonRows), usado en
   todas las páginas del panel admin en vez de alert()/confirm().
   ============================================================ */

(function () {
    'use strict';

    // ── Escape HTML ───────────────────────────────────────────────────────────
    window.escapeHTML = function (str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    };

    const ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
    };

    function getToastContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Muestra una notificación tipo toast.
     * @param {string} mensaje - HTML permitido (ej. <strong>texto</strong>)
     * @param {'success'|'error'|'warning'|'info'} tipo
     * @param {number} duracion - ms antes de auto-cerrar (default 3500)
     */
    window.toast = function (mensaje, tipo = 'info', duracion = 3500) {
        const container = getToastContainer();
        const el = document.createElement('div');
        el.className = `toast-criollo ${tipo}`;
        el.innerHTML = `
      <i class="fas ${ICONS[tipo] || ICONS.info}"></i>
      <span>${mensaje}</span>
      <button class="toast-criollo-close" aria-label="Cerrar">
        <i class="fas fa-xmark"></i>
      </button>
    `;
        container.appendChild(el);

        requestAnimationFrame(() => el.classList.add('show'));

        const cerrar = () => {
            el.classList.remove('show');
            el.classList.add('hide');
            setTimeout(() => el.remove(), 350);
        };

        el.querySelector('.toast-criollo-close').addEventListener('click', cerrar);
        if (duracion > 0) setTimeout(cerrar, duracion);

        return el;
    };

    /**
     * Muestra un modal de confirmación criollo y devuelve una Promise<boolean>.
     * @param {Object} opts
     * @param {string} opts.titulo
     * @param {string} [opts.mensaje]
     * @param {string} [opts.icono] - clase fontawesome, ej 'fa-trash'
     * @param {string} [opts.textoConfirmar='Confirmar']
     * @param {string} [opts.textoCancelar='Cancelar']
     * @param {boolean} [opts.peligroso=false]
     */
    window.confirmDialog = function (opts) {
        const {
            titulo = '¿Estás seguro?',
            mensaje = '',
            icono = 'fa-circle-question',
            textoConfirmar = 'Confirmar',
            textoCancelar = 'Cancelar',
            peligroso = false,
        } = opts || {};

        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            overlay.innerHTML = `
        <div class="confirm-box ${peligroso ? 'peligroso' : ''}">
          <i class="fas ${icono} confirm-icon"></i>
          <div class="confirm-box-titulo">${titulo}</div>
          ${mensaje ? `<div class="confirm-box-mensaje">${mensaje}</div>` : ''}
          <div class="confirm-box-actions">
            <button type="button" class="btn-cancelar">${textoCancelar}</button>
            <button type="button" class="btn-confirmar">${textoConfirmar}</button>
          </div>
        </div>
      `;
            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add('show'));

            const cerrar = (resultado) => {
                overlay.classList.remove('show');
                setTimeout(() => overlay.remove(), 200);
                resolve(resultado);
            };

            overlay.querySelector('.btn-cancelar').addEventListener('click', () => cerrar(false));
            overlay.querySelector('.btn-confirmar').addEventListener('click', () => cerrar(true));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) cerrar(false);
            });
            document.addEventListener('keydown', function escListener(e) {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', escListener);
                    cerrar(false);
                }
            });
        });
    };

    /**
     * Genera filas <tr> de skeleton para usar como placeholder en tablas
     * mientras carga la data por fetch.
     * @param {number} filas - cantidad de filas
     * @param {number} columnas - cantidad de columnas (<td>) por fila
     */
    window.skeletonRows = function (filas = 5, columnas = 5) {
        let html = '';
        for (let i = 0; i < filas; i++) {
            html += '<tr class="skeleton-row">';
            for (let j = 0; j < columnas; j++) {
                html += '<td><div class="skeleton-block" style="height:18px;margin:0;"></div></td>';
            }
            html += '</tr>';
        }
        return html;
    };
})();
