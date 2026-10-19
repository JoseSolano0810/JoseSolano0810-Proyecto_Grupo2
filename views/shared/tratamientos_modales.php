<?php
/**
 * tratamientos_modales.php — modales del módulo de tratamientos (TRA-01 … TRA-04)
 */
$hoy = date('Y-m-d');
?>

<!-- ───────── TRA-01 · Nuevo plan de tratamiento ───────── -->
<div class="modal-overlay" id="modal-nuevo-tratamiento">
    <div class="modal-caja" style="max-width:720px;">
        <div class="modal-header">
            <h3>Nuevo plan de tratamiento</h3>
            <button class="modal-cerrar" onclick="treCancelarNuevo()" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="campo-grupo">
            <label for="nt-paciente">Paciente *</label>
            <select id="nt-paciente"><option value="">Cargando pacientes…</option></select>
        </div>

        <div class="campo-fila">
            <div class="campo-grupo">
                <label for="nt-nombre">Nombre del tratamiento *</label>
                <input type="text" id="nt-nombre" maxlength="150" placeholder="Ej: Ortodoncia fase 1">
            </div>
            <div class="campo-grupo">
                <label for="nt-estado">Estado *</label>
                <select id="nt-estado">
                    <option value="pendiente">Pendiente</option>
                    <option value="proceso">En proceso</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        <div class="campo-grupo">
            <label>Piezas dentales involucradas *</label>
            <div class="tre-picker" id="nt-picker"></div>
            <div class="tre-picker-resumen" id="nt-picker-resumen"></div>
        </div>

        <div class="campo-grupo">
            <label for="nt-descripcion">Descripción *</label>
            <textarea id="nt-descripcion" rows="3" placeholder="¿Qué se va a realizar y por qué?"></textarea>
        </div>

        <div class="campo-fila">
            <div class="campo-grupo">
                <label for="nt-costo">Costo estimado (₡) *</label>
                <input type="number" id="nt-costo" min="0" step="0.01" placeholder="0">
            </div>
            <div class="campo-grupo">
                <label for="nt-sesiones">Sesiones previstas *</label>
                <input type="number" id="nt-sesiones" min="1" max="200" value="1">
            </div>
        </div>

        <div class="campo-fila">
            <div class="campo-grupo">
                <label for="nt-inicio">Fecha de inicio *</label>
                <input type="date" id="nt-inicio" value="<?= $hoy ?>">
            </div>
            <div class="campo-grupo">
                <label for="nt-fin">Fecha estimada de finalización *</label>
                <input type="date" id="nt-fin">
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="treCancelarNuevo()">Cancelar</button>
            <button class="btn-odent" id="nt-btn-guardar" onclick="treGuardarNuevo()"><i class="bi bi-check-lg"></i> Guardar Tratamiento</button>
        </div>
    </div>
</div>

<!-- ───────── Detalle del plan ───────── -->
<div class="modal-overlay" id="modal-trat-detalle">
    <div class="modal-caja" style="max-width:640px;">
        <div class="modal-header">
            <h3 id="td-titulo">Tratamiento</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-trat-detalle')" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="td-cuerpo"></div>
        <div class="modal-footer" id="td-acciones">
            <button class="btn-outline-odent" onclick="treDesdeDetalle('asociar')"><i class="bi bi-diagram-3"></i> Asociar</button>
            <button class="btn-odent" onclick="treDesdeDetalle('estado')"><i class="bi bi-pencil-square"></i> Actualizar estado</button>
        </div>
    </div>
</div>

<!-- ───────── TRA-02 · Actualizar estado ───────── -->
<div class="modal-overlay" id="modal-trat-estado">
    <div class="modal-caja" style="max-width:480px;">
        <div class="modal-header">
            <div>
                <h3 id="te-titulo">Actualizar tratamiento</h3>
                <p id="te-paciente" style="font-size:13px;color:var(--gris-azulado);margin-top:2px;"></p>
            </div>
            <button class="modal-cerrar" onclick="treCancelarEstado()" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="te-id">

        <div class="campo-grupo">
            <label for="te-estado">Estado</label>
            <select id="te-estado" onchange="treEstadoCambio()">
                <option value="pendiente">Pendiente</option>
                <option value="proceso">En proceso</option>
                <option value="finalizado">Finalizado</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>
        <div class="campo-grupo">
            <label for="te-sesiones">Sesiones realizadas (de <span id="te-sesiones-total">1</span>)</label>
            <input type="number" id="te-sesiones" min="0" value="0">
            <small id="te-ayuda" class="tre-ayuda"></small>
        </div>
        <div class="campo-grupo">
            <label for="te-observaciones">Observaciones</label>
            <textarea id="te-observaciones" rows="3" maxlength="2000" placeholder="¿Cómo va el paciente? ¿Algo a tener en cuenta?"></textarea>
        </div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="treCancelarEstado()">Cancelar</button>
            <button class="btn-odent" id="te-btn-guardar" onclick="treGuardarEstado()"><i class="bi bi-check-lg"></i> Guardar Cambios</button>
        </div>
    </div>
</div>

<!-- ───────── TRA-03 · Asociar a expediente y odontograma ───────── -->
<div class="modal-overlay" id="modal-trat-asociar">
    <div class="modal-caja" style="max-width:720px;">
        <div class="modal-header">
            <div>
                <h3>Asociar al expediente y odontograma</h3>
                <p style="font-size:13px;color:var(--gris-azulado);margin-top:2px;">
                    <strong id="ta-titulo"></strong> · <span id="ta-paciente"></span>
                </p>
            </div>
            <button class="modal-cerrar" onclick="cerrarModal('modal-trat-asociar')" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="ta-id">

        <div class="alerta info" style="margin-bottom:14px;">
            <i class="bi bi-info-circle"></i>
            Elija las piezas involucradas. Verá el avance del tratamiento marcado en cada diente del odontograma.
        </div>
        <div class="tre-picker" id="ta-picker"></div>
        <div class="tre-picker-resumen" id="ta-picker-resumen"></div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-trat-asociar')">Cancelar</button>
            <button class="btn-odent" id="ta-btn-guardar" onclick="treGuardarAsociar()"><i class="bi bi-diagram-3"></i> Asociar Tratamiento</button>
        </div>
    </div>
</div>

<!-- ───────── TRA-04 · Historial de tratamientos del paciente ───────── -->
<div class="modal-overlay" id="modal-trat-historial">
    <div class="modal-caja" style="max-width:820px;">
        <div class="modal-header">
            <div>
                <h3>Historial de tratamientos</h3>
                <p id="th-paciente" style="font-size:13px;color:var(--gris-azulado);margin-top:2px;"></p>
            </div>
            <button class="modal-cerrar" onclick="cerrarModal('modal-trat-historial')" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="tre-hist-filtro">
            <select id="th-filtro">
                <option value="">Todos los estados</option>
                <option value="proceso">En proceso</option>
                <option value="pendiente">Pendiente</option>
                <option value="finalizado">Finalizado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <button class="btn-odent" onclick="treCargarHistorial()"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
        <div id="th-lista"></div>

        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="treNuevoDesdeHistorial()"><i class="bi bi-plus-lg"></i> Nuevo plan para este paciente</button>
            <button class="btn-odent" onclick="cerrarModal('modal-trat-historial')">Cerrar</button>
        </div>
    </div>
</div>
