'use strict';

/**
 * Módulo de Citas (CIT-01 a CIT-06).
 */
window.Citas = (function () {
    const raiz = document.getElementById('agenda-citas');
    if (!raiz) return {};

    const GESTION = raiz.dataset.gestion === '1';
    const ROL     = raiz.dataset.rol;

    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const DIAS  = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

    const S = {
        vista: 'lista',
        ref: iso(new Date()),
        citas: [],
        cat: { pacientes: [], odontologos: [], tipos: [], duraciones: [] },
        modo: 'crear',
        pendienteEstado: null,
        token: 0,
    };

    const $ = (id) => document.getElementById(id);

    /* ── Utilidades ─────────────────────────────────────────── */
    function pad(n) { return String(n).padStart(2, '0'); }
    function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parseISO(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); }
    function sumarDias(d, n) { const r = new Date(d); r.setDate(r.getDate() + n); return r; }
    function lunesDe(d) { const r = new Date(d); r.setDate(r.getDate() - ((r.getDay() + 6) % 7)); return r; }
    function fmt(isoStr) { const [y, m, d] = isoStr.split('-'); return d + '/' + m + '/' + y; }
    function hoy() { return iso(new Date()); }
    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function activa(c) { return c.estado === 'programada' || c.estado === 'confirmada'; }
    function insignia(estado) { return '<span class="badge-estado badge-' + esc(estado) + '">' + esc(cap(estado)) + '</span>'; }
    function corto(nombre) {
        const p = String(nombre).trim().split(/\s+/);
        return p.length > 1 ? p[0] + ' ' + p[p.length - 1].charAt(0) + '.' : p[0];
    }

    async function get(accion, params = {}) {
        const qs = new URLSearchParams({ accion, ...params });
        const res = await fetch(window.ODENT.base + '/index.php?' + qs.toString(), {
            headers: { 'Accept': 'application/json' }, cache: 'no-store'
        });
        if (res.status === 401) {
            mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => location.href = window.ODENT.base + '/index.php?accion=logout&motivo=inactividad', 1500);
            return null;
        }
        return res.json();
    }

    /** Marca los campos vacíos y avisa cuáles faltan (CIT-01 esc. 2) */
    function validarRequeridos(lista) {
        const faltan = [];
        let primero = null;
        lista.forEach(([id, etiqueta]) => {
            const el = $(id);
            if (!el) return;
            const vacio = !String(el.value ?? '').trim();
            el.classList.toggle('campo-invalido', vacio);
            if (vacio) {
                faltan.push(etiqueta);
                primero = primero || el;
                el.addEventListener('input', () => el.classList.remove('campo-invalido'), { once: true });
                el.addEventListener('change', () => el.classList.remove('campo-invalido'), { once: true });
            }
        });
        if (faltan.length) {
            primero.focus();
            mostrarToast('Complete los campos obligatorios: ' + faltan.join(', ') + '.', 'aviso', 6000);
            return false;
        }
        return true;
    }

    /* ── Carga de datos ─────────────────────────────────────── */
    async function cargarCatalogos() {
        try {
            const d = await get('citas.catalogos');
            if (!d || !d.ok) return;
            S.cat = d;
            llenarSelect($('agenda-odontologo'), d.odontologos, 'Todos los odontólogos');
            if (GESTION) {
                llenarSelect($('cita-paciente'), d.pacientes.map(p => ({ id: p.id, nombre: p.nombre + (p.cedula ? ' — ' + p.cedula : '') })), 'Seleccione un paciente');
                llenarSelect($('cita-odontologo'), d.odontologos, 'Seleccione un odontólogo');
                llenarSelect($('cita-tipo'), d.tipos.map(t => ({ id: t, nombre: t })), 'Seleccione');
                llenarSelect($('cita-duracion'), d.duraciones.map(m => ({ id: m, nombre: m + ' minutos' })), 'Seleccione');
            }
        } catch (e) { /* los selects quedan vacíos; el servidor valida igual */ }
    }

    function llenarSelect(sel, items, vacio) {
        if (!sel) return;
        const actual = sel.value;
        sel.innerHTML = '<option value="">' + esc(vacio) + '</option>' +
            items.map(i => '<option value="' + esc(i.id) + '">' + esc(i.nombre) + '</option>').join('');
        sel.value = actual;
    }

    function rango() {
        const ref = parseISO(S.ref);
        if (S.vista === 'dia') return [S.ref, S.ref];
        if (S.vista === 'semana') { const l = lunesDe(ref); return [iso(l), iso(sumarDias(l, 6))]; }
        const primero = new Date(ref.getFullYear(), ref.getMonth(), 1);
        const ultimo  = new Date(ref.getFullYear(), ref.getMonth() + 1, 0);
        return [iso(lunesDe(primero)), iso(sumarDias(lunesDe(ultimo), 6))];
    }

    async function cargar() {
        const token = ++S.token;
        const params = {};
        const odon = $('agenda-odontologo').value;
        if (odon) params.odontologo = odon;

        if (S.vista === 'lista') {
            const q = $('filtro-q').value.trim(), fecha = $('filtro-fecha').value, estado = $('filtro-estado').value;
            if (q) params.q = q;
            if (fecha) params.fecha = fecha;
            if (estado) params.estado = estado;
            S.hayFiltros = !!(q || fecha || estado || odon);
            if (!S.hayFiltros) params.desde = hoy();    // sin criterios: próximas citas
        } else {
            [params.desde, params.hasta] = rango();
        }

        $('agenda-contenido').innerHTML = '<div class="agenda-vacio">Cargando…</div>';
        try {
            const d = await get('citas.listar', params);
            if (token !== S.token || !d) return;
            if (!d.ok) { $('agenda-contenido').innerHTML = '<div class="agenda-vacio">' + esc(d.error || 'No se pudo cargar la agenda.') + '</div>'; return; }
            S.citas = d.data;
            pintar();
        } catch (e) {
            if (token !== S.token) return;
            $('agenda-contenido').innerHTML = '<div class="agenda-vacio"><i class="bi bi-wifi-off"></i>No se pudo cargar la agenda. Intente de nuevo.</div>';
        }
    }

    /* ── Pintado ────────────────────────────────────────────── */
    function pintar() {
        cerrarMenu();
        const esLista = S.vista === 'lista';
        $('agenda-filtros').style.display = esLista ? 'flex' : 'none';
        $('agenda-nav').style.display     = esLista ? 'none' : 'inline-flex';
        $('agenda-leyenda').style.display = esLista ? 'none' : 'flex';
        document.querySelectorAll('#agenda-vistas button').forEach(b => b.classList.toggle('activo', b.dataset.vista === S.vista));

        if (!esLista) $('agenda-etiqueta').textContent = etiquetaPeriodo();

        const cont = $('agenda-contenido');
        if (S.vista === 'lista')  cont.innerHTML = htmlLista();
        if (S.vista === 'dia')    cont.innerHTML = htmlDia();
        if (S.vista === 'semana') cont.innerHTML = htmlSemana();
        if (S.vista === 'mes')    cont.innerHTML = htmlMes();
    }

    function etiquetaPeriodo() {
        const ref = parseISO(S.ref);
        if (S.vista === 'dia') return DIAS[(ref.getDay() + 6) % 7] + ', ' + ref.getDate() + ' de ' + MESES[ref.getMonth()] + ' de ' + ref.getFullYear();
        if (S.vista === 'semana') {
            const l = lunesDe(ref), f = sumarDias(l, 6);
            return l.getDate() + ' ' + MESES[l.getMonth()].slice(0, 3) + ' – ' + f.getDate() + ' ' + MESES[f.getMonth()].slice(0, 3) + ' ' + f.getFullYear();
        }
        return MESES[ref.getMonth()] + ' ' + ref.getFullYear();
    }

    function vacio(texto) {
        return '<div class="agenda-vacio"><i class="bi bi-calendar-x"></i>' + esc(texto) + '</div>';
    }

    /* Lista (CIT-02 esc. 1) */
    function htmlLista() {
        if (!S.citas.length) {
            return vacio(S.hayFiltros ? 'No se encontraron citas con los criterios indicados.' : 'No hay citas próximas registradas.');
        }
        const filas = S.citas.map(c =>
            '<tr>' +
            '<td>' + esc(c.fecha_fmt) + '</td>' +
            '<td><strong>' + esc(c.hora) + '</strong> – ' + esc(c.hora_fin) + '</td>' +
            '<td>' + esc(c.paciente) + '</td>' +
            '<td>' + esc(c.odontologo) + '</td>' +
            '<td>' + esc(c.tipo_consulta) + '</td>' +
            '<td>' + insignia(c.estado) + '</td>' +
            '<td>' + botonesAccion(c) + '</td>' +
            '</tr>'
        ).join('');
        return '<div class="tabla-scroll"><table class="tabla-odent"><thead><tr>' +
            '<th>Fecha</th><th>Hora</th><th>Paciente</th><th>Odontólogo</th><th>Tipo de consulta</th><th>Estado</th><th>Acciones</th>' +
            '</tr></thead><tbody>' + filas + '</tbody></table></div>' +
            '<p class="agenda-nota">' + (S.hayFiltros
                ? S.citas.length + ' cita(s) encontrada(s).'
                : 'Mostrando las próximas citas. Use los filtros para buscar en todo el historial.') + '</p>';
    }

    /** Acciones disponibles para una cita según su estado y el rol (se muestran en el menú ⋮) */
    function itemsAccion(c) {
        const items = [['ver', 'eye', 'Ver detalle']];
        if (GESTION && activa(c)) {
            const yaOcurrio = c.fecha <= hoy();
            if (c.estado === 'programada') items.push(['confirmar', 'check2', 'Confirmar cita']);
            items.push(['editar', 'pencil', 'Modificar']);
            items.push(['reprogramar', 'calendar2-week', 'Reprogramar']);
            if (yaOcurrio) {
                items.push(['atendida', 'check2-circle', 'Marcar como atendida']);
                items.push(['ausente', 'person-x', 'Marcar como ausente']);
            }
            items.push(['cancelar', 'x-lg', 'Cancelar cita', 'peligro']);
        }
        return items;
    }

    function botonesAccion(c) {
        return '<div class="acciones-cita">' +
            '<button type="button" class="btn-icono" data-cita-accion="menu" data-id="' + c.id + '" title="Acciones" aria-haspopup="true" aria-expanded="false">' +
            '<i class="bi bi-three-dots-vertical"></i></button></div>';
    }

    /* ── Menú desplegable de acciones ───────────────────────── */
    let menuEl = null, menuCita = null, menuBtn = null;

    function cerrarMenu() {
        if (menuEl) menuEl.classList.remove('abierto');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
        menuCita = menuBtn = null;
    }

    function abrirMenu(btn, c) {
        if (!menuEl) {
            menuEl = document.createElement('div');
            menuEl.id = 'cita-menu';
            menuEl.className = 'menu-acciones';
            menuEl.setAttribute('role', 'menu');
            document.body.appendChild(menuEl);
        }
        menuEl.innerHTML = itemsAccion(c).map(([acc, icono, texto, extra]) =>
            '<button type="button" role="menuitem" class="menu-acciones-item ' + (extra || '') + '" data-cita-accion="' + acc + '" data-id="' + c.id + '">' +
            '<i class="bi bi-' + icono + '"></i>' + esc(texto) + '</button>'
        ).join('');

        menuEl.classList.add('abierto');
        const r = btn.getBoundingClientRect();
        const w = menuEl.offsetWidth, h = menuEl.offsetHeight;
        let top  = r.bottom + 4;
        if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 4);   // si no cabe abajo, se abre hacia arriba
        const left = Math.min(Math.max(8, r.right - w), window.innerWidth - w - 8);
        menuEl.style.top = top + 'px';
        menuEl.style.left = left + 'px';

        menuCita = c.id;
        menuBtn = btn;
        btn.setAttribute('aria-expanded', 'true');
    }

    /* Día (CIT-04 esc. 1) */
    function htmlDia() {
        if (!S.citas.length) return vacio('No hay citas programadas para este día.');
        return S.citas.map(c =>
            '<div class="cal-dia-item estado-' + esc(c.estado) + '" data-cita-accion="menu" data-id="' + c.id + '">' +
            '<div class="cal-dia-hora">' + esc(c.hora) + ' – ' + esc(c.hora_fin) + '</div>' +
            '<div class="cal-dia-info"><strong>' + esc(c.paciente) + '</strong>' +
            '<small>' + esc(c.odontologo) + ' · ' + esc(c.tipo_consulta) + ' · ' + esc(c.fecha_fmt) + '</small></div>' +
            insignia(c.estado) + '</div>'
        ).join('');
    }

    function chip(c, conOdontologo) {
        return '<button type="button" class="cal-chip estado-' + esc(c.estado) + '" data-cita-accion="menu" data-id="' + c.id + '" ' +
            'title="' + esc(c.hora + ' ' + c.paciente + ' — ' + c.odontologo + ' (' + cap(c.estado) + ')') + '">' +
            '<b>' + esc(c.hora) + '</b> ' + esc(corto(c.paciente)) +
            (conOdontologo ? '<small>' + esc(corto(c.odontologo)) + '</small>' : '') + '</button>';
    }

    /* Semana (CIT-04 esc. 2) */
    function htmlSemana() {
        const lunes = lunesDe(parseISO(S.ref));
        const dias = Array.from({ length: 7 }, (_, i) => sumarDias(lunes, i));
        let hMin = 8, hMax = 17;
        S.citas.forEach(c => {
            const h = parseInt(c.hora.slice(0, 2), 10);
            hMin = Math.min(hMin, h); hMax = Math.max(hMax, h);
        });

        let cab = '<tr><th></th>' + dias.map(d =>
            '<th class="' + (iso(d) === hoy() ? 'hoy' : '') + '">' + cap(DIAS[(d.getDay() + 6) % 7]).slice(0, 3) + ' ' + d.getDate() + '/' + (d.getMonth() + 1) + '</th>'
        ).join('') + '</tr>';

        let cuerpo = '';
        for (let h = hMin; h <= hMax; h++) {
            cuerpo += '<tr><th>' + pad(h) + ':00</th>' + dias.map(d => {
                const f = iso(d);
                const celdas = S.citas.filter(c => c.fecha === f && parseInt(c.hora.slice(0, 2), 10) === h);
                return '<td>' + celdas.map(c => chip(c, true)).join('') + '</td>';
            }).join('') + '</tr>';
        }
        const nota = S.citas.length ? '' : '<p class="agenda-nota">No hay citas programadas en esta semana.</p>';
        return '<div class="tabla-scroll"><table class="cal-semana"><thead>' + cab + '</thead><tbody>' + cuerpo + '</tbody></table></div>' + nota;
    }

    /* Mes (CIT-04 esc. 3) */
    function htmlMes() {
        const ref = parseISO(S.ref);
        const primero = new Date(ref.getFullYear(), ref.getMonth(), 1);
        const ultimo  = new Date(ref.getFullYear(), ref.getMonth() + 1, 0);
        const inicio  = lunesDe(primero);
        const semanas = Math.ceil(((primero.getDay() + 6) % 7 + ultimo.getDate()) / 7);

        let h = '<div class="tabla-scroll"><div class="cal-mes">' +
            DIAS.map(d => '<div class="cal-mes-cab">' + cap(d).slice(0, 3) + '</div>').join('');

        for (let i = 0; i < semanas * 7; i++) {
            const d = sumarDias(inicio, i), f = iso(d);
            const delDia = S.citas.filter(c => c.fecha === f);
            const clases = 'cal-mes-dia' + (d.getMonth() !== ref.getMonth() ? ' fuera' : '') + (f === hoy() ? ' hoy' : '');
            h += '<div class="' + clases + '">' +
                '<button type="button" class="cal-num" data-cita-accion="dia" data-fecha="' + f + '" title="Ver el día">' + d.getDate() + '</button>' +
                delDia.slice(0, 3).map(c => chip(c, false)).join('') +
                (delDia.length > 3 ? '<button type="button" class="cal-mas" data-cita-accion="dia" data-fecha="' + f + '">+' + (delDia.length - 3) + ' más</button>' : '') +
                '</div>';
        }
        return h + '</div></div>' + (S.citas.length ? '' : '<p class="agenda-nota">No hay citas programadas en este mes.</p>');
    }

    /* ── Navegación ─────────────────────────────────────────── */
    function mover(delta) {
        const ref = parseISO(S.ref);
        if (S.vista === 'dia')    S.ref = iso(sumarDias(ref, delta));
        if (S.vista === 'semana') S.ref = iso(sumarDias(ref, 7 * delta));
        if (S.vista === 'mes')    S.ref = iso(new Date(ref.getFullYear(), ref.getMonth() + delta, 1));
        cargar();
    }

    function cambiarVista(v, fecha) {
        S.vista = v;
        if (fecha) S.ref = fecha;
        cargar();
    }

    /* ── Formulario: agendar / modificar (CIT-01, CIT-02) ───── */
    function limpiarForm() {
        ['cita-id', 'cita-paciente', 'cita-odontologo', 'cita-fecha', 'cita-hora', 'cita-duracion', 'cita-tipo', 'cita-obs'].forEach(id => {
            $(id).value = '';
            $(id).classList.remove('campo-invalido');
        });
        $('cita-estado').value = 'programada';
        $('cita-paciente').disabled = false;
    }

    function nueva() {
        if (!GESTION) return;
        S.modo = 'crear';
        limpiarForm();
        $('cita-titulo').textContent = 'Nueva cita';
        $('cita-btn-guardar').querySelector('span').textContent = 'Agendar Cita';
        $('cita-estado-grupo').style.display = '';
        $('cita-fecha').min = hoy();
        $('cita-fecha').value = (S.vista !== 'lista' && S.ref >= hoy()) ? S.ref : hoy();
        abrirModal('modal-cita');
    }

    function editar(c) {
        S.modo = 'editar';
        limpiarForm();
        $('cita-titulo').textContent = 'Modificar cita';
        $('cita-btn-guardar').querySelector('span').textContent = 'Guardar Cambios';
        $('cita-estado-grupo').style.display = 'none';
        $('cita-id').value = c.id;
        $('cita-paciente').value = c.id_paciente;
        $('cita-paciente').disabled = true;               // la cita pertenece a ese paciente
        $('cita-odontologo').value = c.id_odontologo;
        $('cita-fecha').min = c.fecha < hoy() ? c.fecha : hoy();
        $('cita-fecha').value = c.fecha;
        $('cita-hora').value = c.hora;
        $('cita-duracion').value = c.duracion;
        $('cita-tipo').value = c.tipo_consulta;
        $('cita-obs').value = c.observaciones || '';
        abrirModal('modal-cita');
    }

    /** CIT-01 esc. 3: cancelar descarta lo digitado */
    function cerrarForm() {
        limpiarForm();
        cerrarModal('modal-cita');
        if (typeof mostrarPagina === 'function') mostrarPagina('agenda');   // redirige a la agenda
    }

    async function guardar(btn) {
        const crear = S.modo === 'crear';
        const requeridos = [
            ...(crear ? [['cita-paciente', 'Paciente']] : []),
            ['cita-odontologo', 'Odontólogo'], ['cita-fecha', 'Fecha'], ['cita-hora', 'Hora'],
            ['cita-duracion', 'Duración'], ['cita-tipo', 'Tipo de consulta'],
            ...(crear ? [['cita-estado', 'Estado']] : []),
        ];
        if (!validarRequeridos(requeridos)) return;

        const payload = {
            id_odontologo: Number($('cita-odontologo').value),
            fecha: $('cita-fecha').value,
            hora: $('cita-hora').value,
            duracion: Number($('cita-duracion').value),
            tipo_consulta: $('cita-tipo').value,
            observaciones: $('cita-obs').value.trim(),
        };
        if (crear) { payload.id_paciente = Number($('cita-paciente').value); payload.estado = $('cita-estado').value; }
        else       { payload.id = Number($('cita-id').value); }

        await guardarJSON(crear ? 'citas.crear' : 'citas.editar', payload, {
            boton: btn,
            exito: crear ? 'Cita agendada correctamente.' : 'Cita actualizada correctamente.',
            cerrarModal: 'modal-cita',
            recargar: ['inicio'],
            alExito: () => { limpiarForm(); cargar(); },
        });
    }

    /* ── Reprogramar (CIT-03 esc. 3) ────────────────────────── */
    function reprogramar(c) {
        $('rep-id').value = c.id;
        $('rep-info').innerHTML = '<strong>' + esc(c.paciente) + '</strong> con ' + esc(c.odontologo) +
            '<br>Actual: ' + esc(c.fecha_fmt) + ' a las ' + esc(c.hora) + ' (' + c.duracion + ' min)';
        $('rep-fecha').min = hoy();
        $('rep-fecha').value = c.fecha;
        $('rep-hora').value = c.hora;
        ['rep-fecha', 'rep-hora'].forEach(id => $(id).classList.remove('campo-invalido'));
        abrirModal('modal-cita-reprogramar');
    }

    async function guardarReprogramacion(btn) {
        if (!validarRequeridos([['rep-fecha', 'Nueva fecha'], ['rep-hora', 'Nueva hora']])) return;
        await guardarJSON('citas.reprogramar', {
            id: Number($('rep-id').value), fecha: $('rep-fecha').value, hora: $('rep-hora').value,
        }, {
            boton: btn, exito: 'Cita reprogramada correctamente.', cerrarModal: 'modal-cita-reprogramar',
            recargar: ['inicio'], alExito: () => cargar(),
        });
    }

    /* ── Cancelar (CIT-02 esc. 3, CIT-06 esc. 2) ────────────── */
    function cancelar(c) {
        $('can-id').value = c.id;
        $('can-motivo').value = '';
        $('can-info').innerHTML = '¿Cancelar la cita de <strong>' + esc(c.paciente) + '</strong> del ' +
            esc(c.fecha_fmt) + ' a las ' + esc(c.hora) + '? El registro se conserva en el historial.';
        abrirModal('modal-cita-cancelar');
    }

    async function guardarCancelacion(btn) {
        await guardarJSON('citas.cancelar', {
            id: Number($('can-id').value), motivo: $('can-motivo').value.trim(),
        }, {
            boton: btn, exito: 'Cita cancelada correctamente.', cerrarModal: 'modal-cita-cancelar',
            recargar: ['inicio'], alExito: () => cargar(),
        });
    }

    /* ── Confirmar / atendida / ausente (CIT-06) ────────────── */
    const TEXTOS_ESTADO = {
        confirmada: ['Confirmar cita', 'La cita quedará como confirmada.'],
        atendida:   ['Registrar cita atendida', 'El paciente se presentó y recibió la atención.'],
        ausente:    ['Registrar paciente ausente', 'El paciente no se presentó a la cita y no hubo cancelación previa.'],
    };

    function cambiarEstado(c, estado) {
        S.pendienteEstado = { id: c.id, estado };
        $('conf-titulo').textContent = TEXTOS_ESTADO[estado][0];
        $('conf-texto').innerHTML = '<strong>' + esc(c.paciente) + '</strong> — ' + esc(c.fecha_fmt) + ' ' + esc(c.hora) +
            '<br>' + esc(TEXTOS_ESTADO[estado][1]);
        abrirModal('modal-cita-confirmar');
    }

    async function guardarEstado(btn) {
        const p = S.pendienteEstado;
        if (!p) return;
        await guardarJSON('citas.estado', p, {
            boton: btn, exito: 'Estado de la cita actualizado.', cerrarModal: 'modal-cita-confirmar',
            recargar: ['inicio'], alExito: () => cargar(),
        });
    }

    /* ── Detalle + historial ────────────────────────────────── */
    async function ver(c) {
        $('det-contenido').innerHTML =
            '<div class="det-grid">' +
            '<div><span>Paciente</span>' + esc(c.paciente) + '</div>' +
            '<div><span>Estado</span>' + insignia(c.estado) + '</div>' +
            '<div><span>Odontólogo</span>' + esc(c.odontologo) + '</div>' +
            '<div><span>Tipo de consulta</span>' + esc(c.tipo_consulta) + '</div>' +
            '<div><span>Fecha</span>' + esc(c.fecha_fmt) + '</div>' +
            '<div><span>Horario</span>' + esc(c.hora) + ' – ' + esc(c.hora_fin) + ' (' + c.duracion + ' min)</div>' +
            '<div class="full"><span>Observaciones</span>' + (c.observaciones ? esc(c.observaciones) : '—') + '</div></div>';
        $('det-historial').innerHTML = '<div class="item">Cargando…</div>';

        $('det-acciones').innerHTML = '<button class="btn-outline-odent" onclick="cerrarModal(\'modal-cita-detalle\')">Cerrar</button>';
        abrirModal('modal-cita-detalle');

        try {
            const d = await get('citas.historial', { id: c.id });
            $('det-historial').innerHTML = (d && d.ok && d.data.length)
                ? d.data.map(h => '<div class="item"><strong>' + esc(cap(h.accion.toLowerCase())) + '</strong> — ' + esc(h.detalle) +
                    '<small>' + esc(h.usuario_nombre) + ' · ' + esc(h.created_at) + '</small></div>').join('')
                : '<div class="item">Sin cambios registrados.</div>';
        } catch (e) {
            $('det-historial').innerHTML = '<div class="item">No se pudo cargar el historial.</div>';
        }
    }

    /* ── Recordatorios (CIT-05) ─────────────────────────────── */
    async function abrirRecordatorios() {
        abrirModal('modal-cita-recordatorios');
        $('rec-contenido').innerHTML = '<div class="agenda-vacio">Cargando…</div>';
        try {
            const d = await get('citas.recordatorios');
            if (!d || !d.ok) { $('rec-contenido').innerHTML = '<div class="agenda-vacio">' + esc(d?.error || 'No se pudo cargar.') + '</div>'; return; }
            $('rec-info').innerHTML = '<i class="bi bi-info-circle"></i> Se envía un recordatorio por correo a los pacientes con citas en las próximas ' +
                d.horas_antes + ' horas. El proceso corre automáticamente (cron) y también puede ejecutarse aquí.';
            $('rec-contenido').innerHTML = d.data.length
                ? '<div class="tabla-scroll"><table class="tabla-odent"><thead><tr><th>Fecha y hora</th><th>Paciente</th><th>Cita</th><th>Medio</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>' +
                  d.data.map(r => '<tr><td>' + esc(r.envio) + '</td><td>' + esc(r.paciente) + '</td><td>' + esc(r.cita) + '</td><td>' + esc(r.medio) +
                      (r.destino ? '<br><small>' + esc(r.destino) + '</small>' : '') + '</td><td><span class="badge-estado badge-' + esc(r.estado) + '">' + esc(cap(r.estado)) +
                      '</span></td><td>' + esc(r.detalle) + '</td></tr>').join('') + '</tbody></table></div>'
                : '<div class="agenda-vacio"><i class="bi bi-bell-slash"></i>Aún no hay recordatorios registrados.</div>';
        } catch (e) {
            $('rec-contenido').innerHTML = '<div class="agenda-vacio">No se pudo cargar el registro.</div>';
        }
    }

    async function ejecutarRecordatorios(btn) {
        const r = await guardarJSON('citas.recordatorios.ejecutar', {}, {
            boton: btn, exito: 'Proceso de recordatorios ejecutado.',
        });
        if (r && r.ok) {
            mostrarToast(r.mensaje, (r.resultado.fallidos || r.resultado.omitidos) ? 'aviso' : 'info', 6000);
            abrirRecordatorios();
        }
    }

    /* ── Eventos ────────────────────────────────────────────── */
    function buscarCita(id) { return S.citas.find(c => c.id === Number(id)); }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-cita-accion]');
        if (!btn) { cerrarMenu(); return; }
        const acc = btn.dataset.citaAccion;

        if (acc === 'menu') {
            const mismo = menuCita === Number(btn.dataset.id);
            cerrarMenu();
            if (!mismo) { const c = buscarCita(btn.dataset.id); if (c) abrirMenu(btn, c); }
            return;
        }
        cerrarMenu();

        if (acc === 'dia') { cambiarVista('dia', btn.dataset.fecha); return; }

        const c = buscarCita(btn.dataset.id);
        if (!c) return;
        if (acc !== 'ver') cerrarModal('modal-cita-detalle');

        if (acc === 'ver') ver(c);
        else if (!GESTION) return;
        else if (acc === 'editar') editar(c);
        else if (acc === 'reprogramar') reprogramar(c);
        else if (acc === 'cancelar') cancelar(c);
        else if (acc === 'confirmar') cambiarEstado(c, 'confirmada');
        else if (acc === 'atendida') cambiarEstado(c, 'atendida');
        else if (acc === 'ausente') cambiarEstado(c, 'ausente');
    });

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarMenu(); });
    window.addEventListener('resize', cerrarMenu);
    window.addEventListener('scroll', cerrarMenu, true);

    function iniciar() {
        document.querySelectorAll('#agenda-vistas button').forEach(b =>
            b.addEventListener('click', () => cambiarVista(b.dataset.vista)));
        $('agenda-prev').addEventListener('click', () => mover(-1));
        $('agenda-sig').addEventListener('click', () => mover(1));
        $('agenda-hoy').addEventListener('click', () => { S.ref = hoy(); cargar(); });
        $('agenda-odontologo').addEventListener('change', cargar);
        $('filtro-buscar').addEventListener('click', cargar);
        $('filtro-limpiar').addEventListener('click', () => {
            ['filtro-q', 'filtro-fecha', 'filtro-estado', 'agenda-odontologo'].forEach(id => $(id).value = '');
            cargar();
        });
        ['filtro-q', 'filtro-fecha'].forEach(id => $(id).addEventListener('keydown', ev => { if (ev.key === 'Enter') cargar(); }));
        $('filtro-estado').addEventListener('change', cargar);

        cargarCatalogos();
        cargar();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();

    return { nueva, cerrarForm, guardar, guardarReprogramacion, guardarCancelacion, guardarEstado, abrirRecordatorios, ejecutarRecordatorios, recargar: cargar };
})();