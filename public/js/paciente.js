/**
 * paciente.js — Portal del paciente
 *  PAC-07: detalle de un tratamiento (finalizados y vigentes)
 *  PAC-08: campana de notificaciones y apertura del tratamiento desde una notificación
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

    /* ── PAC-08: campana de notificaciones ──────────────────── */

    const wrap = document.getElementById('notif-wrap');
    if (wrap) iniciarCampana(wrap);

    function iniciarCampana(wrap) {
        const $boton = document.getElementById('notif-boton');
        const $badge = document.getElementById('notif-badge');
        const $panel = document.getElementById('notif-panel');
        const $lista = document.getElementById('notif-lista');
        const $todas = document.getElementById('notif-marcar-todas');
        let datos = { items: [], no_leidas: 0 };
        let ultimaCarga = Date.now();

        try { datos = JSON.parse(wrap.dataset.inicial || '') || datos; } catch (e) { /* se recarga al abrir */ }

        function pintar() {
            $badge.hidden = !datos.no_leidas;
            $badge.textContent = datos.no_leidas > 9 ? '9+' : String(datos.no_leidas);
            $boton.setAttribute('aria-label', 'Notificaciones' + (datos.no_leidas ? ' (' + datos.no_leidas + ' sin leer)' : ''));
            $todas.hidden = !datos.no_leidas;

            $lista.replaceChildren();
            if (!datos.items.length) {
                const vacio = el('div', 'notif-vacio');
                vacio.append(el('i', 'bi bi-bell-slash'), el('span', null, 'No tiene notificaciones.'));
                $lista.appendChild(vacio);
                return;
            }
            datos.items.forEach(n => {
                const b = el('button', 'notif-item' + (n.leida ? '' : ' notif-nueva'));
                b.type = 'button';
                b.setAttribute('role', 'menuitem');
                const ico = el('i', 'bi bi-clipboard2-pulse notif-ico');
                const txt = el('div', 'notif-txt');
                txt.append(el('strong', null, n.titulo), el('span', null, n.mensaje), el('small', null, n.fecha));
                b.append(ico, txt);
                b.addEventListener('click', () => abrirNotificacion(n));
                $lista.appendChild(b);
            });
        }

        async function recargar() {
            try {
                const r = await api('notificaciones.listar');
                if (r.ok) { datos = { items: r.items, no_leidas: r.no_leidas }; ultimaCarga = Date.now(); pintar(); }
            } catch (e) { /* sin conexión: se conserva lo que hay */ }
        }

        function abrirPanel(abrir) {
            $panel.hidden = !abrir;
            $boton.setAttribute('aria-expanded', String(abrir));
            if (abrir) recargar();
        }

        async function abrirNotificacion(n) {
            abrirPanel(false);
            if (!n.leida) {
                n.leida = true;
                datos.no_leidas = Math.max(0, datos.no_leidas - 1);
                pintar();
                api('notificaciones.leer', { id: n.id }).catch(() => {});
            }
            if (n.id_tratamiento) await abrirTratamientoDeNotificacion(n.id_tratamiento);
        }

        $boton.addEventListener('click', e => { e.stopPropagation(); abrirPanel($panel.hidden); });
        $panel.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('click', () => { if (!$panel.hidden) abrirPanel(false); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !$panel.hidden) { abrirPanel(false); $boton.focus(); } });

        $todas.addEventListener('click', async () => {
            datos.items.forEach(n => { n.leida = true; });
            datos.no_leidas = 0;
            pintar();
            api('notificaciones.leer', { todas: true }).catch(() => {});
        });

        // Sin temporizador (no mantiene viva la sesión): se actualiza cuando el paciente vuelve a la pestaña
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && Date.now() - ultimaCarga > 60000) recargar();
        });

        pintar();
    }

    /** Lleva al paciente a la sección que corresponde y abre el detalle del tratamiento */
    async function abrirTratamientoDeNotificacion(id) {
        const t = await window.abrirDetalleTratamiento(id);
        if (t && typeof mostrarPagina === 'function') {
            mostrarPagina(t.estado === 'finalizado' ? 'finalizados' : 'tratamientos');
        }
    }

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