'use strict';
/**
 * asistente_reportes.js — ASI-04
 * Consulta de los reportes que los pacientes registran con el asistente inteligente.
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

let _arepLista = [];

function _ar(id) { return document.getElementById(id); }

function arEsc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function arBase() {
    return (window.ODENT && window.ODENT.base) || (typeof BASE !== 'undefined' ? BASE : '');
}

function arBadge(prioridad) {
    const p = AREP_PRIORIDADES[prioridad] || { texto: prioridad, icono: 'dot' };
    return `<span class="arep-prio arep-prio-${arEsc(prioridad)}"><i class="bi bi-${p.icono}"></i> ${arEsc(p.texto)}</span>`;
}

/** GET con manejo uniforme de errores. Devuelve el JSON o null (ya avisó al usuario). */
async function arGet(accion, params = {}) {
    const qs = new URLSearchParams({ accion, ...params }).toString();
    try {
        const res = await fetch(`${arBase()}/index.php?${qs}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = arBase() + '/index.php?accion=logout&motivo=inactividad', 1500);
            return null;
        }
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

/* ══════════════════════════ Filtros ══════════════════════════ */

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

/* ══════════════════════════ Listado (ASI-04 esc. 1 y 3) ══════════════════════════ */

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
                    <td>${arBadge(r.prioridad)}</td>
                    <td><button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"
                                onclick="event.stopPropagation(); asiRepDetalle(${r.id})"><i class="bi bi-eye"></i> Ver</button></td>
                </tr>`).join('')}
            </tbody>
        </table>
        </div>`;
}

/* ══════════════════════════ Detalle (ASI-04 esc. 2) ══════════════════════════ */

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
        </p>`;

    abrirModal('modal-arep-detalle');
}
