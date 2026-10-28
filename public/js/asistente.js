(function () {
    'use strict';

    const app = document.getElementById('asi-app');
    if (!app) return;

    const $mensajes = document.getElementById('asi-mensajes');
    const $form     = document.getElementById('asi-form');
    const $texto    = document.getElementById('asi-texto');
    const $enviar   = document.getElementById('asi-enviar');
    const $atajos   = document.getElementById('asi-atajos');
    const $barra    = document.getElementById('asi-barra-flujo');
    const $alerta   = document.getElementById('asi-alerta-urgente');
    const $alertaTexto   = $alerta.querySelector('.asi-alerta-texto span');
    const $alertaLlamar  = $alerta.querySelector('a.asi-btn-peligro');
    const alertaTextoOriginal = $alertaTexto.innerHTML;

    const TELEFONO = (app.dataset.telefono || '').trim();
    const CLINICA  = app.dataset.clinica || 'la clínica';

    const PLACEHOLDER = 'Escriba su consulta…';
    const PASOS = ['descripcion', 'zona', 'intensidad', 'tiempo', 'resumen'];

    const estado = {
        ocupado: false,
        enFlujo: false,
        paso: null,
        volverAResumen: false,
        catalogos: null,
        datos: { descripcion: '', zona: '', intensidad: null, tiempo: '' },
    };

    /* ── Utilidades DOM ─────────────────────────────────────── */

    function el(tag, clase, texto) {
        const e = document.createElement(tag);
        if (clase) e.className = clase;
        if (texto !== undefined && texto !== null) e.textContent = texto;
        return e;
    }

    function icono(nombre) {
        return el('i', 'bi bi-' + nombre);
    }

    function bajar() {
        $mensajes.scrollTop = $mensajes.scrollHeight;
    }

    function burbujaBot(...hijos) {
        const m = el('div', 'asi-msg asi-bot');
        hijos.forEach(h => m.appendChild(typeof h === 'string' ? document.createTextNode(h) : h));
        $mensajes.appendChild(m);
        bajar();
        return m;
    }

    function burbujaUsuario(texto) {
        const m = el('div', 'asi-msg asi-usuario', texto);
        $mensajes.appendChild(m);
        bajar();
    }

    function escribiendo() {
        const m = el('div', 'asi-msg asi-bot asi-escribiendo');
        m.append(el('span'), el('span'), el('span'));
        $mensajes.appendChild(m);
        bajar();
        return m;
    }

    function bloquear(contenedor) {
        contenedor.classList.add('asi-bloqueado');
        contenedor.querySelectorAll('button, textarea, input').forEach(b => { b.disabled = true; });
    }

    function fijarOcupado(v) {
        estado.ocupado = v;
        $enviar.disabled = v;
        $atajos.querySelectorAll('button').forEach(b => { b.disabled = v; });
    }

    function fijarEntradaTexto(habilitada, placeholder) {
        $texto.disabled = !habilitada;
        $texto.placeholder = placeholder || PLACEHOLDER;
        $enviar.disabled = !habilitada || estado.ocupado;
        if (habilitada) $texto.focus();
    }

    /* ── Red ────────────────────────────────────────────────── */

    async function api(accion, cuerpo) {
        const opciones = cuerpo === undefined
            ? { headers: { 'Accept': 'application/json' } }
            : {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(cuerpo),
            };

        const res = await fetch(window.ODENT.base + '/index.php?accion=' + accion, opciones);

        if (res.status === 401) {
            if (typeof mostrarToast === 'function') mostrarToast('Su sesión expiró. Inicie sesión nuevamente.', 'aviso');
            setTimeout(() => { location.href = window.ODENT.base + '/index.php?accion=logout&motivo=inactividad'; }, 1500);
            throw new Error('sesion');
        }

        let data;
        try { data = await res.json(); }
        catch (e) { throw new Error('El servidor devolvió una respuesta inválida.'); }
        return data;
    }

    function errorDeRed(e) {
        if (e && e.message === 'sesion') return;
        burbujaBot(navigator.onLine
            ? 'Ocurrió un problema al procesar su solicitud. Intente de nuevo en unos minutos.'
            : 'Parece que no tiene conexión a internet. Intente de nuevo cuando se restablezca.');
    }

    /* ── Contacto con la clínica ────────────────────────────── */

    function bloqueContacto() {
        const c = el('div', 'asi-contacto');
        if (TELEFONO) {
            const a = el('a', 'asi-btn asi-btn-primario');
            a.href = 'tel:' + TELEFONO.replace(/[^0-9+]/g, '');
            a.append(icono('telephone-fill'), document.createTextNode(' Llamar a ' + CLINICA + ' (' + TELEFONO + ')'));
            c.appendChild(a);
        } else {
            c.append(icono('telephone'), document.createTextNode(' Comuníquese con la recepción de ' + CLINICA + '.'));
        }
        return c;
    }

    /* ── ASI-01: consultas ──────────────────────────────────── */

    async function consultar(carga, etiquetaUsuario) {
        if (estado.ocupado) return;
        if (estado.enFlujo) {
            burbujaBot('Está en medio de un reporte. Termínelo o cancélelo para hacer otra consulta.');
            return;
        }

        burbujaUsuario(etiquetaUsuario);
        fijarOcupado(true);
        const espera = escribiendo();

        try {
            const r = await api('asistente.consulta', carga);
            espera.remove();
            if (!r.ok) { burbujaBot(r.error || 'No pude procesar su consulta.'); return; }
            pintarRespuesta(r.respuesta);
        } catch (e) {
            espera.remove();
            errorDeRed(e);
        } finally {
            fijarOcupado(false);
        }
    }

    function pintarRespuesta(r) {
        switch (r.tipo) {
            case 'cita':           return pintarCita(r);
            case 'lista':          return pintarLista(r);
            case 'sin_datos':      return pintarSinDatos(r);
            case 'sugerir_reporte':return pintarSugerencia(r);
            default:               return burbujaBot(r.mensaje || 'No logré identificar su consulta. Elija una de las opciones de abajo.');
        }
    }

    function pintarCita(r) {
        const tarjeta = el('div', 'asi-tarjeta');
        tarjeta.appendChild(el('h4', null, r.titulo));
        const dl = el('dl', 'asi-datos');
        r.datos.forEach(d => {
            const fila = el('div');
            fila.append(el('dt', null, d.etiqueta), el('dd', null, d.valor));
            dl.appendChild(fila);
        });
        tarjeta.appendChild(dl);
        burbujaBot(r.mensaje, tarjeta);
    }

    function pintarLista(r) {
        const cont = el('div', 'asi-lista');
        r.items.forEach(it => {
            const t = el('div', 'asi-tarjeta');
            t.appendChild(el('h4', null, it.titulo));
            Object.values(it.lineas || {}).forEach(l => t.appendChild(el('p', null, l)));
            if (typeof it.progreso === 'number') {
                const barra = el('div', 'barra-progreso');
                const rel = el('div', 'barra-relleno');
                rel.style.width = Math.max(0, Math.min(100, it.progreso)) + '%';
                barra.appendChild(rel);
                t.appendChild(barra);
            }
            cont.appendChild(t);
        });
        burbujaBot(r.mensaje, cont);
    }

    function pintarSinDatos(r) {
        const caja = el('div', 'asi-sin-datos');
        caja.appendChild(icono('info-circle'));
        caja.appendChild(el('span', null, r.mensaje));
        burbujaBot(caja, bloqueContacto());
    }

    function pintarSugerencia(r) {
        const acciones = el('div', 'asi-opciones');
        const si = el('button', 'asi-btn asi-btn-primario', 'Sí, iniciar reporte');
        const no = el('button', 'asi-btn asi-btn-secundario', 'No, gracias');
        si.type = no.type = 'button';
        si.addEventListener('click', () => { bloquear(acciones); burbujaUsuario('Sí, iniciar reporte'); iniciarReporte(); });
        no.addEventListener('click', () => { bloquear(acciones); burbujaUsuario('No, gracias'); burbujaBot('De acuerdo. Si necesita algo más, elija una opción o escriba su consulta.'); });
        acciones.append(si, no);
        burbujaBot(r.mensaje, acciones);
    }

    /* ── ASI-02: reporte guiado ─────────────────────────────── */

    async function iniciarReporte() {
        if (estado.ocupado) return;
        if (estado.enFlujo) { burbujaBot('Ya tiene un reporte en curso.'); return; }

        if (!estado.catalogos) {
            fijarOcupado(true);
            try {
                const r = await api('asistente.catalogos');
                if (!r.ok) throw new Error(r.error || 'No se pudieron cargar las opciones.');
                estado.catalogos = r;
            } catch (e) {
                errorDeRed(e);
                fijarOcupado(false);
                return;
            }
            fijarOcupado(false);
        }

        estado.enFlujo = true;
        estado.volverAResumen = false;
        estado.datos = { descripcion: '', zona: '', intensidad: null, tiempo: '' };
        $barra.hidden = false;
        $atajos.classList.add('asi-atajos-ocultos');

        burbujaBot('Lamento que tenga una molestia. Le haré cuatro preguntas rápidas para que la clínica pueda orientarle. Puede cancelar en cualquier momento.');
        irAPaso('descripcion');
    }

    function irAPaso(paso) {
        estado.paso = paso;
        switch (paso) {
            case 'descripcion': return pasoDescripcion();
            case 'zona':        return pasoZona();
            case 'intensidad':  return pasoIntensidad();
            case 'tiempo':      return pasoTiempo();
            case 'resumen':     return pasoResumen();
        }
    }

    function siguientePaso() {
        if (estado.volverAResumen) { estado.volverAResumen = false; return irAPaso('resumen'); }
        irAPaso(PASOS[PASOS.indexOf(estado.paso) + 1]);
    }

    function pasoDescripcion() {
        burbujaBot('1/4 — Describa brevemente qué siente o qué problema tiene con su tratamiento.');
        fijarEntradaTexto(true, 'Ej.: Me duele al masticar del lado derecho…');
    }

    function recibirDescripcion(texto) {
        burbujaUsuario(texto);
        if (texto.length < 5) {
            burbujaBot('Necesito un poco más de detalle (al menos 5 caracteres). ¿Qué es lo que siente?');
            return;
        }
        estado.datos.descripcion = texto.slice(0, 500);
        fijarEntradaTexto(false, 'Elija una opción…');
        siguientePaso();
    }

    function pasoZona() {
        fijarEntradaTexto(false, 'Elija una opción…');
        const grupo = el('div', 'asi-opciones');
        Object.entries(estado.catalogos.zonas).forEach(([codigo, etiqueta]) => {
            const b = el('button', 'asi-chip', etiqueta);
            b.type = 'button';
            b.addEventListener('click', () => {
                estado.datos.zona = codigo;
                bloquear(grupo);
                burbujaUsuario(etiqueta);
                siguientePaso();
            });
            grupo.appendChild(b);
        });
        burbujaBot('2/4 — ¿En qué zona siente la molestia?', grupo);
    }

    function pasoIntensidad() {
        const grupo = el('div', 'asi-escala');
        for (let n = 1; n <= 10; n++) {
            const b = el('button', 'asi-nivel asi-nivel-' + (n <= 4 ? 'leve' : n <= 7 ? 'media' : 'alta'), String(n));
            b.type = 'button';
            b.setAttribute('aria-label', 'Intensidad ' + n);
            b.addEventListener('click', () => {
                estado.datos.intensidad = n;
                bloquear(grupo.parentElement);
                burbujaUsuario('Intensidad ' + n + ' de 10');
                siguientePaso();
            });
            grupo.appendChild(b);
        }
        const leyenda = el('div', 'asi-escala-leyenda');
        leyenda.append(el('span', null, '1 = muy leve'), el('span', null, '10 = insoportable'));
        const caja = el('div');
        caja.append(grupo, leyenda);
        burbujaBot('3/4 — ¿Qué tan fuerte es? Del 1 al 10.', caja);
    }

    function pasoTiempo() {
        const grupo = el('div', 'asi-opciones');
        Object.entries(estado.catalogos.tiempos).forEach(([codigo, etiqueta]) => {
            const b = el('button', 'asi-chip', etiqueta);
            b.type = 'button';
            b.addEventListener('click', () => {
                estado.datos.tiempo = codigo;
                bloquear(grupo);
                burbujaUsuario(etiqueta);
                siguientePaso();
            });
            grupo.appendChild(b);
        });
        burbujaBot('4/4 — ¿Hace cuánto comenzó?', grupo);
    }

    function faltantes() {
        const d = estado.datos, f = [];
        if (d.descripcion.length < 5) f.push('descripcion');
        if (!d.zona)                  f.push('zona');
        if (!d.intensidad)            f.push('intensidad');
        if (!d.tiempo)                f.push('tiempo');
        return f;
    }

    const ETIQUETA_CAMPO = {
        descripcion: 'Descripción', zona: 'Zona afectada', intensidad: 'Intensidad', tiempo: 'Tiempo desde que comenzó',
    };

    function valorLegible(campo) {
        const d = estado.datos;
        if (campo === 'zona')       return estado.catalogos.zonas[d.zona] || '—';
        if (campo === 'tiempo')     return estado.catalogos.tiempos[d.tiempo] || '—';
        if (campo === 'intensidad') return d.intensidad ? d.intensidad + ' de 10' : '—';
        return d.descripcion || '—';
    }

    function pasoResumen() {
        fijarEntradaTexto(false, 'Confirme o cancele el reporte…');

        const tarjeta = el('div', 'asi-tarjeta');
        tarjeta.appendChild(el('h4', null, 'Resumen de su reporte'));
        const dl = el('dl', 'asi-datos');
        Object.keys(ETIQUETA_CAMPO).forEach(campo => {
            const fila = el('div');
            const dd = el('dd');
            dd.appendChild(document.createTextNode(valorLegible(campo) + ' '));
            const cambiar = el('button', 'asi-enlace', 'Cambiar');
            cambiar.type = 'button';
            cambiar.addEventListener('click', () => {
                bloquear(caja);
                estado.volverAResumen = true;
                irAPaso(campo);
            });
            dd.appendChild(cambiar);
            fila.append(el('dt', null, ETIQUETA_CAMPO[campo]), dd);
            dl.appendChild(fila);
        });
        tarjeta.appendChild(dl);

        const acciones = el('div', 'asi-opciones');
        const ok = el('button', 'asi-btn asi-btn-primario');
        ok.type = 'button';
        ok.append(icono('send-check'), document.createTextNode(' Confirmar envío'));
        ok.addEventListener('click', () => confirmarEnvio(caja));
        const no = el('button', 'asi-btn asi-btn-secundario');
        no.type = 'button';
        no.append(icono('x-circle'), document.createTextNode(' Cancelar'));
        no.addEventListener('click', () => { bloquear(caja); cancelarReporte(); });
        acciones.append(ok, no);

        const caja = el('div');
        caja.append(tarjeta, acciones);
        burbujaBot('Revise la información antes de enviarla:', caja);
    }

    async function confirmarEnvio(caja) {
        if (estado.ocupado) return;

        const f = faltantes();
        if (f.length) {
            bloquear(caja);
            burbujaBot('Aún falta información para enviar su reporte: ' + f.map(c => ETIQUETA_CAMPO[c].toLowerCase()).join(', ') + '.');
            estado.volverAResumen = true;
            irAPaso(f[0]);
            return;
        }

        fijarOcupado(true);
        bloquear(caja);
        burbujaUsuario('Confirmar envío');
        const espera = escribiendo();

        try {
            const r = await api('asistente.reporte.enviar', estado.datos);
            espera.remove();

            if (!r.ok) {
                burbujaBot(r.error || 'No se pudo enviar el reporte. Intente de nuevo.');
                const primero = r.faltantes ? Object.keys(r.faltantes)[0] : null;
                if (primero) { estado.volverAResumen = true; irAPaso(primero); }
                else { irAPaso('resumen'); }
                return;
            }

            terminarFlujo();
            pintarResultado(r);
        } catch (e) {
            espera.remove();
            errorDeRed(e);
            irAPaso('resumen');
        } finally {
            fijarOcupado(false);
        }
    }

    async function cancelarReporte() {
        if (!estado.enFlujo) return;
        burbujaUsuario('Cancelar reporte');
        terminarFlujo();
        try { await api('asistente.reporte.cancelar', {}); } catch (e) { /* no bloquea al paciente */ }
        burbujaBot('Su reporte no fue enviado. No se guardó ninguna información. Si lo necesita, puede iniciar uno nuevo cuando quiera.');
    }

    function terminarFlujo() {
        estado.enFlujo = false;
        estado.paso = null;
        estado.volverAResumen = false;
        estado.datos = { descripcion: '', zona: '', intensidad: null, tiempo: '' };
        $barra.hidden = true;
        $atajos.classList.remove('asi-atajos-ocultos');
        fijarEntradaTexto(true);
    }

    /* ── ASI-03: resultado con prioridad ────────────────────── */

    function pintarResultado(r) {
        const nivel = ['normal', 'prioritario', 'urgente'].includes(r.prioridad) ? r.prioridad : 'normal';
        const iconos = { normal: 'check-circle-fill', prioritario: 'exclamation-triangle-fill', urgente: 'exclamation-octagon-fill' };

        const caja = el('div', 'asi-resultado asi-resultado-' + nivel);
        const cab = el('div', 'asi-resultado-cab');
        cab.append(icono(iconos[nivel]), el('strong', null, r.titulo));
        caja.append(cab, el('p', null, r.mensaje));

        if (r.consejos && r.consejos.length) {
            caja.appendChild(el('h5', null, 'Recomendaciones generales'));
            const ul = el('ul');
            r.consejos.forEach(c => ul.appendChild(el('li', null, c)));
            caja.appendChild(ul);
        }

        // Con la clínica abierta: en urgente basta el botón "Llamar ahora" de la alerta superior;
        // en prioritario se muestra el botón de llamada dentro del resultado.
        // Con la clínica cerrada no se ofrece llamar: se dan indicaciones y la hora de apertura.
        const abierta = r.clinica_abierta !== false;
        if (nivel === 'prioritario' && abierta) caja.appendChild(bloqueContacto());

        caja.appendChild(el('small', null, 'Reporte N.º ' + r.id_reporte + ' registrado.'));
        burbujaBot('Su reporte fue enviado a la clínica.', caja);

        if (nivel === 'urgente') {
            if (abierta) {
                $alertaTexto.innerHTML = alertaTextoOriginal;
                if ($alertaLlamar) $alertaLlamar.hidden = false;
            } else {
                $alertaTexto.textContent = 'La clínica está cerrada ahora. Siga las indicaciones de abajo y llame'
                    + (TELEFONO ? ' al ' + TELEFONO : '') + ' ' + (r.proxima_apertura || 'cuando abra')
                    + ((r.proxima_apertura || '').endsWith('.') ? ' ' : '. ')
                    + 'Si es grave, acuda a urgencias o llame al 911.';
                if ($alertaLlamar) $alertaLlamar.hidden = true;
            }
            $alerta.hidden = false;
            $alerta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (typeof mostrarToast === 'function') {
                mostrarToast(abierta
                    ? 'Su reporte es urgente. Llame de inmediato a la clínica' + (TELEFONO ? ' al ' + TELEFONO : '') + '.'
                    : 'Su reporte es urgente. La clínica está cerrada. Siga las indicaciones y, si es grave, acuda a urgencias o llame al 911.',
                    'peligro', 8000);
            }
        }
    }

    /* ── Eventos ────────────────────────────────────────────── */

    $form.addEventListener('submit', function (e) {
        e.preventDefault();
        const texto = $texto.value.trim();
        if (!texto || estado.ocupado || $texto.disabled) return;
        $texto.value = '';

        if (estado.enFlujo && estado.paso === 'descripcion') return recibirDescripcion(texto);
        consultar({ texto: texto }, texto);
    });

    $atajos.addEventListener('click', function (e) {
        const b = e.target.closest('button');
        if (!b || b.disabled) return;
        if (b.dataset.accion === 'reportar') {
            burbujaUsuario('Reportar un problema');
            return iniciarReporte();
        }
        consultar({ intencion: b.dataset.intencion }, b.textContent.trim());
    });

    document.getElementById('asi-cancelar-reporte').addEventListener('click', cancelarReporte);
    document.getElementById('asi-alerta-cerrar').addEventListener('click', () => { $alerta.hidden = true; });

    /* ── Saludo inicial ─────────────────────────────────────── */

    burbujaBot('Hola, soy el asistente de ' + CLINICA + '. Puedo mostrarle información de su atención o ayudarle a reportar una molestia. Elija una opción o escriba su consulta.');
})();