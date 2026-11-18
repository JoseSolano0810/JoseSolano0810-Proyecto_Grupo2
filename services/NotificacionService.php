<?php

require_once ROOT_PATH . '/models/Notificacion.php';
require_once ROOT_PATH . '/models/Bitacora.php';

/**
 * NotificacionService.php — PAC-08
 * Genera las notificaciones al paciente cuando el odontólogo registra un avance.
 */
class NotificacionService
{
    public const MENSAJE_GENERAL = 'El seguimiento de su tratamiento fue actualizado.';

    /** @return string[] medios activos: 'portal' y/o 'correo' */
    private static function medios(): array
    {
        $cfg = defined('NOTIFICACION_MEDIO') ? strtolower((string) NOTIFICACION_MEDIO) : 'ambos';
        return match ($cfg) {
            'portal' => ['portal'],
            'correo' => ['correo'],
            default  => ['portal', 'correo'],
        };
    }

    /** Enlace que abre el tratamiento; si no hay sesión, el sistema pide iniciar sesión primero */
    public static function enlace(int $idNotificacion): string
    {
        return BASE_URL . '/index.php?accion=abrir_notificacion&id=' . $idNotificacion;
    }

    /**
     * PAC-08 esc. 1: el avance se guardó correctamente.
     * $t = fila de Tratamiento::obtenerPorId() (incluye paciente_id_usuario).
     * Nunca lanza: un fallo al notificar no debe deshacer el avance ya guardado.
     */
    public static function avanceRegistrado(array $t): ?int
    {
        try {
            $idUsuario = (int) ($t['paciente_id_usuario'] ?? 0);
            if (!$idUsuario) return null;

            $medios = self::medios();
            $modelo = new Notificacion();

            // El registro siempre queda en el portal; si la clínica solo usa correo, se guarda ya leído.
            $id = $modelo->crear(
                $idUsuario,
                Notificacion::TIPO_AVANCE,
                'Tratamiento actualizado',
                'El seguimiento de su tratamiento «' . $t['nombre'] . '» fue actualizado.',
                (int) $t['id_tratamiento']
            );
            if (!in_array('portal', $medios, true)) {
                $modelo->marcarLeida($id, $idUsuario);
            }

            if (in_array('correo', $medios, true)) {
                $modelo->registrarEnvioCorreo($id, self::enviarCorreo($idUsuario, $id));
            }

            Bitacora::registrar('NOTIFICACION_AVANCE', "Notificación #$id generada por avance del plan #{$t['id_tratamiento']}");
            return $id;
        } catch (Throwable $e) {
            error_log('PAC-08 notificar avance: ' . $e->getMessage());
            return null;
        }
    }

    /** @return string 'enviado' | 'fallido' | 'omitido' */
    private static function enviarCorreo(int $idUsuario, int $idNotificacion): string
    {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT nombre_completo, correo FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$idUsuario]);
        $u = $stmt->fetch();

        $correo = trim((string) ($u['correo'] ?? ''));
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) return 'omitido';

        $asunto = '=?UTF-8?B?' . base64_encode('Actualización de su seguimiento - ' . CLINICA_NOMBRE) . '?=';
        $cuerpo = "Hola {$u['nombre_completo']},\r\n\r\n"
                . self::MENSAJE_GENERAL . "\r\n\r\n"
                . "Para ver el detalle ingrese al portal (se le pedirá iniciar sesión):\r\n"
                . self::enlace($idNotificacion) . "\r\n\r\n"
                . CLINICA_NOMBRE . "\r\n";

        $cabeceras = "MIME-Version: 1.0\r\n"
                   . "Content-Type: text/plain; charset=UTF-8\r\n"
                   . "From: " . MAIL_REMITENTE . "\r\n";

        return @mail($correo, $asunto, $cuerpo, $cabeceras) ? 'enviado' : 'fallido';
    }
}