/**
 * notificaciones.js — Campana del topbar (todos los roles)
 */
(function () {
    'use strict';

    const wrap = document.getElementById('notif-wrap');
    if (!wrap) return;

    const $boton = document.getElementById('notif-boton');
    const $badge = document.getElementById('notif-badge');
    const $panel = document.getElementById('notif-panel');
    const $lista = document.getElementById('notif-lista');
    const $todas = document.getElementById('notif-marcar-todas');

    let datos = { items: [], no_leidas: 0 };
    let ultimaCarga = Date.now();

    try { datos = JSON.parse(wrap.dataset.inicial || '') || datos; } catch (e) { /* se recarga al abrir */ }

    function el(tag, clase, texto) {
        const e = document.createElement(tag);
        if (clase) e.className = clase;
        if (texto !== undefined && texto !== null) e.textContent = texto;   // nunca HTML del servidor
        return e;
    }

    async function api(accion, cuerpo) {
        const opciones = cuerpo === undefined
            ? { headers: { 'Accept': 'application/json' } }
            : { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cuerpo) };
        const base = (window.ODENT && window.ODENT.base) || '';
        const res = await fetch(base + '/index.php?accion=' + accion, opciones);
        if (res.status === 401) {
            if (typeof mostrarToast === 'function') mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => { location.href = base + '/index.php?accion=logout&motivo=inactividad'; }, 1500);
            throw new Error('sesion');
        }
        return res.json();
    }

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
            const txt = el('div', 'notif-txt');
            txt.append(el('strong', null, n.titulo), el('span', null, n.mensaje), el('small', null, n.fecha));
            b.append(el('i', 'bi ' + icono(n.tipo) + ' notif-ico'), txt);
            b.addEventListener('click', () => abrirNotificacion(n));
            $lista.appendChild(b);
        });
    }

    function icono(tipo) {
        switch (tipo) {
            case 'reporte_asistente': return 'bi-chat-left-text';
            case 'solicitud_cita':    return 'bi-calendar-plus';
            case 'insumo_bajo':       return 'bi-box-seam';
            case 'cita_nueva':        return 'bi-calendar-check';
            default:                  return 'bi-clipboard2-pulse';
        }
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
        if (typeof window.ODENT_ABRIR_NOTIF === 'function' && n.id_tratamiento) {
            await window.ODENT_ABRIR_NOTIF(n);
        } else if (n.destino && document.getElementById('pagina-' + n.destino) && typeof mostrarPagina === 'function') {
            mostrarPagina(n.destino);
        }
    }

    $boton.addEventListener('click', e => { e.stopPropagation(); abrirPanel($panel.hidden); });
    $panel.addEventListener('click', e => e.stopPropagation());
    document.addEventListener('click', () => { if (!$panel.hidden) abrirPanel(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !$panel.hidden) { abrirPanel(false); $boton.focus(); } });

    $todas.addEventListener('click', () => {
        datos.items.forEach(n => { n.leida = true; });
        datos.no_leidas = 0;
        pintar();
        api('notificaciones.leer', { todas: true }).catch(() => {});
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && Date.now() - ultimaCarga > 60000) recargar();
    });

    pintar();
})();