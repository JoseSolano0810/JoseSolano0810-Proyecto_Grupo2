<?php

require_once ROOT_PATH . '/models/ReporteAsistente.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/services/AsistenteService.php';
require_once ROOT_PATH . '/services/ClasificadorPrioridad.php';

/**
 * AsistenteController.php — ASI-01 / ASI-02 / ASI-03
 * Asistente inteligente del portal del paciente.
 */
class AsistenteController
{
    public function __construct()
    {
        AuthService::requerir(['paciente']);

        if (($this->sesion()['rol'] ?? '') !== 'paciente') {
            $this->responder(['ok' => false, 'error' => 'El asistente solo está disponible en el portal del paciente.'], 403);
        }
    }

    /* ── Utilidades ─────────────────────────────────────────── */

    private function sesion(): array
    {
        return AuthService::usuarioActual() ?? [];
    }

    private function idUsuario(): int
    {
        return (int) ($this->sesion()['id'] ?? 0);
    }

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function entrada(): array
    {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    /* ── ASI-01: consultas (solo lectura) ───────────────────── */

    public function consultar(): void
    {
        $d         = $this->entrada();
        $intencion = (string) ($d['intencion'] ?? '');
        $texto     = mb_substr(trim((string) ($d['texto'] ?? '')), 0, 300);

        if (!in_array($intencion, AsistenteService::INTENCIONES, true)) {
            if ($texto === '') {
                $this->responder(['ok' => false, 'error' => 'Escriba su consulta o elija una opción.'], 422);
            }
            $intencion = AsistenteService::detectarIntencion($texto);
        }

        if ($intencion === 'reportar') {
            $this->responder(['ok' => true, 'respuesta' => [
                'tipo'    => 'sugerir_reporte',
                'titulo'  => 'Parece que tiene una molestia',
                'mensaje' => 'Puedo ayudarle a reportarla para que la clínica la revise y le oriente. ¿Desea iniciar el reporte?',
            ]]);
        }

        $respuesta = (new AsistenteService($this->idUsuario()))->responder($intencion);

        Bitacora::registrar('ASISTENTE_CONSULTA', 'Consulta al asistente: ' . $intencion);
        $this->responder(['ok' => true, 'respuesta' => $respuesta]);
    }

    /* ── ASI-02: reporte guiado ─────────────────────────────── */

    public function catalogos(): void
    {
        $this->responder([
            'ok'      => true,
            'zonas'   => ReporteAsistente::ZONAS,
            'tiempos' => ReporteAsistente::TIEMPOS,
        ]);
    }

    public function enviarReporte(): void
    {
        $d = $this->entrada();

        $descripcion = trim((string) ($d['descripcion'] ?? ''));
        $zona        = (string) ($d['zona'] ?? '');
        $intensidad  = $d['intensidad'] ?? null;
        $tiempo      = (string) ($d['tiempo'] ?? '');

        $faltantes = [];
        if ($descripcion === '' || mb_strlen($descripcion) < 5) {
            $faltantes['descripcion'] = 'Describa brevemente la molestia (mínimo 5 caracteres).';
        } elseif (mb_strlen($descripcion) > 500) {
            $faltantes['descripcion'] = 'La descripción no puede superar 500 caracteres.';
        }
        if (!isset(ReporteAsistente::ZONAS[$zona])) {
            $faltantes['zona'] = 'Seleccione la zona afectada.';
        }
        if (!is_numeric($intensidad) || (int) $intensidad < 1 || (int) $intensidad > 10 || (int) $intensidad != $intensidad) {
            $faltantes['intensidad'] = 'Indique la intensidad de 1 a 10.';
        }
        if (!isset(ReporteAsistente::TIEMPOS[$tiempo])) {
            $faltantes['tiempo'] = 'Indique hace cuánto comenzó la molestia.';
        }

        if ($faltantes) {
            $this->responder([
                'ok'        => false,
                'error'     => 'Faltan datos para enviar el reporte. ' . implode(' ', $faltantes),
                'faltantes' => $faltantes,
            ], 422);
        }

        $reporte = [
            'descripcion' => $descripcion,
            'zona'        => $zona,
            'intensidad'  => (int) $intensidad,
            'tiempo'      => $tiempo,
        ];

        $modelo = new ReporteAsistente();
        $clasif = ClasificadorPrioridad::clasificar($reporte, $modelo->reglasActivas());

        $id = $modelo->crear($reporte + [
            'id_usuario' => $this->idUsuario(),
            'prioridad'  => $clasif['prioridad'],
            'criterios'  => mb_substr(implode(' | ', $clasif['motivos']), 0, 500),
        ]);

        Bitacora::registrar('ASISTENTE_REPORTE', "Reporte #{$id} registrado con prioridad {$clasif['prioridad']}");

        $clinica = AsistenteService::estadoClinica();
        $o = ClasificadorPrioridad::orientacion($clasif['prioridad'], $reporte, $clinica);

        $this->responder([
            'ok'          => true,
            'id_reporte'  => $id,
            'prioridad'   => $clasif['prioridad'],
            'titulo'      => $o['titulo'],
            'mensaje'     => $o['mensaje'],
            'consejos'    => $o['consejos'],
            'contacto'    => AsistenteService::contacto(),
            'clinica_abierta'  => $clinica['abierta'],
            'proxima_apertura' => $clinica['proxima'],
        ]);
    }

    /** ASI-02 esc. 3: el paciente cancela antes de confirmar. No se guarda el contenido. */
    public function cancelarReporte(): void
    {
        Bitacora::registrar('ASISTENTE_REPORTE_CANCELADO', 'El paciente canceló un reporte antes de enviarlo');
        $this->responder(['ok' => true, 'mensaje' => 'Su reporte no fue enviado. No se guardó ninguna información.']);
    }
}