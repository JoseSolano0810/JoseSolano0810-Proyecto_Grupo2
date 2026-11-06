<?php
/**
 * reportes_pagina.php — REP-02 (y base para REP-01)
 * Reportes de la clínica para el administrador.
 */
?>
<div class="pagina" id="pagina-reportes">

    <div class="panel-titulo" style="margin-bottom:20px;">
        <div>
            <h2>Reportes</h2>
            <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                Analice la productividad de la clínica en el período que necesite
            </p>
        </div>
    </div>

    <!-- ───────── Reporte de citas (REP-02) ───────── -->
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
