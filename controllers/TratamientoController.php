<?php

require_once ROOT_PATH . '/models/Tratamiento.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

/**
 * TratamientoController.php — TRA-01 / TRA-02 / TRA-03 / TRA-04
 * Odontólogo y administrador gestionan los planes; el paciente solo consulta los suyos.
 */
class TratamientoController
{
    /** Roles que crean y administran tratamientos */
    private const ROLES_GESTION = ['odontologo', 'administrador'];

    private Tratamiento $model;

    public function __construct()
    {
        AuthService::requerir(['odontologo', 'administrador', 'paciente']);
        $this->model = new Tratamiento();
    }

    /* ── Utilidades ─────────────────────────────────────────── */

    private function rolActivo(): string
    {
        return (string) (AuthService::usuarioActual()['rol'] ?? '');
    }

    private function idUsuario(): int
    {
        return (int) (AuthService::usuarioActual()['id'] ?? 0);
    }

    private function nombreUsuario(): string
    {
        return (string) (AuthService::usuarioActual()['usuario'] ?? 'Sistema');
    }

    private function puedeGestionar(): bool
    {
        return in_array($this->rolActivo(), self::ROLES_GESTION, true);
    }

    /** Corta la petición con 403 si el rol activo no gestiona tratamientos */
    private function exigirGestion(): void
    {
        if (!$this->puedeGestionar()) {
            Bitacora::registrar('ACCESO_DENEGADO', 'Intento de gestionar tratamientos sin permisos: ' . mb_substr($_GET['accion'] ?? '', 0, 60));
            $this->responder(['ok' => false, 'error' => 'No cuenta con los permisos necesarios para gestionar tratamientos.'], 403);
        }
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

    private function esFecha(string $f): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f;
    }

    private function fecha(?string $f, bool $conHora = false): string
    {
        if (!$f) return '';
        $t = strtotime($f);
        return $t ? date($conHora ? 'd/m/Y H:i' : 'd/m/Y', $t) : '';
    }

    /** El paciente solo puede ver sus propios tratamientos; el personal clínico, todos */
    private function tieneAcceso(array $t): bool
    {
        if ($this->puedeGestionar()) return true;
        return $this->rolActivo() === 'paciente' && (int) $t['paciente_id_usuario'] === $this->idUsuario();
    }

    private function formatear(array $t): array
    {
        $costo = (float) $t['costo_estimado'];
        return [
            'id'                 => (int) $t['id_tratamiento'],
            'id_paciente'        => (int) $t['id_paciente'],
            'id_expediente'      => $t['id_expediente'] !== null ? (int) $t['id_expediente'] : null,
            'asociado'           => $t['id_expediente'] !== null,
            'paciente'           => $t['paciente'] ?? 'Paciente #' . $t['id_paciente'],
            'paciente_cedula'    => $t['paciente_cedula'] ?? '',
            'id_odontologo'      => (int) $t['id_odontologo'],
            'odontologo'         => $t['odontologo'] ?? 'Odontólogo #' . $t['id_odontologo'],
            'nombre'             => $t['nombre'],
            'tratamiento'        => $t['nombre'],             // compatibilidad con el inicio del panel
            'descripcion'        => $t['descripcion'],
            'costo'              => $costo,
            'costo_fmt'          => '₡' . number_format($costo, 0, ',', '.'),
            'fecha_inicio'       => $t['fecha_inicio'],
            'inicio'             => $this->fecha($t['fecha_inicio']),
            'fecha_fin_estimada' => $t['fecha_fin_estimada'],
            'fin_estimada'       => $this->fecha($t['fecha_fin_estimada']),
            'fin_real'           => $this->fecha($t['fecha_fin_real']),
            'estado'             => $t['estado'],
            'estado_texto'       => Tratamiento::ETIQUETAS[$t['estado']] ?? $t['estado'],
            'progreso'           => (int) $t['progreso'],
            'observaciones'      => $t['observaciones'] ?? '',
            'piezas'             => $t['piezas'] ?? [],
            'asociado_el'        => $this->fecha($t['fecha_asociacion'], true),
        ];
    }

