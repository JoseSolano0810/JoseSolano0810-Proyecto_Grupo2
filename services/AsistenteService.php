<?php

require_once ROOT_PATH . '/models/Cita.php';
require_once ROOT_PATH . '/models/Tratamiento.php';
require_once ROOT_PATH . '/models/ExpedienteClinico.php';
require_once ROOT_PATH . '/models/Pago.php';
require_once ROOT_PATH . '/services/ClasificadorPrioridad.php';

/**
 * AsistenteService.php — ASI-01
 * Consultas del paciente al asistente. SOLO LECTURA.
 */
class AsistenteService
{
    public const INTENCIONES = ['proxima_cita', 'tratamientos', 'procedimientos', 'pagos', 'indicaciones'];

    private const PALABRAS = [
        'proxima_cita'   => ['cita', 'citas', 'proxima', 'agenda', 'cuando me toca', 'cuando es mi', 'hora de mi'],
        'tratamientos'   => ['tratamiento', 'tratamientos', 'plan', 'ortodoncia', 'avance', 'progreso'],
        'procedimientos' => ['procedimiento', 'procedimientos', 'realizado', 'realizaron', 'me hicieron', 'atenciones', 'historial'],
        'pagos'          => ['pago', 'pagos', 'pagar', 'saldo', 'deuda', 'debo', 'cuota', 'cuotas', 'pendiente de pago', 'cobro', 'factura'],
        'indicaciones'   => ['indicacion', 'indicaciones', 'recomendacion', 'recomendaciones', 'cuidados', 'instrucciones'],
    ];

    private const SINTOMAS = [
        'dolor', 'duele', 'molestia', 'molesta', 'sangr', 'inflam', 'hincha', 'fiebre', 'se cayo', 'se me cayo',
        ' roto', 'rompi', 'quebr', 'sensibilidad', 'reportar', 'reporte', 'golpe', 'bracket', 'absceso',
    ];

    private int $idUsuario;

    public function __construct(int $idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }

    /* ── Intención ──────────────────────────────────────────── */

    public static function detectarIntencion(string $texto): string
    {
        $t = ' ' . ClasificadorPrioridad::normalizar($texto) . ' ';
        if (trim($t) === '') return 'desconocida';

        foreach (self::SINTOMAS as $s) {
            if (str_contains($t, $s)) return 'reportar';
        }
        foreach (self::PALABRAS as $intencion => $palabras) {
            foreach ($palabras as $p) {
                // inicio de palabra: "cita" también coincide con "citas"
                if (str_contains($t, ' ' . $p)) return $intencion;
            }
        }
        return 'desconocida';
    }

    /* ── Punto de entrada ───────────────────────────────────── */

    public function responder(string $intencion): array
    {
        return match ($intencion) {
            'proxima_cita'   => $this->proximaCita(),
            'tratamientos'   => $this->tratamientos(),
            'procedimientos' => $this->procedimientos(),
            'pagos'          => $this->pagosPendientes(),
            'indicaciones'   => $this->indicaciones(),
            default          => $this->menu(),
        };
    }

    /* ── ASI-01 esc. 1: próxima cita ────────────────────────── */

    private function proximaCita(): array
    {
        $hoy   = date('Y-m-d');
        $ahora = date('H:i');

        $citas = (new Cita())->consultar(['id_paciente' => $this->idUsuario, 'desde' => $hoy]);

        foreach ($citas as $c) {
            if (!in_array($c['estado'], Cita::ESTADOS_ACTIVOS, true)) continue;
            if ($c['fecha'] === $hoy && substr($c['hora_inicio'], 0, 5) < $ahora) continue;

            return [
                'tipo'    => 'cita',
                'titulo'  => 'Su próxima cita',
                'mensaje' => 'Esta es la información de su próxima cita:',
                'datos'   => [
                    ['etiqueta' => 'Fecha',        'valor' => date('d/m/Y', strtotime($c['fecha']))],
                    ['etiqueta' => 'Hora',         'valor' => date('g:i a', strtotime($c['hora_inicio']))],
                    ['etiqueta' => 'Odontólogo',   'valor' => $c['odontologo'] ?? 'Por asignar'],
                    ['etiqueta' => 'Tipo de atención', 'valor' => $c['tipo_consulta']],
                    ['etiqueta' => 'Estado',       'valor' => ucfirst($c['estado'])],
                ],
            ];
        }

        return $this->sinDatos('No encontré citas próximas programadas a su nombre.');
    }

