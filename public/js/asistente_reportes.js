'use strict';
/**
 * asistente_reportes.js — ASI-04 y ASI-05
 * Reportes del asistente inteligente y bandeja de solicitudes de cita.
 * Depende de app.js: abrirModal, cerrarModal, mostrarToast.
 */

const AREP_PRIORIDADES = {
    urgente:     { texto: 'Urgente',     icono: 'exclamation-octagon-fill' },
    prioritario: { texto: 'Prioritario', icono: 'exclamation-triangle-fill' },
    normal:      { texto: 'Normal',      icono: 'check-circle-fill' },
};

const AREP_ACCION_SUGERIDA = {
    urgente:     'Se recomienda comunicarse con el paciente cuanto antes.',
    prioritario: 'Se recomienda contactar al paciente para coordinar una revisión pronto.',
    normal:      'No requiere atención inmediata; basta con el seguimiento habitual.',
};

let _arepLista = [];      // reportes cargados
let _solLista = [];       // solicitudes cargadas
let _arCatalogos = null;  // odontólogos, tipos y duraciones del módulo de Citas

function _ar(id) { return document.getElementById(id); }

function arEsc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function arBase() {
    return (window.ODENT && window.ODENT.base) || (typeof BASE !== 'undefined' ? BASE : '');
}

function arHoyISO() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function arBadge(prioridad) {
    const p = AREP_PRIORIDADES[prioridad] || { texto: prioridad, icono: 'dot' };
    return `<span class="arep-prio arep-prio-${arEsc(prioridad)}"><i class="bi bi-${p.icono}"></i> ${arEsc(p.texto)}</span>`;
}

function arSesionExpirada() {
    mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
    setTimeout(() => location.href = arBase() + '/index.php?accion=logout&motivo=inactividad', 1500);
}

/** GET con manejo uniforme de errores. Devuelve el JSON o null (ya avisó al usuario). */
async function arGet(accion, params = {}) {
    const qs = new URLSearchParams({ accion, ...params }).toString();
    try {
        const res = await fetch(`${arBase()}/index.php?${qs}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (res.status === 401) { arSesionExpirada(); return null; }
        const data = await res.json();
        if (!data.ok) {
            mostrarToast(data.error || 'No pudimos completar la consulta.', 'peligro', 6000);
            return null;
        }
        return data;
    } catch (e) {
        mostrarToast(navigator.onLine ? 'No pudimos cargar la información. Intente de nuevo.' : 'Sin conexión a internet.', 'peligro', 6000);
        return null;
    }
}

/** POST JSON. Siempre devuelve un objeto con "ok"; los errores de red se convierten en { ok:false, error } */
async function arPost(accion, cuerpo) {
    try {
        const res = await fetch(`${arBase()}/index.php?accion=${accion}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(cuerpo),
        });
        if (res.status === 401) { arSesionExpirada(); return { ok: false, error: 'Su sesión expiró.' }; }
        return await res.json();
    } catch (e) {
        return { ok: false, error: navigator.onLine ? 'No pudimos completar la acción. Intente de nuevo.' : 'Sin conexión a internet.' };
    }
}

/* ══════════════════════════ Pestañas ══════════════════════════ */

function asiRepTab(cual) {
    const reportes = cual === 'reportes';
    _ar('arep-panel-reportes').hidden = !reportes;
    _ar('arep-panel-solicitudes').hidden = reportes;

    const tR = _ar('arep-tab-reportes').classList, tS = _ar('arep-tab-solicitudes').classList;
    if (reportes) { tR.add('arep-tab-activa'); tS.remove('arep-tab-activa'); }
    else          { tS.add('arep-tab-activa'); tR.remove('arep-tab-activa'); }

    if (!reportes) asiSolCargar();
}

/* ══════════════════════════ Filtros de reportes ══════════════════════════ */

function arLeerFiltros() {
    return {
        q:         _ar('arep-q').value.trim(),
        prioridad: _ar('arep-prioridad').value,
        desde:     _ar('arep-desde').value,
        hasta:     _ar('arep-hasta').value,
    };
}