    private function formatearHistorial(array $h): array
    {
        $etq = Tratamiento::ETIQUETAS;
        $ant = $etq[$h['estado_anterior']] ?? null;
        $nue = $etq[$h['estado_nuevo']] ?? null;

        $titulo = match ($h['accion']) {
            'creado'   => 'Plan creado' . ($nue ? " como «{$nue}»" : ''),
            'estado'   => $ant && $nue ? "Cambió de «{$ant}» a «{$nue}»" : 'Actualización de estado',
            'asociado' => 'Asociado al expediente y al odontograma',
            default    => 'Cambio registrado',
        };

        return [
            'accion'        => $h['accion'],
            'titulo'        => $titulo,
            'estado_nuevo'  => $h['estado_nuevo'],
            'observaciones' => $h['observaciones'] ?? '',
            'usuario'       => $h['usuario_nombre'],
            'fecha'         => $this->fecha($h['created_at'], true),
        ];
    }

    /* ── Datos para las vistas del panel ────────────────────── */

    /**
     * Alimenta el inicio del panel y el portal del paciente.
     * Paciente: todos sus tratamientos. Odontólogo/administrador: los que están en curso.
     */
    public function listar(): array
    {
        if ($this->rolActivo() === 'paciente') {
            $idPaciente = $this->model->idPacientePorUsuario($this->idUsuario());
            if (!$idPaciente) return [];
            $filas = $this->model->consultar(['id_paciente' => $idPaciente]);
        } else {
            $filas = $this->model->consultar(['estado' => ['pendiente', 'en_proceso']]);
        }
        return array_map([$this, 'formatear'], $filas);
    }

    /* ── GET: consultas ─────────────────────────────────────── */

    /** TRA-02 esc. 1 y TRA-04: búsqueda y filtros (q, estado, id_paciente) */
    public function consultar(): void
    {
        $filtros = [
            'q'      => trim($_GET['q'] ?? ''),
            'estado' => trim($_GET['estado'] ?? ''),
        ];

        if ($this->rolActivo() === 'paciente') {
            $idPaciente = $this->model->idPacientePorUsuario($this->idUsuario());
            if (!$idPaciente) $this->responder(['ok' => true, 'data' => []]);
            $filtros['id_paciente'] = $idPaciente;
        } else {
            $filtros['id_paciente'] = (int) ($_GET['id_paciente'] ?? 0);
        }

        $filas = array_map([$this, 'formatear'], $this->model->consultar($filtros));
        $this->responder(['ok' => true, 'data' => $filas]);
    }

    /** Listas para los formularios y filtros */
    public function catalogos(): void
    {
        $this->exigirGestion();

        $pacientes = array_map(fn($p) => [
            'id'              => (int) $p['id_paciente'],
            'nombre'          => $p['nombre_completo'],
            'cedula'          => $p['identificacion'],
            'tiene_expediente' => $p['id_expediente'] !== null,
        ], $this->model->pacientesActivos());

        $estados = [];
        foreach (Tratamiento::ETIQUETAS as $valor => $etiqueta) {
            $estados[] = ['valor' => $valor, 'etiqueta' => $etiqueta];
        }

        $this->responder(['ok' => true, 'pacientes' => $pacientes, 'estados' => $estados]);
    }

    /** Detalle de un tratamiento con su línea de tiempo (TRA-02 esc. 2) */
    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $t  = $id ? $this->model->obtenerPorId($id) : null;

        if (!$t || !$this->tieneAcceso($t)) {
            $this->responder(['ok' => false, 'error' => 'No encontramos ese tratamiento.'], 404);
        }

