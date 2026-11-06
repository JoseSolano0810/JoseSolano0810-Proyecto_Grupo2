<?php
/**
 * tratamientos_modales.php — modales del módulo de tratamientos (TRA-01 … TRA-04)
 * Se incluye desde views/odontologo/index.php
 */
?>
    <!-- Modal nuevo plan de tratamiento (TRA-01) -->
    <div class="modal-overlay" id="modal-nuevo-tratamiento">
        <div class="modal-caja" style="max-width:660px;">
            <div class="modal-header">
                <h3>Nuevo plan de tratamiento</h3>
                <button class="modal-cerrar" onclick="trCancelarNuevo()"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="campo-grupo"><label>Paciente *</label>
                <select id="nt-paciente">
                    <option value="">Seleccione un paciente</option>
                </select>
            </div>
            <div class="campo-grupo"><label>Nombre del tratamiento *</label>
                <input type="text" id="nt-nombre" maxlength="120" placeholder="Ej: Ortodoncia fase 1">
            </div>
            <div class="campo-grupo"><label>Piezas dentales involucradas *</label>
                <div class="tr-picker" id="nt-piezas"></div>
                <div class="tr-picker-resumen" id="nt-piezas-resumen"></div>
            </div>
            <div class="campo-grupo"><label>Descripción *</label>
                <textarea id="nt-descripcion" rows="3" maxlength="2000" placeholder="¿Qué se va a hacer y por qué?"></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="campo-grupo"><label>Costo estimado (₡) *</label>
                    <input type="number" id="nt-costo" min="0" step="any" placeholder="0">
                </div>
                <div class="campo-grupo"><label>Estado *</label>
                    <select id="nt-estado" onchange="trAlCambiarEstado('nt')">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="campo-grupo"><label>Fecha de inicio *</label>
                    <input type="date" id="nt-inicio" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="campo-grupo"><label>Fecha estimada de finalización *</label>
                    <input type="date" id="nt-fin">
                </div>
            </div>

            <div class="campo-grupo" id="nt-progreso-grupo" style="display:none;">
                <label>Avance actual: <strong id="nt-progreso-valor">25%</strong></label>
                <input type="range" id="nt-progreso" min="1" max="99" value="25"
                       oninput="document.getElementById('nt-progreso-valor').textContent = this.value + '%'">
            </div>

            <div class="campo-grupo"><label>Observaciones</label>
                <textarea id="nt-observaciones" rows="2" maxlength="1000" placeholder="Opcional"></textarea>
            </div>

            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="trCancelarNuevo()">Cancelar</button>
                <button class="btn-odent" id="nt-btn-guardar" onclick="trGuardarNuevo()">
                    <i class="bi bi-check-lg"></i> Guardar Tratamiento
                </button>
            </div>
        </div>
    </div>

    <!-- Modal actualizar estado del tratamiento (TRA-02) -->
    <div class="modal-overlay" id="modal-tr-estado">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Actualizar tratamiento</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-tr-estado')"><i class="bi bi-x-lg"></i></button>
            </div>
            <input type="hidden" id="te-id">
            <p class="tr-modal-resumen" id="te-resumen"></p>

            <div class="campo-grupo"><label>Estado *</label>
                <select id="te-estado" onchange="trAlCambiarEstado('te')">
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="campo-grupo" id="te-progreso-grupo" style="display:none;">
                <label>Avance actual: <strong id="te-progreso-valor">25%</strong></label>
                <input type="range" id="te-progreso" min="1" max="99" value="25"
                       oninput="document.getElementById('te-progreso-valor').textContent = this.value + '%'">
            </div>
            <div class="campo-grupo"><label>Observaciones</label>
                <textarea id="te-observaciones" rows="3" maxlength="1000" placeholder="Cuente cómo va el tratamiento o por qué cambia de estado…"></textarea>
            </div>

            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-tr-estado')">Cancelar</button>
                <button class="btn-odent" id="te-btn-guardar" onclick="trGuardarEstado()">
                    <i class="bi bi-check-lg"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <!-- Modal asociar tratamiento al expediente y al odontograma (TRA-03) -->
    <div class="modal-overlay" id="modal-tr-asociar">
        <div class="modal-caja" style="max-width:660px;">
            <div class="modal-header">
                <h3>Asociar al odontograma</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-tr-asociar')"><i class="bi bi-x-lg"></i></button>
            </div>
            <input type="hidden" id="ta-id">
            <p class="tr-modal-resumen" id="ta-resumen"></p>
            <div class="campo-grupo"><label>Piezas dentales involucradas *</label>
                <div class="tr-picker" id="ta-piezas"></div>
                <div class="tr-picker-resumen" id="ta-piezas-resumen"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-tr-asociar')">Cancelar</button>
                <button class="btn-odent" id="ta-btn-guardar" onclick="trGuardarAsociacion()">
                    <i class="bi bi-link-45deg"></i> Asociar Tratamiento
                </button>
            </div>
        </div>
    </div>

    <!-- Modal detalle del tratamiento -->
    <div class="modal-overlay" id="modal-tr-detalle">
        <div class="modal-caja" style="max-width:700px;">
            <div class="modal-header">
                <h3>Detalle del tratamiento</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-tr-detalle')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="td-contenido">
                <div class="tr-vacio">Cargando…</div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-tr-detalle')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal historial de tratamientos del paciente (TRA-04) -->
    <div class="modal-overlay" id="modal-tr-historial">
        <div class="modal-caja" style="max-width:780px;">
            <div class="modal-header">
                <div>
                    <h3>Historial de tratamientos</h3>
                    <p class="tr-modal-resumen" id="th-paciente" style="margin:2px 0 0;"></p>
                </div>
                <button class="modal-cerrar" onclick="cerrarModal('modal-tr-historial')"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="tr-filtros" style="margin-bottom:16px;">
                <select id="th-filtro">
                    <option value="">Todos los estados</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <button class="btn-odent" onclick="trCargarHistorial()"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
            <div id="th-lista">
                <div class="tr-vacio">Cargando…</div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="trNuevoDesdeHistorial()"><i class="bi bi-plus-lg"></i> Nuevo plan para este paciente</button>
                <button class="btn-odent" onclick="cerrarModal('modal-tr-historial')">Cerrar</button>
            </div>
        </div>
    </div>
