<?php

require_once ROOT_PATH . '/models/Notificacion.php';
require_once ROOT_PATH . '/models/Bitacora.php';

/**
 * NotificacionService.php — PAC-08
 * Genera las notificaciones al paciente cuando el odontólogo registra un avance.
 *
 * El medio lo define la clínica con NOTIFICACION_MEDIO ('portal' | 'correo' | 'ambos').
 * Lo que sale del portal (correo) es un mensaje general: nunca lleva nombre del
 * tratamiento, porcentajes ni observaciones; el detalle se ve solo tras autenticarse.
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

    /* ── Avisos para el personal (campana de administrador, odontólogo, recepción y asistente) ── */

    /** Roles que gestionan reportes, solicitudes e inventario */
    private const ROLES_GESTION = ['recepcionista', 'asistente_dental', 'administrador'];

    /** Crea el aviso para cada usuario de esos roles. Nunca lanza. */
    private static function avisarRoles(array $roles, string $tipo, string $titulo, string $mensaje, ?int $excluirUsuario = null): void
    {
        try {
            $modelo = new Notificacion();
            foreach ($modelo->usuariosConRol($roles) as $idUsuario) {
                if ($idUsuario === $excluirUsuario) continue;
                $modelo->crear($idUsuario, $tipo, $titulo, $mensaje, null);
            }
        } catch (Throwable $e) {
            error_log('Notificación al personal (' . $tipo . '): ' . $e->getMessage());
        }
    }

    /** El paciente envió un reporte de molestia desde el asistente */
    public static function reporteNuevo(int $idReporte, string $prioridad): void
    {
        $alta = $prioridad === 'alta';
        self::avisarRoles(
            self::ROLES_GESTION,
            Notificacion::TIPO_REPORTE,
            $alta ? 'Reporte de prioridad ALTA' : 'Nuevo reporte de un paciente',
            "Un paciente envió el reporte #{$idReporte} (prioridad {$prioridad}) desde el asistente. Revíselo en «Reportes del asistente»."
        );
    }

    /** El paciente pidió una cita desde su reporte */
    public static function solicitudCitaNueva(int $idSolicitud, ?string $fecha, ?string $hora): void
    {
        $cuando = ($fecha && $hora) ? ' para el ' . date('d/m/Y', strtotime($fecha)) . ' a las ' . substr($hora, 0, 5) : ' sin horario definido';
        self::avisarRoles(
            self::ROLES_GESTION,
            Notificacion::TIPO_SOLICITUD,
            'Nueva solicitud de cita',
            "Un paciente solicitó la cita #{$idSolicitud}{$cuando}. Está pendiente de programar."
        );
    }

    /** Un insumo quedó por debajo de su nivel mínimo */
    public static function insumoBajo(string $nombre, int $cantidad, int $minimo): void
    {
        self::avisarRoles(
            self::ROLES_GESTION,
            Notificacion::TIPO_INSUMO,
            'Insumo bajo el nivel mínimo',
            "«{$nombre}» tiene {$cantidad} unidades (mínimo {$minimo}). Conviene reponerlo."
        );
    }

    /** Se agendó una cita al odontólogo (no se avisa a quien la creó) */
    public static function citaNueva(int $idOdontologo, string $fecha, string $hora, int $creadaPor): void
    {
        try {
            if ($idOdontologo === $creadaPor) return;
            (new Notificacion())->crear(
                $idOdontologo,
                Notificacion::TIPO_CITA,
                'Nueva cita asignada',
                'Tiene una cita el ' . date('d/m/Y', strtotime($fecha)) . ' a las ' . substr($hora, 0, 5) . '.',
                null
            );
        } catch (Throwable $e) {
            error_log('Notificación de cita: ' . $e->getMessage());
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