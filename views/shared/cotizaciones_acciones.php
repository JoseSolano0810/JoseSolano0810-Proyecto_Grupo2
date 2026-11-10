<?php
/**
 * cotizaciones_acciones.php — FIN-01 (consulta/impresión, piezas dentales, IVA) y FIN-02 (estado)
 */
$cotx_clinica = defined('CLINICA_NOMBRE') ? (string) CLINICA_NOMBRE : 'Odent Centro Odontológico';
?>

<!-- ═══════ MODAL CAMBIAR ESTADO DE COTIZACIÓN (FIN-02) ═══════ -->
<div class="modal-overlay" id="modal-cot-estado">
    <div class="modal-caja">
        <div class="modal-header">
            <h3>Estado de la cotización</h3>
            <button class="modal-cerrar" onclick="cotxCancelarEstado()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div id="cotx-estado-resumen" style="font-size:14px;margin-bottom:14px;"></div>
        <input type="hidden" id="cotx-estado-id">

        <div class="campo-grupo">
            <label>Decisión del paciente *</label>
            <select id="cotx-estado-valor">
                <option value="">— Seleccione —</option>
                <option value="aprobada">Aprobada</option>
                <option value="rechazada">Rechazada</option>
            </select>
        </div>
        <div class="campo-grupo">
            <label>Observaciones <span style="color:var(--gris-azulado);font-size:12px;">(opcional)</span></label>
            <textarea id="cotx-estado-obs" rows="3" maxlength="500" placeholder="Motivo o comentarios de la decisión…"></textarea>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-outline-odent" onclick="cotxCancelarEstado()">Cancelar</button>
            <button type="button" class="btn-odent" id="cotx-estado-guardar" onclick="cotxGuardarEstado()">
                <i class="bi bi-check-lg"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- ═══════ MODAL DETALLE / IMPRESIÓN DE COTIZACIÓN (FIN-01) ═══════ -->
