<?php
/**
 * reportes_pagina.php — REP-01 y REP-02
 * Reportes financiero y de citas para el administrador.
 */
?>
<div class="pagina" id="pagina-reportes">

    <div class="panel-titulo" style="margin-bottom:16px;">
        <div>
            <h2>Reportes</h2>
            <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                Analice los ingresos, los gastos y la productividad de la clínica en el período que necesite
            </p>
        </div>
    </div>

    <div class="rep-tabs">
        <button class="rep-tab rep-tab-activa" id="rep-tab-fin" onclick="repTab('fin')">
            <i class="bi bi-cash-stack"></i> Financiero
        </button>
        <button class="rep-tab" id="rep-tab-citas" onclick="repTab('citas')">
            <i class="bi bi-calendar-check"></i> Citas
        </button>
    </div>

    <!-- ───────── Reporte financiero (REP-01) ───────── -->
    <div class="rep-panel" id="rep-panel-fin">
        <div class="tarjeta" style="margin-bottom:20px;">
            <h3 class="rep-titulo"><i class="bi bi-cash-stack"></i> Reporte financiero</h3>
            <p class="rep-ayuda">Elija el período y presione «Generar Reporte» para ver los ingresos, los egresos y el balance general.</p>

            <div class="rep-filtros">
                <label class="rep-campo">Fecha inicial
                    <input type="date" id="rep-fin-desde" onchange="repQuitarError('rep-fin')">
                </label>
                <label class="rep-campo">Fecha final
                    <input type="date" id="rep-fin-hasta" onchange="repQuitarError('rep-fin')">
                </label>
                <button class="btn-odent" id="rep-fin-btn" onclick="repFinGenerar()">
                    <i class="bi bi-bar-chart-line"></i> Generar Reporte
                </button>
                <button class="btn-outline-odent rep-btn-derecha" onclick="repGastoAbrir()">
                    <i class="bi bi-plus-lg"></i> Registrar gasto
                </button>
            </div>
        </div>

        <div id="rep-fin-resultado">
            <div class="tarjeta">
                <div class="rep-vacio">
                    <i class="bi bi-cash-stack"></i>
                    <h4>Todavía no hay un reporte generado</h4>
                    <p>Elija un período arriba y presione «Generar Reporte».</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ───────── Reporte de citas (REP-02) ───────── -->
    <div class="rep-panel" id="rep-panel-citas" hidden>
        <div class="tarjeta" style="margin-bottom:20px;">
            <h3 class="rep-titulo"><i class="bi bi-calendar-check"></i> Reporte de citas</h3>
            <p class="rep-ayuda">Elija el período y presione «Generar Reporte» para ver cuántas citas hubo, cómo terminaron y cómo se distribuyeron.</p>

            <div class="rep-filtros">
                <label class="rep-campo">Fecha inicial
                    <input type="date" id="rep-citas-desde" onchange="repQuitarError('rep-citas')">
                </label>
                <label class="rep-campo">Fecha final
                    <input type="date" id="rep-citas-hasta" onchange="repQuitarError('rep-citas')">
                </label>
                <button class="btn-odent" id="rep-citas-btn" onclick="repCitasGenerar()">
                    <i class="bi bi-bar-chart-line"></i> Generar Reporte
                </button>
            </div>
        </div>

        <div id="rep-citas-resultado">
            <div class="tarjeta">
                <div class="rep-vacio">
                    <i class="bi bi-bar-chart-line"></i>
                    <h4>Todavía no hay un reporte generado</h4>
                    <p>Elija un período arriba y presione «Generar Reporte».</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Registrar un gasto -->
<div class="modal-overlay" id="modal-rep-egreso">
    <div class="modal-caja" style="max-width:520px;">
        <div class="modal-header">
            <h3>Registrar gasto</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-rep-egreso')"><i class="bi bi-x-lg"></i></button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="campo-grupo"><label>Fecha del gasto *</label>
                <input type="date" id="reg-fecha">
            </div>
            <div class="campo-grupo"><label>Categoría *</label>
                <!-- Mantener estas opciones iguales a Reporte::CATEGORIAS -->
                <select id="reg-categoria">
                    <option value="">Seleccione…</option>
                    <option value="insumos">Insumos y materiales</option>
                    <option value="salarios">Salarios</option>
                    <option value="servicios">Servicios (luz, agua, internet)</option>
                    <option value="alquiler">Alquiler</option>
                    <option value="equipo">Equipo y mantenimiento</option>
                    <option value="otros">Otros</option>
                </select>
            </div>
        </div>
        <div class="campo-grupo"><label>Concepto *</label>
            <input type="text" id="reg-concepto" maxlength="150" placeholder="Ej: Compra de resina compuesta">
        </div>
        <div class="campo-grupo"><label>Monto (₡) *</label>
            <input type="number" id="reg-monto" min="0.01" step="0.01" placeholder="0">
        </div>
        <div class="campo-grupo"><label>Observaciones</label>
            <textarea id="reg-observaciones" rows="2" maxlength="500" placeholder="Opcional"></textarea>
        </div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-rep-egreso')">Cancelar</button>
            <button class="btn-odent" id="reg-btn" onclick="repGastoGuardar()">
                <i class="bi bi-check-lg"></i> Registrar gasto
            </button>
        </div>
    </div>
</div>

<!-- Confirmar que se elimina un gasto -->
<div class="modal-overlay" id="modal-rep-eliminar">
    <div class="modal-caja" style="max-width:420px;">
        <div class="modal-header">
            <h3>Eliminar gasto</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-rep-eliminar')"><i class="bi bi-x-lg"></i></button>
        </div>
        <p style="font-size:14px;margin:0 0 6px;">¿Eliminar el gasto <strong id="red-concepto"></strong>?</p>
        <p class="rep-ayuda" style="margin-bottom:18px;">El balance del período se recalcula sin este gasto. Esta acción no se puede deshacer.</p>
        <input type="hidden" id="red-id">
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-rep-eliminar')">Volver</button>
            <button class="btn-peligro" id="red-btn" onclick="repGastoConfirmarEliminar()">
                <i class="bi bi-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>
