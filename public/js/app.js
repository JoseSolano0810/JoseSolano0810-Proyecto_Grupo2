
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    console.log('Odent — Sistema iniciado');
    actualizarTopbarTitulo();
    iniciarFechaTopbar();
    iniciarCalculoIVA();
    iniciarValidacionLogin();
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
    'cita':        `Su próxima cita programada es el 14 de agosto de 2026 a las 9:00 a.m. con el Dr. Méndez para control de ortodoncia.`,
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

function mostrarToast(mensaje, tipo = 'exito') {
    const toast = document.createElement('div');
    const icono = tipo === 'exito' ? 'check-circle' : tipo === 'peligro' ? 'exclamation-circle' : 'info-circle';
    toast.className = 'alerta ' + tipo;
    toast.innerHTML = `<i class="bi bi-${icono}"></i> ${mensaje}`;
    toast.style.cssText = `
        position:fixed; bottom:24px; right:24px; z-index:999;
        min-width:280px; max-width:400px;
        box-shadow: 0 4px 20px rgba(0,0,0,.15);
        animation: fadeInUp .3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .4s'; }, 3000);
    setTimeout(() => toast.remove(), 3500);
}

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
