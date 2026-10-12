'use strict';

document.addEventListener('DOMContentLoaded', function () {
    console.log('Odent — Sistema iniciado');
    prepararCierreDeSesion();
    restaurarPaginaActiva();
    actualizarTopbarTitulo();
    iniciarFechaTopbar();
    iniciarCalculoIVA();
    iniciarValidacionLogin();
    iniciarControlInactividad();
});

function iniciarFechaTopbar() {
    const el = document.querySelector('.topbar-fecha');
    if (!el) return;
    const hoy = new Date();
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    el.textContent = hoy.toLocaleDateString('es-CR', opciones);
}

function mostrarPagina(slug) {

    document.querySelectorAll('.pagina').forEach(p => p.classList.remove('activa'));

    const target = document.getElementById('pagina-' + slug);
    if (target) target.classList.add('activa');

    document.querySelectorAll('.sidebar-enlace').forEach(l => l.classList.remove('activo'));
    const enlaceActivo = document.querySelector(`.sidebar-enlace[onclick*="'${slug}'"]`);
    if (enlaceActivo) enlaceActivo.classList.add('activo');

    actualizarTopbarTitulo();
    guardarPaginaActiva(slug);

    if (window.innerWidth < 768) {
        document.getElementById('sidebar')?.classList.remove('abierto');
    }
}

const titulos = {
    inicio:        'Panel principal',
    agenda:        'Agenda',
    expedientes:   'Expedientes clínicos',
    odontograma:   'Odontograma',
    tratamientos:  'Planes de tratamiento',
    pacientes:     'Pacientes',
    cotizaciones:  'Cotizaciones',
    pagos:         'Pagos',
    citas:         'Mis citas',
    asistente:     'Asistente de triaje',
    inventario:    'Inventario',
    insumos:       'Control de insumos',
    usuarios:      'Usuarios',
    reportes:      'Reportes',
    bitacora:      'Bitácora de auditoría',
};

function actualizarTopbarTitulo() {
    const activa = document.querySelector('.pagina.activa');
    const topbar = document.getElementById('topbar-titulo');
    if (!activa || !topbar) return;
    const slug = activa.id.replace('pagina-', '');
    topbar.textContent = titulos[slug] || 'Panel';
}

function abrirModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.add('abierto');

        document.body.style.overflow = 'hidden';
    }
}

function cerrarModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.remove('abierto');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('abierto');
        document.body.style.overflow = '';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.abierto').forEach(m => {
            m.classList.remove('abierto');
            document.body.style.overflow = '';
        });
    }
});

let dienteSeleccionado = null;

function seleccionarDiente(numero) {

    if (dienteSeleccionado) {
        document.getElementById('diente-' + dienteSeleccionado)?.classList.remove('seleccionado');
    }

    dienteSeleccionado = numero;
    const el = document.getElementById('diente-' + numero);
    if (el) el.classList.add('seleccionado');

    document.getElementById('detalle-diente').style.display = 'none';
    const panelEdicion = document.getElementById('panel-edicion-diente');
    if (panelEdicion) {
        panelEdicion.style.display = 'block';
        document.getElementById('diente-numero').value = numero;

        const estadoActual = el ? [...el.classList].find(c => ['sano','caries','corona','ausente'].includes(c)) : 'sano';
        document.getElementById('diente-estado').value = estadoActual || 'sano';
    }
}

function guardarDiente() {
    if (!dienteSeleccionado) return;
    const nuevoEstado = document.getElementById('diente-estado').value;
    const el = document.getElementById('diente-' + dienteSeleccionado);
    if (el) {

        el.classList.remove('sano','caries','corona','ausente');
        el.classList.add(nuevoEstado);
    }

    cancelarDiente();
    mostrarToast('Pieza ' + dienteSeleccionado + ' actualizada correctamente.', 'exito');
}

function cancelarDiente() {
    if (dienteSeleccionado) {
        document.getElementById('diente-' + dienteSeleccionado)?.classList.remove('seleccionado');
    }
    dienteSeleccionado = null;
    const panelEdicion = document.getElementById('panel-edicion-diente');
    if (panelEdicion) panelEdicion.style.display = 'none';
    const detalle = document.getElementById('detalle-diente');
    if (detalle) detalle.style.display = 'block';
}