function arHayFiltros(f) {
    return !!(f.q || f.prioridad || f.desde || f.hasta);
}

function arLimpiarCampos() {
    _ar('arep-q').value = '';
    _ar('arep-prioridad').value = '';
    _ar('arep-desde').value = '';
    _ar('arep-hasta').value = '';
}

/* ══════════════════════════ Listado de reportes (ASI-04 esc. 1 y 3) ══════════════════════════ */

/** Botón "Consultar reportes": trae todos los reportes, sin filtros */
function asiRepConsultar() {
    arLimpiarCampos();
    arCargar({});
}

/** Botón "Buscar": aplica los criterios elegidos */
function asiRepBuscar() {
    const f = arLeerFiltros();
    if (f.desde && f.hasta && f.desde > f.hasta) {
        return mostrarToast('La fecha inicial no puede ser posterior a la fecha final.', 'aviso', 6000);
    }
    arCargar(f);
}

function asiRepLimpiar() {
    arLimpiarCampos();
    _arepLista = [];
    _ar('arep-resumen').innerHTML = '';
    _ar('arep-resultado').innerHTML = `
        <div class="arep-vacio">
            <i class="bi bi-clipboard2-pulse"></i>
            <h4>Aquí verá los reportes de los pacientes</h4>
            <p>Presione «Consultar reportes» para ver el listado completo, o use los filtros de arriba para buscar.</p>
            <button class="btn-odent" onclick="asiRepConsultar()"><i class="bi bi-clipboard2-pulse"></i> Consultar reportes</button>
        </div>`;
}

async function arCargar(filtros) {
    const caja = _ar('arep-resultado');
    caja.innerHTML = '<div class="arep-vacio"><span class="spinner-mini"></span> Buscando reportes…</div>';

    const params = {};
    Object.entries(filtros).forEach(([k, v]) => { if (v) params[k] = v; });

    const data = await arGet('asistente.reportes.listar', params);
    if (!data) {
        caja.innerHTML = `<div class="arep-vacio"><i class="bi bi-wifi-off"></i><h4>No pudimos cargar los reportes</h4>
            <button class="btn-outline-odent" onclick="asiRepBuscar()">Reintentar</button></div>`;
        return;
    }

    _arepLista = data.data || [];
    arRender(_arepLista, arHayFiltros(filtros));
}

function arRenderResumen(lista) {
    const cont = _ar('arep-resumen');
    if (!lista.length) { cont.innerHTML = ''; return; }

    const cuenta = { urgente: 0, prioritario: 0, normal: 0 };
    lista.forEach(r => { if (r.prioridad in cuenta) cuenta[r.prioridad]++; });

    cont.innerHTML =
        `<span class="arep-chip"><strong>${lista.length}</strong> reporte${lista.length === 1 ? '' : 's'}</span>` +
        ['urgente', 'prioritario', 'normal'].filter(k => cuenta[k] > 0).map(k =>
            `<span class="arep-chip arep-chip-${k}"><strong>${cuenta[k]}</strong> ${AREP_PRIORIDADES[k].texto.toLowerCase()}${cuenta[k] === 1 ? '' : 's'}</span>`).join('');
}

/** Marca junto a la prioridad si el paciente pidió cita desde ese reporte */
function arIndicadorCita(estado) {
    if (estado === 'pendiente')  return '<span class="arep-tiene-cita arep-tiene-cita-pendiente" title="El paciente solicitó cita y aún no se programa"><i class="bi bi-calendar-plus"></i> Pidió cita</span>';
    if (estado === 'programada') return '<span class="arep-tiene-cita" title="La cita ya fue agendada"><i class="bi bi-calendar-check"></i> Cita agendada</span>';
    return '';
}

