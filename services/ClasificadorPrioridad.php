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

    public static function orientacion(string $prioridad): array
    {
        return match ($prioridad) {
            'urgente' => [
                'titulo'   => 'Atención urgente',
                'mensaje'  => 'Su situación requiere atención inmediata. Comuníquese con la clínica o acuda ahora mismo. '
                            . 'Si tiene dificultad para respirar o tragar, o sangrado que no se detiene, acuda a urgencias.',
                'consejos' => [],
            ],
            'prioritario' => [
                'titulo'   => 'Atención prioritaria',
                'mensaje'  => 'Su molestia necesita ser revisada pronto. Comuníquese con la clínica para recibir atención oportuna.',
                'consejos' => [
                    'Evite masticar del lado afectado.',
                    'No coloque calor ni medicamentos directamente sobre la zona.',
                    'Si la molestia empeora o aparece hinchazón o fiebre, comuníquese de inmediato.',
                ],
            ],
            default => [
                'titulo'   => 'Atención normal',
                'mensaje'  => 'Por lo que nos indica, no parece requerir atención inmediata. Su reporte quedó registrado y la clínica le dará seguimiento habitual.',
                'consejos' => [
                    'Mantenga su higiene dental habitual con cepillado suave.',
                    'Evite alimentos muy duros, fríos o calientes en la zona afectada.',
                    'Si la molestia aumenta o aparecen nuevos síntomas, comuníquese con la clínica.',
                ],
            ],
        };
    }
}