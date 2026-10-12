<?php

require_once ROOT_PATH . '/models/Cita.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/services/RecordatorioService.php';

class CitaController
{
    /** Roles que gestionan la agenda (CIT-01, 02, 03, 06) */
    private const ROLES_GESTION = ['recepcionista', 'asistente_dental', 'administrador'];

    private Cita $model;

    public function __construct()
    {
        AuthService::requerir(['odontologo', 'asistente_dental', 'recepcionista', 'paciente', 'administrador']);
        $this->model = new Cita();
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

    private function puedeGestionar(): bool
    {
        return in_array($this->rolActivo(), self::ROLES_GESTION, true);
    }

    /** Corta la petición con 403 si el rol activo no gestiona citas */
    private function exigirGestion(): void
    {
        if (!$this->puedeGestionar()) {
            Bitacora::registrar('ACCESO_DENEGADO', 'Intento de gestionar citas sin permisos: ' . mb_substr($_GET['accion'] ?? '', 0, 60));
            $this->responder(['ok' => false, 'error' => 'No cuenta con los permisos necesarios para gestionar citas.'], 403);
        }
    }

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function entrada(): array
    {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    private function formatear(array $c): array
    {
        return [
            'id'            => (int) $c['id_cita'],
            'id_paciente'   => (int) $c['id_paciente'],
            'id_odontologo' => (int) $c['id_odontologo'],
            'paciente'      => $c['paciente']   ?? 'Paciente #' . $c['id_paciente'],
            'paciente_cedula' => $c['paciente_cedula'] ?? '',
            'odontologo'    => $c['odontologo'] ?? 'Odontólogo #' . $c['id_odontologo'],
            'fecha'         => $c['fecha'],
            'fecha_fmt'     => date('d/m/Y', strtotime($c['fecha'])),
            'hora'          => substr($c['hora_inicio'], 0, 5),
            'hora_fin'      => substr($c['hora_fin'], 0, 5),
            'duracion'      => (int) $c['duracion_min'],
            'tipo_consulta' => $c['tipo_consulta'],
            'tratamiento'   => $c['tipo_consulta'],     
            'observaciones' => $c['observaciones'] ?? '',
            'estado'        => $c['estado'],
        ];
    }

    /** Limita lo que cada rol puede ver: el odontólogo su agenda, el paciente sus citas */
    private function aplicarAlcance(array $filtros): array
    {
        $rol = $this->rolActivo();
        if ($rol === 'odontologo') $filtros['id_odontologo'] = $this->idUsuario();
        if ($rol === 'paciente')   $filtros['id_paciente']   = $this->idUsuario();
        return $filtros;
    }

    private function esFecha(string $f): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f;
    }

    private function esHora(string $h): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $h);
    }

    /* ── Datos para las vistas del panel ────────────────────── */

    /** Citas de hoy (según el alcance del rol): alimenta el "Inicio" de cada panel */
    public function listar(): array
    {
        $filtros = $this->aplicarAlcance(['fecha' => date('Y-m-d')]);
        return array_map([$this, 'formatear'], $this->model->consultar($filtros));
    }

    /** Portal del paciente: sus citas, de la más reciente a la más antigua */
    public function listarMisCitas(): array
    {
        $citas = array_map([$this, 'formatear'], $this->model->consultar(['id_paciente' => $this->idUsuario()]));
        return array_reverse($citas);
    }

    /** Portal del paciente: su próxima cita activa */
    public function proximaCita(): ?array
    {
        $hoy = date('Y-m-d');
        $ahora = date('H:i');
        $meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

        foreach ($this->model->consultar(['id_paciente' => $this->idUsuario(), 'desde' => $hoy]) as $c) {
            if (!in_array($c['estado'], Cita::ESTADOS_ACTIVOS, true)) continue;
            if ($c['fecha'] === $hoy && substr($c['hora_inicio'], 0, 5) < $ahora) continue;

            $f = strtotime($c['fecha']);
            return [
                'mes'         => $meses[(int) date('n', $f) - 1],
                'dia'         => date('d', $f),
                'hora'        => date('g:i a', strtotime($c['hora_inicio'])),
                'odontologo'  => $c['odontologo'] ?? '',
                'tratamiento' => $c['tipo_consulta'],
            ];
        }
        return null;
    }

    /* ── GET: consultas ─────────────────────────────────────── */

    /** CIT-02 esc. 1 y CIT-04: consulta con filtros */
    public function consultar(): void
    {
        $filtros = [
            'q'             => trim($_GET['q'] ?? ''),
            'id_odontologo' => (int) ($_GET['odontologo'] ?? 0),
            'estado'        => trim($_GET['estado'] ?? ''),
        ];
        foreach (['fecha', 'desde', 'hasta'] as $campo) {
            $v = trim($_GET[$campo] ?? '');
            if ($v !== '' && $this->esFecha($v)) $filtros[$campo] = $v;
        }

        $filtros = $this->aplicarAlcance($filtros);
        $citas   = array_map([$this, 'formatear'], $this->model->consultar($filtros));

        $this->responder(['ok' => true, 'data' => $citas]);
    }

    /** Listas para los formularios y filtros */
    public function catalogos(): void
    {
        $rol = $this->rolActivo();
        if ($rol === 'paciente') {
            $this->responder(['ok' => false, 'error' => 'No cuenta con los permisos necesarios.'], 403);
        }

        $odontologos = array_map(fn($u) => [
            'id' => (int) $u['id_usuario'], 'nombre' => $u['nombre_completo'],
        ], $this->model->odontologosActivos());

        if ($rol === 'odontologo') {
            $yo = $this->idUsuario();
            $odontologos = array_values(array_filter($odontologos, fn($o) => $o['id'] === $yo));
        }

        $pacientes = $this->puedeGestionar()
            ? array_map(fn($u) => [
                'id' => (int) $u['id_usuario'], 'nombre' => $u['nombre_completo'], 'cedula' => $u['identificacion'],
              ], $this->model->pacientesActivos())
            : [];

        $this->responder([
            'ok'          => true,
            'pacientes'   => $pacientes,
            'odontologos' => $odontologos,
            'tipos'       => Cita::TIPOS,
            'duraciones'  => Cita::DURACIONES,
            'estados'     => Cita::ESTADOS,
        ]);
    }

    /** Historial de cambios de una cita */
    public function historial(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $cita = $id ? $this->model->obtenerPorId($id) : null;
        if (!$cita || !$this->tieneAcceso($cita)) {
            $this->responder(['ok' => false, 'error' => 'Cita no encontrada'], 404);
        }
        $this->responder(['ok' => true, 'data' => $this->model->historial($id)]);
    }

    private function tieneAcceso(array $cita): bool
    {
        $rol = $this->rolActivo();
        if ($rol === 'odontologo') return (int) $cita['id_odontologo'] === $this->idUsuario();
        if ($rol === 'paciente')   return (int) $cita['id_paciente']   === $this->idUsuario();
        return true;
    }

    /* ── POST: CIT-01 registrar ─────────────────────────────── */

    public function crear(): void
    {
        $this->exigirGestion();
        $d = $this->entrada();

        // CIT-01 esc. 2: validación de campos obligatorios (observaciones es opcional)
        $etiquetas = [
            'id_paciente' => 'Paciente', 'id_odontologo' => 'Odontólogo', 'fecha' => 'Fecha',
            'hora' => 'Hora', 'duracion' => 'Duración', 'tipo_consulta' => 'Tipo de consulta', 'estado' => 'Estado',
        ];
        $this->exigirCampos($d, $etiquetas);

        $datos = $this->validarCita($d);

        if (!in_array($d['estado'], Cita::ESTADOS_ACTIVOS, true)) {
            $this->responder(['ok' => false, 'error' => 'Una cita nueva solo puede registrarse como programada o confirmada.'], 400);
        }
        if (!$this->model->usuarioTieneRol((int) $d['id_paciente'], 'paciente')) {
            $this->responder(['ok' => false, 'error' => 'El paciente seleccionado no existe o está inactivo.'], 400);
        }

        $datos['id_paciente'] = (int) $d['id_paciente'];
        $datos['estado']      = $d['estado'];
        $datos['creada_por']  = $this->idUsuario();

        try {
            $id = $this->model->crear($datos);
        } catch (HorarioNoDisponibleException $e) {
            $this->responder(['ok' => false, 'error' => $e->getMessage()], 409);
        } catch (PDOException $e) {
            error_log('CIT-01 crear cita: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No se pudo registrar la cita.'], 500);
        }

        $this->auditar($id, 'CREADA', 'CITA_CREADA',
            "Cita registrada para el {$datos['fecha']} a las " . substr($datos['hora_inicio'], 0, 5)
            . " ({$datos['duracion_min']} min, {$datos['tipo_consulta']})");

        $this->responder([
            'ok'      => true,
            'mensaje' => 'Cita agendada correctamente',
            'data'    => $this->formatear($this->model->obtenerPorId($id)),
        ], 201);
    }

    /* ── POST: CIT-02 esc. 2 modificar ──────────────────────── */

    public function editar(): void
    {
        $this->exigirGestion();
        $d  = $this->entrada();
        $id = (int) ($d['id'] ?? 0);

        $cita = $id ? $this->model->obtenerPorId($id) : null;
        if (!$cita) $this->responder(['ok' => false, 'error' => 'Cita no encontrada'], 404);
        $this->exigirActiva($cita, 'modificar');

        $this->exigirCampos($d, [
            'id_odontologo' => 'Odontólogo', 'fecha' => 'Fecha', 'hora' => 'Hora',
            'duracion' => 'Duración', 'tipo_consulta' => 'Tipo de consulta',
        ]);
        $datos = $this->validarCita($d, $cita['fecha']);

        try {
            $this->model->actualizar($id, $datos);
        } catch (HorarioNoDisponibleException $e) {
            $this->responder(['ok' => false, 'error' => $e->getMessage()], 409);
        } catch (PDOException $e) {
            error_log('CIT-02 editar cita: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No se pudo actualizar la cita.'], 500);
        }

        $this->auditar($id, 'MODIFICADA', 'CITA_EDITADA',
            'Cita modificada. ' . $this->describirCambios($cita, $datos));

        $this->responder([
            'ok'      => true,
            'mensaje' => 'Cita actualizada correctamente',
            'data'    => $this->formatear($this->model->obtenerPorId($id)),
        ]);
    }

    /* ── POST: CIT-03 esc. 3 reprogramar ────────────────────── */

    public function reprogramar(): void
    {
        $this->exigirGestion();
        $d  = $this->entrada();
        $id = (int) ($d['id'] ?? 0);

        $cita = $id ? $this->model->obtenerPorId($id) : null;
        if (!$cita) $this->responder(['ok' => false, 'error' => 'Cita no encontrada'], 404);
        $this->exigirActiva($cita, 'reprogramar');

        $this->exigirCampos($d, ['fecha' => 'Nueva fecha', 'hora' => 'Nueva hora']);

        $datos = $this->validarCita([
            'id_odontologo' => $cita['id_odontologo'],
            'fecha'         => $d['fecha'],
            'hora'          => $d['hora'],
            'duracion'      => $cita['duracion_min'],
            'tipo_consulta' => $cita['tipo_consulta'],
            'observaciones' => $cita['observaciones'] ?? '',
        ]);

        try {
            $this->model->actualizar($id, $datos);
        } catch (HorarioNoDisponibleException $e) {
            $this->responder(['ok' => false, 'error' => $e->getMessage()], 409);
        } catch (PDOException $e) {
            error_log('CIT-03 reprogramar cita: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No se pudo reprogramar la cita.'], 500);
        }

        $this->auditar($id, 'REPROGRAMADA', 'CITA_REPROGRAMADA',
            'De ' . date('d/m/Y', strtotime($cita['fecha'])) . ' ' . substr($cita['hora_inicio'], 0, 5)
            . ' a ' . date('d/m/Y', strtotime($datos['fecha'])) . ' ' . substr($datos['hora_inicio'], 0, 5));

        $this->responder([
            'ok'      => true,
            'mensaje' => 'Cita reprogramada correctamente',
            'data'    => $this->formatear($this->model->obtenerPorId($id)),
        ]);
    }

    /* ── POST: CIT-02 esc. 3 / CIT-06 estados ───────────────── */

    public function cancelar(): void
    {
        $d = $this->entrada();
        $d['estado'] = 'cancelada';
        $this->aplicarEstado($d);
    }

    public function cambiarEstado(): void
    {
        $this->aplicarEstado($this->entrada());
    }

    private function aplicarEstado(array $d): void
    {
        $this->exigirGestion();
        $id     = (int) ($d['id'] ?? 0);
        $estado = (string) ($d['estado'] ?? '');
        $motivo = mb_substr(trim((string) ($d['motivo'] ?? '')), 0, 150);

        if (!$id || !in_array($estado, ['confirmada', 'atendida', 'cancelada', 'ausente'], true)) {
            $this->responder(['ok' => false, 'error' => 'Datos inválidos'], 400);
        }

        $cita = $this->model->obtenerPorId($id);
        if (!$cita) $this->responder(['ok' => false, 'error' => 'Cita no encontrada'], 404);

        if (!in_array($cita['estado'], Cita::ESTADOS_ACTIVOS, true)) {
            $this->responder(['ok' => false, 'error' => "La cita ya está {$cita['estado']} y no puede cambiar de estado."], 409);
        }
        if ($estado === 'confirmada' && $cita['estado'] !== 'programada') {
            $this->responder(['ok' => false, 'error' => 'La cita ya está confirmada.'], 409);
        }
        if (in_array($estado, ['atendida', 'ausente'], true) && $cita['fecha'] > date('Y-m-d')) {
            $this->responder(['ok' => false, 'error' => 'No se puede marcar como ' . $estado . ' una cita con fecha futura.'], 400);
        }

        try {
            $this->model->cambiarEstado($id, $estado);
        } catch (PDOException $e) {
            error_log('CIT-06 cambiar estado: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No se pudo actualizar el estado de la cita.'], 500);
        }

        $accion = ['confirmada' => 'CITA_CONFIRMADA', 'atendida' => 'CITA_ATENDIDA',
                   'cancelada'  => 'CITA_CANCELADA',  'ausente'  => 'CITA_AUSENTE'][$estado];
        $detalle = "Estado: {$cita['estado']} → $estado" . ($motivo !== '' ? ". Motivo: $motivo" : '');
        $this->auditar($id, strtoupper($estado), $accion, $detalle);

        $mensajes = [
            'confirmada' => 'Cita confirmada',
            'atendida'   => 'Cita marcada como atendida',
            'cancelada'  => 'Cita cancelada correctamente',
            'ausente'    => 'Cita marcada como ausente',
        ];
        $this->responder(['ok' => true, 'mensaje' => $mensajes[$estado], 'data' => $this->formatear($this->model->obtenerPorId($id))]);
    }

    /* ── CIT-05: recordatorios ──────────────────────────────── */

    public function recordatorios(): void
    {
        $this->exigirGestion();
        $filas = array_map(fn($r) => [
            'id'       => (int) $r['id_recordatorio'],
            'envio'    => date('d/m/Y H:i', strtotime($r['created_at'])),
            'medio'    => $r['medio'],
            'destino'  => $r['destino'] ?? '',
            'estado'   => $r['estado'],
            'detalle'  => $r['detalle'],
            'paciente' => $r['paciente'] ?? '—',
            'cita'     => $r['fecha'] ? date('d/m/Y', strtotime($r['fecha'])) . ' ' . substr($r['hora_inicio'], 0, 5) : '—',
        ], $this->model->listarRecordatorios(50));

        $this->responder(['ok' => true, 'data' => $filas, 'horas_antes' => RECORDATORIO_HORAS_ANTES]);
    }

    public function ejecutarRecordatorios(): void
    {
        $this->exigirGestion();
        try {
            $r = RecordatorioService::procesar();
        } catch (Throwable $e) {
            error_log('CIT-05 recordatorios: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No se pudo ejecutar el proceso de recordatorios.'], 500);
        }
        $this->responder(['ok' => true, 'resultado' => $r,
            'mensaje' => "Recordatorios: {$r['enviados']} enviados, {$r['fallidos']} fallidos, {$r['omitidos']} sin medio válido."]);
    }

    /* ── Validaciones y auditoría ───────────────────────────── */

    /** Responde 400 listando los campos obligatorios que faltan */
    private function exigirCampos(array $d, array $etiquetas): void
    {
        $faltan = [];
        foreach ($etiquetas as $campo => $etiqueta) {
            if (trim((string) ($d[$campo] ?? '')) === '') $faltan[] = $etiqueta;
        }
        if ($faltan) {
            $this->responder([
                'ok'       => false,
                'error'    => 'Complete los campos obligatorios: ' . implode(', ', $faltan) . '.',
                'faltantes' => $faltan,
            ], 400);
        }
    }

    private function exigirActiva(array $cita, string $verbo): void
    {
        if (!in_array($cita['estado'], Cita::ESTADOS_ACTIVOS, true)) {
            $this->responder(['ok' => false, 'error' => "Solo se pueden $verbo citas programadas o confirmadas (esta está {$cita['estado']})."], 409);
        }
    }

    private function validarCita(array $d, ?string $fechaOriginal = null): array
    {
        $fecha = trim((string) $d['fecha']);
        $hora  = trim((string) $d['hora']);

        if (!$this->esFecha($fecha))  $this->responder(['ok' => false, 'error' => 'La fecha no tiene un formato válido.'], 400);
        if (!$this->esHora($hora))    $this->responder(['ok' => false, 'error' => 'La hora no tiene un formato válido (HH:MM).'], 400);
        if ($fecha < date('Y-m-d') && $fecha !== $fechaOriginal) $this->responder(['ok' => false, 'error' => 'No se puede agendar una cita en una fecha pasada.'], 400);

        $duracion = (int) $d['duracion'];
        if (!in_array($duracion, Cita::DURACIONES, true)) {
            $this->responder(['ok' => false, 'error' => 'La duración seleccionada no es válida.'], 400);
        }

        $tipo = trim((string) $d['tipo_consulta']);
        if (!in_array($tipo, Cita::TIPOS, true)) {
            $this->responder(['ok' => false, 'error' => 'El tipo de consulta seleccionado no es válido.'], 400);
        }

        $obs = trim((string) ($d['observaciones'] ?? ''));
        if (mb_strlen($obs) > 500) {
            $this->responder(['ok' => false, 'error' => 'Las observaciones no pueden superar los 500 caracteres.'], 400);
        }

        $idOdontologo = (int) $d['id_odontologo'];
        if (!$this->model->usuarioTieneRol($idOdontologo, 'odontologo')) {
            $this->responder(['ok' => false, 'error' => 'El odontólogo seleccionado no existe o está inactivo.'], 400);
        }

        [$h, $m]   = array_map('intval', explode(':', $hora));
        $inicioMin = $h * 60 + $m;
        $finMin    = $inicioMin + $duracion;
        if ($finMin >= 24 * 60) {
            $this->responder(['ok' => false, 'error' => 'La cita debe terminar dentro del mismo día.'], 400);
        }

        return [
            'id_odontologo' => $idOdontologo,
            'fecha'         => $fecha,
            'hora_inicio'   => sprintf('%02d:%02d:00', $h, $m),
            'hora_fin'      => sprintf('%02d:%02d:00', intdiv($finMin, 60), $finMin % 60),
            'duracion_min'  => $duracion,
            'tipo_consulta' => $tipo,
            'observaciones' => $obs,
        ];
    }

    private function describirCambios(array $antes, array $despues): string
    {
        $cambios = [];
        if ((int) $antes['id_odontologo'] !== $despues['id_odontologo']) $cambios[] = 'odontólogo';
        if ($antes['fecha'] !== $despues['fecha'])                       $cambios[] = 'fecha';
        if (substr($antes['hora_inicio'], 0, 5) !== substr($despues['hora_inicio'], 0, 5)) $cambios[] = 'hora';
        if ((int) $antes['duracion_min'] !== $despues['duracion_min'])   $cambios[] = 'duración';
        if ($antes['tipo_consulta'] !== $despues['tipo_consulta'])       $cambios[] = 'tipo de consulta';
        if (($antes['observaciones'] ?? '') !== $despues['observaciones']) $cambios[] = 'observaciones';

        return $cambios ? 'Campos cambiados: ' . implode(', ', $cambios) . '.' : 'Sin cambios en los datos.';
    }

    /** Historial de la cita + bitácora general del sistema */
    private function auditar(int $idCita, string $accionHistorial, string $accionBitacora, string $detalle): void
    {
        try {
            $u = AuthService::usuarioActual();
            $this->model->registrarHistorial($idCita, $accionHistorial, $detalle, (int) $u['id'], $u['usuario'] ?? null);
        } catch (Throwable $e) {
            error_log('Historial de cita: ' . $e->getMessage());
        }
        Bitacora::registrar($accionBitacora, "Cita ID $idCita: $detalle");
    }
}
