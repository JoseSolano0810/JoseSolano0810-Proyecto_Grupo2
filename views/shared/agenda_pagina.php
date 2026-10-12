<?php
/**
 * Página "Agenda" del módulo de Citas (CIT-01 a CIT-06).
 */
$puedeGestionarCitas = in_array($rol ?? '', ['recepcionista', 'asistente_dental', 'administrador'], true);
?>
<div class="pagina" id="pagina-agenda">
    <div id="agenda-citas"
         data-gestion="<?= $puedeGestionarCitas ? '1' : '0' ?>"
         data-rol="<?= htmlspecialchars($rol ?? '') ?>">

        <div class="panel-titulo" style="margin-bottom:20px;">
            <h2><?= ($rol ?? '') === 'odontologo' ? 'Mi agenda' : 'Agenda general' ?></h2>
            <?php if ($puedeGestionarCitas): ?>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn-outline-odent" onclick="Citas.abrirRecordatorios()">
                    <i class="bi bi-bell"></i> Recordatorios
                </button>
                <button class="btn-odent" onclick="Citas.nueva()">
                    <i class="bi bi-plus-lg"></i> Nueva cita
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="tarjeta">
            <div class="agenda-barra">
                <div class="segmentado" id="agenda-vistas">
                    <button type="button" data-vista="lista" class="activo"><i class="bi bi-list-ul"></i> Lista</button>
                    <button type="button" data-vista="dia">Día</button>
                    <button type="button" data-vista="semana">Semana</button>
                    <button type="button" data-vista="mes">Mes</button>
                </div>

                <div class="agenda-nav" id="agenda-nav" style="display:none;">
                    <button type="button" class="btn-outline-odent" id="agenda-prev" title="Anterior"><i class="bi bi-chevron-left"></i></button>
                    <strong id="agenda-etiqueta"></strong>
                    <button type="button" class="btn-outline-odent" id="agenda-sig" title="Siguiente"><i class="bi bi-chevron-right"></i></button>
                    <button type="button" class="btn-outline-odent" id="agenda-hoy">Hoy</button>
                </div>

                <select id="agenda-odontologo" <?= ($rol ?? '') === 'odontologo' ? 'style="display:none;"' : '' ?>>
                    <option value="">Todos los odontólogos</option>
                </select>
            </div>

            <div class="agenda-filtros" id="agenda-filtros">
                <input type="text" id="filtro-q" placeholder="Buscar por paciente o cédula..." style="flex:1;min-width:200px;">
                <input type="date" id="filtro-fecha" title="Fecha">
                <select id="filtro-estado">
                    <option value="">Todos los estados</option>
                    <option value="programada">Programada</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="atendida">Atendida</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="ausente">Ausente</option>
                </select>
                <button type="button" class="btn-odent" id="filtro-buscar"><i class="bi bi-search"></i> Buscar</button>
                <button type="button" class="btn-outline-odent" id="filtro-limpiar">Limpiar</button>
            </div>

            <div id="agenda-contenido"></div>

            <div class="agenda-leyenda" id="agenda-leyenda">
                <span><i class="punto estado-programada"></i> Programada</span>
                <span><i class="punto estado-confirmada"></i> Confirmada</span>
                <span><i class="punto estado-atendida"></i> Atendida</span>
                <span><i class="punto estado-cancelada"></i> Cancelada</span>
                <span><i class="punto estado-ausente"></i> Ausente</span>
            </div>
        </div>
    </div>
</div>