function iniciarCalculoIVA() {
    const inputMonto = document.getElementById('cot-monto');
    if (!inputMonto) return;
    inputMonto.addEventListener('input', function () {
        const monto = parseFloat(this.value) || 0;
        const iva   = monto * 0.13;
        const total = monto + iva;
        const fmt = n => '₡' + n.toLocaleString('es-CR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        document.getElementById('cot-iva').value   = fmt(iva);
        document.getElementById('cot-total').value = fmt(total);
    });
}

const respuestasChat = {
    'dolor':       'El dolor dental puede indicar caries profunda, infección o un nervio expuesto. Le recomendamos programar una cita lo antes posible. ¿Es el dolor constante o solo al masticar?',
    'muela':       'Si el dolor de muela es intenso y persistente, podría necesitar atención urgente. Intente no tomar antiinflamatorios por más de 2 días sin evaluación profesional.',
    'cayó':        '¡Importante! Si se cayó un diente permanente, manténgalo húmedo (en leche o solución salina) y acuda a la clínica en menos de 2 horas para posible reimplante.',
    'cita':        'Puede consultar sus citas en la sección «Mis citas» del menú. Para agendar o modificar una cita, comuníquese con la recepción de la clínica.',
    'encía':       'Las encías inflamadas pueden ser señal de gingivitis o periodontitis. Un cepillado suave y enjuague con agua salina puede ayudar, pero se recomienda revisión profesional.',
    'bracket':     'Si se rompió un bracket, no es una emergencia, pero debe notificar a su odontólogo para reagendar o repararlo en la próxima cita.',
    'emergencia':  '🚨 En caso de emergencia dental severa (hemorragia, trauma, dolor intenso), llame directamente a la clínica o acuda a urgencias.',
    'default':     'Entiendo su consulta. Para darle la mejor orientación, ¿podría describirme más detalladamente su situación o síntoma? También puede solicitar una cita para evaluación presencial.',
};

function obtenerRespuesta(texto) {
    const t = texto.toLowerCase();
    if (t.includes('cayó') || t.includes('cayo'))  return respuestasChat['cayó'];
    if (t.includes('dolor') && t.includes('muela')) return respuestasChat['muela'];
    if (t.includes('dolor'))                         return respuestasChat['dolor'];
    if (t.includes('cita'))                          return respuestasChat['cita'];
    if (t.includes('encía') || t.includes('encia')) return respuestasChat['encía'];
    if (t.includes('bracket'))                       return respuestasChat['bracket'];
    if (t.includes('emergencia'))                    return respuestasChat['emergencia'];
    return respuestasChat['default'];
}

function enviarMensajeChat() {
    const input = document.getElementById('chat-input-texto');
    const mensajes = document.getElementById('chat-mensajes');
    if (!input || !mensajes) return;

    const texto = input.value.trim();
    if (!texto) return;

    const msgUsuario = document.createElement('div');
    msgUsuario.className = 'mensaje usuario';
    msgUsuario.textContent = texto;
    mensajes.appendChild(msgUsuario);

    setTimeout(() => {
        const msgBot = document.createElement('div');
        msgBot.className = 'mensaje bot';
        msgBot.innerHTML = obtenerRespuesta(texto);
        mensajes.appendChild(msgBot);
        mensajes.scrollTop = mensajes.scrollHeight;
    }, 600);

    input.value = '';
    mensajes.scrollTop = mensajes.scrollHeight;

}

function preguntaRapida(pregunta) {
    const input = document.getElementById('chat-input-texto');
    if (input) {
        input.value = pregunta;
        enviarMensajeChat();
    }
}

function chatEnter(e) {
    if (e.key === 'Enter') enviarMensajeChat();
}

function mostrarToast(mensaje, tipo = 'exito', duracion = 4000) {
    if (tipo === 'advertencia') tipo = 'aviso';
    const iconos = { exito: 'check-circle-fill', peligro: 'exclamation-octagon-fill', aviso: 'exclamation-triangle-fill', info: 'info-circle-fill' };

    let cont = document.getElementById('toast-contenedor');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'toast-contenedor';
        cont.setAttribute('aria-live', 'polite');
        document.body.appendChild(cont);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-odent ' + tipo;
    toast.setAttribute('role', tipo === 'peligro' ? 'alert' : 'status');

    const icono = document.createElement('i');
    icono.className = 'bi bi-' + (iconos[tipo] || iconos.info);
    const texto = document.createElement('span');
    texto.className = 'toast-texto';
    texto.textContent = mensaje;             
    const cerrar = document.createElement('button');
    cerrar.className = 'toast-cerrar';
    cerrar.setAttribute('aria-label', 'Cerrar aviso');
    cerrar.innerHTML = '&times;';
    const barra = document.createElement('div');
    barra.className = 'toast-barra';
    barra.style.animationDuration = duracion + 'ms';

    toast.append(icono, texto, cerrar, barra);
    cont.appendChild(toast);

    while (cont.children.length > 4) cont.firstElementChild.remove();

    let cerrado = false;
    const quitar = () => {
        if (cerrado) return;
        cerrado = true;
        toast.classList.add('saliendo');
        setTimeout(() => toast.remove(), 300);
    };
    let t = setTimeout(quitar, duracion);
    cerrar.addEventListener('click', quitar);
    toast.addEventListener('mouseenter', () => { clearTimeout(t); barra.style.animationPlayState = 'paused'; });
    toast.addEventListener('mouseleave', () => { barra.style.animationPlayState = 'running'; t = setTimeout(quitar, 1500); });
}

/* ── Cierre de sesión: no dejar rastros de la sesión anterior ── */
function olvidarPaginaActiva() {
    try { sessionStorage.removeItem('odent_pagina_activa'); } catch (e) {}
}

function prepararCierreDeSesion() {
    document.addEventListener('click', function (e) {
        if (e.target.closest?.('a[href*="accion=logout"]')) olvidarPaginaActiva();
    });

    if (!document.querySelector('.pagina')) {
        olvidarPaginaActiva();
        if (location.hash) history.replaceState(null, '', location.pathname + location.search);
    }
}

/* ── Recordar la sección activa (no volver a "Panel principal") ── */
const CLAVE_PAGINA = 'odent_pagina_activa';

function guardarPaginaActiva(slug) {
    try { sessionStorage.setItem(CLAVE_PAGINA, slug); } catch (e) { /* modo privado */ }
    if (history.replaceState) history.replaceState(null, '', '#' + slug);
}

function restaurarPaginaActiva() {
    if (!document.querySelector('.pagina')) return;        
    let slug = location.hash.replace('#', '');
    if (!slug) { try { slug = sessionStorage.getItem(CLAVE_PAGINA) || ''; } catch (e) {} }
    if (slug && document.getElementById('pagina-' + slug)) mostrarPagina(slug);
}

window.addEventListener('hashchange', function () {
    const slug = location.hash.replace('#', '');
    if (slug && document.getElementById('pagina-' + slug)) mostrarPagina(slug);
});

/* ── Refrescar secciones SIN recargar la página ────────────── */
async function refrescarSecciones(slugs = []) {
    try {
        const res = await fetch(window.ODENT.base + '/index.php?accion=panel', { headers: { 'Accept': 'text/html' }, cache: 'no-store' });
        if (res.status === 401 || res.redirected && res.url.includes('accion=login')) { location.reload(); return false; }
        const html = await res.text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');

        slugs.forEach(slug => {
            const actual = document.getElementById('pagina-' + slug);
            const nueva  = doc.getElementById('pagina-' + slug);
            if (!actual || !nueva) return;

            const scroll = window.scrollY;
            const buscador = actual.querySelector('input[type="text"][oninput]');
            const filtro   = buscador ? buscador.value : '';

            actual.innerHTML = nueva.innerHTML;

            const nuevoBuscador = actual.querySelector('input[type="text"][oninput]');
            if (nuevoBuscador && filtro) {
                nuevoBuscador.value = filtro;
                nuevoBuscador.dispatchEvent(new Event('input'));
            }
            window.scrollTo(0, scroll);

            actual.classList.add('recien-actualizada');
            setTimeout(() => actual.classList.remove('recien-actualizada'), 900);
        });
        return true;
    } catch (e) {
        location.reload();
        return false;
    }
}

async function guardarJSON(accion, payload, opciones = {}) {
    const btn = opciones.boton || null;
    let htmlOriginal = '';
    if (btn) {
        if (btn.disabled) return null;                     
        htmlOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-mini"></span> Guardando…';
    }
    try {
        const res = await fetch(window.ODENT.base + '/index.php?accion=' + accion, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = window.ODENT.base + '/index.php?accion=logout&motivo=inactividad', 1500);
            return null;
        }
        let data;
        try { data = await res.json(); }
        catch (e) { throw new Error('El servidor devolvió una respuesta inválida.'); }

        if (data.ok) {
            if (opciones.cerrarModal) cerrarModal(opciones.cerrarModal);
            mostrarToast(opciones.exito || 'Guardado con éxito.', 'exito');
            if (opciones.recargar?.length) await refrescarSecciones(opciones.recargar);
            if (typeof opciones.alExito === 'function') opciones.alExito(data);
        } else {
            mostrarToast(data.error || opciones.error || 'No se pudo guardar.', 'peligro', 6000);
        }
        return data;
    } catch (e) {
        mostrarToast(navigator.onLine ? (e.message || 'Ocurrió un error inesperado.') : 'Sin conexión a internet. Intente de nuevo.', 'peligro', 6000);
        return null;
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = htmlOriginal; }
    }
}