    /* ── ASI-01 esc. 2: información de la atención ──────────── */

    private function tratamientos(): array
    {
        $filas = $this->tratamientosDelPaciente();
        if (!$filas) return $this->sinDatos('No encontré tratamientos registrados a su nombre.');

        $items = [];
        foreach ($filas as $t) {
            $lineas = [
                'Estado: ' . (Tratamiento::ETIQUETAS[$t['estado']] ?? $t['estado']),
                'Progreso: ' . (int) $t['progreso'] . '%',
                'Odontólogo: ' . ($t['odontologo'] ?? 'Por asignar'),
                'Inicio: ' . date('d/m/Y', strtotime($t['fecha_inicio']))
                    . ' · Fin estimado: ' . date('d/m/Y', strtotime($t['fecha_fin_estimada'])),
            ];
            $items[] = ['titulo' => $t['nombre'], 'lineas' => $lineas, 'progreso' => (int) $t['progreso']];
        }

        return [
            'tipo'    => 'lista',
            'titulo'  => 'Sus tratamientos',
            'mensaje' => 'Estos son los tratamientos registrados a su nombre:',
            'items'   => $items,
        ];
    }

    private function procedimientos(): array
    {
        $idPaciente = (new Tratamiento())->idPacientePorUsuario($this->idUsuario);
        $idExp      = $idPaciente ? (new Tratamiento())->expedienteDePaciente($idPaciente) : null;
        $atenciones = $idExp ? (new ExpedienteClinico())->atenciones($idExp) : [];

        $items = [];
        foreach ($atenciones as $a) {
            $hecho = trim((string) ($a['tratamiento_realizado'] ?? ''));
            if ($hecho === '') continue;
            $items[] = [
                'titulo' => date('d/m/Y', strtotime($a['created_at'])),
                'lineas' => [$hecho, 'Odontólogo: ' . $a['odontologo']],
            ];
        }

        foreach ($this->tratamientosDelPaciente() as $t) {
            if ($t['estado'] !== 'finalizado') continue;
            $items[] = [
                'titulo' => $t['nombre'],
                'lineas' => ['Finalizado' . ($t['fecha_fin_real'] ? ' el ' . date('d/m/Y', strtotime($t['fecha_fin_real'])) : ''),
                             'Odontólogo: ' . ($t['odontologo'] ?? 'Por asignar')],
            ];
        }

        if (!$items) return $this->sinDatos('No encontré procedimientos realizados registrados a su nombre.');

        return [
            'tipo'    => 'lista',
            'titulo'  => 'Procedimientos realizados',
            'mensaje' => 'Estos son los procedimientos registrados en su historial:',
            'items'   => $items,
        ];
    }

    private function indicaciones(): array
    {
        $items = [];
        foreach ($this->tratamientosDelPaciente() as $t) {
            $nota = trim((string) ($t['observaciones'] ?? ''));
            if ($nota === '') continue;
            $items[] = ['titulo' => $t['nombre'], 'lineas' => [$nota]];
        }

        if (!$items) return $this->sinDatos('No encontré indicaciones registradas por su odontólogo.');

        return [
            'tipo'    => 'lista',
            'titulo'  => 'Indicaciones de su odontólogo',
            'mensaje' => 'Estas son las indicaciones registradas en sus tratamientos:',
            'items'   => $items,
        ];
    }

    private function pagosPendientes(): array
    {
        $pendientes = $this->pagosPendientesDelPaciente();
        if (!$pendientes) return $this->sinDatos('No encontré pagos pendientes registrados a su nombre.');

        $items = [];
        $total = 0.0;
        foreach ($pendientes as $p) {
            $monto  = (float) ($p['monto'] ?? 0);
            $total += $monto;
            $items[] = [
                'titulo' => (string) ($p['concepto'] ?? 'Pago pendiente'),
                'lineas' => array_filter([
                    'Monto: ₡' . number_format($monto, 0, ',', '.'),
                    !empty($p['fecha']) ? 'Fecha: ' . $p['fecha'] : null,
                ]),
            ];
        }

        return [
            'tipo'    => 'lista',
            'titulo'  => 'Pagos pendientes',
            'mensaje' => 'Tiene un saldo pendiente de ₡' . number_format($total, 0, ',', '.') . '. Para coordinar el pago comuníquese con recepción.',
            'items'   => $items,
        ];
    }

    /* ── Fuentes de datos ───────────────────────────────────── */

