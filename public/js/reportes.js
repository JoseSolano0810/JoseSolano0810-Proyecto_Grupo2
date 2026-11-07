'use strict';
/**
 * reportes.js — REP-01 y REP-02
 * Reportes financiero y de citas para el administrador.
 * Depende de app.js: mostrarToast, abrirModal, cerrarModal.
 */

const REP_COLORES = {
    atendida:  '#2F7F7A',
    cancelada: '#C0392B',
    pendiente: '#f0ad00',
    ausente:   '#78909c',
};

function _rep(id) { return document.getElementById(id); }

function repEsc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function repBase() {
    return (window.ODENT && window.ODENT.base) || (typeof BASE !== 'undefined' ? BASE : '');
}

/** 42.857 → "42,9" */
function repPct(n) {
    return Number(n || 0).toLocaleString('es-CR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
}

function repISO(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Por defecto: del primer día del mes en curso a hoy */
function repFechasPorDefecto(prefijo) {
    const hoy = new Date();
    const desde = _rep(prefijo + '-desde'), hasta = _rep(prefijo + '-hasta');
    if (desde && !desde.value) desde.value = repISO(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
    if (hasta && !hasta.value) hasta.value = repISO(hoy);
}

function repMarcarError(prefijo) {
    ['desde', 'hasta'].forEach(k => { const e = _rep(`${prefijo}-${k}`); if (e) e.classList.add('campo-invalido'); });
}

function repQuitarError(prefijo) {
    ['desde', 'hasta'].forEach(k => { const e = _rep(`${prefijo}-${k}`); if (e) e.classList.remove('campo-invalido'); });
}

/** GET con manejo uniforme de errores. Devuelve el JSON o null (ya avisó al usuario). */
async function repGet(accion, params) {
    const qs = new URLSearchParams({ accion, ...params }).toString();
    try {
        const res = await fetch(`${repBase()}/index.php?${qs}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = repBase() + '/index.php?accion=logout&motivo=inactividad', 1500);
            return null;
        }
        const data = await res.json();
        if (!data.ok) {
            mostrarToast(data.error || 'No pudimos generar el reporte.', 'peligro', 6000);
            return null;
        }
        return data;
    } catch (e) {
        mostrarToast(navigator.onLine ? 'No pudimos generar el reporte. Intente de nuevo.' : 'Sin conexión a internet.', 'peligro', 6000);
        return null;
    }
}

/**
 * Valida el período antes de pedirlo al servidor (el servidor lo vuelve a validar).
 * Escenario 3: fecha inicial mayor que la final.
 */
function repPeriodoValido(prefijo) {
    const desde = _rep(prefijo + '-desde').value;
    const hasta = _rep(prefijo + '-hasta').value;

    if (!desde || !hasta) {
        mostrarToast('Seleccione la fecha inicial y la fecha final del período.', 'aviso', 6000);
        return null;
    }
    if (desde > hasta) {
        repMarcarError(prefijo);
        mostrarToast('El rango de fechas es inválido: la fecha inicial no puede ser mayor que la fecha final.', 'aviso', 6000);
        return null;
    }
    repQuitarError(prefijo);
    return { desde, hasta };
}

/* ══════════════════════════ Reporte de citas (REP-02) ══════════════════════════ */

async function repCitasGenerar() {
    const periodo = repPeriodoValido('rep-citas');
    if (!periodo) return;

    const boton = _rep('rep-citas-btn');
    const caja = _rep('rep-citas-resultado');
    boton.disabled = true;
    caja.innerHTML = '<div class="tarjeta"><div class="rep-vacio"><span class="spinner-mini"></span> Generando el reporte…</div></div>';

    try {
        const data = await repGet('reportes.citas', periodo);
        if (!data) {
            caja.innerHTML = '<div class="tarjeta"><div class="rep-vacio"><i class="bi bi-exclamation-circle"></i><h4>No pudimos generar el reporte</h4><p>Revise el período e intente de nuevo.</p></div></div>';
            return;
        }
        repCitasRender(data);
    } finally {
        boton.disabled = false;
    }
}

function repKpi(icono, color, numero, etiqueta) {
    return `<div class="kpi-card">
        <div class="kpi-icono ${color}"><i class="bi bi-${icono}"></i></div>
        <div><div class="kpi-numero">${numero}</div><div class="kpi-etiqueta">${etiqueta}</div></div>
    </div>`;
}

function repCitasRender(r) {
    const caja = _rep('rep-citas-resultado');
    const p = r.periodo;

    // Escenario 2: sin citas en el período
    if (r.vacio) {
        caja.innerHTML = `<div class="tarjeta"><div class="rep-vacio">
            <i class="bi bi-calendar-x"></i>
            <h4>${repEsc(r.mensaje)}</h4>
            <p>Período consultado: ${repEsc(p.desde_fmt)} al ${repEsc(p.hasta_fmt)}. Pruebe con otro rango de fechas.</p>
        </div></div>`;
        return;
    }

    const s = r.resumen;
    const estados = r.estados.filter(e => e.cantidad > 0);
    const mejor = r.odontologos[0];

    // Frase resumen en lenguaje natural
    const frase = `Entre el ${repEsc(p.desde_fmt)} y el ${repEsc(p.hasta_fmt)} se registraron <strong>${s.total}</strong> cita${s.total === 1 ? '' : 's'}: `
        + `<strong>${s.atendidas}</strong> atendida${s.atendidas === 1 ? '' : 's'} (${repPct(r.estados.find(e => e.clave === 'atendida').porcentaje)} %), `
        + `<strong>${s.canceladas}</strong> cancelada${s.canceladas === 1 ? '' : 's'} y <strong>${s.pendientes}</strong> pendiente${s.pendientes === 1 ? '' : 's'}.`
        + (mejor && mejor.atendidas > 0 ? ` Quien más citas atendió fue <strong>${repEsc(mejor.nombre)}</strong> (${mejor.atendidas}).` : '');

    const kpis =
        repKpi('calendar3', 'azul', s.total, 'Citas en total') +
        repKpi('check-circle', 'verde', s.atendidas, 'Atendidas') +
        repKpi('x-circle', 'rojo', s.canceladas, 'Canceladas') +
        repKpi('hourglass-split', 'amarillo', s.pendientes, 'Pendientes') +
        (s.ausentes > 0 ? repKpi('person-x', 'azul', s.ausentes, 'No asistieron') : '');

    const barraApilada = estados.map(e =>
        `<span style="width:${e.porcentaje}%;background:${REP_COLORES[e.clave]};" title="${repEsc(e.texto)}: ${e.cantidad} (${repPct(e.porcentaje)} %)"></span>`).join('');

    const leyenda = estados.map(e => `
        <li><i style="background:${REP_COLORES[e.clave]};"></i>
            <span>${repEsc(e.texto)}</span>
            <strong>${e.cantidad}</strong>
            <small>${repPct(e.porcentaje)} %</small></li>`).join('');

    const tipos = r.tipos.map(t => `
        <div class="rep-fila-barra">
            <span class="rep-fila-nombre">${repEsc(t.tipo)}</span>
            <span class="rep-fila-pista"><i style="width:${t.porcentaje}%;"></i></span>
            <span class="rep-fila-valor">${t.cantidad} <small>(${repPct(t.porcentaje)} %)</small></span>
        </div>`).join('');

    const odontologos = r.odontologos.map(o => `
        <tr>
            <td><strong>${repEsc(o.nombre)}</strong></td>
            <td>${o.total}</td>
            <td>${o.atendidas}</td>
            <td>${o.canceladas}</td>
            <td>${o.pendientes}</td>
            <td>${o.ausentes}</td>
            <td><span class="rep-fila-pista rep-pista-chica"><i style="width:${o.porcentaje_atendidas}%;"></i></span> ${repPct(o.porcentaje_atendidas)} %</td>
        </tr>`).join('');

    // Serie en el tiempo: la altura de cada columna es proporcional al total de citas
    const maximo = Math.max(...r.serie.map(x => x.total), 1);
    const serie = r.serie.map(x => {
        const otras = x.total - x.atendidas - x.canceladas;
        const alto = Math.max(6, Math.round(x.total * 100 / maximo));
        const detalle = `${x.etiqueta}: ${x.total} cita${x.total === 1 ? '' : 's'} (${x.atendidas} atendida${x.atendidas === 1 ? '' : 's'}, ${x.canceladas} cancelada${x.canceladas === 1 ? '' : 's'})`;
        return `<div class="rep-col" title="${repEsc(detalle)}">
            <span class="rep-col-valor">${x.total}</span>
            <div class="rep-col-barra" style="height:${alto}%;">
                ${x.canceladas ? `<i style="flex:${x.canceladas};background:${REP_COLORES.cancelada};"></i>` : ''}
                ${otras > 0 ? `<i style="flex:${otras};background:#b0bec5;"></i>` : ''}
                ${x.atendidas ? `<i style="flex:${x.atendidas};background:${REP_COLORES.atendida};"></i>` : ''}
            </div>
            <span class="rep-col-etq">${repEsc(x.etiqueta)}</span>
        </div>`;
    }).join('');

    caja.innerHTML = `
        <div class="tarjeta rep-resumen-frase"><i class="bi bi-info-circle"></i><p>${frase}</p></div>

        <div class="kpi-grid" style="margin:20px 0;">${kpis}</div>

        <div class="panel-grid col-2" style="margin-bottom:20px;">
            <div class="tarjeta">
                <h3 class="rep-subtitulo">Distribución por estado</h3>
                <div class="rep-apilada">${barraApilada}</div>
                <ul class="rep-leyenda">${leyenda}</ul>
            </div>
            <div class="tarjeta">
                <h3 class="rep-subtitulo">Por tipo de consulta</h3>
                ${tipos}
            </div>
        </div>

        <div class="tarjeta" style="margin-bottom:20px;">
            <h3 class="rep-subtitulo">Productividad por odontólogo</h3>
            <div class="rep-tabla-scroll">
                <table class="tabla-odent">
                    <thead><tr><th>Odontólogo</th><th>Citas</th><th>Atendidas</th><th>Canceladas</th><th>Pendientes</th><th>No asistieron</th><th>% atendidas</th></tr></thead>
                    <tbody>${odontologos}</tbody>
                </table>
            </div>
        </div>

        <div class="tarjeta">
            <h3 class="rep-subtitulo">Citas por ${r.agrupacion === 'mes' ? 'mes' : 'día'}</h3>
            <div class="rep-serie">${serie}</div>
            <div class="rep-leyenda-serie">
                <span><i style="background:${REP_COLORES.atendida};"></i> Atendidas</span>
                <span><i style="background:${REP_COLORES.cancelada};"></i> Canceladas</span>
                <span><i style="background:#b0bec5;"></i> Pendientes u otras</span>
            </div>
        </div>`;
}

/* ══════════════════════════ Pestañas ══════════════════════════ */

function repTab(cual) {
    const fin = cual === 'fin';
    _rep('rep-panel-fin').hidden = !fin;
    _rep('rep-panel-citas').hidden = fin;

    const a = _rep('rep-tab-fin').classList, b = _rep('rep-tab-citas').classList;
    if (fin) { a.add('rep-tab-activa'); b.remove('rep-tab-activa'); }
    else     { b.add('rep-tab-activa'); a.remove('rep-tab-activa'); }
}

/* ══════════════════════════ Reporte financiero (REP-01) ══════════════════════════ */

let _repFinPeriodo = null;   // último período generado, para refrescarlo al registrar o eliminar gastos

/** 1250000 → "₡1.250.000" (mismo formato que el resto del sistema) */
function repColones(n) {
    const v = Math.round(Math.abs(Number(n) || 0));
    const texto = String(v).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return (Number(n) < 0 && v > 0 ? '-' : '') + '₡' + texto;
}

/** POST JSON. Siempre devuelve un objeto con "ok" */
async function repPost(accion, cuerpo) {
    try {
        const res = await fetch(`${repBase()}/index.php?accion=${accion}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(cuerpo),
        });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = repBase() + '/index.php?accion=logout&motivo=inactividad', 1500);
            return { ok: false, error: 'Su sesión expiró.' };
        }
        return await res.json();
    } catch (e) {
        return { ok: false, error: navigator.onLine ? 'No pudimos completar la acción. Intente de nuevo.' : 'Sin conexión a internet.' };
    }
}