function validarCampos(ids, mensaje = 'Complete todos los campos obligatorios.') {
    let primero = null;
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const vacio = !String(el.value ?? '').trim();
        el.classList.toggle('campo-invalido', vacio);
        if (vacio && !primero) primero = el;
        if (vacio) el.addEventListener('input', () => el.classList.remove('campo-invalido'), { once: true });
    });
    if (primero) { primero.focus(); mostrarToast(mensaje, 'aviso'); return false; }
    return true;
}

document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON') return;
    const modal = e.target.closest?.('.modal-overlay.abierto');
    if (!modal) return;
    const principal = modal.querySelector('.modal-footer .btn-odent, .modal-footer .btn-peligro');
    if (principal) { e.preventDefault(); principal.click(); }
});

function iniciarValidacionLogin() {
    const form = document.getElementById('form-login');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        const nombre = document.getElementById('nombre')?.value.trim();
        const pass   = document.getElementById('contrasena')?.value.trim();
        if (!nombre || !pass) {
            e.preventDefault();
            mostrarToast('Ingrese su usuario y contraseña.', 'peligro');
        }

    });
}

const observador = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.width = entry.target.dataset.ancho;
        }
    });
}, { threshold: 0.2 });

document.querySelectorAll('.barra-relleno').forEach(barra => {
    const ancho = barra.style.width;
    barra.dataset.ancho = ancho;
    barra.style.width = '0%';
    observador.observe(barra);
});

