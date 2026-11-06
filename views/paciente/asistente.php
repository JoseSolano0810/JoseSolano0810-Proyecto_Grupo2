<?php

$asi_telefono = defined('CLINICA_TELEFONO') ? trim((string) CLINICA_TELEFONO) : '';
?>
<div class="pagina" id="pagina-asistente">
    <h2 style="margin-bottom:6px;">Asistente inteligente</h2>
    <p style="font-size:14px;color:var(--gris-azulado);margin-bottom:20px;">
        Consulte información de su atención o reporte una molestia para recibir orientación inicial de la clínica.
    </p>

    <div class="tarjeta asi-contenedor"
         id="asi-app"
         data-telefono="<?= htmlspecialchars($asi_telefono) ?>"
         data-clinica="<?= htmlspecialchars(defined('CLINICA_NOMBRE') ? CLINICA_NOMBRE : 'la clínica') ?>">

        <div class="asi-alerta-urgente" id="asi-alerta-urgente" role="alert" hidden>
            <i class="bi bi-exclamation-octagon-fill"></i>
            <div class="asi-alerta-texto">
                <strong>Atención urgente</strong>
                <span>Comuníquese con la clínica al <strong><?= htmlspecialchars($asi_telefono) ?></strong> o acuda de inmediato.</span>
            </div>
            <?php if ($asi_telefono !== ''): ?>
                <a class="asi-btn asi-btn-peligro" href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $asi_telefono)) ?>">
                    <i class="bi bi-telephone-fill"></i> Llamar ahora
                </a>
            <?php endif; ?>
            <button type="button" class="asi-alerta-cerrar" id="asi-alerta-cerrar" aria-label="Cerrar alerta">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="asi-mensajes" id="asi-mensajes" aria-live="polite"></div>

        <div class="asi-barra-flujo" id="asi-barra-flujo" hidden>
            <span><i class="bi bi-clipboard2-pulse"></i> Reportando una molestia</span>
            <button type="button" class="asi-btn asi-btn-secundario" id="asi-cancelar-reporte">
                <i class="bi bi-x-circle"></i> Cancelar reporte
            </button>
        </div>

        <div class="asi-atajos" id="asi-atajos">
            <button type="button" class="asi-atajo" data-intencion="proxima_cita"><i class="bi bi-calendar-check"></i> Mi próxima cita</button>
            <button type="button" class="asi-atajo" data-intencion="tratamientos"><i class="bi bi-bandaid"></i> Mis tratamientos</button>
            <button type="button" class="asi-atajo" data-intencion="procedimientos"><i class="bi bi-card-checklist"></i> Procedimientos realizados</button>
            <button type="button" class="asi-atajo" data-intencion="pagos"><i class="bi bi-wallet2"></i> Pagos pendientes</button>
            <button type="button" class="asi-atajo" data-intencion="indicaciones"><i class="bi bi-journal-medical"></i> Indicaciones</button>
            <button type="button" class="asi-atajo asi-atajo-reporte" data-accion="reportar"><i class="bi bi-exclamation-circle"></i> Reportar un problema</button>
        </div>

        <form class="asi-entrada" id="asi-form" autocomplete="off">
            <input type="text" id="asi-texto" maxlength="300" placeholder="Escriba su consulta…" aria-label="Escriba su consulta">
            <button type="submit" id="asi-enviar" aria-label="Enviar"><i class="bi bi-send-fill"></i></button>
        </form>
    </div>
</div>