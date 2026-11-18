/**
 * paciente.js — Portal del paciente
 *  · PAC-07: detalle de un tratamiento (finalizados y vigentes)
 *  · PAC-08: apertura del tratamiento desde una notificación (la campana está en notificaciones.js)
 */
(function () {
    'use strict';

    const ETIQUETAS = { pendiente: 'Pendiente', en_proceso: 'En proceso', finalizado: 'Finalizado', cancelado: 'Cancelado' };

    function el(tag, clase, texto) {
        const e = document.createElement(tag);
        if (clase) e.className = clase;
        if (texto !== undefined && texto !== null) e.textContent = texto;     // siempre textContent: nada de HTML del servidor
        return e;
    }

    async function api(accion, cuerpo) {
        const opciones = cuerpo === undefined
            ? { headers: { 'Accept': 'application/json' } }
            : { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cuerpo) };
        const res = await fetch(window.ODENT.base + '/index.php?accion=' + accion, opciones);
        if (res.status === 401) {
            if (typeof mostrarToast === 'function') mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => { location.href = window.ODENT.base + '/index.php?accion=logout&motivo=inactividad'; }, 1500);
            throw new Error('sesion');
        }
        return res.json();
    }

    /* ── PAC-07: detalle de un tratamiento ──────────────────── */

    function dato(etiqueta, valor) {
        const d = el('div');
        d.append(el('strong', null, etiqueta), el('br'), document.createTextNode(valor || '—'));
        return d;
    }

    function pintarDetalle(t, historial) {
        const finalizado = t.estado === 'finalizado';
        const cuerpo = document.getElementById('td-cuerpo');
        cuerpo.replaceChildren();

        document.getElementById('td-titulo').textContent = t.nombre;

        const estado = el('span', 'badge-estado badge-' + t.estado, ETIQUETAS[t.estado] || t.estado);
        const cab = el('div', 'td-cab');
        cab.appendChild(estado);
        cuerpo.appendChild(cab);

        const grid = el('div', 'td-grid');
        grid.append(
            dato('Fecha de inicio', t.inicio),
            dato(finalizado ? 'Fecha de finalización' : 'Fin estimado', finalizado ? (t.fin_real || t.fin_estimada) : t.fin_estimada),
            dato('Odontólogo responsable', t.odontologo),
            dato(finalizado ? 'Porcentaje final' : 'Avance actual', (t.progreso || 0) + '%')
        );
        cuerpo.appendChild(grid);

        const barra = el('div', 'barra-progreso');
        const rel = el('div', 'barra-relleno' + (t.estado === 'cancelado' ? ' tre-cancelado' : ''));
        rel.style.width = Math.max(0, Math.min(100, t.progreso || 0)) + '%';
        barra.appendChild(rel);
        cuerpo.appendChild(barra);

        cuerpo.appendChild(el('h4', 'tre-sub', 'Etapas realizadas'));
        if (!historial.length) {
            cuerpo.appendChild(el('p', 'tre-sin-datos', 'Este tratamiento no tiene etapas registradas.'));
            return;
        }
        const linea = el('div', 'tre-timeline');
        // el servidor las envía de la más reciente a la más antigua; aquí van en orden cronológico
        historial.slice().reverse().forEach(h => {
            const item = el('div', 'tre-linea');
            item.appendChild(el('span', 'tre-linea-punto'));
            item.appendChild(el('div', 'tre-linea-cab', h.titulo));
            item.appendChild(el('div', 'tre-linea-sub', h.fecha + (h.usuario ? ' · ' + h.usuario : '')));
            if (h.observaciones) item.appendChild(el('div', 'tre-linea-obs', h.observaciones));
            linea.appendChild(item);
        });
        cuerpo.appendChild(linea);
    }

    /** Abre el detalle; devuelve el tratamiento (o null si no se pudo cargar) */
    window.abrirDetalleTratamiento = async function (id) {
        try {
            const r = await api('tratamientos.detalle&id=' + encodeURIComponent(id));
            if (!r.ok) {
                if (typeof mostrarToast === 'function') mostrarToast(r.error || 'No pudimos abrir el tratamiento.', 'aviso');
                return null;
            }
            pintarDetalle(r.data, r.historial || []);
            abrirModal('modal-trat-detalle');
            return r.data;
        } catch (e) {
            if (e.message !== 'sesion' && typeof mostrarToast === 'function') {
                mostrarToast(navigator.onLine ? 'No pudimos cargar el tratamiento. Intente de nuevo.' : 'Sin conexión a internet.', 'peligro');
            }
            return null;
        }
    };

    /** Lleva al paciente a la sección que corresponde y abre el detalle del tratamiento */
    async function abrirTratamientoDeNotificacion(id) {
        const t = await window.abrirDetalleTratamiento(id);
        if (t && typeof mostrarPagina === 'function') {
            mostrarPagina(t.estado === 'finalizado' ? 'finalizados' : 'tratamientos');
        }
    }

    // Al hacer clic en una notificación de la campana, el paciente abre el tratamiento relacionado
    window.ODENT_ABRIR_NOTIF = function (n) {
        if (n.id_tratamiento) return abrirTratamientoDeNotificacion(n.id_tratamiento);
    };

    /* ── PAC-08 esc. 2: llegó desde el enlace de una notificación ── */

    const abrirId = window.PACIENTE && window.PACIENTE.abrirTratamiento;
    if (abrirId) {
        // se quita ?trat= de la barra para que recargar la página no vuelva a abrir el modal
        const url = new URL(location.href);
        url.searchParams.delete('trat');
        history.replaceState(null, '', url.pathname + url.search + url.hash);
        document.addEventListener('DOMContentLoaded', () => abrirTratamientoDeNotificacion(abrirId));
    }
})();