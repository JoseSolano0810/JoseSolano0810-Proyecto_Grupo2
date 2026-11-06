<?php
/**
 * asistente_reportes_pagina.php — ASI-04 y ASI-05
 * Reportes del asistente inteligente y solicitudes de cita, para recepción y asistente dental.
 */
?>
<div class="pagina" id="pagina-asistente-reportes">

    <div class="panel-titulo" style="margin-bottom:16px;">
        <div>
            <h2>Asistente inteligente</h2>
            <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                Revise las molestias que reportan los pacientes y atienda sus solicitudes de cita
            </p>
        </div>
    </div>

    <div class="arep-tabs">
        <button class="arep-tab arep-tab-activa" id="arep-tab-reportes" onclick="asiRepTab('reportes')">
            <i class="bi bi-clipboard2-pulse"></i> Reportes
        </button>
        <button class="arep-tab" id="arep-tab-solicitudes" onclick="asiRepTab('solicitudes')">
            <i class="bi bi-calendar-plus"></i> Solicitudes de cita
            <span class="arep-contador" id="arep-contador" hidden>0</span>
        </button>
    </div>

    <!-- ───────── Pestaña: reportes (ASI-04) ───────── -->
    <div class="arep-panel" id="arep-panel-reportes">

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
                <button class="btn-odent arep-btn-consultar" onclick="asiRepConsultar()">
                    <i class="bi bi-clipboard2-pulse"></i> Consultar reportes
                </button>
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

    <!-- ───────── Pestaña: solicitudes de cita (ASI-05) ───────── -->
    <div class="arep-panel" id="arep-panel-solicitudes" hidden>

        <div class="tarjeta" style="margin-bottom:20px;">
            <div class="arep-filtros">
                <select id="arsol-estado" onchange="asiSolCargar()">
                    <option value="pendiente">Pendientes</option>
                    <option value="programada">Programadas</option>
                    <option value="descartada">Descartadas</option>
                    <option value="">Todas</option>
                </select>
                <button class="btn-outline-odent" onclick="asiSolCargar()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                <span class="arep-sub" style="margin-left:auto;">Las más urgentes y las que llevan más tiempo esperando aparecen primero.</span>
            </div>
        </div>

        <div id="arsol-lista">
            <div class="tarjeta"><div class="arep-vacio">Cargando solicitudes…</div></div>
        </div>
    </div>

</div>