/* ── USU-04: sesión, inactividad y botón "Atrás" ───────────── */

window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
});

function iniciarControlInactividad() {
    if (!window.ODENT) return; 

    const limiteMs = window.ODENT.minutosInactividad * 60 * 1000;
    let temporizador;
    let ultimoPing = Date.now();

    const expirar = () => {
        olvidarPaginaActiva();
        window.location.href = window.ODENT.base + '/index.php?accion=logout&motivo=inactividad';
    };

    const reiniciar = () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(expirar, limiteMs);
        if (Date.now() - ultimoPing > 60000) {
            ultimoPing = Date.now();
            fetch(window.ODENT.base + '/index.php?accion=ping', { headers: { 'Accept': 'application/json' } })
                .then(r => { if (r.status === 401) expirar(); })
                .catch(() => {});
        }
    };

    ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(ev =>
        document.addEventListener(ev, reiniciar, { passive: true })
    );
    reiniciar();
}

/* ── USU-05: política de contraseñas y cambio propio ───────── */

function validarPoliticaContrasena(c) {
    if (c.length < 8 || !/[A-Z]/.test(c) || !/[a-z]/.test(c) || !/\d/.test(c)) {
        return 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.';
    }
    return null;
}

function abrirCambiarContrasena() {
    ['cc-actual', 'cc-nueva', 'cc-confirmar'].forEach(id => document.getElementById(id).value = '');
    abrirModal('modal-cambiar-contrasena');
}

async function guardarCambioContrasena() {
    const actual    = document.getElementById('cc-actual').value;
    const nueva     = document.getElementById('cc-nueva').value;
    const confirmar = document.getElementById('cc-confirmar').value;

    if (!validarCampos(['cc-actual', 'cc-nueva', 'cc-confirmar'], 'Complete todos los campos.')) return;
    const errPolitica = validarPoliticaContrasena(nueva);
    if (errPolitica) { mostrarToast(errPolitica, 'aviso', 6000); return; }
    if (nueva !== confirmar) { mostrarToast('La confirmación no coincide con la nueva contraseña.', 'aviso'); return; }

    await guardarJSON('perfil.contrasena', { actual, nueva, confirmar }, {
        boton: document.querySelector('#modal-cambiar-contrasena .modal-footer .btn-odent'),
        cerrarModal: 'modal-cambiar-contrasena',
        exito: 'Contraseña actualizada con éxito.'
    });
}