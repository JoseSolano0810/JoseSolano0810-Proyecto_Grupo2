'use strict';
/**
 * reportes.js — REP-02 (y base para REP-01)
 * Reportes de la clínica para el administrador.
 * Depende de app.js: mostrarToast.
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

/* ══════════════════════════ Arranque ══════════════════════════ */

repFechasPorDefecto('rep-citas');
