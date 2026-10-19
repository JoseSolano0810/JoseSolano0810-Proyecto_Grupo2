<?php

require_once ROOT_PATH . '/models/Insumo.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

class InventarioController
{
    private Insumo $model;

    public function __construct()
    {
        $this->model = new Insumo();
    }

    public function listar(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        try {
            $insumos = $this->model->listar();

            $this->responder([
                'ok' => true,
                'insumos' => $insumos
            ]);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible consultar el inventario.'
            ], 500);
        }
    }

    public function crear(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        $datos = $this->entrada();

        $nombre      = trim((string) ($datos['nombre'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $categoria   = trim((string) ($datos['categoria'] ?? ''));
        $cantidad    = $datos['cantidad_disponible'] ?? null;
        $nivelMinimo = $datos['nivel_minimo'] ?? null;

        if (
            $nombre === '' ||
            $descripcion === '' ||
            $categoria === '' ||
            $cantidad === null ||
            $nivelMinimo === null
        ) {
            $this->responder([
                'ok' => false,
                'error' => 'Debe completar todos los campos obligatorios.'
            ], 422);
        }

        if (!is_numeric($cantidad) || (int) $cantidad < 0) {
            $this->responder([
                'ok' => false,
                'error' => 'La cantidad disponible debe ser igual o mayor a cero.'
            ], 422);
        }

        if (!is_numeric($nivelMinimo) || (int) $nivelMinimo < 0) {
            $this->responder([
                'ok' => false,
                'error' => 'El nivel mínimo debe ser igual o mayor a cero.'
            ], 422);
        }

        try {
            $usuario = AuthService::usuarioActual();

            $id = $this->model->crear([
                'nombre'              => $nombre,
                'descripcion'         => $descripcion,
                'categoria'           => $categoria,
                'cantidad_disponible' => (int) $cantidad,
                'nivel_minimo'        => (int) $nivelMinimo
            ], (int) $usuario['id']);

            Bitacora::registrar(
                'INVENTARIO_INSUMO_CREADO',
                'Se registró el insumo: ' . $nombre
            );

            $this->responder([
                'ok' => true,
                'mensaje' => 'Insumo registrado correctamente.',
                'id_insumo' => $id
            ], 201);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible registrar el insumo.'
            ], 500);
        }
    }

    public function editar(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        $datos = $this->entrada();

        $id          = (int) ($datos['id_insumo'] ?? 0);
        $nombre      = trim((string) ($datos['nombre'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $categoria   = trim((string) ($datos['categoria'] ?? ''));
        $nivelMinimo = $datos['nivel_minimo'] ?? null;

        if ($id <= 0) {
            $this->responder([
                'ok' => false,
                'error' => 'El insumo no es válido.'
            ], 422);
        }

        if (
            $nombre === '' ||
            $descripcion === '' ||
            $categoria === '' ||
            $nivelMinimo === null
        ) {
            $this->responder([
                'ok' => false,
                'error' => 'Debe completar todos los campos obligatorios.'
            ], 422);
        }

        if (!is_numeric($nivelMinimo) || (int) $nivelMinimo < 0) {
            $this->responder([
                'ok' => false,
                'error' => 'El nivel mínimo debe ser igual o mayor a cero.'
            ], 422);
        }

        try {
            $insumo = $this->model->obtener($id);

            if (!$insumo) {
                $this->responder([
                    'ok' => false,
                    'error' => 'El insumo no existe.'
                ], 404);
            }

            if ($insumo['estado'] !== 'activo') {
                $this->responder([
                    'ok' => false,
                    'error' => 'No se puede editar un insumo inactivo.'
                ], 422);
            }

            $this->model->actualizar($id, [
                'nombre'       => $nombre,
                'descripcion'  => $descripcion,
                'categoria'    => $categoria,
                'nivel_minimo' => (int) $nivelMinimo
            ]);

            Bitacora::registrar(
                'INVENTARIO_INSUMO_EDITADO',
                'Se modificó el insumo: ' . $nombre
            );

            $this->responder([
                'ok' => true,
                'mensaje' => 'Insumo actualizado correctamente.'
            ]);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible actualizar el insumo.'
            ], 500);
        }
    }

    public function desactivar(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        $datos = $this->entrada();
        $id = (int) ($datos['id_insumo'] ?? 0);

        if ($id <= 0) {
            $this->responder([
                'ok' => false,
                'error' => 'El insumo no es válido.'
            ], 422);
        }

        try {
            $insumo = $this->model->obtener($id);

            if (!$insumo) {
                $this->responder([
                    'ok' => false,
                    'error' => 'El insumo no existe.'
                ], 404);
            }

            if ($insumo['estado'] === 'inactivo') {
                $this->responder([
                    'ok' => false,
                    'error' => 'El insumo ya se encuentra inactivo.'
                ], 422);
            }

            $this->model->desactivar($id);

            Bitacora::registrar(
                'INVENTARIO_INSUMO_DESACTIVADO',
                'Se desactivó el insumo: ' . $insumo['nombre']
            );

            $this->responder([
                'ok' => true,
                'mensaje' => 'Insumo desactivado correctamente.'
            ]);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible desactivar el insumo.'
            ], 500);
        }
    }

    public function activar(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        $datos = $this->entrada();
        $id = (int) ($datos['id_insumo'] ?? 0);

        if ($id <= 0) {
            $this->responder([
                'ok' => false,
                'error' => 'El insumo no es válido.'
            ], 422);
        }

        try {
            $insumo = $this->model->obtener($id);

            if (!$insumo) {
                $this->responder([
                    'ok' => false,
                    'error' => 'El insumo no existe.'
                ], 404);
            }

            if ($insumo['estado'] === 'activo') {
                $this->responder([
                    'ok' => false,
                    'error' => 'El insumo ya se encuentra activo.'
                ], 422);
            }

            $this->model->activar($id);

            Bitacora::registrar(
                'INVENTARIO_INSUMO_ACTIVADO',
                'Se activó el insumo: ' . $insumo['nombre']
            );

            $this->responder([
                'ok' => true,
                'mensaje' => 'Insumo activado correctamente.'
            ]);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible activar el insumo.'
            ], 500);
        }
    }

    public function movimiento(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        $datos = $this->entrada();

        $id          = (int) ($datos['id_insumo'] ?? 0);
        $tipo        = trim((string) ($datos['tipo'] ?? ''));
        $cantidad    = $datos['cantidad'] ?? null;
        $observacion = trim((string) ($datos['observacion'] ?? ''));

        if ($id <= 0) {
            $this->responder([
                'ok' => false,
                'error' => 'Debe seleccionar un insumo.'
            ], 422);
        }

        if (!in_array($tipo, ['entrada', 'salida'], true)) {
            $this->responder([
                'ok' => false,
                'error' => 'El tipo de movimiento no es válido.'
            ], 422);
        }

        if (!is_numeric($cantidad) || (int) $cantidad <= 0) {
            $this->responder([
                'ok' => false,
                'error' => 'La cantidad debe ser mayor a cero.'
            ], 422);
        }

        try {
            $usuario = AuthService::usuarioActual();

            $insumo = $this->model->registrarMovimiento(
                $id,
                $tipo,
                (int) $cantidad,
                (int) $usuario['id'],
                $observacion
            );

            Bitacora::registrar(
                $tipo === 'entrada'
                    ? 'INVENTARIO_ENTRADA'
                    : 'INVENTARIO_SALIDA',
                ucfirst($tipo) .
                ' de ' .
                (int) $cantidad .
                ' unidades del insumo #' .
                $id
            );

            $this->responder([
                'ok' => true,
                'mensaje' => $tipo === 'entrada'
                    ? 'Entrada registrada correctamente.'
                    : 'Salida registrada correctamente.',
                'insumo' => $insumo
            ]);

        } catch (RuntimeException $e) {
            $this->responder([
                'ok' => false,
                'error' => $e->getMessage()
            ], 422);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible actualizar el inventario.'
            ], 500);
        }
    }

    public function historial(): void
    {
        AuthService::requerir(['asistente_dental', 'administrador']);

        $id = (int) ($_GET['id_insumo'] ?? 0);

        if ($id <= 0) {
            $this->responder([
                'ok' => false,
                'error' => 'El insumo no es válido.'
            ], 422);
        }

        try {
            $insumo = $this->model->obtener($id);

            if (!$insumo) {
                $this->responder([
                    'ok' => false,
                    'error' => 'El insumo no existe.'
                ], 404);
            }

            $movimientos = $this->model->movimientos($id);

            $this->responder([
                'ok' => true,
                'insumo' => $insumo,
                'movimientos' => $movimientos
            ]);

        } catch (Throwable $e) {
            $this->responder([
                'ok' => false,
                'error' => 'No fue posible consultar el historial.'
            ], 500);
        }
    }

    private function entrada(): array
    {
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($tipo, 'application/json')) {
            $json = json_decode(
                file_get_contents('php://input'),
                true
            );

            return is_array($json) ? $json : [];
        }

        return $_POST;
    }

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}