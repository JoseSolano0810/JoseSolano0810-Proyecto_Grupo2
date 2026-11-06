<?php
/**
 * asistente_reportes_modales.php — ASI-04 y ASI-05
 * Detalle de un reporte, programación de la cita y confirmación para descartar una solicitud.
 */
?>
<!-- Detalle de un reporte -->
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

<!-- Programar la cita de una solicitud -->
<div class="modal-overlay" id="modal-arep-programar">
    <div class="modal-caja" style="max-width:600px;">
        <div class="modal-header">
            <h3>Programar cita</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-arep-programar')"><i class="bi bi-x-lg"></i></button>
        </div>

        <input type="hidden" id="arp-id">

        <div class="arep-prog-resumen">
            <strong id="arp-paciente"></strong>
            <p id="arp-motivo"></p>
            <p id="arp-aviso" class="arep-aviso" hidden></p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="campo-grupo"><label>Odontólogo *</label>
                <select id="arp-odontologo"><option value="">Seleccione…</option></select>
            </div>
            <div class="campo-grupo"><label>Tipo de consulta *</label>
                <select id="arp-tipo"></select>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
            <div class="campo-grupo"><label>Fecha *</label>
                <input type="date" id="arp-fecha">
            </div>
            <div class="campo-grupo"><label>Hora *</label>
                <input type="time" id="arp-hora" step="900">
            </div>
            <div class="campo-grupo"><label>Duración *</label>
                <select id="arp-duracion"></select>
            </div>
        </div>
        <div class="campo-grupo"><label>Observaciones</label>
            <textarea id="arp-observaciones" rows="3" maxlength="500"></textarea>
        </div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-arep-programar')">Cancelar</button>
            <button class="btn-odent" id="arp-btn" onclick="asiSolConfirmarProgramar()">
                <i class="bi bi-calendar-check"></i> Agendar cita
            </button>
        </div>
    </div>
</div>

<!-- Confirmar que se descarta una solicitud -->
<div class="modal-overlay" id="modal-arep-descartar">
    <div class="modal-caja" style="max-width:420px;">
        <div class="modal-header">
            <h3>Descartar solicitud</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-arep-descartar')"><i class="bi bi-x-lg"></i></button>
        </div>
        <p style="font-size:14px;margin:0 0 6px;">
            ¿Descartar la solicitud de cita de <strong id="ard-paciente"></strong>?
        </p>
        <p class="arep-sub" style="margin-bottom:18px;">Si había un horario reservado, quedará libre otra vez. El reporte del paciente se conserva.</p>
        <input type="hidden" id="ard-id">
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-arep-descartar')">Volver</button>
            <button class="btn-peligro" id="ard-btn" onclick="asiSolConfirmarDescartar()">
                <i class="bi bi-trash"></i> Descartar
            </button>
        </div>
    </div>
</div>
