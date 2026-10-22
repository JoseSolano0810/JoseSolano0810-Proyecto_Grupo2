<div class="pagina" id="pagina-inventario">

                    <div class="panel-titulo" style="margin-bottom:20px;">
                        <div>
                            <h2>Inventario</h2>
                            <p style="color:var(--gris-azulado);margin-top:5px;">
                                Control de insumos odontológicos.
                            </p>
                        </div>

                        <button class="btn-odent"
                            onclick="abrirModal('modal-nuevo-insumo')">
                            <i class="bi bi-plus-lg"></i> Registrar insumo
                        </button>
                    </div>

                    <div id="inventario-alertas"
                        style="display:none;margin-bottom:18px;">
                    </div>

                    <div class="tarjeta">

                        <table class="tabla-odent">

                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th>Categoría</th>
                                    <th>Disponible</th>
                                    <th>Nivel mínimo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="inventario-lista">
                                <tr>
                                    <td colspan="6" style="text-align:center;">
                                        No hay insumos registrados.
                                    </td>
                                </tr>
                            </tbody>

                        </table>

                    </div>

                </div>