function arRender(lista, filtrando) {
    const caja = _ar('arep-resultado');
    arRenderResumen(lista);

    if (!lista.length) {
        caja.innerHTML = filtrando
            ? `<div class="arep-vacio">
                   <i class="bi bi-search"></i>
                   <h4>No existen reportes para los criterios seleccionados</h4>
                   <p>Pruebe con otro nombre, otra prioridad o un rango de fechas más amplio. Los filtros siguen disponibles arriba.</p>
               </div>`
            : `<div class="arep-vacio">
                   <i class="bi bi-inbox"></i>
                   <h4>Todavía no hay reportes registrados</h4>
                   <p>Cuando un paciente reporte una molestia con el asistente, aparecerá aquí.</p>
               </div>`;
        return;
    }

    caja.innerHTML = `
        <div class="arep-tabla-scroll">
        <table class="tabla-odent">
            <thead>
                <tr><th>Paciente</th><th>Fecha</th><th>Hora</th><th>Descripción del problema</th><th>Prioridad</th><th></th></tr>
            </thead>
            <tbody>
                ${lista.map(r => `
                <tr class="arep-fila arep-fila-${arEsc(r.prioridad)}" tabindex="0"
                    onclick="asiRepDetalle(${r.id})"
                    onkeydown="if (event.key === 'Enter') asiRepDetalle(${r.id})">
                    <td><strong>${arEsc(r.paciente)}</strong>${r.cedula ? `<br><small class="arep-sub">${arEsc(r.cedula)}</small>` : ''}</td>
                    <td>${arEsc(r.fecha)}</td>
                    <td>${arEsc(r.hora)}</td>
                    <td class="arep-desc" title="${arEsc(r.descripcion)}">${arEsc(r.descripcion)}</td>
                    <td>${arBadge(r.prioridad)}${arIndicadorCita(r.solicitud_estado)}</td>
                    <td><button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"
                                onclick="event.stopPropagation(); asiRepDetalle(${r.id})"><i class="bi bi-eye"></i> Ver</button></td>
                </tr>`).join('')}
            </tbody>
        </table>
        </div>`;
}

/* ══════════════════════════ Detalle de un reporte (ASI-04 esc. 2) ══════════════════════════ */

function arTextoSolicitud(s) {
    if (!s) return '';
    const horario = s.con_horario
        ? `Pidió ${arEsc(s.fecha_fmt)} a las ${arEsc(s.hora_texto)}${s.odontologo ? ' con ' + arEsc(s.odontologo) : ''}.`
        : 'No eligió horario; hay que coordinarlo con el paciente.';
    const texto = {
        pendiente:  `El paciente solicitó una cita y aún está pendiente de programar. ${horario}`,
        programada: `El paciente solicitó una cita y ya fue agendada${s.id_cita ? ' (cita N.º ' + s.id_cita + ')' : ''}.`,
        descartada: 'El paciente solicitó una cita, pero la solicitud fue descartada.',
    }[s.estado] || '';
    return `<div class="arep-det-solicitud arep-det-solicitud-${arEsc(s.estado)}"><i class="bi bi-calendar-check"></i> ${texto}</div>`;
}