async function repFinGenerar() {
    const periodo = repPeriodoValido('rep-fin');
    if (!periodo) return;
    await repFinCargar(periodo);
}

async function repFinCargar(periodo) {
    const boton = _rep('rep-fin-btn');
    const caja = _rep('rep-fin-resultado');
    boton.disabled = true;
    caja.innerHTML = '<div class="tarjeta"><div class="rep-vacio"><span class="spinner-mini"></span> Generando el reporte…</div></div>';

    try {
        const data = await repGet('reportes.financiero', periodo);
        if (!data) {
            caja.innerHTML = '<div class="tarjeta"><div class="rep-vacio"><i class="bi bi-exclamation-circle"></i><h4>No pudimos generar el reporte</h4><p>Revise el período e intente de nuevo.</p></div></div>';
            return;
        }
        _repFinPeriodo = periodo;
        repFinRender(data);
    } finally {
        boton.disabled = false;
    }
}

function repFilaBarra(nombre, porcentaje, valor, claseExtra) {
    return `<div class="rep-fila-barra">
        <span class="rep-fila-nombre" title="${repEsc(nombre)}">${repEsc(nombre)}</span>
        <span class="rep-fila-pista ${claseExtra || ''}"><i style="width:${porcentaje}%;"></i></span>
        <span class="rep-fila-valor">${valor} <small>(${repPct(porcentaje)} %)</small></span>
    </div>`;
}

