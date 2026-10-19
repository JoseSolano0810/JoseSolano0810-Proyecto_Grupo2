const Inventario = {

    insumos: [],

    async listar() {
        try {
            const respuesta = await fetch('index.php?accion=inventario.listar');
            const datos = await respuesta.json();

            if (!datos.ok) {
                alert(datos.error || 'No fue posible cargar el inventario.');
                return;
            }

            this.insumos = datos.insumos;

            this.mostrar(datos.insumos);
            this.mostrarAlertas(datos.insumos);

        } catch (error) {
            console.error(error);
            alert('No fue posible cargar el inventario.');
        }
    },

    mostrar(insumos) {
        const tbody = document.getElementById('inventario-lista');

        if (!tbody) return;

        if (!insumos.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;">
                        No hay insumos registrados.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = insumos.map(insumo => {

            let estado = `
                <span class="badge-estado badge-confirmada">
                    Disponible
                </span>
            `;

            if (insumo.estado_stock === 'minimo') {
                estado = `
                    <span class="badge-estado badge-pendiente">
                        Stock mínimo
                    </span>
                `;
            }

            if (insumo.estado_stock === 'bajo') {
                estado = `
                    <span class="badge-estado badge-cancelada">
                        Stock bajo
                    </span>
                `;
            }

            if (insumo.estado_stock === 'inactivo') {
                estado = `
                    <span style="color:var(--gris-azulado);font-size:13px;">
                        Inactivo
                    </span>
                `;
            }

            return `
                <tr>
                    <td>
                        <strong>${this.escapar(insumo.nombre)}</strong>

                        <div style="font-size:12px;color:var(--gris-azulado);">
                            ${this.escapar(insumo.descripcion)}
                        </div>
                    </td>

                    <td>${this.escapar(insumo.categoria)}</td>

                    <td>
                        ${insumo.cantidad_disponible}
                    </td>

                    <td>
                        ${insumo.nivel_minimo}
                    </td>

                    <td>
                        ${estado}
                    </td>

                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">

                            ${
                                insumo.estado === 'activo'
                                    ? `
                                        <button
                                            class="btn-outline-odent"
                                            onclick="Inventario.abrirMovimiento(${insumo.id_insumo}, 'entrada')"
                                            style="font-size:12px;padding:4px 10px;">
                                            <i class="bi bi-box-arrow-in-down"></i>
                                            Entrada
                                        </button>

                                        <button
                                            class="btn-outline-odent"
                                            onclick="Inventario.abrirMovimiento(${insumo.id_insumo}, 'salida')"
                                            style="font-size:12px;padding:4px 10px;">
                                            <i class="bi bi-box-arrow-up"></i>
                                            Salida
                                        </button>

                                        <button
                                            class="btn-outline-odent"
                                            onclick="Inventario.abrirEditar(${insumo.id_insumo})"
                                            style="font-size:12px;padding:4px 10px;">
                                            <i class="bi bi-pencil"></i>
                                            Editar
                                        </button>

                                        <button
                                            class="btn-outline-odent"
                                            onclick="Inventario.desactivar(${insumo.id_insumo})"
                                            style="font-size:12px;padding:4px 10px;">
                                            <i class="bi bi-slash-circle"></i>
                                            Desactivar
                                        </button>
                                    `
                                    : `
                                        <button
                                            class="btn-outline-odent"
                                            onclick="Inventario.activar(${insumo.id_insumo})"
                                            style="font-size:12px;padding:4px 10px;">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            Activar
                                        </button>
                                    `
                            }

                            <button
                                class="btn-outline-odent"
                                onclick="Inventario.abrirHistorial(${insumo.id_insumo})"
                                style="font-size:12px;padding:4px 10px;">
                                <i class="bi bi-clock-history"></i>
                                Historial
                            </button>

                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    mostrarAlertas(insumos) {
        const contenedor = document.getElementById('inventario-alertas');

        if (!contenedor) return;

        const alertas = insumos.filter(insumo =>
            insumo.estado === 'activo' &&
            (
                insumo.estado_stock === 'minimo' ||
                insumo.estado_stock === 'bajo'
            )
        );

        if (!alertas.length) {
            contenedor.style.display = 'none';
            contenedor.innerHTML = '';
            return;
        }

        const cantidadBajos = alertas.filter(
            insumo => insumo.estado_stock === 'bajo'
        ).length;

        const cantidadMinimos = alertas.filter(
            insumo => insumo.estado_stock === 'minimo'
        ).length;

        let resumen = '';

        if (cantidadBajos > 0) {
            resumen += `${cantidadBajos} con stock bajo`;
        }

        if (cantidadBajos > 0 && cantidadMinimos > 0) {
            resumen += ' y ';
        }

        if (cantidadMinimos > 0) {
            resumen += `${cantidadMinimos} en nivel mínimo`;
        }

        const detalle = alertas.map(insumo => {

            const tipo = insumo.estado_stock === 'bajo'
                ? 'Stock bajo'
                : 'Stock mínimo';

            return `
                <div style="
                    display:flex;
                    justify-content:space-between;
                    gap:15px;
                    padding:7px 0;
                    border-top:1px solid rgba(0,0,0,0.08);
                ">
                    <span>
                        <strong>${this.escapar(insumo.nombre)}</strong>
                    </span>

                    <span>
                        ${tipo}:
                        ${insumo.cantidad_disponible}
                        disponibles /
                        mínimo ${insumo.nivel_minimo}
                    </span>
                </div>
            `;
        }).join('');

        contenedor.innerHTML = `
            <div class="tarjeta"
                style="
                    border-left:4px solid #d99a28;
                    padding:16px 18px;
                ">

                <div style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    margin-bottom:10px;
                ">
                    <i class="bi bi-exclamation-triangle"
                        style="font-size:20px;">
                    </i>

                    <div>
                        <strong>Alerta de inventario</strong>

                        <div style="
                            font-size:13px;
                            color:var(--gris-azulado);
                            margin-top:2px;
                        ">
                            ${resumen}.
                        </div>
                    </div>
                </div>

                ${detalle}

            </div>
        `;

        contenedor.style.display = 'block';
    },

    async guardar(evento) {
        evento.preventDefault();

        const datos = {
            nombre:
                document.getElementById('insumo-nombre').value.trim(),

            descripcion:
                document.getElementById('insumo-descripcion').value.trim(),

            categoria:
                document.getElementById('insumo-categoria').value.trim(),

            cantidad_disponible:
                document.getElementById('insumo-cantidad').value,

            nivel_minimo:
                document.getElementById('insumo-minimo').value
        };

        try {
            const respuesta = await fetch(
                'index.php?accion=inventario.crear',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                }
            );

            const resultado = await respuesta.json();

            if (!resultado.ok) {
                alert(
                    resultado.error ||
                    'No fue posible registrar el insumo.'
                );
                return;
            }

            alert(resultado.mensaje);

            document
                .getElementById('form-nuevo-insumo')
                .reset();

            document.getElementById('insumo-cantidad').value = 0;
            document.getElementById('insumo-minimo').value = 0;

            cerrarModal('modal-nuevo-insumo');

            await this.listar();

        } catch (error) {
            console.error(error);
            alert('No fue posible registrar el insumo.');
        }
    },

    abrirEditar(id) {
        const insumo = this.insumos.find(
            i => Number(i.id_insumo) === Number(id)
        );

        if (!insumo) return;

        document.getElementById('editar-insumo-id').value =
            insumo.id_insumo;

        document.getElementById('editar-insumo-nombre').value =
            insumo.nombre;

        document.getElementById('editar-insumo-descripcion').value =
            insumo.descripcion;

        document.getElementById('editar-insumo-categoria').value =
            insumo.categoria;

        document.getElementById('editar-insumo-minimo').value =
            insumo.nivel_minimo;

        abrirModal('modal-editar-insumo');
    },

    async guardarEdicion(evento) {
        evento.preventDefault();

        const datos = {
            id_insumo:
                document.getElementById('editar-insumo-id').value,

            nombre:
                document.getElementById('editar-insumo-nombre').value.trim(),

            descripcion:
                document.getElementById('editar-insumo-descripcion').value.trim(),

            categoria:
                document.getElementById('editar-insumo-categoria').value,

            nivel_minimo:
                document.getElementById('editar-insumo-minimo').value
        };

        try {
            const respuesta = await fetch(
                'index.php?accion=inventario.editar',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                }
            );

            const resultado = await respuesta.json();

            if (!resultado.ok) {
                alert(
                    resultado.error ||
                    'No fue posible actualizar el insumo.'
                );
                return;
            }

            alert(resultado.mensaje);

            cerrarModal('modal-editar-insumo');

            await this.listar();

        } catch (error) {
            console.error(error);
            alert('No fue posible actualizar el insumo.');
        }
    },

    abrirMovimiento(id, tipo) {
        const insumo = this.insumos.find(
            i => Number(i.id_insumo) === Number(id)
        );

        if (!insumo) return;

        if (insumo.estado !== 'activo') {
            alert(
                'No se pueden registrar movimientos en un insumo inactivo.'
            );
            return;
        }

        document.getElementById('movimiento-insumo-id').value =
            insumo.id_insumo;

        document.getElementById('movimiento-tipo').value =
            tipo;

        document.getElementById('movimiento-insumo-nombre').value =
            insumo.nombre;

        document.getElementById('movimiento-stock-actual').value =
            insumo.cantidad_disponible;

        document.getElementById('movimiento-cantidad').value =
            1;

        document.getElementById('movimiento-observacion').value =
            '';

        const titulo =
            document.getElementById('movimiento-titulo');

        const boton =
            document.getElementById('movimiento-boton');

        if (tipo === 'entrada') {
            titulo.textContent = 'Registrar entrada';

            boton.innerHTML =
                '<i class="bi bi-box-arrow-in-down"></i> Registrar entrada';

        } else {
            titulo.textContent = 'Registrar salida';

            boton.innerHTML =
                '<i class="bi bi-box-arrow-up"></i> Registrar salida';
        }

        abrirModal('modal-movimiento-inventario');
    },

    async guardarMovimiento(evento) {
        evento.preventDefault();

        const id = Number(
            document.getElementById('movimiento-insumo-id').value
        );

        const tipo =
            document.getElementById('movimiento-tipo').value;

        const cantidad = Number(
            document.getElementById('movimiento-cantidad').value
        );

        const observacion =
            document
                .getElementById('movimiento-observacion')
                .value
                .trim();

        const insumo = this.insumos.find(
            i => Number(i.id_insumo) === id
        );

        if (!insumo) {
            alert('No fue posible identificar el insumo.');
            return;
        }

        if (!Number.isInteger(cantidad) || cantidad <= 0) {
            alert('La cantidad debe ser mayor a cero.');
            return;
        }

        if (
            tipo === 'salida' &&
            cantidad > Number(insumo.cantidad_disponible)
        ) {
            alert(
                `No existe suficiente inventario disponible. ` +
                `Actualmente hay ${insumo.cantidad_disponible} unidades.`
            );
            return;
        }

        const datos = {
            id_insumo: id,
            tipo: tipo,
            cantidad: cantidad,
            observacion: observacion
        };

        try {
            const respuesta = await fetch(
                'index.php?accion=inventario.movimiento',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                }
            );

            const resultado = await respuesta.json();

            if (!resultado.ok) {
                alert(
                    resultado.error ||
                    'No fue posible registrar el movimiento.'
                );
                return;
            }

            alert(resultado.mensaje);

            cerrarModal('modal-movimiento-inventario');

            document
                .getElementById('form-movimiento-inventario')
                .reset();

            await this.listar();

        } catch (error) {
            console.error(error);
            alert('No fue posible registrar el movimiento.');
        }
    },

    async abrirHistorial(id) {
        const insumo = this.insumos.find(
            i => Number(i.id_insumo) === Number(id)
        );

        if (!insumo) return;

        try {
            const respuesta = await fetch(
                `index.php?accion=inventario.historial&id_insumo=${id}`
            );

            const resultado = await respuesta.json();

            if (!resultado.ok) {
                alert(
                    resultado.error ||
                    'No fue posible consultar el historial.'
                );
                return;
            }

            document.getElementById('historial-insumo-nombre').textContent =
                resultado.insumo.nombre;

            this.mostrarHistorial(resultado.movimientos);

            abrirModal('modal-historial-inventario');

        } catch (error) {
            console.error(error);
            alert('No fue posible consultar el historial.');
        }
    },

    mostrarHistorial(movimientos) {
        const tbody =
            document.getElementById('inventario-historial-lista');

        if (!tbody) return;

        if (!movimientos.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;">
                        No hay movimientos registrados.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = movimientos.map(movimiento => {

            const esEntrada =
                movimiento.tipo === 'entrada';

            const tipo = esEntrada
                ? `
                    <span class="badge-estado badge-confirmada">
                        Entrada
                    </span>
                `
                : `
                    <span class="badge-estado badge-cancelada">
                        Salida
                    </span>
                `;

            const signo = esEntrada ? '+' : '-';

            const observacion =
                movimiento.observacion
                    ? this.escapar(movimiento.observacion)
                    : 'Sin observación';

            return `
                <tr>
                    <td>
                        ${this.formatearFecha(movimiento.created_at)}
                    </td>

                    <td>
                        ${tipo}
                    </td>

                    <td>
                        <strong>
                            ${signo}${movimiento.cantidad}
                        </strong>
                    </td>

                    <td>
                        ${movimiento.cantidad_anterior}
                    </td>

                    <td>
                        ${movimiento.cantidad_resultante}
                    </td>

                    <td>
                        ${observacion}
                    </td>
                </tr>
            `;
        }).join('');
    },

    formatearFecha(fecha) {
        if (!fecha) return '';

        const valor = fecha.replace(' ', 'T');
        const fechaObjeto = new Date(valor);

        if (Number.isNaN(fechaObjeto.getTime())) {
            return fecha;
        }

        return fechaObjeto.toLocaleString('es-CR');
    },

    async desactivar(id) {
        const insumo = this.insumos.find(
            i => Number(i.id_insumo) === Number(id)
        );

        if (!insumo) return;

        const confirmar = window.confirm(
            `¿Desea desactivar el insumo "${insumo.nombre}"?`
        );

        if (!confirmar) return;

        try {
            const respuesta = await fetch(
                'index.php?accion=inventario.desactivar',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_insumo: id
                    })
                }
            );

            const resultado = await respuesta.json();

            if (!resultado.ok) {
                alert(
                    resultado.error ||
                    'No fue posible desactivar el insumo.'
                );
                return;
            }

            alert(resultado.mensaje);

            await this.listar();

        } catch (error) {
            console.error(error);
            alert('No fue posible desactivar el insumo.');
        }
    },

    async activar(id) {
        const insumo = this.insumos.find(
            i => Number(i.id_insumo) === Number(id)
        );

        if (!insumo) return;

        const confirmar = window.confirm(
            `¿Desea activar nuevamente el insumo "${insumo.nombre}"?`
        );

        if (!confirmar) return;

        try {
            const respuesta = await fetch(
                'index.php?accion=inventario.activar',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_insumo: id
                    })
                }
            );

            const resultado = await respuesta.json();

            if (!resultado.ok) {
                alert(
                    resultado.error ||
                    'No fue posible activar el insumo.'
                );
                return;
            }

            alert(resultado.mensaje);

            await this.listar();

        } catch (error) {
            console.error(error);
            alert('No fue posible activar el insumo.');
        }
    },

    escapar(texto) {
        const div = document.createElement('div');

        div.textContent = texto ?? '';

        return div.innerHTML;
    }
};

function cambiarCantidad(id, cambio) {
    const campo = document.getElementById(id);

    if (!campo) return;

    let valor = parseInt(campo.value) || 0;

    valor += cambio;

    if (id === 'movimiento-cantidad') {
        if (valor < 1) valor = 1;
    } else {
        if (valor < 0) valor = 0;
    }

    campo.value = valor;
}

document.addEventListener('DOMContentLoaded', () => {

    const formulario =
        document.getElementById('form-nuevo-insumo');

    const formularioEditar =
        document.getElementById('form-editar-insumo');

    const formularioMovimiento =
        document.getElementById('form-movimiento-inventario');

    if (formulario) {
        formulario.addEventListener(
            'submit',
            evento => Inventario.guardar(evento)
        );
    }

    if (formularioEditar) {
        formularioEditar.addEventListener(
            'submit',
            evento => Inventario.guardarEdicion(evento)
        );
    }

    if (formularioMovimiento) {
        formularioMovimiento.addEventListener(
            'submit',
            evento => Inventario.guardarMovimiento(evento)
        );
    }

    if (formulario) {
        Inventario.listar();
    }

});