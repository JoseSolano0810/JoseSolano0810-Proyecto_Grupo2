<div class="modal-overlay" id="modal-nuevo-insumo">
            <div class="modal-caja">

                <div class="modal-header">
                    <h3>Registrar insumo</h3>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-nuevo-insumo')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form id="form-nuevo-insumo">

                    <div class="campo-grupo">
                        <label>Nombre *</label>
                        <input
                            type="text"
                            id="insumo-nombre"
                            name="nombre">
                    </div>

                    <div class="campo-grupo">
                        <label>Descripción *</label>
                        <textarea
                            id="insumo-descripcion"
                            name="descripcion"
                            rows="3"></textarea>
                    </div>

                    <div class="campo-grupo">
                        <label>Categoría *</label>

                        <select
                            id="insumo-categoria"
                            name="categoria">

                            <option value="">
                                Seleccione una categoría
                            </option>

                            <option value="Protección">
                                Protección
                            </option>

                            <option value="Higiene y desinfección">
                                Higiene y desinfección
                            </option>

                            <option value="Material clínico">
                                Material clínico
                            </option>

                            <option value="Anestesia">
                                Anestesia
                            </option>

                            <option value="Restauración">
                                Restauración
                            </option>

                            <option value="Endodoncia">
                                Endodoncia
                            </option>

                            <option value="Impresión">
                                Impresión
                            </option>

                            <option value="Otros">
                                Otros
                            </option>

                        </select>
                    </div>

                    <div class="campo-grupo">
                        <label>Cantidad disponible *</label>

                        <div style="display:flex;gap:8px;align-items:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-cantidad', -1)">
                                −
                            </button>

                            <input
                                type="number"
                                id="insumo-cantidad"
                                name="cantidad_disponible"
                                min="0"
                                step="1"
                                value="0"
                                style="text-align:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-cantidad', 1)">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="campo-grupo">
                        <label>Nivel mínimo de alerta *</label>

                        <div style="display:flex;gap:8px;align-items:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-minimo', -1)">
                                −
                            </button>

                            <input
                                type="number"
                                id="insumo-minimo"
                                name="nivel_minimo"
                                min="0"
                                step="1"
                                value="0"
                                style="text-align:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-minimo', 1)">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-outline-odent"
                            onclick="cerrarModal('modal-nuevo-insumo')">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn-odent">
                            <i class="bi bi-check-lg"></i>
                            Guardar
                        </button>

                    </div>

                </form>

            </div>
        </div>

        <div class="modal-overlay" id="modal-editar-insumo">
            <div class="modal-caja">

                <div class="modal-header">
                    <h3>Editar insumo</h3>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-editar-insumo')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form id="form-editar-insumo">

                    <input
                        type="hidden"
                        id="editar-insumo-id">

                    <div class="campo-grupo">
                        <label>Nombre *</label>

                        <input
                            type="text"
                            id="editar-insumo-nombre">
                    </div>

                    <div class="campo-grupo">
                        <label>Descripción *</label>

                        <textarea
                            id="editar-insumo-descripcion"
                            rows="3"></textarea>
                    </div>

                    <div class="campo-grupo">
                        <label>Categoría *</label>

                        <select id="editar-insumo-categoria">

                            <option value="">
                                Seleccione una categoría
                            </option>

                            <option value="Protección">
                                Protección
                            </option>

                            <option value="Higiene y desinfección">
                                Higiene y desinfección
                            </option>

                            <option value="Material clínico">
                                Material clínico
                            </option>

                            <option value="Anestesia">
                                Anestesia
                            </option>

                            <option value="Restauración">
                                Restauración
                            </option>

                            <option value="Endodoncia">
                                Endodoncia
                            </option>

                            <option value="Impresión">
                                Impresión
                            </option>

                            <option value="Otros">
                                Otros
                            </option>

                        </select>
                    </div>

                    <div class="campo-grupo">
                        <label>Nivel mínimo de alerta *</label>

                        <input
                            type="number"
                            id="editar-insumo-minimo"
                            min="0">
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-outline-odent"
                            onclick="cerrarModal('modal-editar-insumo')">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn-odent">
                            <i class="bi bi-check-lg"></i>
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </div>
        </div>

        <div class="modal-overlay" id="modal-movimiento-inventario">
            <div class="modal-caja">

                <div class="modal-header">

                    <h3 id="movimiento-titulo">
                        Movimiento de inventario
                    </h3>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-movimiento-inventario')">
                        <i class="bi bi-x-lg"></i>
                    </button>

                </div>

                <form id="form-movimiento-inventario">

                    <input
                        type="hidden"
                        id="movimiento-insumo-id">

                    <input
                        type="hidden"
                        id="movimiento-tipo">

                    <div class="campo-grupo">
                        <label>Insumo</label>

                        <input
                            type="text"
                            id="movimiento-insumo-nombre"
                            readonly>
                    </div>

                    <div class="campo-grupo">
                        <label>Stock actual</label>

                        <input
                            type="number"
                            id="movimiento-stock-actual"
                            readonly>
                    </div>

                    <div class="campo-grupo">
                        <label>Cantidad *</label>

                        <div style="display:flex;gap:8px;align-items:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('movimiento-cantidad', -1)">
                                −
                            </button>

                            <input
                                type="number"
                                id="movimiento-cantidad"
                                min="1"
                                step="1"
                                value="1"
                                style="text-align:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('movimiento-cantidad', 1)">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="campo-grupo">
                        <label>Observación</label>

                        <textarea
                            id="movimiento-observacion"
                            rows="3"
                            placeholder="Ej. Compra de materiales, uso clínico, ajuste de inventario..."></textarea>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-outline-odent"
                            onclick="cerrarModal('modal-movimiento-inventario')">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn-odent"
                            id="movimiento-boton">
                            <i class="bi bi-check-lg"></i>
                            Registrar
                        </button>

                    </div>

                </form>

            </div>
        </div>

        <div class="modal-overlay" id="modal-historial-inventario">

            <div
                class="modal-caja"
                style="max-width:850px;">

                <div class="modal-header">

                    <div>
                        <h3>Historial de movimientos</h3>

                        <div
                            id="historial-insumo-nombre"
                            style="
                            font-size:13px;
                            color:var(--gris-azulado);
                            margin-top:4px;
                        ">
                        </div>
                    </div>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-historial-inventario')">
                        <i class="bi bi-x-lg"></i>
                    </button>

                </div>

                <div style="overflow-x:auto;">

                    <table class="tabla-odent">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Anterior</th>
                                <th>Resultante</th>
                                <th>Observación</th>
                            </tr>
                        </thead>

                        <tbody id="inventario-historial-lista">

                            <tr>
                                <td
                                    colspan="6"
                                    style="text-align:center;">
                                    No hay movimientos registrados.
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn-outline-odent"
                        onclick="cerrarModal('modal-historial-inventario')">
                        Cerrar
                    </button>

                </div>

            </div>

        </div>
