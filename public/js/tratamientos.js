'use strict';
/**
 * tratamientos.js — Módulo de Tratamientos (TRA-01 … TRA-04)
 * Depende de app.js (abrirModal, cerrarModal, mostrarToast, guardarJSON, window.ODENT).
 */

/* ══════════════════════════ Estado y utilidades ══════════════════════════ */

const TRE_ESTADOS = {
    pendiente:  { texto: 'Pendiente',  icono: 'hourglass-split' },
    proceso:    { texto: 'En proceso', icono: 'activity' },
    finalizado: { texto: 'Finalizado', icono: 'check-circle' },
    cancelado:  { texto: 'Cancelado',  icono: 'x-circle' },
};
const TRE_SUPERIOR = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
const TRE_INFERIOR = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

let _treLista = [];            // última lista cargada
let _treActual = null;         // tratamiento abierto en un modal
let _treCatalogoListo = false; // pacientes ya cargados en el select
let _treTimerBusqueda = null;

function _tre(id) { return document.getElementById(id); }

function treEsc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function treBase() { return (window.ODENT && window.ODENT.base) || ''; }

/** 'YYYY-MM-DD' → 'dd/mm/yyyy' (sin pasar por Date para evitar desfases de zona horaria). */
function treFecha(iso) {
    if (!iso) return '—';
    const m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : iso;
}

function treFechaHora(iso) {
    if (!iso) return '—';
    const m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : iso;
}