    private function tratamientosDelPaciente(): array
    {
        $modelo     = new Tratamiento();
        $idPaciente = $modelo->idPacientePorUsuario($this->idUsuario);
        if (!$idPaciente) return [];
        return $modelo->consultar(['id_paciente' => $idPaciente]);
    }

    private function pagosPendientesDelPaciente(): array
    {
        $modelo = new Pago();
        if (!method_exists($modelo, 'pendientesPorPaciente')) return [];
        $filas = $modelo->pendientesPorPaciente($this->idUsuario);
        return is_array($filas) ? $filas : [];
    }

    /* ── Respuestas comunes ─────────────────────────────────── */

    /** ASI-01 esc. 3: nunca se inventan datos */
    private function sinDatos(string $detalle): array
    {
        return [
            'tipo'     => 'sin_datos',
            'titulo'   => 'Sin información disponible',
            'mensaje'  => $detalle . ' Le recomiendo comunicarse con la clínica para más información.',
            'contacto' => self::contacto(),
        ];
    }

    private function menu(): array
    {
        return [
            'tipo'    => 'menu',
            'titulo'  => '¿En qué le puedo ayudar?',
            'mensaje' => 'No logré identificar su consulta. Puede elegir una opción o escribirla de otra forma.',
        ];
    }

    /**
     * Indica si la clínica está atendiendo ahora y, si no, cuándo vuelve a abrir.
     * Usa CLINICA_HORA_APERTURA, CLINICA_HORA_CIERRE y CLINICA_DIAS_ATENCION.
     */
    public static function estadoClinica(?DateTimeImmutable $ahora = null): array
    {
        $ahora    = $ahora ?? new DateTimeImmutable('now');
        $apertura = defined('CLINICA_HORA_APERTURA') ? (string) CLINICA_HORA_APERTURA : '09:30';
        $cierre   = defined('CLINICA_HORA_CIERRE')   ? (string) CLINICA_HORA_CIERRE   : '18:00';
        $dias     = defined('CLINICA_DIAS_ATENCION') ? (array) CLINICA_DIAS_ATENCION  : [1, 2, 3, 4, 5];
        sort($dias);

        $nombres = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];

        $hoy     = (int) $ahora->format('N');
        $hora    = $ahora->format('H:i');
        $laboral = in_array($hoy, $dias, true);
        $abierta = $laboral && $hora >= $apertura && $hora < $cierre;

        // Texto del horario: "de lunes a viernes, de 9:30 a. m. a 6:00 p. m."
        $consecutivos = count($dias) > 1 && ($dias[count($dias) - 1] - $dias[0] === count($dias) - 1);
        if ($consecutivos) {
            $textoDias = 'de ' . $nombres[$dias[0]] . ' a ' . $nombres[$dias[count($dias) - 1]];
        } else {
            $textoDias = implode(', ', array_map(fn($d) => $nombres[$d] ?? '', $dias));
        }
        $horario = $textoDias . ', de ' . self::formatoHora($apertura) . ' a ' . self::formatoHora($cierre);

        // Próxima apertura
        $proxima = '';
        if (!$abierta) {
            $cuando = '';
            if ($laboral && $hora < $apertura) {
                $cuando = 'hoy';
            } else {
                for ($i = 1; $i <= 7; $i++) {
                    $d = (($hoy - 1 + $i) % 7) + 1;
                    if (in_array($d, $dias, true)) {
                        $cuando = $i === 1 ? 'mañana' : 'el ' . $nombres[$d];
                        break;
                    }
                }
            }
            $proxima = trim($cuando . ' a las ' . self::formatoHora($apertura));
        }

        return [
            'abierta'  => $abierta,
            'horario'  => $horario,
            'proxima'  => $proxima,
            'telefono' => defined('CLINICA_TELEFONO') ? (string) CLINICA_TELEFONO : '',
        ];
    }

    private static function formatoHora(string $hhmm): string
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm) + [0, 0]);
        $h12 = $h % 12 ?: 12;
        return $h12 . ':' . sprintf('%02d', $m) . ' ' . ($h >= 12 ? 'p. m.' : 'a. m.');
    }

    public static function contacto(): array
    {
        return [
            'clinica'  => defined('CLINICA_NOMBRE') ? CLINICA_NOMBRE : 'la clínica',
            'telefono' => defined('CLINICA_TELEFONO') ? (string) CLINICA_TELEFONO : '',
        ];
    }
}