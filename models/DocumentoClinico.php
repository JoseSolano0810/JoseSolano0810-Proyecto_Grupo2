<?php
/**
 * Gestiona los documentos adjuntos de un expediente clínico.
 * Los archivos físicos se guardan en /uploads/documentos/{id_expediente}/.
 */
class DocumentoClinico
{
    private PDO $db;

    /** Tipos MIME permitidos */
    private const MIME_PERMITIDOS = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /** Tamaño máximo por archivo: 8 MB */
    private const MAX_BYTES = 8 * 1024 * 1024;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /* ── Migración ──────────────────────────────────────────── */
    private function asegurarTablas(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS documento_clinico (
                id_documento    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_expediente   INT UNSIGNED NOT NULL,
                nombre_original VARCHAR(255) NOT NULL,
                nombre_disco    VARCHAR(255) NOT NULL,
                tipo_mime       VARCHAR(100) NOT NULL,
                tamano_bytes    INT UNSIGNED NOT NULL DEFAULT 0,
                descripcion     VARCHAR(500) DEFAULT NULL,
                subido_por      INT UNSIGNED NOT NULL,
                created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_expediente (id_expediente),
                INDEX idx_usuario (subido_por)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /* ── Lectura ────────────────────────────────────────────── */

    /** Lista los documentos de un expediente, del más reciente al más antiguo. */
    public function listarPorExpediente(int $idExpediente): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.id_documento, d.nombre_original, d.nombre_disco,
                    d.tipo_mime, d.tamano_bytes, d.descripcion,
                    d.created_at,
                    u.nombre_completo AS subido_por_nombre
             FROM   documento_clinico d
             JOIN   usuario u ON u.id_usuario = d.subido_por
             WHERE  d.id_expediente = :ide
             ORDER  BY d.created_at DESC"
        );
        $stmt->execute([':ide' => $idExpediente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Obtiene un documento por ID (para verificar pertenencia antes de descargar). */
    public function obtenerPorId(int $idDocumento)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM documento_clinico WHERE id_documento = :id LIMIT 1"
        );
        $stmt->execute([':id' => $idDocumento]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ── Escritura ──────────────────────────────────────────── */

    /**
     * Valida y mueve el archivo subido, luego registra en la BD.
     * $archivo = $_FILES['archivo'] (ya validado en el controller).
     * Devuelve ['ok' => bool, 'id_documento' => int, 'mensaje' => string].
     */
    public function subir(array $archivo, int $idExpediente, int $idUsuario, string $descripcion = ''): array
    {
        // Validar tamaño
        if ($archivo['size'] > self::MAX_BYTES) {
            return ['ok' => false, 'mensaje' => 'El archivo supera el límite de 8 MB.'];
        }

        // Validar MIME real (no la extensión)
        $mime = mime_content_type($archivo['tmp_name']);
        if (!in_array($mime, self::MIME_PERMITIDOS, true)) {
            return ['ok' => false, 'mensaje' => 'Tipo de archivo no permitido. Use PDF, imagen (JPG/PNG/WEBP) o Word.'];
        }

        // Directorio de destino
        $directorio = ROOT_PATH . '/uploads/documentos/' . $idExpediente;
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Nombre único en disco (evita colisiones y path traversal)
        $extension   = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombreDisco = uniqid('doc_', true) . '.' . $extension;
        $rutaFinal   = $directorio . '/' . $nombreDisco;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
            return ['ok' => false, 'mensaje' => 'No se pudo guardar el archivo en el servidor.'];
        }

        // Registrar en BD
        $stmt = $this->db->prepare(
            "INSERT INTO documento_clinico
                (id_expediente, nombre_original, nombre_disco, tipo_mime, tamano_bytes, descripcion, subido_por)
             VALUES (:ide, :nomb, :disco, :mime, :tam, :desc, :uid)"
        );
        $stmt->execute([
            ':ide'  => $idExpediente,
            ':nomb' => mb_substr($archivo['name'], 0, 255),
            ':disco'=> $nombreDisco,
            ':mime' => $mime,
            ':tam'  => (int)$archivo['size'],
            ':desc' => mb_substr($descripcion, 0, 500) ?: null,
            ':uid'  => $idUsuario,
        ]);

        return ['ok' => true, 'id_documento' => (int)$this->db->lastInsertId()];
    }

    /**
     * Elimina el registro de BD y el archivo físico.
     * Devuelve ['ok' => bool, 'mensaje' => string].
     */
    public function eliminar(int $idDocumento, int $idExpediente): array
    {
        $doc = $this->obtenerPorId($idDocumento);
        if (!$doc || (int)$doc['id_expediente'] !== $idExpediente) {
            return ['ok' => false, 'mensaje' => 'Documento no encontrado.'];
        }

        // Borrar archivo físico
        $ruta = ROOT_PATH . '/uploads/documentos/' . $idExpediente . '/' . $doc['nombre_disco'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }

        // Borrar registro
        $stmt = $this->db->prepare("DELETE FROM documento_clinico WHERE id_documento = :id");
        $stmt->execute([':id' => $idDocumento]);

        return ['ok' => true, 'mensaje' => 'Documento eliminado.'];
    }

    /* ── Helpers estáticos ──────────────────────────────────── */

    /** Devuelve la ruta absoluta del archivo en disco. */
    public static function rutaFisica(int $idExpediente, string $nombreDisco): string
    {
        return ROOT_PATH . '/uploads/documentos/' . $idExpediente . '/' . $nombreDisco;
    }

    /** Formatea bytes en KB/MB legibles. */
    public static function formatearTamano(int $bytes): string
    {
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}