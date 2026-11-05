<?php
/**
 * asistente_reportes_pagina.php — ASI-04
 * Pantalla para que recepción y asistente dental consulten los reportes del asistente inteligente.
 */
?>
<div class="pagina" id="pagina-asistente-reportes">

    <div class="panel-titulo" style="margin-bottom:20px;">
        <div>
            <h2>Reportes del asistente inteligente</h2>
            <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                Revise las molestias que reportan los pacientes y dé seguimiento según su prioridad
            </p>
        </div>
        <button class="btn-odent" onclick="asiRepConsultar()">
            <i class="bi bi-clipboard2-pulse"></i> Consultar reportes
        </button>
    </div>

    <div class="tarjeta" style="margin-bottom:20px;">
        <div class="arep-filtros">
            <input type="text" id="arep-q" placeholder="Buscar por paciente o cédula…"
                   onkeydown="if (event.key === 'Enter') asiRepBuscar()">

            <select id="arep-prioridad">
                <option value="">Todas las prioridades</option>
                <option value="urgente">Urgente</option>
                <option value="prioritario">Prioritario</option>
                <option value="normal">Normal</option>
            </select>

            <label class="arep-fecha">Desde
                <input type="date" id="arep-desde">
            </label>
            <label class="arep-fecha">Hasta
                <input type="date" id="arep-hasta">
            </label>

            <button class="btn-odent" onclick="asiRepBuscar()"><i class="bi bi-search"></i> Buscar</button>
            <button class="btn-outline-odent" onclick="asiRepLimpiar()">Limpiar</button>
        </div>
    </div>

    <div id="arep-resumen" class="arep-resumen"></div>

    <div class="tarjeta" id="arep-resultado">
        <div class="arep-vacio">
            <i class="bi bi-clipboard2-pulse"></i>
            <h4>Aquí verá los reportes de los pacientes</h4>
            <p>Presione «Consultar reportes» para ver el listado completo, o use los filtros de arriba para buscar.</p>
            <button class="btn-odent" onclick="asiRepConsultar()">
                <i class="bi bi-clipboard2-pulse"></i> Consultar reportes
            </button>
        </div>
    </div>

</div>
