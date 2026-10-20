<?php
/**
 * tratamientos_pagina.php — contenido de la pantalla de tratamientos.
 * Lo incluye views/odontologo/index.php dentro del contenedor de la página.
 */
?>
        <div class="panel-titulo" style="margin-bottom:20px;">
            <div>
                <h2>Planes de tratamiento</h2>
                <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                    Acompañe el camino de cada paciente, desde el primer plan hasta el alta
                </p>
            </div>
            <button class="btn-odent" onclick="trAbrirNuevo()"><i class="bi bi-plus-lg"></i> Nuevo plan</button>
        </div>

        <div class="tarjeta" style="margin-bottom:20px;">
            <div class="tr-filtros">
                <input type="text" id="tr-buscar" placeholder="Buscar por paciente, cédula o tratamiento…"
                       onkeydown="if (event.key === 'Enter') trCargar()">
                <select id="tr-filtro-estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <button class="btn-odent" onclick="trCargar()"><i class="bi bi-search"></i> Buscar</button>
                <button class="btn-outline-odent" onclick="trLimpiarFiltros()">Limpiar</button>
            </div>
        </div>

        <div id="tr-resumen" class="tr-resumen"></div>

        <div id="tr-lista" class="panel-grid col-2">
            <div class="tr-vacio">Cargando los planes de tratamiento…</div>
        </div>