function repFinRender(r) {
    const caja = _rep('rep-fin-resultado');
    const p = r.periodo;

    // Escenario 2: sin movimientos financieros en el período
    if (r.vacio) {
        caja.innerHTML = `<div class="tarjeta"><div class="rep-vacio">
            <i class="bi bi-cash-stack"></i>
            <h4>${repEsc(r.mensaje)}</h4>
            <p>Período consultado: ${repEsc(p.desde_fmt)} al ${repEsc(p.hasta_fmt)}. Pruebe con otro rango de fechas${r.pagos_disponibles ? '' : ', o registre los gastos con «Registrar gasto»'}.</p>
        </div></div>`;
        return;
    }

    const s = r.resumen;
    const signo = s.balance > 0 ? 'positivo' : (s.balance < 0 ? 'negativo' : 'en equilibrio');

    let frase = `Entre el ${repEsc(p.desde_fmt)} y el ${repEsc(p.hasta_fmt)} ingresaron <strong>${repColones(s.ingresos)}</strong> `
        + `(${s.n_pagos} pago${s.n_pagos === 1 ? '' : 's'}) y se gastaron <strong>${repColones(s.egresos)}</strong> `
        + `(${s.n_egresos} gasto${s.n_egresos === 1 ? '' : 's'}). `;
    if (s.balance > 0)      frase += `El balance es <strong>positivo</strong>: quedaron <strong>${repColones(s.balance)}</strong> a favor.`;
    else if (s.balance < 0) frase += `El balance es <strong>negativo</strong>: se gastó <strong>${repColones(-s.balance)}</strong> más de lo que ingresó.`;
    else                    frase += 'El balance quedó <strong>en equilibrio</strong>.';

    const colorBalance = s.balance > 0 ? 'verde' : (s.balance < 0 ? 'rojo' : 'azul');
    const kpis =
        repKpi('arrow-down-circle', 'verde', repColones(s.ingresos), 'Ingresos') +
        repKpi('arrow-up-circle', 'rojo', repColones(s.egresos), 'Egresos') +
        repKpi('wallet2', colorBalance, repColones(s.balance), `Balance ${signo}`);

    const aviso = r.aviso
        ? `<div class="alerta info" style="margin-bottom:20px;"><i class="bi bi-info-circle"></i> ${repEsc(r.aviso)}</div>` : '';

    const metodos = r.metodos.length
        ? r.metodos.map(m => repFilaBarra(m.texto, m.porcentaje, repColones(m.total))).join('')
        : '<p class="rep-ayuda" style="margin:0;">No hubo ingresos en este período.</p>';

    const categorias = r.categorias.length
        ? r.categorias.map(c => repFilaBarra(c.texto, c.porcentaje, repColones(c.total), 'rep-pista-roja')).join('')
        : '<p class="rep-ayuda" style="margin:0;">No hubo gastos registrados en este período.</p>';

    // Serie: dos barras por período (ingresos y egresos), a la misma escala
    const maximo = Math.max(...r.serie.flatMap(x => [x.ingresos, x.egresos]), 1);
    const alto = v => (v > 0 ? Math.max(4, Math.round(v * 100 / maximo)) : 0);
    const serie = r.serie.map(x => `
        <div class="rep-col rep-col-doble" title="${repEsc(x.etiqueta)}: ingresos ${repColones(x.ingresos)} · egresos ${repColones(x.egresos)}">
            <div class="rep-par">
                <i class="rep-barra-ing" style="height:${alto(x.ingresos)}%;"></i>
                <i class="rep-barra-egr" style="height:${alto(x.egresos)}%;"></i>
            </div>
            <span class="rep-col-etq">${repEsc(x.etiqueta)}</span>
        </div>`).join('');

    const gastos = r.gastos.length ? `
        <div class="rep-tabla-scroll">
            <table class="tabla-odent">
                <thead><tr><th>Fecha</th><th>Categoría</th><th>Concepto</th><th>Monto</th><th>Registrado por</th><th></th></tr></thead>
                <tbody>${r.gastos.map(g => `
                    <tr>
                        <td>${repEsc(g.fecha_fmt)}</td>
                        <td>${repEsc(g.categoria)}</td>
                        <td><strong>${repEsc(g.concepto)}</strong>${g.observaciones ? `<br><small class="rep-sub">${repEsc(g.observaciones)}</small>` : ''}</td>
                        <td>${repColones(g.monto)}</td>
                        <td>${repEsc(g.registrado_por)}</td>
                        <td><button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;" title="Eliminar gasto"
                                    onclick="repGastoEliminar(${g.id})"><i class="bi bi-trash"></i></button></td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<p class="rep-ayuda" style="margin:0;">No hay gastos registrados en este período. Use «Registrar gasto» para agregarlos.</p>';

    _repGastos = r.gastos;

    caja.innerHTML = `
        ${aviso}
        <div class="tarjeta rep-resumen-frase"><i class="bi bi-info-circle"></i><p>${frase}</p></div>

        <div class="kpi-grid" style="margin:20px 0;grid-template-columns:repeat(3,1fr);">${kpis}</div>

        <div class="panel-grid col-2" style="margin-bottom:20px;">
            <div class="tarjeta">
                <h3 class="rep-subtitulo">Ingresos por método de pago</h3>
                ${metodos}
            </div>
            <div class="tarjeta">
                <h3 class="rep-subtitulo">Egresos por categoría</h3>
                ${categorias}
            </div>
        </div>

        <div class="tarjeta" style="margin-bottom:20px;">
            <h3 class="rep-subtitulo">Ingresos y egresos por ${r.agrupacion === 'mes' ? 'mes' : 'día'}</h3>
            <div class="rep-serie">${serie}</div>
            <div class="rep-leyenda-serie">
                <span><i style="background:${REP_COLORES.atendida};"></i> Ingresos</span>
                <span><i style="background:${REP_COLORES.cancelada};"></i> Egresos</span>
            </div>
        </div>

        <div class="tarjeta">
            <h3 class="rep-subtitulo">Gastos registrados en el período</h3>
            ${gastos}
        </div>`;
}