<div class="modal-overlay" id="modal-cot-detalle">
    <div class="modal-caja">
        <div class="modal-header">
            <h3>Detalle de la cotización</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cot-detalle')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="cotx-detalle-contenido" style="padding:4px 0 12px;"></div>
        <div class="modal-footer">
            <button type="button" class="btn-outline-odent" onclick="cerrarModal('modal-cot-detalle')">Cerrar</button>
            <button type="button" class="btn-odent" id="cotx-btn-imprimir" onclick="cotxImprimir()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const COTX_BASE = '<?= BASE_URL ?>';
        const COTX_CLINICA = <?= json_encode($cotx_clinica, JSON_UNESCAPED_UNICODE) ?>;

        /* ── utilidades ───────────────────────────────────────── */
        const $ = (id) => document.getElementById(id);

        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        function fmt(n) {
            return '₡' + Number(n ?? 0).toLocaleString('es-CR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function cap(s) {
            s = String(s ?? '');
            return s.charAt(0).toUpperCase() + s.slice(1);
        }

        const BADGE = {
            aprobada: 'confirmada',
            rechazada: 'cancelada',
            pagada: 'completada',
            pendiente: 'pendiente'
        };

        /** Los IDs del formulario cambian entre la vista de recepción y la del administrador */
        function idsForm() {
            return $('cot-id-paciente') ? {
                paciente: 'cot-id-paciente',
                tratamiento: 'cot-id-tratamiento'
            } : {
                paciente: 'cot-paciente-id',
                tratamiento: 'cot-tratamiento'
            };
        }

        async function api(accion, opciones) {
            const res = await fetch(`${COTX_BASE}/index.php?accion=${accion}`, opciones);
            let data = null;
            try {
                data = await res.json();
            } catch (e) {}
            return {
                ok: res.ok && data && data.ok !== false,
                data: data || {}
            };
        }

        /* ── tabla de cotizaciones con columna «Acciones» ─────── */
        let _cache = [];

        function filaCotizacion(c) {
            const pendiente = c.estado === 'pendiente';
            const colTrat = !!document.querySelector('#cot-tbody')?.closest('table')
                ?.querySelectorAll('thead th')[2]?.textContent.match(/Tratamiento/i);

            return `<tr>
                <td><strong>#${c.id_cotizacion}</strong></td>
                <td>${esc(c.paciente)}</td>
                ${colTrat ? `<td>${esc(c.tratamiento)}</td>` : ''}
                <td>${esc(c.fecha)}</td>
                <td>₡${Number(c.total).toLocaleString('es-CR')}</td>
                <td><span class="badge-estado badge-${BADGE[c.estado] ?? 'pendiente'}">${esc(cap(c.estado))}</span></td>
                <td style="white-space:nowrap;">
                    <button type="button" class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"
                        onclick="cotxVerDetalle(${c.id_cotizacion})" title="Ver / imprimir">
                        <i class="bi bi-printer"></i> Ver / Imprimir
                    </button>
                    ${pendiente ? `<button type="button" class="btn-odent" style="font-size:12px;padding:4px 10px;margin-left:4px;"
                        onclick="cotxAbrirEstado(${c.id_cotizacion})" title="Aprobar o rechazar">
                        <i class="bi bi-check2-square"></i> Estado
                    </button>` : ''}
                </td>
            </tr>`;
        }

        window.recargarCotizaciones = async function() {
            try {
                const {
                    data
                } = await api('cotizaciones.listar');
                if (!Array.isArray(data)) return;
                _cache = data;
                const tbody = $('cot-tbody');
                if (!tbody) return;
                const cols = tbody.closest('table')?.querySelectorAll('thead th').length || 7;
                tbody.innerHTML = data.length ?
                    data.map(filaCotizacion).join('') :
                    `<tr><td colspan="${cols}" style="text-align:center;color:#888;">Sin cotizaciones registradas</td></tr>`;
            } catch (e) {}
        };

        /* ── FIN-02: aprobar / rechazar ───────────────────────── */
        window.cotxAbrirEstado = function(id) {
            const c = _cache.find((x) => Number(x.id_cotizacion) === Number(id));
            if (!c) return mostrarToast('No se encontró la cotización.', 'aviso');
            if (c.estado !== 'pendiente') {
                return mostrarToast('Solo se puede cambiar el estado de una cotización pendiente.', 'aviso');
            }
            $('cotx-estado-id').value = c.id_cotizacion;
            $('cotx-estado-valor').value = '';
            $('cotx-estado-obs').value = '';
            $('cotx-estado-resumen').innerHTML =
                `<strong>Cotización #${c.id_cotizacion}</strong> · ${esc(c.paciente)}<br>` +
                `<span style="color:var(--gris-azulado);">${esc(c.tratamiento)} — Total ${fmt(c.total)}</span>`;
            abrirModal('modal-cot-estado');
        };

        /** Escenario 3: descarta lo escrito y deja el estado anterior sin cambios */
        window.cotxCancelarEstado = function() {
            $('cotx-estado-valor').value = '';
            $('cotx-estado-obs').value = '';
            cerrarModal('modal-cot-estado');
        };

        window.cotxGuardarEstado = async function() {
            const id = $('cotx-estado-id').value;
            const estado = $('cotx-estado-valor').value;
            const obs = $('cotx-estado-obs').value.trim();

            if (!estado) return mostrarToast('Seleccione «Aprobada» o «Rechazada».', 'aviso');

            const btn = $('cotx-estado-guardar');
            btn.disabled = true;
            try {
                const {
                    ok,
                    data
                } = await api('cotizaciones.estado', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_cotizacion: Number(id),
                        estado,
                        observaciones: obs
                    })
                });
                if (!ok) {
                    return mostrarToast(data.mensaje || 'No se pudo actualizar el estado.', 'peligro');
                }
                mostrarToast(data.mensaje || 'Estado actualizado.', 'exito');
                cotxCancelarEstado();
                window.recargarCotizaciones();
            } catch (e) {
                mostrarToast('Error de conexión.', 'peligro');
            } finally {
                btn.disabled = false;
            }
        };

        /* ── FIN-01 esc. 3: consultar e imprimir ──────────────── */
        let _detalleActual = null;

        function htmlDetalle(c, paraImpresion) {
            const fila = (et, val) => `<tr><td style="padding:6px 10px;color:#666;width:34%;">${et}</td><td style="padding:6px 10px;"><strong>${val}</strong></td></tr>`;
            const decision = c.fecha_decision ?
                fila('Decisión registrada', esc(c.fecha_decision) + (c.observaciones_decision ? ' — ' + esc(c.observaciones_decision) : '')) : '';
            return `
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    ${fila('Cotización N.º', '#' + c.id_cotizacion)}
                    ${fila('Fecha', esc(c.fecha))}
                    ${fila('Estado', esc(cap(c.estado)))}
                    ${fila('Paciente', esc(c.paciente))}
                    ${fila('Tratamiento', esc(c.tratamiento))}
                    ${fila('Piezas dentales', esc(c.piezas_dentales || '—'))}
                    ${fila('Descripción', esc(c.descripcion || '—'))}
                    ${decision}
                </table>
                <table style="width:100%;border-collapse:collapse;font-size:14px;margin-top:14px;${paraImpresion ? 'border-top:1px solid #999;' : 'background:var(--fondo-odent,#f8f9fa);border-radius:8px;'}">
                    ${fila('Subtotal', fmt(c.subtotal))}
                    ${fila('Descuento', '− ' + fmt(c.descuento))}
                    ${fila('Impuesto (' + Number(c.tasa_iva) + '%)', fmt(c.iva))}
                    ${fila('Total final', `<span style="font-size:16px;">${fmt(c.total)}</span>`)}
                </table>
                ${c.observaciones ? `<p style="font-size:13px;margin-top:12px;"><strong>Observaciones:</strong> ${esc(c.observaciones)}</p>` : ''}`;
        }

        window.cotxVerDetalle = async function(id) {
            try {
                const {
                    ok,
                    data
                } = await api('cotizaciones.detalle&id=' + encodeURIComponent(id));
                if (!ok) return mostrarToast(data.mensaje || 'No se pudo cargar la cotización.', 'peligro');
                _detalleActual = data.data;
                $('cotx-detalle-contenido').innerHTML = htmlDetalle(_detalleActual, false);
                abrirModal('modal-cot-detalle');
            } catch (e) {
                mostrarToast('Error de conexión.', 'peligro');
            }
        };

        /** Genera el documento y abre el diálogo de impresión (iframe oculto: no lo bloquea el navegador) */
        window.cotxImprimir = function() {
            if (!_detalleActual) return;
            const c = _detalleActual;
            const doc = `<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
                <title>Cotización #${c.id_cotizacion}</title>
                <style>
                    body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:32px;}
                    h1{font-size:20px;margin:0;} h2{font-size:15px;font-weight:normal;color:#555;margin:2px 0 22px;}
                    td{vertical-align:top;} .pie{margin-top:40px;font-size:12px;color:#666;}
                </style></head><body>
                <h1>${esc(COTX_CLINICA)}</h1>
                <h2>Cotización de tratamiento odontológico</h2>
                ${htmlDetalle(c, true)}
                <p class="pie">Cotización generada el ${esc(c.fecha)}. Los montos están expresados en colones (₡).
                Este documento es un estimado informado al paciente.</p>
                </body></html>`;

            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
            document.body.appendChild(iframe);
            iframe.contentDocument.open();
            iframe.contentDocument.write(doc);
            iframe.contentDocument.close();
            iframe.onload = null;
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => iframe.remove(), 2000);
            }, 250);
        };

        /* ── FIN-01: formulario (piezas dentales + impuesto) ──── */
        window.cotRecalcular = function() {
            const sub = parseFloat($('cot-subtotal')?.value) || 0;
            const desc = parseFloat($('cot-descuento')?.value) || 0;
            const pctTxt = $('cot-iva-pct')?.value;
            const pct = pctTxt === '' || pctTxt === undefined ? 13 : (parseFloat(pctTxt) || 0);
            const base = Math.max(0, sub - desc);
            const iva = Math.round(base * (pct / 100) * 100) / 100;
            const tot = Math.round((base + iva) * 100) / 100;
            if ($('cot-iva')) $('cot-iva').value = base > 0 ? fmt(iva) : '';
            if ($('cot-total')) $('cot-total').value = base > 0 ? fmt(tot) : '';
        };

        window.cotGuardar = async function() {
            const ids = idsForm();
            const idPaciente = $(ids.paciente)?.value;
            const idTratamiento = $(ids.tratamiento)?.value;
            const piezas = ($('cot-piezas')?.value || '').trim();
            const descripcion = ($('cot-descripcion')?.value || '').trim();
            const subtotal = parseFloat($('cot-subtotal')?.value) || 0;
            const descuento = parseFloat($('cot-descuento')?.value) || 0;
            const pct = $('cot-iva-pct')?.value;

            // Escenario 2: avisar exactamente qué falta
            const faltan = [];
            if (!idPaciente) faltan.push('paciente');
            if (!idTratamiento) faltan.push('tratamiento');
            if (!piezas) faltan.push('piezas dentales');
            if (!descripcion) faltan.push('descripción');
            if (subtotal <= 0) faltan.push('subtotal');
            if (faltan.length) {
                return mostrarToast('Complete los campos obligatorios: ' + faltan.join(', ') + '.', 'aviso', 6000);
            }

            const btn = $('cot-btn-guardar');
            if (btn) btn.disabled = true;
            try {
                const {
                    ok,
                    data
                } = await api('cotizaciones.crear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_paciente: Number(idPaciente),
                        id_tratamiento: Number(idTratamiento),
                        piezas_dentales: piezas,
                        descripcion,
                        subtotal,
                        descuento,
                        iva_porcentaje: pct === '' ? '' : Number(pct),
                        observaciones: ($('cot-observaciones')?.value || '').trim()
                    })
                });
                if (!ok) {
                    return mostrarToast(data.mensaje || 'No se pudo crear la cotización.', 'peligro', 6000);
                }
                mostrarToast(`Cotización #${data.id_cotizacion} creada. Total: ${fmt(data.total)}`, 'exito');
                cerrarModal('modal-nueva-cotizacion');

                [ids.paciente, 'cot-piezas', 'cot-subtotal', 'cot-iva', 'cot-total', 'cot-descripcion', 'cot-observaciones']
                .forEach((i) => {
                    if ($(i)) $(i).value = '';
                });
                if ($('cot-descuento')) $('cot-descuento').value = '0';
                if ($('cot-iva-pct')) $('cot-iva-pct').value = '13';
                cotRenderPicker([]);
                if ($(ids.tratamiento)) $(ids.tratamiento).innerHTML = '<option value="">— Seleccione primero un paciente —</option>';
                window.recargarCotizaciones();
            } catch (e) {
                mostrarToast('Error de conexión.', 'peligro');
            } finally {
                if (btn) btn.disabled = false;
            }
        };

        /* ── Cancelar el registro de pago: descarta lo escrito y cierra, sin abrir nada más ── */
        window.pagoCancelar = function() {
            if ($('pago-id-cotizacion')) $('pago-id-cotizacion').value = '';
            if ($('pago-id-paciente')) $('pago-id-paciente').value = '';
            if ($('pago-sel-cotizacion')) $('pago-sel-cotizacion').innerHTML = '<option value="">— Seleccione primero un paciente —</option>';
            if ($('pago-monto')) $('pago-monto').value = '';
            if ($('pago-referencia')) $('pago-referencia').value = '';
            if ($('pago-saldo-info')) $('pago-saldo-info').style.display = 'none';

            cerrarModal('modal-nuevo-pago');
        };


        /* ── Odontograma para elegir las piezas (FDI) ─────────────
           Usa las mismas clases (.tr-pieza, .d-molar…) del módulo de tratamientos.
           El valor viaja en #cot-piezas como «16, 26, 36», igual que antes. */
        const COT_SUP = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
        const COT_INF = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

        function cotTipoDiente(n) {
            const u = n % 10;
            return u <= 2 ? 'incisivo' : u === 3 ? 'canino' : u <= 5 ? 'premolar' : 'molar';
        }

        function cotSincronizarPiezas() {
            const activas = [...document.querySelectorAll('#cot-picker .tr-pieza.activa')]
                .map((b) => Number(b.dataset.pieza)).sort((a, b) => a - b);
            if ($('cot-piezas')) $('cot-piezas').value = activas.join(', ');
            const res = $('cot-picker-resumen');
            if (res) {
                const hayTrat = !!$(idsForm().tratamiento)?.value;
                res.innerHTML = activas.length ?
                    `Piezas del tratamiento (${activas.length}): ` + activas.map((p) => `<span class="tr-chip">${p}</span>`).join('') :
                    (hayTrat ? 'Este tratamiento no tiene piezas registradas.' : 'Seleccione un tratamiento para ver sus piezas dentales.');
            }
        }

        function cotRenderPicker(seleccionadas) {
            const cont = $('cot-picker');
            if (!cont) return;
            const sel = new Set((seleccionadas || []).map(Number));
            const fila = (nums, zona) => nums.map((n) =>
                `<button type="button" class="tr-pieza d-${cotTipoDiente(n)} ${zona} ${sel.has(n) ? 'activa' : ''}"
                    data-pieza="${n}" title="Pieza ${n}" tabindex="-1" aria-disabled="true"><span>${n}</span></button>`).join('');
            cont.innerHTML = `
                <p class="tr-picker-etq">MAXILAR SUPERIOR</p>
                <div class="tr-picker-fila">${fila(COT_SUP, 'sup')}</div>
                <div class="tr-picker-linea"></div>
                <div class="tr-picker-fila">${fila(COT_INF, 'inf')}</div>
                <p class="tr-picker-etq" style="margin:8px 0 0;">MAXILAR INFERIOR</p>`;
            cotSincronizarPiezas();
        }
        window.cotRenderPicker = cotRenderPicker;

        /** El odontograma es de solo lectura: siempre refleja las piezas del tratamiento elegido */
        function cotAplicarPiezas() {
            const sel = $(idsForm().tratamiento);
            const t = _cotTrats.find((x) => String(x.id_tratamiento) === String(sel?.value));
            cotRenderPicker(t && Array.isArray(t.piezas) ? t.piezas : []);
        }

        /* ── Tratamientos del paciente (siempre desde el servidor) ─ */
        let _cotTrats = [];

        window.cotCargarTratamientos = async function(idPaciente, conservar) {
            const ids = idsForm();
            const sel = $(ids.tratamiento);
            if (!sel) return;
            if (idPaciente === undefined || idPaciente === null) idPaciente = $(ids.paciente)?.value;
            if (!idPaciente) {
                sel.innerHTML = '<option value="">— Seleccione primero un paciente —</option>';
                _cotTrats = [];
                cotRenderPicker([]);
                return;
            }
            const previo = conservar ? sel.value : '';
            sel.innerHTML = '<option value="">— Cargando… —</option>';
            try {
                const res = await fetch(`${COTX_BASE}/index.php?accion=cotizaciones.tratamientosDisponibles&_=${Date.now()}`, {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (!data.ok) throw new Error(data.mensaje);
                // el paciente pudo cambiar mientras llegaba la respuesta
                if (String($(ids.paciente)?.value) !== String(idPaciente)) return;
                _cotTrats = (data.tratamientos || []).filter((t) => String(t.id_paciente) === String(idPaciente));
                if (!_cotTrats.length) {
                    sel.innerHTML = '<option value="">— Sin tratamientos disponibles —</option>';
                    cotRenderPicker([]);
                    return;
                }
                sel.innerHTML = '<option value="">— Seleccione un tratamiento —</option>' +
                    _cotTrats.map((t) => `<option value="${t.id_tratamiento}">${esc(t.nombre ?? 'Tratamiento #' + t.id_tratamiento)}</option>`).join('');
                if (previo && _cotTrats.some((t) => String(t.id_tratamiento) === String(previo))) sel.value = previo;
                cotAplicarPiezas();
            } catch (e) {
                sel.innerHTML = '<option value="">— Error al cargar tratamientos —</option>';
                cotRenderPicker([]);
            }
        };

        /** Lo llama el módulo de tratamientos al crear un plan, para que aparezca sin recargar la página */
        window.cotRefrescarTratamientos = function() {
            if ($(idsForm().paciente)?.value) window.cotCargarTratamientos(undefined, true);
        };

        /* Al elegir tratamiento se precargan sus piezas en el odontograma */
        document.addEventListener('change', (e) => {
            if (!e.target || e.target.id !== idsForm().tratamiento) return;
            cotAplicarPiezas();
        });

        /* Cada vez que se abre el modal: odontograma limpio y tratamientos al día */
        function observarModalCotizacion() {
            const overlay = $('modal-nueva-cotizacion');
            if (!overlay) return;
            let abierto = overlay.classList.contains('abierto');
            new MutationObserver(() => {
                const ahora = overlay.classList.contains('abierto');
                if (ahora && !abierto) {
                    window.cotCargarTratamientos(undefined, true);
                }
                abierto = ahora;
            }).observe(overlay, {
                attributes: true,
                attributeFilter: ['class']
            });
        }

        /* ── arranque ─────────────────────────────────────────── */
        function iniciar() {
            ['cot-iva-pct', 'cot-subtotal', 'cot-descuento'].forEach((id) => {
                $(id)?.addEventListener('input', window.cotRecalcular);
            });
            cotRenderPicker([]);
            observarModalCotizacion();
            window.recargarCotizaciones();
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
        else iniciar();
    })();
</script>