function treColones(n) {
    return '₡' + Number(n || 0).toLocaleString('es-CR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function treHoyISO() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function treBadge(estado) {
    const e = TRE_ESTADOS[estado] || { texto: estado, icono: 'dot' };
    return `<span class="badge-estado badge-${treEsc(estado)}"><i class="bi bi-${e.icono}"></i> ${e.texto}</span>`;
}

/** Frase amable según el avance y la fecha estimada. */
function treFrase(t) {
    if (t.estado === 'cancelado')  return 'Este plan fue cancelado.';
    if (t.estado === 'finalizado') return '¡Tratamiento terminado! 🎉';
    if (t.estado === 'pendiente' && t.sesiones_realizadas === 0) return 'Listo para comenzar.';

    const falta = t.sesiones_total - t.sesiones_realizadas;
    const base = falta <= 0 ? 'Todas las sesiones realizadas.'
        : falta === 1 ? 'Solo falta 1 sesión.'
        : `Faltan ${falta} sesiones.`;

    const hoy = new Date(treHoyISO() + 'T00:00:00');
    const fin = new Date(t.fecha_fin_estimada + 'T00:00:00');
    const dias = Math.round((fin - hoy) / 86400000);
    if (dias < 0)  return `${base} Superó la fecha estimada hace ${-dias} día${dias === -1 ? '' : 's'}.`;
    if (dias === 0) return `${base} La fecha estimada es hoy.`;
    if (dias <= 7) return `${base} Termina en ${dias} día${dias === 1 ? '' : 's'}.`;
    return base;
}

function treChipsPiezas(piezas) {
    if (!piezas || !piezas.length) return '<span class="tre-sin-datos">Sin piezas</span>';
    return piezas.map(p => `<span class="tre-chip-pieza">${p}</span>`).join('');
}

/** Llamada GET con manejo uniforme de errores. Devuelve el JSON o null. */
async function treGet(accion, params = {}) {
    const qs = new URLSearchParams({ accion, ...params }).toString();
    try {
        const res = await fetch(`${treBase()}/index.php?${qs}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = treBase() + '/index.php?accion=logout&motivo=inactividad', 1500);
            return null;
        }
        const data = await res.json();
        if (data && data.ok === false) {
            mostrarToast(data.error || 'No se pudo completar la consulta.', 'peligro', 6000);
            return null;
        }
        return data;
    } catch (e) {
        mostrarToast(navigator.onLine ? 'No pudimos cargar la información. Intente de nuevo.' : 'Sin conexión a internet.', 'peligro', 6000);
        return null;
    }
}

/* ══════════════════════════ Lista principal (TRA-02 esc. 1) ══════════════════════════ */

function treProgreso(t) {
    return `
        <div class="tratamiento-header"><span>${t.sesiones_realizadas} de ${t.sesiones_total} sesiones</span><span>${t.progreso}%</span></div>
        <div class="barra-progreso"><div class="barra-relleno ${t.estado === 'cancelado' ? 'tre-cancelado' : ''}" style="width:${t.progreso}%;"></div></div>`;
}

function treTarjeta(t) {
    const abierto = t.estado === 'pendiente' || t.estado === 'proceso';
    return `
    <div class="tarjeta tre-tarjeta">
        <div class="tre-tarjeta-cab">
            <div>
                <h4>${treEsc(t.nombre)}</h4>
                <p class="tre-paciente"><i class="bi bi-person"></i> ${treEsc(t.paciente)}</p>
            </div>
            ${treBadge(t.estado)}
        </div>
        ${treProgreso(t)}
        <p class="tre-frase">${treEsc(treFrase(t))}</p>
        <div class="tre-piezas">${treChipsPiezas(t.piezas)}</div>
        <div class="tre-meta">
            <div><strong>Inicio</strong><br>${treFecha(t.fecha_inicio)}</div>
            <div><strong>Fin estimado</strong><br>${treFecha(t.fecha_fin_estimada)}</div>
            <div><strong>Costo</strong><br>${treColones(t.costo_estimado)}</div>
            <div><strong>Responsable</strong><br>${treEsc(t.odontologo)}</div>
        </div>
        ${t.id_expediente ? '<p class="tre-asoc"><i class="bi bi-link-45deg"></i> Vinculado al expediente y al odontograma</p>' : ''}
        <div class="tre-acciones">
            <button class="btn-outline-odent" onclick="treAbrirDetalle(${t.id_tratamiento})"><i class="bi bi-eye"></i> Ver detalle</button>
            <button class="btn-outline-odent" onclick="treAbrirEstado(${t.id_tratamiento})"><i class="bi bi-pencil-square"></i> Estado</button>
            ${abierto || !t.id_expediente ? `<button class="btn-outline-odent" onclick="treAbrirAsociar(${t.id_tratamiento})"><i class="bi bi-diagram-3"></i> ${t.id_expediente ? 'Reasociar' : 'Asociar'}</button>` : ''}
        </div>
    </div>`;
}

function treRenderLista(lista) {
    const cont = _tre('trat-lista');
    if (!cont) return;
    const cuenta = _tre('trat-contador');

    if (!lista.length) {
        const filtrando = (_tre('trat-buscar')?.value.trim() || _tre('trat-filtro-estado')?.value);
        cont.innerHTML = `
            <div class="tre-vacio">
                <i class="bi bi-clipboard2-pulse"></i>
                <h4>${filtrando ? 'No encontramos tratamientos con ese criterio' : 'Todavía no hay planes de tratamiento'}</h4>
                <p>${filtrando ? 'Pruebe con otro nombre o quite el filtro de estado.' : 'Cree el primer plan para empezar a dar seguimiento a sus pacientes.'}</p>
                ${filtrando ? '' : '<button class="btn-odent" onclick="treAbrirNuevo()"><i class="bi bi-plus-lg"></i> Crear el primer plan</button>'}
            </div>`;
        if (cuenta) cuenta.textContent = '';
        return;
    }
    cont.innerHTML = lista.map(treTarjeta).join('');
    if (cuenta) cuenta.textContent = `${lista.length} tratamiento${lista.length === 1 ? '' : 's'}`;
}

async function treCargar() {
    const cont = _tre('trat-lista');
    if (!cont) return;
    cont.innerHTML = '<div class="tre-cargando"><span class="spinner-mini"></span> Cargando tratamientos…</div>';

    const data = await treGet('tratamientos.listar', {
        q: _tre('trat-buscar')?.value.trim() || '',
        estado: _tre('trat-filtro-estado')?.value || '',
    });
    if (!data) {
        cont.innerHTML = '<div class="tre-vacio"><i class="bi bi-wifi-off"></i><h4>No pudimos cargar los tratamientos</h4><button class="btn-outline-odent" onclick="treCargar()">Reintentar</button></div>';
        return;
    }
    _treLista = Array.isArray(data) ? data : [];
    treRenderLista(_treLista);
}

function treBuscar() { clearTimeout(_treTimerBusqueda); treCargar(); }
function treBuscarDiferido() { clearTimeout(_treTimerBusqueda); _treTimerBusqueda = setTimeout(treCargar, 350); }

function treLimpiarFiltros() {
    if (_tre('trat-buscar')) _tre('trat-buscar').value = '';
    if (_tre('trat-filtro-estado')) _tre('trat-filtro-estado').value = '';
    treCargar();
}

/* ══════════════════════════ Selector de piezas (reutilizable) ══════════════════════════ */

function treRenderPicker(idContenedor, seleccionadas = []) {
    const sel = new Set(seleccionadas.map(Number));
    const fila = nums => nums.map(n =>
        `<div class="diente tre-pieza ${sel.has(n) ? 'seleccionado' : ''}" data-pieza="${n}" role="button" tabindex="0"
              onclick="treTogglePieza(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();treTogglePieza(this);}">
            <i class="bi bi-circle-fill"></i><span>${n}</span></div>`).join('');

    _tre(idContenedor).innerHTML = `
        <p class="tre-picker-etq">MAXILAR SUPERIOR</p>
        <div class="odontograma-fila">${fila(TRE_SUPERIOR)}</div>
        <div class="tre-picker-linea"></div>
        <div class="odontograma-fila">${fila(TRE_INFERIOR)}</div>
        <p class="tre-picker-etq">MAXILAR INFERIOR</p>`;
    treActualizarResumenPiezas(idContenedor);
}

function treTogglePieza(el) {
    el.classList.toggle('seleccionado');
    const cont = el.closest('.tre-picker');
    if (cont) treActualizarResumenPiezas(cont.id);
}

function treLeerPiezas(idContenedor) {
    return [...document.querySelectorAll(`#${idContenedor} .tre-pieza.seleccionado`)]
        .map(el => Number(el.dataset.pieza)).sort((a, b) => a - b);
}

function treActualizarResumenPiezas(idContenedor) {
    const resumen = _tre(idContenedor + '-resumen');
    if (!resumen) return;
    const p = treLeerPiezas(idContenedor);
    resumen.innerHTML = p.length
        ? `Piezas seleccionadas (${p.length}): ${treChipsPiezas(p)}`
        : 'Haga clic en las piezas dentales involucradas.';
}

/* ══════════════════════════ Nuevo plan (TRA-01) ══════════════════════════ */

async function treCargarCatalogo() {
    if (_treCatalogoListo) return true;
    const data = await treGet('tratamientos.catalogos');
    if (!data) return false;
    const sel = _tre('nt-paciente');
    sel.innerHTML = '<option value="">Seleccione un paciente</option>' +
        (data.pacientes || []).map(p =>
            `<option value="${p.id_paciente}">${treEsc(p.nombre_completo)} — ${treEsc(p.identificacion || 's/ced.')}</option>`).join('');
    _treCatalogoListo = true;
    if (!(data.pacientes || []).length) {
        mostrarToast('Aún no hay pacientes con perfil clínico. Complételo desde Expedientes.', 'aviso', 6000);
    }
    return true;
}

async function treAbrirNuevo(idPacientePreseleccionado = null) {
    _tre('nt-paciente').value = '';
    ['nt-nombre', 'nt-descripcion', 'nt-costo'].forEach(id => _tre(id).value = '');
    _tre('nt-sesiones').value = 1;
    _tre('nt-inicio').value = treHoyISO();
    _tre('nt-fin').value = '';
    _tre('nt-estado').value = 'pendiente';
    treRenderPicker('nt-picker', []);
    abrirModal('modal-nuevo-tratamiento');

    if (await treCargarCatalogo() && idPacientePreseleccionado) {
        _tre('nt-paciente').value = String(idPacientePreseleccionado);
    }
    _tre('nt-nombre').focus();
}

async function treGuardarNuevo() {
    const datos = {
        id_paciente:        Number(_tre('nt-paciente').value) || 0,
        nombre:             _tre('nt-nombre').value.trim(),
        descripcion:        _tre('nt-descripcion').value.trim(),
        costo_estimado:     _tre('nt-costo').value.trim(),
        fecha_inicio:       _tre('nt-inicio').value,
        fecha_fin_estimada: _tre('nt-fin').value,
        estado:             _tre('nt-estado').value,
        sesiones_total:     Number(_tre('nt-sesiones').value) || 0,
        piezas:             treLeerPiezas('nt-picker'),
    };

    // Escenario 2: avisar de inmediato qué falta (el servidor vuelve a validar)
    const faltan = [];
    if (!datos.id_paciente)       faltan.push('paciente');
    if (!datos.nombre)            faltan.push('nombre del tratamiento');
    if (!datos.piezas.length)     faltan.push('piezas dentales');
    if (!datos.descripcion)       faltan.push('descripción');
    if (datos.costo_estimado === '') faltan.push('costo estimado');
    if (!datos.fecha_inicio)      faltan.push('fecha de inicio');
    if (!datos.fecha_fin_estimada) faltan.push('fecha estimada de finalización');
    if (faltan.length) {
        return mostrarToast('Para guardar el tratamiento falta completar: ' + faltan.join(', ') + '.', 'aviso', 6000);
    }
    if (datos.fecha_fin_estimada < datos.fecha_inicio) {
        return mostrarToast('La fecha estimada de finalización no puede ser anterior a la de inicio.', 'aviso', 6000);
    }

    await guardarJSON('tratamientos.crear', datos, {
        boton: _tre('nt-btn-guardar'),
        cerrarModal: 'modal-nuevo-tratamiento',
        exito: 'Listo, el plan de tratamiento quedó guardado.',
        recargar: ['inicio'],
        alExito: () => treCargar(),
    });
}

/** Escenario 3: descarta lo escrito y vuelve al listado sin guardar. */
function treCancelarNuevo() {
    cerrarModal('modal-nuevo-tratamiento');
}

/* ══════════════════════════ Detalle e historial del plan ══════════════════════════ */

function treLineaHistorial(h) {
    const cambio = h.estado_anterior
        ? `${(TRE_ESTADOS[h.estado_anterior] || {}).texto || h.estado_anterior} → <strong>${(TRE_ESTADOS[h.estado_nuevo] || {}).texto || h.estado_nuevo}</strong>`
        : `Creado como <strong>${(TRE_ESTADOS[h.estado_nuevo] || {}).texto || h.estado_nuevo}</strong>`;
    return `
        <div class="tre-linea">
            <div class="tre-linea-punto"></div>
            <div>
                <div class="tre-linea-cab">${cambio} · ${h.sesiones_realizadas} sesión(es)</div>
                <div class="tre-linea-sub">${treFechaHora(h.created_at)} · ${treEsc(h.odontologo)}</div>
                ${h.observaciones ? `<div class="tre-linea-obs">${treEsc(h.observaciones)}</div>` : ''}
            </div>
        </div>`;
}

async function treAbrirDetalle(id) {
    const data = await treGet('tratamientos.detalle', { id });
    if (!data) return;
    const t = data.tratamiento;
    _treActual = t;

    _tre('td-titulo').textContent = t.nombre;
    _tre('td-cuerpo').innerHTML = `
        <div class="tre-detalle-cab">
            <div><p class="tre-paciente"><i class="bi bi-person"></i> ${treEsc(t.paciente)} <small>· ${treEsc(t.paciente_cedula || '')}</small></p></div>
            ${treBadge(t.estado)}
        </div>
        ${treProgreso(t)}
        <p class="tre-frase">${treEsc(treFrase(t))}</p>

        <div class="tre-meta tre-meta-4">
            <div><strong>Inicio</strong><br>${treFecha(t.fecha_inicio)}</div>
            <div><strong>Fin estimado</strong><br>${treFecha(t.fecha_fin_estimada)}</div>
            <div><strong>Costo estimado</strong><br>${treColones(t.costo_estimado)}</div>
            <div><strong>Responsable</strong><br>${treEsc(t.odontologo)}</div>
        </div>

        <h5 class="tre-sub">Piezas dentales</h5>
        <div class="tre-piezas">${treChipsPiezas(t.piezas)}</div>
        <p class="tre-asoc">${t.id_expediente
            ? `<i class="bi bi-link-45deg"></i> Vinculado al expediente${t.asociado_at ? ' el ' + treFechaHora(t.asociado_at) : ''}`
            : '<i class="bi bi-link-45deg"></i> Aún no está vinculado al expediente ni al odontograma'}</p>

        <h5 class="tre-sub">Descripción</h5>
        <p class="tre-texto">${treEsc(t.descripcion)}</p>
        ${t.observaciones ? `<h5 class="tre-sub">Última observación</h5><p class="tre-texto">${treEsc(t.observaciones)}</p>` : ''}

        <h5 class="tre-sub">Historial de cambios</h5>
        <div class="tre-timeline">${(data.historial || []).map(treLineaHistorial).join('') || '<p class="tre-sin-datos">Sin movimientos.</p>'}</div>`;

    const acciones = _tre('td-acciones');
    if (acciones) acciones.style.display = window.TRE_PUEDE_EDITAR === false ? 'none' : '';
    abrirModal('modal-trat-detalle');
}

function treDesdeDetalle(accion) {
    if (!_treActual) return;
    const id = _treActual.id_tratamiento;
    cerrarModal('modal-trat-detalle');
    if (accion === 'estado') treAbrirEstado(id);
    if (accion === 'asociar') treAbrirAsociar(id);
}

/* ══════════════════════════ Actualizar estado (TRA-02 esc. 2 y 3) ══════════════════════════ */

async function treAbrirEstado(id) {
    let t = _treLista.find(x => x.id_tratamiento === id);
    if (!t) {
        const d = await treGet('tratamientos.detalle', { id });
        if (!d) return;
        t = d.tratamiento;
    }
    _treActual = t;
    _tre('te-id').value = t.id_tratamiento;
    _tre('te-titulo').textContent = t.nombre;
    _tre('te-paciente').textContent = t.paciente;
    _tre('te-estado').value = t.estado;
    _tre('te-sesiones').value = t.sesiones_realizadas;
    _tre('te-sesiones').max = t.sesiones_total;
    _tre('te-sesiones-total').textContent = t.sesiones_total;
    _tre('te-observaciones').value = '';
    treEstadoCambio();
    abrirModal('modal-trat-estado');
}

/** Ajusta la ayuda y los campos según el estado elegido. */
function treEstadoCambio() {
    const estado = _tre('te-estado').value;
    const total = Number(_tre('te-sesiones').max) || 1;
    const campo = _tre('te-sesiones');
    const ayuda = _tre('te-ayuda');

    if (estado === 'finalizado') {
        campo.value = total;
        campo.disabled = true;
        ayuda.textContent = 'Al finalizar se marcan todas las sesiones como realizadas. Agregue una observación de cierre.';
    } else if (estado === 'cancelado') {
        campo.disabled = false;
        ayuda.textContent = 'Cuéntenos el motivo de la cancelación en las observaciones (es obligatorio).';
    } else {
        campo.disabled = false;
        ayuda.textContent = estado === 'pendiente'
            ? 'El plan queda a la espera de comenzar.'
            : 'Registre cuántas sesiones lleva el paciente.';
    }
}

async function treGuardarEstado() {
    const estado = _tre('te-estado').value;
    const obs = _tre('te-observaciones').value.trim();
    if ((estado === 'cancelado' || estado === 'finalizado') && !obs) {
        return mostrarToast('Agregue una observación para ' + (estado === 'cancelado' ? 'cancelar' : 'finalizar') + ' el tratamiento.', 'aviso', 6000);
    }
    await guardarJSON('tratamientos.estado', {
        id_tratamiento: Number(_tre('te-id').value),
        estado,
        sesiones_realizadas: Number(_tre('te-sesiones').value) || 0,
        observaciones: obs,
    }, {
        boton: _tre('te-btn-guardar'),
        cerrarModal: 'modal-trat-estado',
        exito: estado === 'finalizado' ? '¡Tratamiento finalizado! Buen trabajo.' : 'Estado del tratamiento actualizado.',
        recargar: ['inicio'],
        alExito: () => { treCargar(); treRefrescarOdontogramaSiAbierto(); },
    });
}

/** TRA-02 esc. 3: descarta los cambios, la información anterior se conserva. */
function treCancelarEstado() {
    cerrarModal('modal-trat-estado');
}

/* ══════════════════════════ Asociar a expediente y odontograma (TRA-03) ══════════════════════════ */

async function treAbrirAsociar(id) {
    let t = _treLista.find(x => x.id_tratamiento === id);
    if (!t) {
        const d = await treGet('tratamientos.detalle', { id });
        if (!d) return;
        t = d.tratamiento;
    }
    _treActual = t;
    _tre('ta-id').value = t.id_tratamiento;
    _tre('ta-titulo').textContent = t.nombre;
    _tre('ta-paciente').textContent = t.paciente;
    treRenderPicker('ta-picker', t.piezas || []);
    abrirModal('modal-trat-asociar');
}

async function treGuardarAsociar() {
    const piezas = treLeerPiezas('ta-picker');
    // Escenario 3: sin piezas no se asocia
    if (!piezas.length) {
        return mostrarToast('Debe seleccionar al menos una pieza dental antes de asociar el tratamiento.', 'aviso', 6000);
    }
    await guardarJSON('tratamientos.asociar', {
        id_tratamiento: Number(_tre('ta-id').value),
        piezas,
    }, {
        boton: _tre('ta-btn-guardar'),
        cerrarModal: 'modal-trat-asociar',
        exito: 'Tratamiento asociado al expediente y marcado en el odontograma.',
        alExito: () => { treCargar(); treRefrescarOdontogramaSiAbierto(); },
    });
}

/* ══════════════════════════ Historial del paciente (TRA-04) ══════════════════════════ */

let _treHistPaciente = null;   // { id_paciente, nombre }

/** Se invoca desde el botón "Ver Historial de Tratamientos" del expediente. */
function treAbrirHistorial() {
    const pac = (typeof _expPacienteActual !== 'undefined') ? _expPacienteActual : null;
    if (!pac || !pac.id_paciente) {
        return mostrarToast('Primero abra el expediente de un paciente.', 'aviso');
    }
    _treHistPaciente = { id_paciente: pac.id_paciente, nombre: pac.nombre_completo };
    _tre('th-paciente').textContent = pac.nombre_completo || '';
    _tre('th-filtro').value = '';
    abrirModal('modal-trat-historial');
    treCargarHistorial();
}

async function treCargarHistorial() {
    if (!_treHistPaciente) return;
    const cont = _tre('th-lista');
    cont.innerHTML = '<div class="tre-cargando"><span class="spinner-mini"></span> Cargando historial…</div>';

    const data = await treGet('tratamientos.historial', {
        id_paciente: _treHistPaciente.id_paciente,
        estado: _tre('th-filtro').value,
    });
    if (!data) { cont.innerHTML = '<p class="tre-sin-datos">No se pudo cargar el historial.</p>'; return; }

    if (!data.length) {
        const filtrado = !!_tre('th-filtro').value;
        cont.innerHTML = `<div class="tre-vacio tre-vacio-chico"><i class="bi bi-clipboard2"></i>
            <h4>${filtrado ? 'No hay tratamientos con ese estado' : 'Este paciente no cuenta con tratamientos registrados'}</h4></div>`;
        return;
    }
    cont.innerHTML = `<div class="tabla-scroll"><table class="tabla-odent">
        <thead><tr><th>Tratamiento</th><th>Fechas</th><th>Odontólogo</th><th>Sesiones</th><th>Estado final</th></tr></thead>
        <tbody>${data.map(t => `
            <tr>
                <td><strong>${treEsc(t.nombre)}</strong><br><span class="tre-piezas">${treChipsPiezas(t.piezas)}</span></td>
                <td>${treFecha(t.fecha_inicio)}<br><small>→ ${treFecha(t.fecha_fin_estimada)}</small></td>
                <td>${treEsc(t.odontologo)}</td>
                <td>${t.sesiones_realizadas}/${t.sesiones_total}</td>
                <td>${treBadge(t.estado)}</td>
            </tr>`).join('')}
        </tbody></table></div>`;
}

/** Desde el historial se puede saltar a crear un plan para ese mismo paciente. */
function treNuevoDesdeHistorial() {
    if (!_treHistPaciente) return;
    const id = _treHistPaciente.id_paciente;
    cerrarModal('modal-trat-historial');
    mostrarPagina('tratamientos');
    treAbrirNuevo(id);
}

/* ══════════════════════════ Odontograma (TRA-03 esc. 2) ══════════════════════════ */

let _treMarcasPiezas = {};

/** Pinta en cada diente un indicador del tratamiento asociado. */
async function treOdontograma(idExpediente) {
    if (!idExpediente) return;
    const data = await treGet('tratamientos.odontograma', { id_expediente: idExpediente });
    if (!data) return;
    _treMarcasPiezas = data.piezas || {};

    document.querySelectorAll('#pagina-odontograma .diente').forEach(el => {
        el.classList.remove('con-trat', 'trat-pendiente', 'trat-proceso', 'trat-finalizado');
        el.querySelector('.tre-marca')?.remove();
        el.removeAttribute('data-trat-titulo');
    });

    Object.entries(_treMarcasPiezas).forEach(([pieza, trats]) => {
        const el = document.getElementById('diente-' + pieza);
        if (!el || !trats.length) return;
        // si hay varios, manda el más "activo": proceso > pendiente > finalizado
        const prioridad = { proceso: 3, pendiente: 2, finalizado: 1 };
        const principal = [...trats].sort((a, b) => (prioridad[b.estado] || 0) - (prioridad[a.estado] || 0))[0];

        el.classList.add('con-trat', 'trat-' + principal.estado);
        const marca = document.createElement('span');
        marca.className = 'tre-marca';
        marca.textContent = principal.estado === 'finalizado' ? '✓' : principal.progreso + '%';
        el.appendChild(marca);
        el.title = trats.map(t => `${t.nombre} — ${(TRE_ESTADOS[t.estado] || {}).texto || t.estado} (${t.progreso}%)`).join('\n');
    });
}

function treRefrescarOdontogramaSiAbierto() {
    if (typeof _odoExpediente !== 'undefined' && _odoExpediente && _tre('pagina-odontograma')?.classList.contains('activa')) {
        treOdontograma(_odoExpediente.id_expediente);
    }
}

/* ══════════════════════════ Arranque ══════════════════════════ */

// Cada vez que se entra a la pestaña "Tratamientos" se recarga la lista.
(function () {
    const original = window.mostrarPagina;
    if (typeof original !== 'function') return;
    window.mostrarPagina = function (slug) {
        original.apply(this, arguments);
        if (slug === 'tratamientos' && _tre('trat-lista')) treCargar();
    };
})();
