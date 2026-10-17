<?php
/**
 * OdontogramaController.php — CLI-03 / CLI-04
 * Endpoints AJAX para leer y guardar el odontograma de un expediente.
 */
class OdontogramaController
{
    private Odontograma $model;

    public function __construct()
    {
        // Solo odontólogos y administradores pueden gestionar odontogramas.
        AuthService::requerir(['odontologo', 'administrador']);

        require_once ROOT_PATH . '/models/Odontograma.php';
        $this->model = new Odontograma();
    }

    /* ── GET ?accion=odontograma.obtener&id_expediente=X ────── */
    public function obtener(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idExpediente = (int)($_GET['id_expediente'] ?? 0);

            if (!$idExpediente) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'id_expediente requerido.']);
                return;
            }

            $piezas = $this->model->obtenerPorExpediente($idExpediente);

            echo json_encode([
                'ok'     => true,
                'piezas' => $piezas,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /* ── POST ?accion=odontograma.guardar ───────────────────── */
    public function guardar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos = $this->bodyJson();

            $idExpediente = (int)($datos['id_expediente'] ?? 0);
            $numeroPieza  = (int)($datos['numero_pieza']  ?? 0);
            $estado       = trim($datos['estado']         ?? '');

            if (!$idExpediente || !$numeroPieza || !$estado) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'id_expediente, numero_pieza y estado son requeridos.']);
                return;
            }

            $resultado = $this->model->guardarPieza([
                'id_expediente' => $idExpediente,
                'numero_pieza'  => $numeroPieza,
                'estado'        => $estado,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            if (!$resultado['ok']) {
                http_response_code(422);
                echo json_encode($resultado);
                return;
            }

            // Bitácora
            $sesion = AuthService::usuarioActual();
            Bitacora::registrar(
                'ODONTOGRAMA_EDITADO',
                "Pieza {$numeroPieza} → {$estado} (expediente #{$idExpediente})",
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode([
                'ok'       => true,
                'id_pieza' => $resultado['id_pieza'],
                'mensaje'  => 'Pieza guardada.',
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /* ── Helper: leer body JSON ─────────────────────────────── */
    private function bodyJson(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}