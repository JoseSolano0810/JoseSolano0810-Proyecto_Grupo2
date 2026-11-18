<?php
/**
 * campana.php — campana de notificaciones del topbar (todos los roles).
 */
$notif_campana = $notif_campana ?? ['items' => [], 'no_leidas' => 0];
?>
<div class="notif-wrap" id="notif-wrap"
     data-inicial="<?= htmlspecialchars(json_encode($notif_campana, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
    <button type="button" class="topbar-notificacion" id="notif-boton"
            aria-label="Notificaciones" aria-haspopup="true" aria-expanded="false">
        <i class="bi bi-bell"></i>
        <span class="notif-badge" id="notif-badge" hidden></span>
    </button>
    <div class="notif-panel" id="notif-panel" role="menu" hidden>
        <div class="notif-panel-cab">
            <strong>Notificaciones</strong>
            <button type="button" class="notif-marcar" id="notif-marcar-todas">Marcar todas como leídas</button>
        </div>
        <div class="notif-lista" id="notif-lista"></div>
    </div>
</div>