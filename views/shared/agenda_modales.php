<?php

$puedeGestionarCitas = in_array($rol ?? '', ['recepcionista', 'asistente_dental', 'administrador'], true);
?>

<?php if ($puedeGestionarCitas): ?>
<!-- CIT-01 / CIT-02: agendar y modificar cita -->
<div class="modal-overlay" id="modal-cita">
    <div class="modal-caja" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="cita-titulo">Nueva cita</h3>
            <button class="modal-cerrar" onclick="Citas.cerrarForm()"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="cita-id">
        <div class="campo-grupo"><label>Paciente *</label>
            <select id="cita-paciente"><option value="">Seleccione un paciente</option></select>
        </div>
        <div class="campo-grupo"><label>Odontólogo *</label>
            <select id="cita-odontologo"><option value="">Seleccione un odontólogo</option></select>
        </div>
        <div class="campo-fila">
            <div class="campo-grupo"><label>Fecha *</label><input type="date" id="cita-fecha"></div>
            <div class="campo-grupo"><label>Hora *</label><input type="time" id="cita-hora" step="900"></div>
        </div>
        <div class="campo-fila">
            <div class="campo-grupo"><label>Duración *</label>
                <select id="cita-duracion"><option value="">Seleccione</option></select>
            </div>
            <div class="campo-grupo"><label>Tipo de consulta *</label>
                <select id="cita-tipo"><option value="">Seleccione</option></select>
            </div>
        </div>
        <div class="campo-grupo" id="cita-estado-grupo"><label>Estado de la cita *</label>
            <select id="cita-estado">
                <option value="programada">Programada</option>
                <option value="confirmada">Confirmada</option>
            </select>
        </div>
        <div class="campo-grupo"><label>Observaciones</label>
            <textarea id="cita-obs" rows="3" maxlength="500" placeholder="Observaciones opcionales..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="Citas.cerrarForm()">Cancelar</button>
            <button class="btn-odent" id="cita-btn-guardar" onclick="Citas.guardar(this)"><i class="bi bi-check-lg"></i> <span>Agendar Cita</span></button>
        </div>
    </div>
</div>

<!-- CIT-03: reprogramar -->
<div class="modal-overlay" id="modal-cita-reprogramar">
    <div class="modal-caja" style="max-width:460px;">
        <div class="modal-header">
            <h3>Reprogramar cita</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cita-reprogramar')"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="rep-id">
        <div class="alerta info" id="rep-info" style="margin-bottom:18px;"></div>
        <div class="campo-fila">
            <div class="campo-grupo"><label>Nueva fecha *</label><input type="date" id="rep-fecha"></div>
            <div class="campo-grupo"><label>Nueva hora *</label><input type="time" id="rep-hora" step="900"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-cita-reprogramar')">Cancelar</button>
            <button class="btn-odent" onclick="Citas.guardarReprogramacion(this)"><i class="bi bi-calendar2-check"></i> Reprogramar Cita</button>
        </div>
    </div>
</div>

<!-- CIT-02 esc. 3 / CIT-06 esc. 2: cancelar cita -->
<div class="modal-overlay" id="modal-cita-cancelar">
    <div class="modal-caja" style="max-width:460px;">
        <div class="modal-header">
            <h3>Cancelar cita</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cita-cancelar')"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="can-id">
        <div class="alerta aviso" id="can-info" style="margin-bottom:18px;"></div>
        <div class="campo-grupo"><label>Motivo (opcional)</label>
            <textarea id="can-motivo" rows="2" maxlength="150" placeholder="Solicitud del paciente, decisión de la clínica..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-cita-cancelar')">Volver</button>
            <button class="btn-peligro" onclick="Citas.guardarCancelacion(this)"><i class="bi bi-x-lg"></i> Cancelar Cita</button>
        </div>
    </div>
</div>

<!-- CIT-06: confirmar / atendida / ausente -->
<div class="modal-overlay" id="modal-cita-confirmar">
    <div class="modal-caja" style="max-width:420px;">
        <div class="modal-header">
            <h3 id="conf-titulo">Confirmar</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cita-confirmar')"><i class="bi bi-x-lg"></i></button>
        </div>
        <p id="conf-texto" style="margin-bottom:6px;"></p>
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-cita-confirmar')">Volver</button>
            <button class="btn-odent" onclick="Citas.guardarEstado(this)"><i class="bi bi-check-lg"></i> Aceptar</button>
        </div>
    </div>
</div>

<!-- CIT-05: bitácora de recordatorios -->
<div class="modal-overlay" id="modal-cita-recordatorios">
    <div class="modal-caja" style="max-width:820px;">
        <div class="modal-header">
            <h3>Recordatorios de citas</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cita-recordatorios')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="alerta info" id="rec-info" style="margin-bottom:14px;"></div>
        <div id="rec-contenido"></div>
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-cita-recordatorios')">Cerrar</button>
            <button class="btn-odent" onclick="Citas.ejecutarRecordatorios(this)"><i class="bi bi-send"></i> Enviar recordatorios ahora</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detalle de cita + historial de cambios (todos los roles del personal) -->
<div class="modal-overlay" id="modal-cita-detalle">
    <div class="modal-caja" style="max-width:560px;">
        <div class="modal-header">
            <h3>Detalle de la cita</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cita-detalle')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="det-contenido"></div>
        <h4 style="margin:18px 0 8px;">Historial de cambios</h4>
        <div id="det-historial" class="det-historial"></div>
        <div class="modal-footer" id="det-acciones">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-cita-detalle')">Cerrar</button>
        </div>
    </div>
</div>
