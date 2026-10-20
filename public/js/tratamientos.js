'use strict';
/**
 * tratamientos.js — Módulo de tratamientos (TRA-01 … TRA-04)
 * Depende de app.js: abrirModal, cerrarModal, mostrarToast, guardarJSON, window.ODENT
 */

/* ══════════════════════════ Constantes y utilidades ══════════════════════════ */

const TR_ESTADOS = {
    pendiente:  { texto: 'Pendiente',  icono: 'hourglass-split' },
    en_proceso: { texto: 'En proceso', icono: 'activity' },
    finalizado: { texto: 'Finalizado', icono: 'check-circle' },
    cancelado:  { texto: 'Cancelado',  icono: 'x-circle' },
};
const TR_SUPERIOR = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
const TR_INFERIOR = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

let _trLista = [];            // última lista cargada en la pantalla de tratamientos
let _trMarcas = {};           // tratamientos por pieza del odontograma abierto
let _trHistPaciente = null;   // paciente del historial abierto desde el expediente

function _$(id) { return document.getElementById(id); }

function trEsc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function trBase() {
    return (window.ODENT && window.ODENT.base) || (typeof BASE !== 'undefined' ? BASE : '');
}

/** 'YYYY-MM-DD' → 'dd/mm/yyyy' sin pasar por Date (evita desfases de zona horaria) */
function trFecha(iso) {
    const m = String(iso ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : '—';
}

function trHoyISO() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Días desde hoy hasta una fecha 'YYYY-MM-DD' (negativo si ya pasó) */
function trDiasHasta(iso) {
    const hoy = new Date(trHoyISO() + 'T00:00:00');
    const fin = new Date(iso + 'T00:00:00');
    return Math.round((fin - hoy) / 86400000);
}

function trEstadoPill(estado) {
    const e = TR_ESTADOS[estado] || { texto: estado, icono: 'dot' };
    return `<span class="tr-estado tr-estado-${trEsc(estado)}"><i class="bi bi-${e.icono}"></i> ${e.texto}</span>`;
}

/** Frase amable según el estado, el avance y la fecha estimada */
function trFrase(t) {
    if (t.estado === 'cancelado')  return 'Este plan fue cancelado.';
    if (t.estado === 'finalizado') return '¡Tratamiento terminado! Buen trabajo.';

    const dias = trDiasHasta(t.fecha_fin_estimada);
    if (t.estado === 'pendiente') {
        const paraInicio = trDiasHasta(t.fecha_inicio);
        if (paraInicio > 1)  return `Listo para comenzar el ${trFecha(t.fecha_inicio)}.`;
        if (paraInicio === 1) return 'Listo para comenzar mañana.';
        return 'Pendiente de iniciar.';
    }
    if (dias < 0)   return `Superó la fecha estimada hace ${-dias} día${dias === -1 ? '' : 's'}.`;
    if (dias === 0) return 'La fecha estimada de finalización es hoy.';
    if (dias <= 7)  return `Va por el ${t.progreso}% y termina en ${dias} día${dias === 1 ? '' : 's'}.`;
    return `Va avanzando: ${t.progreso}% completado.`;
}

function trChips(piezas) {
    if (!piezas || !piezas.length) return '<span class="tr-sin-datos">Sin piezas</span>';
    return piezas.map(p => `<span class="tr-chip">${p}</span>`).join('');
}

/** GET con manejo uniforme de errores. Devuelve el JSON o null (ya avisó al usuario). */
async function trGet(accion, params = {}) {
    const qs = new URLSearchParams({ accion, ...params }).toString();
    try {
        const res = await fetch(`${trBase()}/index.php?${qs}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = trBase() + '/index.php?accion=logout&motivo=inactividad', 1500);
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

/* ══════════════════════════ Lista principal (TRA-02 esc. 1) ══════════════════════════ */

function trTarjeta(t) {
    const cancelado = t.estado === 'cancelado';
    return `
    <div class="tarjeta tr-card ${cancelado ? 'cancelado' : ''}">
        <div class="tr-card-cab">
            <div>
                <h4>${trEsc(t.nombre)}</h4>
                <p class="tr-paciente"><i class="bi bi-person"></i> ${trEsc(t.paciente)}</p>
            </div>
            ${trEstadoPill(t.estado)}
        </div>
        <div>
            <div class="tr-progreso-cab"><span>Avance</span><strong>${t.progreso}%</strong></div>
            <div class="barra-progreso"><div class="barra-relleno" style="width:${t.progreso}%;"></div></div>
        </div>
        <p class="tr-frase">${trEsc(trFrase(t))}</p>
        <div class="tr-piezas">${trChips(t.piezas)}</div>
        <div class="tr-meta">
            <div><strong>Inicio</strong><br>${trFecha(t.fecha_inicio)}</div>
            <div><strong>Fin estimado</strong><br>${trFecha(t.fecha_fin_estimada)}</div>
            <div><strong>Costo estimado</strong><br>${trEsc(t.costo_fmt)}</div>
            <div><strong>Responsable</strong><br>${trEsc(t.odontologo)}</div>
        </div>
        <p class="tr-asoc ${t.asociado ? '' : 'pendiente'}"><i class="bi bi-link-45deg"></i>
            ${t.asociado ? 'Vinculado al expediente y al odontograma' : 'Aún sin vincular al odontograma'}</p>
        <div class="tr-acciones">
            <button class="btn-outline-odent" onclick="trAbrirDetalle(${t.id})"><i class="bi bi-eye"></i> Ver detalle</button>
            <button class="btn-outline-odent" onclick="trAbrirEstado(${t.id})"><i class="bi bi-pencil-square"></i> Actualizar estado</button>
            ${cancelado ? '' : `<button class="btn-outline-odent" onclick="trAbrirAsociar(${t.id})"><i class="bi bi-diagram-3"></i> ${t.asociado ? 'Reasociar' : 'Asociar'}</button>`}
        </div>
    </div>`;
}

function trRenderResumen(lista) {
    const cont = _$('tr-resumen');
    if (!cont) return;
    if (!lista.length) { cont.innerHTML = ''; return; }

    const cuenta = { pendiente: 0, en_proceso: 0, finalizado: 0, cancelado: 0 };
    lista.forEach(t => { if (t.estado in cuenta) cuenta[t.estado]++; });

    cont.innerHTML =
        `<span class="tr-resumen-chip"><strong>${lista.length}</strong> en total</span>` +
        Object.keys(cuenta).filter(k => cuenta[k] > 0).map(k =>
            `<span class="tr-resumen-chip"><strong>${cuenta[k]}</strong> ${TR_ESTADOS[k].texto.toLowerCase()}</span>`).join('');
}

function trRenderLista(lista) {
    const cont = _$('tr-lista');
    if (!cont) return;

    if (!lista.length) {
        const filtrando = (_$('tr-buscar')?.value.trim() || _$('tr-filtro-estado')?.value);
        cont.innerHTML = `
            <div class="tr-vacio">
                <i class="bi bi-clipboard2-pulse"></i>
                <h4>${filtrando ? 'No encontramos tratamientos con ese criterio' : 'Todavía no hay planes de tratamiento'}</h4>
                <p>${filtrando ? 'Pruebe con otro nombre o quite el filtro de estado.' : 'Cree el primer plan para empezar a dar seguimiento a sus pacientes.'}</p>
                ${filtrando ? '' : '<button class="btn-odent" onclick="trAbrirNuevo()"><i class="bi bi-plus-lg"></i> Crear el primer plan</button>'}
            </div>`;
        trRenderResumen([]);
        return;
    }
    cont.innerHTML = lista.map(trTarjeta).join('');
    trRenderResumen(lista);
}

async function trCargar() {
    const cont = _$('tr-lista');
    if (!cont) return;
    cont.innerHTML = '<div class="tr-vacio"><span class="spinner-mini"></span> Cargando los planes de tratamiento…</div>';

    const data = await trGet('tratamientos.listar', {
        q: _$('tr-buscar')?.value.trim() || '',
        estado: _$('tr-filtro-estado')?.value || '',
    });
    if (!data) {
        cont.innerHTML = '<div class="tr-vacio"><i class="bi bi-wifi-off"></i><h4>No pudimos cargar los tratamientos</h4><button class="btn-outline-odent" onclick="trCargar()">Reintentar</button></div>';
        return;
    }
    _trLista = data.data || [];
    trRenderLista(_trLista);
}

function trLimpiarFiltros() {
    if (_$('tr-buscar')) _$('tr-buscar').value = '';
    if (_$('tr-filtro-estado')) _$('tr-filtro-estado').value = '';
    trCargar();
}

/* ══════════════════════════ Selector de piezas (reutilizable) ══════════════════════════ */

function trRenderPicker(idContenedor, seleccionadas = []) {
    const sel = new Set(seleccionadas.map(Number));
    const fila = nums => nums.map(n =>
        `<button type="button" class="tr-pieza ${sel.has(n) ? 'activa' : ''}" data-pieza="${n}"
                 aria-pressed="${sel.has(n)}" onclick="trTogglePieza(this)">${n}</button>`).join('');

    _$(idContenedor).innerHTML = `
        <p class="tr-picker-etq">MAXILAR SUPERIOR</p>
        <div class="tr-picker-fila">${fila(TR_SUPERIOR)}</div>
        <div class="tr-picker-linea"></div>
        <div class="tr-picker-fila">${fila(TR_INFERIOR)}</div>
        <p class="tr-picker-etq" style="margin:8px 0 0;">MAXILAR INFERIOR</p>`;
    trActualizarResumenPiezas(idContenedor);
}

function trTogglePieza(el) {
    const activa = el.classList.toggle('activa');
    el.setAttribute('aria-pressed', activa);
    const cont = el.closest('.tr-picker');
    if (cont) trActualizarResumenPiezas(cont.id);
}

function trLeerPiezas(idContenedor) {
    return [...document.querySelectorAll(`#${idContenedor} .tr-pieza.activa`)]
        .map(el => Number(el.dataset.pieza)).sort((a, b) => a - b);
}

function trActualizarResumenPiezas(idContenedor) {
    const resumen = _$(idContenedor + '-resumen');
    if (!resumen) return;
    const p = trLeerPiezas(idContenedor);
    resumen.innerHTML = p.length
        ? `Piezas seleccionadas (${p.length}): ${trChips(p)}`
        : 'Toque las piezas dentales involucradas en el tratamiento.';
}

/** Muestra u oculta el control de avance según el estado elegido ('nt' = nuevo, 'te' = estado) */
function trAlCambiarEstado(prefijo) {
    const grupo = _$(prefijo + '-progreso-grupo');
    if (grupo) grupo.style.display = _$(prefijo + '-estado').value === 'en_proceso' ? '' : 'none';
}

/* ══════════════════════════ Nuevo plan (TRA-01) ══════════════════════════ */

async function trCargarPacientes(idPreseleccionado = null) {
    const data = await trGet('tratamientos.catalogos');
    if (!data) return;

    const pacientes = data.pacientes || [];
    _$('nt-paciente').innerHTML = '<option value="">Seleccione un paciente</option>' +
        pacientes.map(p => `<option value="${p.id}">${trEsc(p.nombre)} — ${trEsc(p.cedula || 's/ced.')}</option>`).join('');

    if (!pacientes.length) {
        mostrarToast('Todavía no hay pacientes activos. Regístrelos desde Usuarios con el rol de paciente.', 'info', 6000);
    }
    if (idPreseleccionado) _$('nt-paciente').value = String(idPreseleccionado);
}

async function trAbrirNuevo(idPaciente = null) {
    ['nt-nombre', 'nt-descripcion', 'nt-costo', 'nt-observaciones'].forEach(id => _$(id).value = '');
    _$('nt-inicio').value = trHoyISO();
    _$('nt-fin').value = '';
    _$('nt-estado').value = 'pendiente';
    _$('nt-progreso').value = 25;
    _$('nt-progreso-valor').textContent = '25%';
    trAlCambiarEstado('nt');
    trRenderPicker('nt-piezas', []);
    abrirModal('modal-nuevo-tratamiento');

    await trCargarPacientes(idPaciente);
    _$('nt-nombre').focus();
}

async function trGuardarNuevo() {
    const estado = _$('nt-estado').value;
    const datos = {
        id_paciente:        Number(_$('nt-paciente').value) || 0,
        nombre:             _$('nt-nombre').value.trim(),
        descripcion:        _$('nt-descripcion').value.trim(),
        costo_estimado:     _$('nt-costo').value.trim(),
        fecha_inicio:       _$('nt-inicio').value,
        fecha_fin_estimada: _$('nt-fin').value,
        estado,
        progreso:           estado === 'en_proceso' ? Number(_$('nt-progreso').value) : null,
        observaciones:      _$('nt-observaciones').value.trim(),
        piezas:             trLeerPiezas('nt-piezas'),
    };

    // Escenario 2: decir de una vez todo lo que falta (el servidor vuelve a validar)
    const faltan = [];
    if (!datos.id_paciente)            faltan.push('paciente');
    if (!datos.nombre)                 faltan.push('nombre del tratamiento');
    if (!datos.piezas.length)          faltan.push('piezas dentales');
    if (!datos.descripcion)            faltan.push('descripción');
    if (datos.costo_estimado === '')   faltan.push('costo estimado');
    if (!datos.fecha_inicio)           faltan.push('fecha de inicio');
    if (!datos.fecha_fin_estimada)     faltan.push('fecha estimada de finalización');
    if (faltan.length) {
        return mostrarToast('Para guardar el plan falta completar: ' + faltan.join(', ') + '.', 'aviso', 6000);
    }
    if (datos.fecha_fin_estimada < datos.fecha_inicio) {
        return mostrarToast('La fecha estimada de finalización no puede ser anterior a la fecha de inicio.', 'aviso', 6000);
    }

    await guardarJSON('tratamientos.crear', datos, {
        boton: _$('nt-btn-guardar'),
        cerrarModal: 'modal-nuevo-tratamiento',
        exito: 'Listo, el plan de tratamiento quedó guardado.',
        recargar: ['inicio'],
        alExito: () => trCargar(),
    });
}

/** Escenario 3: descarta lo escrito y vuelve al listado sin guardar nada */
function trCancelarNuevo() {
    cerrarModal('modal-nuevo-tratamiento');
}

/* ══════════════════════════ Detalle del tratamiento ══════════════════════════ */

function trLineaHistorial(h) {
    return `
        <div class="tr-linea">
            <div class="tr-linea-punto"></div>
            <div class="tr-linea-cab">${trEsc(h.titulo)}</div>
            <div class="tr-linea-sub">${trEsc(h.fecha)} · ${trEsc(h.usuario)}</div>
            ${h.observaciones ? `<div class="tr-linea-obs">${trEsc(h.observaciones)}</div>` : ''}
        </div>`;
}

async function trAbrirDetalle(id) {
    const data = await trGet('tratamientos.detalle', { id });
    if (!data) return;
    const t = data.data;

    _$('td-contenido').innerHTML = `
        <div class="tr-detalle-cab">
            <div>
                <h4 style="margin:0 0 2px;color:var(--azul-odent);">${trEsc(t.nombre)}</h4>
                <p class="tr-paciente"><i class="bi bi-person"></i> ${trEsc(t.paciente)}
                    ${t.paciente_cedula ? `<small>· ${trEsc(t.paciente_cedula)}</small>` : ''}</p>
            </div>
            ${trEstadoPill(t.estado)}
        </div>

        <div class="tr-progreso-cab"><span>Avance</span><strong>${t.progreso}%</strong></div>
        <div class="barra-progreso"><div class="barra-relleno" style="width:${t.progreso}%;"></div></div>
        <p class="tr-frase" style="margin-top:8px;">${trEsc(trFrase(t))}</p>

        <div class="tr-meta" style="margin-top:14px;">
            <div><strong>Inicio</strong><br>${trFecha(t.fecha_inicio)}</div>
            <div><strong>Fin estimado</strong><br>${trFecha(t.fecha_fin_estimada)}</div>
            <div><strong>Costo estimado</strong><br>${trEsc(t.costo_fmt)}</div>
            <div><strong>Odontólogo responsable</strong><br>${trEsc(t.odontologo)}</div>
        </div>

        <h5 class="tr-sub">Piezas dentales</h5>
        <div class="tr-piezas">${trChips(t.piezas)}</div>
        <p class="tr-asoc ${t.asociado ? '' : 'pendiente'}" style="margin-top:8px;"><i class="bi bi-link-45deg"></i>
            ${t.asociado
                ? 'Vinculado al expediente' + (t.asociado_el ? ' el ' + trEsc(t.asociado_el) : '') + ' y visible en el odontograma'
                : 'Aún no está vinculado al expediente ni al odontograma'}</p>

        <h5 class="tr-sub">Descripción</h5>
        <p class="tr-texto">${trEsc(t.descripcion)}</p>
        ${t.observaciones ? `<h5 class="tr-sub">Última observación</h5><p class="tr-texto">${trEsc(t.observaciones)}</p>` : ''}

        <h5 class="tr-sub">Historial de cambios</h5>
        <div class="tr-timeline">${(data.historial || []).map(trLineaHistorial).join('') || '<p class="tr-sin-datos">Todavía no hay movimientos.</p>'}</div>

        <div class="tr-acciones" style="margin-top:18px;">
            <button class="btn-outline-odent" onclick="trDesdeDetalle('estado', ${t.id})"><i class="bi bi-pencil-square"></i> Actualizar estado</button>
            ${t.estado === 'cancelado' ? '' : `<button class="btn-outline-odent" onclick="trDesdeDetalle('asociar', ${t.id})"><i class="bi bi-diagram-3"></i> ${t.asociado ? 'Reasociar' : 'Asociar al odontograma'}</button>`}
        </div>`;
    abrirModal('modal-tr-detalle');
}

function trDesdeDetalle(accion, id) {
    cerrarModal('modal-tr-detalle');
    if (accion === 'estado') trAbrirEstado(id);
    if (accion === 'asociar') trAbrirAsociar(id);
}

/** Busca el tratamiento en la lista cargada o, si no está (p. ej. se abre desde otra pantalla), en el servidor */
async function trObtener(id) {
    const local = _trLista.find(x => x.id === id);
    if (local) return local;
    const data = await trGet('tratamientos.detalle', { id });
    return data ? data.data : null;
}

/* ══════════════════════════ Actualizar estado (TRA-02 esc. 2 y 3) ══════════════════════════ */

async function trAbrirEstado(id) {
    const t = await trObtener(id);
    if (!t) return;

    _$('te-id').value = t.id;
    _$('te-resumen').innerHTML = `<strong>${trEsc(t.nombre)}</strong> · ${trEsc(t.paciente)}`;
    _$('te-estado').value = t.estado;
    const avance = (t.progreso > 0 && t.progreso < 100) ? t.progreso : 25;
    _$('te-progreso').value = avance;
    _$('te-progreso-valor').textContent = avance + '%';
    _$('te-observaciones').value = '';
    trAlCambiarEstado('te');
    abrirModal('modal-tr-estado');
}

async function trGuardarEstado() {
    const estado = _$('te-estado').value;
    await guardarJSON('tratamientos.estado', {
        id: Number(_$('te-id').value),
        estado,
        progreso: estado === 'en_proceso' ? Number(_$('te-progreso').value) : null,
        observaciones: _$('te-observaciones').value.trim(),
    }, {
        boton: _$('te-btn-guardar'),
        cerrarModal: 'modal-tr-estado',
        exito: estado === 'finalizado' ? '¡Tratamiento finalizado! Buen trabajo.' : 'Listo, el tratamiento quedó actualizado.',
        recargar: ['inicio'],
        alExito: () => { trCargar(); trRefrescarOdontograma(); },
    });
}

/* ══════════════════════════ Asociar a expediente y odontograma (TRA-03) ══════════════════════════ */

async function trAbrirAsociar(id) {
    const t = await trObtener(id);
    if (!t) return;

    _$('ta-id').value = t.id;
    _$('ta-resumen').innerHTML = `<strong>${trEsc(t.nombre)}</strong> · ${trEsc(t.paciente)}<br>
        Marque las piezas involucradas. El avance del tratamiento se verá sobre cada diente del odontograma.`;
    trRenderPicker('ta-piezas', t.piezas || []);
    abrirModal('modal-tr-asociar');
}

async function trGuardarAsociacion() {
    const piezas = trLeerPiezas('ta-piezas');
    // Escenario 3: sin piezas no se puede asociar
    if (!piezas.length) {
        return mostrarToast('Debe seleccionar al menos una pieza dental antes de continuar.', 'aviso', 6000);
    }
    await guardarJSON('tratamientos.asociar', {
        id: Number(_$('ta-id').value),
        piezas,
    }, {
        boton: _$('ta-btn-guardar'),
        cerrarModal: 'modal-tr-asociar',
        exito: 'Listo: el tratamiento quedó asociado al expediente y al odontograma.',
        alExito: () => { trCargar(); trRefrescarOdontograma(); },
    });
}

/* ══════════════════════════ Historial del paciente (TRA-04) ══════════════════════════ */

/** Lo llama el botón "Ver Historial de Tratamientos" del expediente */
function treAbrirHistorial() {
    const pac = (typeof _expPacienteActual !== 'undefined') ? _expPacienteActual : null;
    if (!pac || !pac.id_paciente) {
        return mostrarToast('Primero abra el expediente de un paciente.', 'aviso');
    }
    if (!_$('modal-tr-historial')) {
        return mostrarToast('Falta el formulario del historial. Revise tratamientos_modales.php.', 'peligro', 6000);
    }
    _trHistPaciente = { id_paciente: pac.id_paciente, nombre: pac.nombre_completo };
    _$('th-paciente').textContent = pac.nombre_completo || '';
    _$('th-filtro').value = '';
    abrirModal('modal-tr-historial');
    trCargarHistorial();
}

async function trCargarHistorial() {
    if (!_trHistPaciente) return;
    const cont = _$('th-lista');
    cont.innerHTML = '<div class="tr-vacio"><span class="spinner-mini"></span> Cargando el historial…</div>';

    const data = await trGet('tratamientos.listar', {
        id_paciente: _trHistPaciente.id_paciente,
        estado: _$('th-filtro').value,
    });
    if (!data) {
        cont.innerHTML = '<p class="tr-sin-datos" style="text-align:center;">No pudimos cargar el historial.</p>';
        return;
    }

    const lista = data.data || [];
    if (!lista.length) {
        const filtrado = !!_$('th-filtro').value;
        cont.innerHTML = `<div class="tr-vacio"><i class="bi bi-clipboard2"></i>
            <h4>${filtrado ? 'No hay tratamientos con ese estado' : 'Este paciente aún no cuenta con tratamientos registrados'}</h4>
            <p>${filtrado ? 'Pruebe con otro estado o quite el filtro.' : 'Cuando se cree un plan para este paciente aparecerá aquí.'}</p></div>`;
        return;
    }

    cont.innerHTML = `<div class="tr-tabla-scroll"><table class="tabla-odent">
        <thead><tr><th>Tratamiento</th><th>Fechas</th><th>Odontólogo responsable</th><th>Avance</th><th>Estado</th></tr></thead>
        <tbody>${lista.map(t => `
            <tr>
                <td><strong>${trEsc(t.nombre)}</strong><div class="tr-piezas" style="margin-top:4px;">${trChips(t.piezas)}</div></td>
                <td>${trFecha(t.fecha_inicio)}<br><small>hasta ${trFecha(t.fecha_fin_estimada)}</small></td>
                <td>${trEsc(t.odontologo)}</td>
                <td>${t.progreso}%</td>
                <td>${trEstadoPill(t.estado)}</td>
            </tr>`).join('')}
        </tbody></table></div>`;
}

/** Desde el historial se puede crear un plan nuevo para ese mismo paciente */
function trNuevoDesdeHistorial() {
    if (!_trHistPaciente) return;
    const id = _trHistPaciente.id_paciente;
    cerrarModal('modal-tr-historial');
    mostrarPagina('tratamientos');
    trAbrirNuevo(id);
}

/* ══════════════════════════ Odontograma (TRA-03 esc. 2) ══════════════════════════ */

/** Pinta sobre cada diente un punto con el estado del tratamiento. Lo llama el odontograma al cargar. */
async function treOdontograma(idExpediente) {
    if (!idExpediente) return;
    const data = await trGet('tratamientos.odontograma', { id_expediente: idExpediente });
    if (!data) return;
    _trMarcas = data.piezas || {};

    document.querySelectorAll('#pagina-odontograma .diente').forEach(el => {
        el.querySelector('.tr-marca')?.remove();
        el.removeAttribute('title');
    });

    const prioridad = { en_proceso: 3, pendiente: 2, finalizado: 1 };
    Object.entries(_trMarcas).forEach(([pieza, lista]) => {
        const el = document.getElementById('diente-' + pieza);
        if (!el || !lista.length) return;

        // si una pieza tiene varios planes, el punto muestra el más activo
        const principal = [...lista].sort((a, b) => (prioridad[b.estado] || 0) - (prioridad[a.estado] || 0))[0];
        const marca = document.createElement('span');
        marca.className = 'tr-marca tr-marca-' + principal.estado;
        marca.textContent = principal.estado === 'finalizado' ? '✓' : '';
        el.appendChild(marca);
        el.title = lista.map(t => `${t.nombre} — ${(TR_ESTADOS[t.estado] || {}).texto || t.estado}`).join('\n');
    });

    trAgregarLeyendaOdontograma();
}

function trAgregarLeyendaOdontograma() {
    const cont = document.querySelector('#pagina-odontograma .odontograma-container');
    if (!cont || cont.querySelector('.tr-leyenda')) return;
    cont.insertAdjacentHTML('beforeend', `
        <div class="tr-leyenda">
            <strong>Tratamientos:</strong>
            <span><i style="background:#f59e0b;"></i> Pendiente</span>
            <span><i style="background:#2563eb;"></i> En proceso</span>
            <span><i style="background:var(--verde-odent);"></i> Finalizado</span>
        </div>`);
}

function trRefrescarOdontograma() {
    if (typeof _odoExpediente !== 'undefined' && _odoExpediente && _$('pagina-odontograma')?.classList.contains('activa')) {
        treOdontograma(_odoExpediente.id_expediente);
    }
}

/** En el panel de detalle de una pieza lista los tratamientos asociados a ella */
function trPanelPieza(num) {
    const panel = _$('odo-panel-edicion');
    if (!panel) return;
    let caja = _$('tr-odo-pieza');
    if (!caja) {
        caja = document.createElement('div');
        caja.id = 'tr-odo-pieza';
        panel.appendChild(caja);
    }
    const lista = _trMarcas[num] || [];
    caja.innerHTML = lista.length ? `
        <div class="tr-pieza-trat">
            <h4>Tratamientos en esta pieza</h4>
            ${lista.map(t => `<div class="tr-pieza-trat-item"><span>${trEsc(t.nombre)}</span>${trEstadoPill(t.estado)}</div>`).join('')}
        </div>` : '';
}

/* ══════════════════════════ Arranque ══════════════════════════ */

/**
 * Se engancha al terminar de cargar la página, porque el script de la vista redefine
 * mostrarPagina y odoSeleccionarDiente después de este archivo.
 */
document.addEventListener('DOMContentLoaded', () => {
    const paginaBase = window.mostrarPagina;
    if (typeof paginaBase === 'function') {
        window.mostrarPagina = function (slug) {
            const r = paginaBase.apply(this, arguments);
            if (slug === 'tratamientos') trCargar();
            return r;
        };
    }

    const seleccionarBase = window.odoSeleccionarDiente;
    if (typeof seleccionarBase === 'function') {
        window.odoSeleccionarDiente = function (num) {
            const r = seleccionarBase.apply(this, arguments);
            trPanelPieza(num);
            return r;
        };
    }
});