async function asiRepDetalle(id) {
    const data = await arGet('asistente.reportes.detalle', { id });
    if (!data) return;
    const r = data.data;

    const criterios = (r.criterios && r.criterios.length)
        ? `<ul class="arep-criterios">${r.criterios.map(c => `<li>${arEsc(c)}</li>`).join('')}</ul>`
        : '<p class="arep-sub">No se activó ningún criterio de alerta, por eso se clasificó como atención normal.</p>';

    _ar('arep-detalle-contenido').innerHTML = `
        <div class="arep-det-cab">
            <div>
                <h4>${arEsc(r.paciente)}</h4>
                ${r.cedula ? `<p class="arep-sub">Cédula: ${arEsc(r.cedula)}</p>` : ''}
            </div>
            ${arBadge(r.prioridad)}
        </div>

        <div class="arep-det-meta">
            <div><strong>Fecha</strong><br>${arEsc(r.fecha)}</div>
            <div><strong>Hora</strong><br>${arEsc(r.hora)}</div>
            <div><strong>Seguimiento</strong><br>${arEsc(r.estado_texto)}</div>
        </div>

        <h5 class="arep-det-sub">Lo que respondió el paciente</h5>
        <dl class="arep-respuestas">
            <dt>Descripción del problema</dt>
            <dd>${arEsc(r.descripcion)}</dd>
            <dt>Zona afectada</dt>
            <dd>${arEsc(r.zona)}</dd>
            <dt>Intensidad</dt>
            <dd>
                <span class="arep-intensidad"><span class="arep-intensidad-barra"><i style="width:${r.intensidad * 10}%;"></i></span>
                <strong>${r.intensidad}</strong> de 10</span>
            </dd>
            <dt>Tiempo desde que comenzó</dt>
            <dd>${arEsc(r.tiempo)}</dd>
        </dl>

        <h5 class="arep-det-sub">Cómo se clasificó</h5>
        ${criterios}

        <p class="arep-sugerencia arep-sugerencia-${arEsc(r.prioridad)}">
            <i class="bi bi-info-circle"></i> ${arEsc(AREP_ACCION_SUGERIDA[r.prioridad] || '')}
        </p>
        ${arTextoSolicitud(data.solicitud)}`;

    abrirModal('modal-arep-detalle');
}

/* ══════════════════════════ Bandeja de solicitudes de cita (ASI-05) ══════════════════════════ */

function arActualizarContador(n) {
    const c = _ar('arep-contador');
    if (!c) return;
    c.textContent = n;
    c.hidden = !(n > 0);
}