/* ── Registrar y eliminar gastos ─────────────────────────── */

let _repGastos = [];

function repGastoAbrir() {
    _rep('reg-fecha').value = repISO(new Date());
    _rep('reg-fecha').max = repISO(new Date());
    _rep('reg-categoria').value = '';
    _rep('reg-concepto').value = '';
    _rep('reg-monto').value = '';
    _rep('reg-observaciones').value = '';
    abrirModal('modal-rep-egreso');
}

async function repGastoGuardar() {
    const datos = {
        fecha:         _rep('reg-fecha').value,
        categoria:     _rep('reg-categoria').value,
        concepto:      _rep('reg-concepto').value.trim(),
        monto:         _rep('reg-monto').value.trim(),
        observaciones: _rep('reg-observaciones').value.trim(),
    };

    const faltan = [];
    if (!datos.fecha)     faltan.push('fecha');
    if (!datos.categoria) faltan.push('categoría');
    if (!datos.concepto)  faltan.push('concepto');
    if (!datos.monto)     faltan.push('monto');
    if (faltan.length) {
        return mostrarToast('Para registrar el gasto falta completar: ' + faltan.join(', ') + '.', 'aviso', 6000);
    }
    if (!(Number(datos.monto) > 0)) {
        return mostrarToast('El monto debe ser un número mayor que cero.', 'aviso', 6000);
    }

    const boton = _rep('reg-btn');
    boton.disabled = true;
    try {
        const r = await repPost('reportes.egreso.crear', datos);
        if (!r.ok) {
            mostrarToast(r.error || 'No pudimos registrar el gasto.', 'peligro', 6000);
            return;
        }
        cerrarModal('modal-rep-egreso');
        mostrarToast('Listo, el gasto quedó registrado.', 'exito');
        if (_repFinPeriodo) repFinCargar(_repFinPeriodo);   // el balance se actualiza solo
    } finally {
        boton.disabled = false;
    }
}

function repGastoEliminar(id) {
    const g = _repGastos.find(x => x.id === id);
    if (!g) return;
    _rep('red-id').value = g.id;
    _rep('red-concepto').textContent = '«' + g.concepto + '» (' + repColones(g.monto) + ')';
    abrirModal('modal-rep-eliminar');
}

async function repGastoConfirmarEliminar() {
    const id = Number(_rep('red-id').value);
    const boton = _rep('red-btn');
    boton.disabled = true;
    try {
        const r = await repPost('reportes.egreso.eliminar', { id });
        cerrarModal('modal-rep-eliminar');
        if (r.ok) mostrarToast('El gasto se eliminó y el balance se actualizó.', 'exito');
        else mostrarToast(r.error || 'No pudimos eliminar el gasto.', 'peligro', 6000);
        if (_repFinPeriodo) repFinCargar(_repFinPeriodo);
    } finally {
        boton.disabled = false;
    }
}

/* ══════════════════════════ Arranque ══════════════════════════ */

repFechasPorDefecto('rep-fin');
repFechasPorDefecto('rep-citas');
