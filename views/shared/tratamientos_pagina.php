<?php
/**
 * tratamientos_pagina.php — contenido de la pestaña "Planes de tratamiento"
 * (odontólogo / administrador). La lista la dibuja public/js/tratamientos.js
 */
?>
<div class="panel-titulo" style="margin-bottom:20px;">
    <div>
        <h2>Planes de tratamiento</h2>
        <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
            Cree planes, siga el avance de cada paciente y vincúlelos a su odontograma
        </p>
    </div>
    <button class="btn-odent" onclick="treAbrirNuevo()"><i class="bi bi-plus-lg"></i> Nuevo plan</button>
</div>

<div class="tarjeta tre-filtros">
    <div class="tre-filtro-buscar">
        <i class="bi bi-search"></i>
        <input type="text" id="trat-buscar" placeholder="Buscar por paciente, cédula o tratamiento…"
               oninput="treBuscarDiferido()" onkeydown="if(event.key==='Enter')treBuscar()">
    </div>
    <select id="trat-filtro-estado" onchange="treBuscar()">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="proceso">En proceso</option>
        <option value="finalizado">Finalizado</option>
        <option value="cancelado">Cancelado</option>
    </select>
    <button class="btn-odent" onclick="treBuscar()"><i class="bi bi-search"></i> Buscar</button>
    <button class="btn-outline-odent" onclick="treLimpiarFiltros()">Limpiar</button>
    <span id="trat-contador" class="tre-contador"></span>
</div>

<div class="panel-grid col-2" id="trat-lista">
    <div class="tre-cargando"><span class="spinner-mini"></span> Cargando tratamientos…</div>
</div>