        $this->responder([
            'ok'        => true,
            'data'      => $this->formatear($t),
            'historial' => array_map([$this, 'formatearHistorial'], $this->model->historial($id)),
        ]);
    }

    /** Tratamientos por pieza dental, para pintarlos en el odontograma (TRA-03 esc. 2) */
    public function odontograma(): void
    {
        $this->exigirGestion();

        $idExpediente = (int) ($_GET['id_expediente'] ?? 0);
        if (!$idExpediente) {
            $this->responder(['ok' => false, 'error' => 'Falta el expediente.'], 400);
        }

        $mapa = $this->model->porExpediente($idExpediente);
        $this->responder(['ok' => true, 'piezas' => $mapa ?: new stdClass()]);
    }

    /* ── POST: escritura ────────────────────────────────────── */

    /** TRA-01: crear el plan de tratamiento */
    public function crear(): void
    {
        $this->exigirGestion();
        $d = $this->entrada();

        $idPaciente  = (int) ($d['id_paciente'] ?? 0);
        $nombre      = trim((string) ($d['nombre'] ?? ''));
        $descripcion = trim((string) ($d['descripcion'] ?? ''));
        $costoTexto  = trim((string) ($d['costo_estimado'] ?? ''));
        $inicio      = trim((string) ($d['fecha_inicio'] ?? ''));
        $fin         = trim((string) ($d['fecha_fin_estimada'] ?? ''));
        $estado      = trim((string) ($d['estado'] ?? ''));
        $obs         = trim((string) ($d['observaciones'] ?? ''));
        $piezasIn    = is_array($d['piezas'] ?? null) ? $d['piezas'] : [];

        // Escenario 2: avisar de todos los campos que faltan, no solo del primero
        $faltan = [];
        if (!$idPaciente)          $faltan[] = 'paciente';
        if ($nombre === '')        $faltan[] = 'nombre del tratamiento';
        if (!$piezasIn)            $faltan[] = 'piezas dentales';
        if ($descripcion === '')   $faltan[] = 'descripción';
        if ($costoTexto === '')    $faltan[] = 'costo estimado';
        if ($inicio === '')        $faltan[] = 'fecha de inicio';
        if ($fin === '')           $faltan[] = 'fecha estimada de finalización';
        if ($estado === '')        $faltan[] = 'estado';
        if ($faltan) {
            $this->responder(['ok' => false, 'error' => 'Para guardar el plan falta completar: ' . implode(', ', $faltan) . '.'], 400);
        }

        if (mb_strlen($nombre) > 120) {
            $this->responder(['ok' => false, 'error' => 'El nombre del tratamiento no puede superar los 120 caracteres.'], 400);
        }
        if (mb_strlen($descripcion) > 2000 || mb_strlen($obs) > 1000) {
            $this->responder(['ok' => false, 'error' => 'La descripción o las observaciones son demasiado largas.'], 400);
        }

        $costoTexto = str_replace([' ', '₡'], '', $costoTexto);
        if (!is_numeric($costoTexto) || (float) $costoTexto < 0 || (float) $costoTexto > 99999999.99) {
            $this->responder(['ok' => false, 'error' => 'El costo estimado debe ser un monto válido, mayor o igual a cero.'], 400);
        }
        $costo = round((float) $costoTexto, 2);

        if (!$this->esFecha($inicio) || !$this->esFecha($fin)) {
            $this->responder(['ok' => false, 'error' => 'Revise las fechas: deben tener un formato válido.'], 400);
        }
        if ($fin < $inicio) {
            $this->responder(['ok' => false, 'error' => 'La fecha estimada de finalización no puede ser anterior a la fecha de inicio.'], 400);
        }

        if (!in_array($estado, Tratamiento::ESTADOS, true)) {
            $this->responder(['ok' => false, 'error' => 'El estado seleccionado no es válido.'], 400);
        }

        $piezas = Tratamiento::normalizarPiezas($piezasIn);
        if (!$piezas || count($piezas) !== count(array_unique(array_map('intval', $piezasIn)))) {
            $this->responder(['ok' => false, 'error' => 'Alguna de las piezas dentales seleccionadas no es válida.'], 400);
        }

        if (!$this->model->existePaciente($idPaciente)) {
            $this->responder(['ok' => false, 'error' => 'No encontramos al paciente seleccionado.'], 404);
        }

        try {
            $id = $this->model->crear([
                'id_paciente'        => $idPaciente,
                'id_odontologo'      => $this->idUsuario(),
                'nombre'             => $nombre,
                'descripcion'        => $descripcion,
                'costo_estimado'     => $costo,
                'fecha_inicio'       => $inicio,
                'fecha_fin_estimada' => $fin,
                'estado'             => $estado,
                'progreso'           => Tratamiento::calcularProgreso($estado, isset($d['progreso']) ? (int) $d['progreso'] : null),
                'observaciones'      => $obs,
                'piezas'             => $piezas,
                'id_usuario'         => $this->idUsuario(),
                'usuario_nombre'     => $this->nombreUsuario(),
            ]);

            $t = $this->model->obtenerPorId($id);
            Bitacora::registrar('TRATAMIENTO_CREADO', "Plan #$id «" . mb_substr($nombre, 0, 80) . "» para {$t['paciente']}");

            $this->responder([
                'ok'      => true,
                'id'      => $id,
                'mensaje' => "El plan «{$nombre}» quedó registrado para {$t['paciente']}.",
            ], 201);
        } catch (Throwable $e) {
            error_log('TRA-01 crear tratamiento: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos guardar el plan. Intente de nuevo en unos segundos.'], 500);
        }
    }

    /** TRA-02 esc. 2: actualizar estado, avance y observaciones */
    public function actualizarEstado(): void
    {
        $this->exigirGestion();
        $d = $this->entrada();

        $id     = (int) ($d['id'] ?? 0);
        $estado = trim((string) ($d['estado'] ?? ''));
        $obs    = trim((string) ($d['observaciones'] ?? ''));

        $t = $id ? $this->model->obtenerPorId($id) : null;
        if (!$t) {
            $this->responder(['ok' => false, 'error' => 'No encontramos ese tratamiento.'], 404);
        }
        if (!in_array($estado, Tratamiento::ESTADOS, true)) {
            $this->responder(['ok' => false, 'error' => 'Seleccione el nuevo estado del tratamiento.'], 400);
        }
        if (mb_strlen($obs) > 1000) {
            $this->responder(['ok' => false, 'error' => 'Las observaciones no pueden superar los 1000 caracteres.'], 400);
        }

        $progreso = isset($d['progreso']) && $d['progreso'] !== '' ? (int) $d['progreso'] : null;
        $nuevoProgreso = Tratamiento::calcularProgreso($estado, $progreso, (int) $t['progreso']);

        if ($estado === $t['estado'] && $obs === '' && $nuevoProgreso === (int) $t['progreso']) {
            $this->responder(['ok' => false, 'error' => 'No hay cambios por guardar: el estado y el avance siguen igual.'], 400);
        }

        try {
            $anterior = $this->model->actualizarEstado($id, $estado, $obs, $progreso, $this->idUsuario(), $this->nombreUsuario());

            $etq = Tratamiento::ETIQUETAS;
            Bitacora::registrar(
                'TRATAMIENTO_ESTADO',
                "Plan #$id: {$etq[$anterior]} → {$etq[$estado]}" . ($estado === 'en_proceso' ? " ({$nuevoProgreso}%)" : '')
            );

            $this->responder([
                'ok'      => true,
                'mensaje' => "El tratamiento «{$t['nombre']}» ahora está {$etq[$estado]}.",
            ]);
        } catch (Throwable $e) {
            error_log('TRA-02 actualizar estado: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos actualizar el tratamiento. Intente de nuevo.'], 500);
        }
    }

    /** TRA-03: asociar el tratamiento al expediente y a las piezas del odontograma */
    public function asociar(): void
    {
        $this->exigirGestion();
        $d = $this->entrada();

        $id       = (int) ($d['id'] ?? 0);
        $piezasIn = is_array($d['piezas'] ?? null) ? $d['piezas'] : [];

        $t = $id ? $this->model->obtenerPorId($id) : null;
        if (!$t) {
            $this->responder(['ok' => false, 'error' => 'No encontramos ese tratamiento.'], 404);
        }

        // Escenario 3: sin piezas no se puede asociar
        if (!$piezasIn) {
            $this->responder(['ok' => false, 'error' => 'Debe seleccionar al menos una pieza dental antes de continuar.'], 400);
        }

        $piezas = Tratamiento::normalizarPiezas($piezasIn);
        if (!$piezas || count($piezas) !== count(array_unique(array_map('intval', $piezasIn)))) {
            $this->responder(['ok' => false, 'error' => 'Alguna de las piezas dentales seleccionadas no es válida.'], 400);
        }

        if ($t['estado'] === 'cancelado') {
            $this->responder(['ok' => false, 'error' => 'Este tratamiento está cancelado. Reactívelo antes de asociarlo al odontograma.'], 409);
        }

        $idExpediente = $this->model->expedienteDePaciente((int) $t['id_paciente']);
        if (!$idExpediente) {
            $this->responder([
                'ok'    => false,
                'error' => "{$t['paciente']} todavía no tiene expediente clínico. Créelo desde Expedientes y luego vuelva a asociar el tratamiento.",
            ], 409);
        }

        try {
            $this->model->asociar($id, $idExpediente, $piezas, $this->idUsuario(), $this->nombreUsuario());
            Bitacora::registrar('TRATAMIENTO_ASOCIADO', "Plan #$id asociado al expediente #$idExpediente, piezas: " . implode(', ', $piezas));

            $this->responder([
                'ok'      => true,
                'mensaje' => 'Listo: el tratamiento quedó asociado al expediente y al odontograma.',
            ]);
        } catch (Throwable $e) {
            error_log('TRA-03 asociar tratamiento: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos asociar el tratamiento. Intente de nuevo.'], 500);
        }
    }
}
