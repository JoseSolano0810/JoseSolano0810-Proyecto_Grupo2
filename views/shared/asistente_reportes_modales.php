<?php
/**
 * asistente_reportes_modales.php — ASI-04
 * Detalle de un reporte del asistente inteligente.
 */
?>
<div class="modal-overlay" id="modal-arep-detalle">
    <div class="modal-caja" style="max-width:640px;">
        <div class="modal-header">
            <h3>Detalle del reporte</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-arep-detalle')"><i class="bi bi-x-lg"></i></button>
        </div>

        <div id="arep-detalle-contenido">
            <div class="arep-vacio">Cargando…</div>
        </div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-arep-detalle')">Cerrar</button>
        </div>
    </div>
</div>