/** Cuenta en silencio las pendientes para mostrarlas en la pestaña, sin molestar con avisos */
async function asiSolContarPendientes() {
    try {
        const res = await fetch(`${arBase()}/index.php?accion=asistente.solicitudes.listar&estado=pendiente`,
            { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        const data = await res.json();
        if (data.ok) arActualizarContador(data.pendientes || 0);
    } catch (e) { /* el contador es solo informativo */ }
}

async function asiSolCargar() {
    const caja = _ar('arsol-lista');
    caja.innerHTML = '<div class="tarjeta"><div class="arep-vacio"><span class="spinner-mini"></span> Cargando solicitudes…</div></div>';

    const estado = _ar('arsol-estado').value;
    const data = await arGet('asistente.solicitudes.listar', estado ? { estado } : {});
    if (!data) {
        caja.innerHTML = `<div class="tarjeta"><div class="arep-vacio"><i class="bi bi-wifi-off"></i><h4>No pudimos cargar las solicitudes</h4>
            <button class="btn-outline-odent" onclick="asiSolCargar()">Reintentar</button></div></div>`;
        return;
    }

    _solLista = data.data || [];
    arActualizarContador(data.pendientes || 0);
    arSolRender(_solLista, estado);
}

function arSolTarjeta(s) {
    const tel = s.telefono
        ? `<a href="tel:${arEsc(s.telefono)}">${arEsc(s.telefono)}</a>`
        : 'Sin teléfono registrado';

    const horario = s.con_horario
        ? `<div class="arep-sol-horario"><i class="bi bi-calendar-event"></i>
               <span>Pidió <strong>${arEsc(s.fecha_fmt)} · ${arEsc(s.hora_texto)}</strong>${s.odontologo ? ' con ' + arEsc(s.odontologo) : ''}</span></div>`
        : `<div class="arep-sol-horario arep-sin-horario"><i class="bi bi-telephone"></i>
               <span>No eligió horario: comuníquese con el paciente para coordinar.</span></div>`;

    let acciones = '';
    if (s.estado === 'pendiente') {
        acciones = `
            <button class="btn-odent" onclick="asiSolProgramar(${s.id})"><i class="bi bi-calendar-check"></i> Programar cita</button>
            <button class="btn-outline-odent" onclick="asiSolDescartar(${s.id})"><i class="bi bi-x-circle"></i> Descartar</button>`;
    } else if (s.estado === 'programada') {
        acciones = `<span class="arep-sub"><i class="bi bi-calendar-check"></i> Cita agendada${s.id_cita ? ' (N.º ' + s.id_cita + ')' : ''}</span>`;
    }

    return `
    <div class="tarjeta arep-sol arep-sol-${arEsc(s.prioridad)}">
        <div class="arep-sol-cab">
            <div>
                <h4>${arEsc(s.paciente)}</h4>
                <p class="arep-sub">${s.cedula ? 'Cédula ' + arEsc(s.cedula) + ' · ' : ''}${tel}</p>
            </div>
            <div class="arep-sol-etiquetas">
                ${arBadge(s.prioridad)}
                <span class="arep-estado arep-estado-${arEsc(s.estado)}">${arEsc(s.estado_texto)}</span>
            </div>
        </div>
        <p class="arep-sol-motivo">${arEsc(s.motivo)}</p>
        ${horario}
        <div class="arep-sol-pie">
            <span class="arep-sub">Solicitada el ${arEsc(s.solicitada)} · Reporte N.º ${s.id_reporte}</span>
            <div class="arep-sol-acciones">${acciones}</div>
        </div>
    </div>`;
}

function arSolRender(lista, estado) {
    const caja = _ar('arsol-lista');

    if (!lista.length) {
        const textos = {
            pendiente:  ['No hay solicitudes pendientes', 'Todo está al día. Cuando un paciente pida una cita desde el asistente, aparecerá aquí.'],
            programada: ['Todavía no hay solicitudes programadas', 'Las solicitudes aparecen aquí una vez que se agenda su cita.'],
            descartada: ['No hay solicitudes descartadas', ''],
        };
        const [titulo, detalle] = textos[estado] || ['No hay solicitudes de cita', 'Cuando un paciente pida una cita desde el asistente, aparecerá aquí.'];
        caja.innerHTML = `<div class="tarjeta"><div class="arep-vacio"><i class="bi bi-calendar-check"></i><h4>${titulo}</h4>${detalle ? `<p>${detalle}</p>` : ''}</div></div>`;
        return;
    }

    caja.innerHTML = lista.map(arSolTarjeta).join('');
}

/* ── Programar la cita ───────────────────────────────────── */

async function arCargarCatalogos() {
    if (_arCatalogos) return _arCatalogos;
    const data = await arGet('citas.catalogos');
    if (data) _arCatalogos = data;
    return _arCatalogos;
}

async function asiSolProgramar(id) {
    const s = _solLista.find(x => x.id === id);
    if (!s) return;

    const cat = await arCargarCatalogos();
    if (!cat) return;

    _ar('arp-id').value = s.id;
    _ar('arp-paciente').textContent = s.paciente + (s.cedula ? ' · ' + s.cedula : '');
    _ar('arp-motivo').textContent = s.motivo;

    const aviso = _ar('arp-aviso');
    if (s.con_horario) {
        aviso.hidden = true;
        aviso.textContent = '';
    } else {
        aviso.hidden = false;
        aviso.textContent = 'El paciente no eligió horario. Coordínelo con él' + (s.telefono ? ' por teléfono (' + s.telefono + ')' : '') + ' y registre aquí el que acuerden.';
    }

    _ar('arp-odontologo').innerHTML = '<option value="">Seleccione…</option>' +
        (cat.odontologos || []).map(o => `<option value="${o.id}">${arEsc(o.nombre)}</option>`).join('');
    _ar('arp-tipo').innerHTML = (cat.tipos || []).map(t => `<option value="${arEsc(t)}">${arEsc(t)}</option>`).join('');
    _ar('arp-duracion').innerHTML = (cat.duraciones || []).map(d => `<option value="${d}">${d} min</option>`).join('');

    _ar('arp-odontologo').value = s.id_odontologo ? String(s.id_odontologo) : '';
    _ar('arp-tipo').value = s.prioridad === 'urgente' ? 'Urgencia' : 'Revisión general';
    _ar('arp-duracion').value = '30';
    _ar('arp-fecha').value = s.fecha || arHoyISO();
    _ar('arp-hora').value = s.hora || '';
    _ar('arp-observaciones').value = (s.motivo || '').slice(0, 500);

    abrirModal('modal-arep-programar');
}

/**
 * La cita se crea con el mismo endpoint del módulo de Citas (así conserva sus validaciones,
 * su historial y su bitácora) y después se enlaza con la solicitud.
 */
async function asiSolConfirmarProgramar() {
    const id = Number(_ar('arp-id').value);
    const s = _solLista.find(x => x.id === id);
    if (!s) return;

    const datos = {
        id_paciente:   s.id_usuario,
        id_odontologo: Number(_ar('arp-odontologo').value) || 0,
        fecha:         _ar('arp-fecha').value,
        hora:          _ar('arp-hora').value,
        duracion:      Number(_ar('arp-duracion').value) || 0,
        tipo_consulta: _ar('arp-tipo').value,
        estado:        'programada',
        observaciones: _ar('arp-observaciones').value.trim(),
    };

    const faltan = [];
    if (!datos.id_odontologo) faltan.push('odontólogo');
    if (!datos.fecha)         faltan.push('fecha');
    if (!datos.hora)          faltan.push('hora');
    if (faltan.length) {
        return mostrarToast('Para agendar la cita falta completar: ' + faltan.join(', ') + '.', 'aviso', 6000);
    }

    const boton = _ar('arp-btn');
    boton.disabled = true;
    try {
        const creada = await arPost('citas.crear', datos);
        if (!creada.ok) {
            mostrarToast(creada.error || 'No se pudo agendar la cita.', 'peligro', 6000);
            return;
        }

        const enlace = await arPost('asistente.solicitudes.programada', {
            id_solicitud: id,
            id_cita: creada.data && creada.data.id,
        });

        cerrarModal('modal-arep-programar');
        if (enlace.ok) {
            mostrarToast('Listo: la cita quedó agendada y la solicitud marcada como programada.', 'exito', 6000);
        } else {
            mostrarToast('La cita se agendó, pero no pudimos marcar la solicitud como programada. Actualice la lista.', 'aviso', 8000);
        }
        asiSolCargar();
    } finally {
        boton.disabled = false;
    }
}

/* ── Descartar ───────────────────────────────────────────── */

function asiSolDescartar(id) {
    const s = _solLista.find(x => x.id === id);
    if (!s) return;
    _ar('ard-id').value = s.id;
    _ar('ard-paciente').textContent = s.paciente;
    abrirModal('modal-arep-descartar');
}

async function asiSolConfirmarDescartar() {
    const id = Number(_ar('ard-id').value);
    const boton = _ar('ard-btn');
    boton.disabled = true;
    try {
        const r = await arPost('asistente.solicitudes.descartar', { id_solicitud: id });
        cerrarModal('modal-arep-descartar');
        if (r.ok) mostrarToast('La solicitud se descartó. El reporte del paciente se conserva.', 'exito');
        else mostrarToast(r.error || 'No pudimos descartar la solicitud.', 'peligro', 6000);
        asiSolCargar();
    } finally {
        boton.disabled = false;
    }
}

/* ══════════════════════════ Arranque ══════════════════════════ */

/**
 * Cada vez que se entra a esta página se actualiza el contador de solicitudes pendientes
 * (y la bandeja, si es la pestaña que está abierta).
 */
(function () {
    const pagina = document.getElementById('pagina-asistente-reportes');
    if (!pagina || typeof MutationObserver === 'undefined') return;

    let estabaActiva = pagina.classList.contains('activa');
    new MutationObserver(() => {
        const activa = pagina.classList.contains('activa');
        if (activa && !estabaActiva) {
            asiSolContarPendientes();
            if (!_ar('arep-panel-solicitudes').hidden) asiSolCargar();
        }
        estabaActiva = activa;
    }).observe(pagina, { attributes: true, attributeFilter: ['class'] });

    if (estabaActiva) asiSolContarPendientes();
})();
