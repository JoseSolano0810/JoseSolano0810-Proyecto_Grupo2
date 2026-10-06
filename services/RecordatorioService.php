<?php

require_once ROOT_PATH . '/models/Cita.php';
require_once ROOT_PATH . '/models/Bitacora.php';

/**
 * CIT-05: proceso automático de recordatorios de citas.
 */
class RecordatorioService
{
    /** Máximo de intentos fallidos por cita antes de dejar de reintentar */
    private const MAX_FALLIDOS = 3;

    /** @return array{enviados:int, fallidos:int, omitidos:int, revisadas:int} */
    public static function procesar(): array
    {
        $model  = new Cita();
        $ahora  = date('Y-m-d H:i:s');
        $limite = date('Y-m-d H:i:s', strtotime('+' . (int) RECORDATORIO_HORAS_ANTES . ' hours'));

        $citas = $model->pendientesDeRecordatorio($ahora, $limite);
        $res   = ['enviados' => 0, 'fallidos' => 0, 'omitidos' => 0, 'revisadas' => count($citas)];

        foreach ($citas as $c) {
            $id     = (int) $c['id_cita'];
            $correo = trim((string) ($c['paciente_correo'] ?? ''));

            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                if ($model->contarRecordatorios($id, 'omitido') === 0) {
                    $model->registrarRecordatorio(
                        $id, 'correo', 'omitido',
                        'No fue posible enviar el recordatorio: el paciente no tiene un correo válido configurado.',
                        $correo !== '' ? $correo : null
                    );
                    $res['omitidos']++;
                }
                continue;
            }

            if ($model->contarRecordatorios($id, 'fallido') >= self::MAX_FALLIDOS) {
                continue;
            }

            if (self::enviarCorreo($correo, $c)) {
                $model->registrarRecordatorio($id, 'correo', 'enviado', 'Recordatorio enviado correctamente.', $correo);
                $res['enviados']++;
            } else {
                $model->registrarRecordatorio($id, 'correo', 'fallido', 'El servidor de correo no pudo enviar el mensaje.', $correo);
                $res['fallidos']++;
            }
        }

        if ($res['enviados'] || $res['fallidos'] || $res['omitidos']) {
            Bitacora::registrar(
                'RECORDATORIOS',
                "Proceso de recordatorios: {$res['enviados']} enviados, {$res['fallidos']} fallidos, {$res['omitidos']} sin medio válido"
            );
        }

        return $res;
    }

    private static function enviarCorreo(string $correo, array $c): bool
    {
        $fecha = date('d/m/Y', strtotime($c['fecha']));
        $hora  = substr($c['hora_inicio'], 0, 5);

        $asunto = '=?UTF-8?B?' . base64_encode('Recordatorio de cita - ' . CLINICA_NOMBRE) . '?=';
        $cuerpo = "Hola {$c['paciente']},\r\n\r\n"
                . "Le recordamos que tiene una cita en " . CLINICA_NOMBRE . ":\r\n\r\n"
                . "  Fecha: $fecha\r\n"
                . "  Hora: $hora\r\n"
                . "  Odontólogo: " . ($c['odontologo'] ?? 'Por asignar') . "\r\n"
                . "  Tipo de consulta: {$c['tipo_consulta']}\r\n\r\n"
                . "Si no puede asistir, por favor comuníquese con la clínica para reprogramarla.\r\n";

        $cabeceras = "MIME-Version: 1.0\r\n"
                   . "Content-Type: text/plain; charset=UTF-8\r\n"
                   . "From: " . MAIL_REMITENTE . "\r\n";

        return (bool) @mail($correo, $asunto, $cuerpo, $cabeceras);
    }
}
