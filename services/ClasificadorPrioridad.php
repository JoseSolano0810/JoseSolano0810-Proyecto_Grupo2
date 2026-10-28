<?php

/**
 * ClasificadorPrioridad.php — ASI-03
 * Motor de reglas que clasifica un reporte como normal, prioritario o urgente.
 */
class ClasificadorPrioridad
{
    private const ORDEN = ['urgente', 'prioritario'];

    public static function clasificar(array $reporte, array $reglas): array
    {
        $descripcion = self::normalizar((string) ($reporte['descripcion'] ?? ''));
        $intensidad  = (int) ($reporte['intensidad'] ?? 0);
        $tiempo      = (string) ($reporte['tiempo'] ?? '');

        foreach (self::ORDEN as $nivel) {
            $motivos = [];

            foreach ($reglas as $r) {
                if ($r['nivel'] !== $nivel) continue;
                if (self::cumple($r, $descripcion, $intensidad, $tiempo)) {
                    $motivos[] = $r['detalle'];
                }
            }

            if ($motivos) {
                return ['prioridad' => $nivel, 'motivos' => array_values(array_unique($motivos))];
            }
        }

        return ['prioridad' => 'normal', 'motivos' => ['No cumple criterios de prioridad ni de urgencia']];
    }

    private static function cumple(array $regla, string $descripcion, int $intensidad, string $tiempo): bool
    {
        $valor = (string) $regla['valor'];

        switch ($regla['campo']) {
            case 'intensidad':
                return $regla['operador'] === '>=' && $intensidad >= (int) $valor;

            case 'tiempo':
                if ($regla['operador'] !== 'en') return false;
                $codigos = array_map('trim', explode(',', $valor));
                return in_array($tiempo, $codigos, true);

            case 'descripcion':
                if ($regla['operador'] !== 'contiene') return false;
                foreach (explode(',', $valor) as $frase) {
                    $frase = self::normalizar($frase);
                    if ($frase === '') continue;
                    // Palabra o frase completa (evita que "pus" coincida con "puse")
                    if (preg_match('/(?<![a-z0-9])' . preg_quote($frase, '/') . '(?![a-z0-9])/', $descripcion)) {
                        return true;
                    }
                }
                return false;
        }

        return false;
    }

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto) ?? '';
        return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    }

    /** true si la descripción (ya normalizada) contiene alguna de las frases */
    private static function menciona(string $descripcion, array $frases): bool
    {
        foreach ($frases as $frase) {
            $frase = self::normalizar($frase);
            if ($frase !== '' && preg_match('/(?<![a-z0-9])' . preg_quote($frase, '/') . '(?![a-z0-9])/', $descripcion)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Indicaciones generales para pasar la noche cuando la clínica está cerrada.
     * No nombra medicamentos: cada persona sabe a qué es alérgica.
     */
    private static function consejosFueraDeHorario(array $reporte): array
    {
        $desc       = self::normalizar((string) ($reporte['descripcion'] ?? ''));
        $zona       = (string) ($reporte['zona'] ?? '');
        $intensidad = (int) ($reporte['intensidad'] ?? 0);

        $consejos = [];

        // Situaciones puntuales (primero, son las más delicadas)
        if (self::menciona($desc, ['diente caido', 'se cayo el diente', 'se me cayo un diente', 'golpe', 'trauma', 'accidente', 'diente roto', 'se me quebro'])) {
            $consejos[] = 'Si se salió un diente completo, tómelo por la corona (la parte blanca), sin tocar la raíz ni frotarlo. Guárdelo en leche fría o suero; no lo ponga en agua. Entre más pronto reciba atención, más posibilidades hay de salvarlo, así que acuda a urgencias sin esperar a mañana.';
            $consejos[] = 'Si el golpe fue fuerte en la cabeza, o hay mareo, vómito o pérdida del conocimiento, acuda a urgencias.';
        }
        if (self::menciona($desc, ['sangrado', 'sangra', 'sangran', 'sangro', 'sangre', 'hemorragia', 'sangrado abundante', 'sangra mucho', 'no para de sangrar'])) {
            $consejos[] = 'Presione con una gasa o paño limpio sobre la zona que sangra durante 15 a 20 minutos seguidos, sin retirarlo para revisar. Si sigue sangrando, acuda a urgencias.';
        }
        if (self::menciona($desc, ['hinchazon', 'hinchado', 'inflamacion', 'inflamada', 'cara hinchada', 'cara inflamada', 'hinchazon en la cara', 'hinchazon facial', 'fiebre', 'pus', 'absceso'])) {
            $consejos[] = 'No aplique calor sobre la zona. Si la hinchazón crece rápido, tiene fiebre, o le cuesta abrir la boca, tragar o respirar, acuda a urgencias aunque sea de noche.';
        }

        // Según la zona indicada
        switch ($zona) {
            case 'diente':
                $consejos[] = 'Evite masticar del lado afectado y los alimentos muy fríos, calientes o duros.';
                $consejos[] = 'Puede enjuagar suavemente con agua tibia con un poco de sal (media cucharadita en un vaso).';
                break;
            case 'encias':
                $consejos[] = 'Enjuague suavemente con agua tibia con un poco de sal y cepille con delicadeza, sin presionar la zona.';
                break;
            case 'mandibula':
                $consejos[] = 'Descanse la mandíbula. Coma alimentos blandos, evite abrir la boca muy grande y no mastique chicle ni cosas duras.';
                break;
            case 'cara':
                $consejos[] = 'Aplique frío por fuera de la mejilla (un paño frío envuelto, 10 a 15 minutos y descanse otros 10).';
                break;
            case 'lengua':
                $consejos[] = 'Enjuague con agua tibia con un poco de sal y evite comidas picantes, ácidas o muy calientes.';
                break;
            case 'ortodoncia':
                $consejos[] = 'Si un alambre o bracket le pincha, cúbralo con cera de ortodoncia o un pedacito de algodón. No lo corte ni lo doble usted mismo.';
                break;
            case 'restauracion':
                $consejos[] = 'Guarde la corona, el empaste o la pieza que se soltó para llevarla a la clínica. No la pegue con pegamentos caseros y evite masticar de ese lado.';
                break;
            default:
                $consejos[] = 'Evite los alimentos muy fríos, calientes o duros mientras la molestia continúe.';
        }

        // Dolor
        if ($intensidad >= 5) {
            $consejos[] = 'Para el dolor, puede tomar un analgésico que usted ya sepa que tolera, siguiendo la dosis indicada en su empaque. Si tiene alergias, está embarazada o toma otros medicamentos y no está seguro de qué tomar, mejor no se automedique.';
        }
        if (!in_array($zona, ['cara', 'ortodoncia', 'restauracion'], true)) {
            $consejos[] = 'Un paño frío por fuera de la mejilla (10 a 15 minutos, con descansos) puede ayudar a calmar la molestia.';
        }
        $consejos[] = 'Al acostarse, mantenga la cabeza un poco elevada con una almohada extra; ayuda a reducir la presión y el dolor.';

        return array_slice(array_values(array_unique($consejos)), 0, 7);
    }

    public static function orientacion(string $prioridad, array $reporte = [], ?array $clinica = null): array
    {
        $abierta  = $clinica === null || !empty($clinica['abierta']);
        $horario  = (string) ($clinica['horario']  ?? '');
        $proxima  = (string) ($clinica['proxima']  ?? '');
        $telefono = defined('CLINICA_TELEFONO') && CLINICA_TELEFONO !== '' ? (string) CLINICA_TELEFONO : '';
        $llamar   = $telefono !== '' ? " al {$telefono}" : '';

        switch ($prioridad) {
            case 'urgente':
                if ($abierta) {
                    return [
                        'titulo'   => 'Atención urgente',
                        'mensaje'  => 'Su situación requiere atención inmediata. Comuníquese con la clínica' . $llamar . ' o acuda ahora mismo. '
                                    . 'Si tiene dificultad para respirar o tragar, o sangrado que no se detiene, acuda a urgencias.',
                        'consejos' => [],
                    ];
                }
                return [
                    'titulo'   => 'Atención urgente',
                    'mensaje'  => 'La clínica está cerrada en este momento (atendemos ' . $horario . '). Su reporte quedó registrado para que la clínica lo revise. '
                                . 'Mientras tanto, siga las indicaciones de abajo y llame a la clínica' . $llamar . ' ' . $proxima . (str_ends_with($proxima, '.') ? ' ' : '. ')
                                . 'Si tiene dificultad para respirar o tragar, sangrado que no se detiene, o la hinchazón avanza hacia el ojo o el cuello, '
                                . 'no espere y acuda a urgencias o llame al 911.',
                    'consejos' => self::consejosFueraDeHorario($reporte),
                ];

            case 'prioritario':
                return [
                    'titulo'   => 'Atención prioritaria',
                    'mensaje'  => $abierta
                        ? 'Su molestia necesita ser revisada pronto. Comuníquese con la clínica para recibir atención oportuna.'
                        : 'Su molestia necesita ser revisada pronto. La clínica está cerrada en este momento (atendemos ' . $horario . '); llame' . $llamar . ' ' . $proxima . ' para recibir atención oportuna.',
                    'consejos' => [
                        'Evite masticar del lado afectado.',
                        'No coloque calor ni medicamentos directamente sobre la zona.',
                        'Si la molestia empeora o aparece hinchazón o fiebre, comuníquese de inmediato.',
                    ],
                ];

            default:
                return [
                    'titulo'   => 'Atención normal',
                    'mensaje'  => 'Por lo que nos indica, no parece requerir atención inmediata. Su reporte quedó registrado y la clínica le dará seguimiento habitual.',
                    'consejos' => [
                        'Mantenga su higiene dental habitual con cepillado suave.',
                        'Evite alimentos muy duros, fríos o calientes en la zona afectada.',
                        'Si la molestia aumenta o aparecen nuevos síntomas, comuníquese con la clínica.',
                    ],
                ];
        }
    }
}