<?php
/**
 * Endpoints para listar, subir, descargar y eliminar documentos clínicos.
 */
class DocumentoController
{
    private DocumentoClinico $model;

    public function __construct()
    {
        AuthService::requerir(['odontologo', 'administrador']);
        require_once ROOT_PATH . '/models/DocumentoClinico.php';
        $this->model = new DocumentoClinico();
    }

    /* ── GET ?accion=documentos.listar&id_expediente=X ──────── */
    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idExpediente = (int)($_GET['id_expediente'] ?? 0);
            if (!$idExpediente) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'id_expediente requerido.']);
                return;
            }

            $docs = $this->model->listarPorExpediente($idExpediente);

            // Agregar URL de descarga y tamaño legible para el frontend
            foreach ($docs as &$d) {
                $d['url_descarga'] = BASE_URL . '/index.php?accion=documentos.descargar&id=' . $d['id_documento'];
                $d['tamano_fmt']   = DocumentoClinico::formatearTamano((int)$d['tamano_bytes']);
            }
            unset($d);

            echo json_encode(['ok' => true, 'documentos' => $docs]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno al listar documentos: ' . $e->getMessage()]);
        }
    }

    /* ── POST ?accion=documentos.subir (multipart/form-data) ── */
    public function subir(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $codigo = $_FILES['archivo']['error'] ?? -1;
                if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
                    $msg = 'El archivo supera el tamaño permitido.';
                } elseif ($codigo === UPLOAD_ERR_NO_FILE) {
                    $msg = 'No se recibió ningún archivo.';
                } else {
                    $msg = 'Error al recibir el archivo (código ' . $codigo . ').';
                }
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => $msg]);
                return;
            }

            $idExpediente = (int)($_POST['id_expediente'] ?? 0);
            $descripcion  = trim($_POST['descripcion'] ?? '');

            if (!$idExpediente) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'id_expediente requerido.']);
                return;
            }

            $sesion    = AuthService::usuarioActual();
            $resultado = $this->model->subir($_FILES['archivo'], $idExpediente, $sesion['id'], $descripcion);

            if (!$resultado['ok']) {
                http_response_code(422);
                echo json_encode($resultado);
                return;
            }

            Bitacora::registrar(
                'DOCUMENTO_SUBIDO',
                "Documento #{$resultado['id_documento']} subido al expediente #{$idExpediente}: " . $_FILES['archivo']['name'],
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode([
                'ok'           => true,
                'id_documento' => $resultado['id_documento'],
                'mensaje'      => 'Documento subido correctamente.',
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /* ── GET ?accion=documentos.descargar&id=X ──────────────── */
    public function descargar(): void
    {
        $idDocumento = (int)($_GET['id'] ?? 0);
        if (!$idDocumento) {
            http_response_code(400);
            echo 'ID de documento requerido.';
            return;
        }

        $doc = $this->model->obtenerPorId($idDocumento);
        if (!$doc) {
            http_response_code(404);
            echo 'Documento no encontrado.';
            return;
        }

        $ruta = DocumentoClinico::rutaFisica((int)$doc['id_expediente'], $doc['nombre_disco']);
        if (!file_exists($ruta)) {
            http_response_code(404);
            echo 'Archivo no encontrado en el servidor.';
            return;
        }

        // Servir el archivo
        header('Content-Type: ' . $doc['tipo_mime']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($doc['nombre_original']) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, no-cache');
        readfile($ruta);
        exit;
    }

    /* ── POST ?accion=documentos.eliminar ───────────────────── */
    public function eliminar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos        = $this->bodyJson();
            $idDocumento  = (int)($datos['id_documento']  ?? 0);
            $idExpediente = (int)($datos['id_expediente'] ?? 0);

            if (!$idDocumento || !$idExpediente) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'id_documento e id_expediente requeridos.']);
                return;
            }

            $resultado = $this->model->eliminar($idDocumento, $idExpediente);

            if (!$resultado['ok']) {
                http_response_code(404);
                echo json_encode($resultado);
                return;
            }

            $sesion = AuthService::usuarioActual();
            Bitacora::registrar(
                'DOCUMENTO_ELIMINADO',
                "Documento #{$idDocumento} eliminado del expediente #{$idExpediente}",
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode(['ok' => true, 'mensaje' => 'Documento eliminado.']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno al eliminar: ' . $e->getMessage()]);
        }
    }

    /* ── Helper ─────────────────────────────────────────────── */
    private function bodyJson(): array
    {
        $raw  = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}