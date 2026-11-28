<?php
// Encabezado compartido (notificaciones, sesión y perfil).
$usuario = $usuario ?? null;
$baseUrl = $baseUrl ?? '';
?>
<div class="perfil">
    <?php if ($usuario): ?>
        <!-- NOTIFICACIONES -->
        <div class="notificaciones" id="notificaciones"
             data-api="<?= htmlspecialchars($baseUrl) ?>/api"
             data-base="<?= htmlspecialchars($baseUrl) ?>">
            <button type="button" id="notif-boton" class="notif-boton" aria-label="Notificaciones" aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel">
                <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>
                </svg>
                <span class="notif-contador" id="notif-contador" hidden>0</span>
            </button>

            <div class="notif-panel" id="notif-panel" hidden>
                <div class="notif-cabecera">
                    <h3>Notificaciones</h3>
                    <button type="button" id="notif-leer-todas" class="notif-enlace">Marcar todas como leídas</button>
                </div>
                <ul class="notif-lista" id="notif-lista"></ul>
                <p class="notif-vacio" id="notif-vacio" hidden>No tienes notificaciones.</p>
                <p class="notif-error" id="notif-error" hidden>No se pudieron cargar las notificaciones.</p>
                <button type="button" id="notif-mas" class="notif-enlace notif-mas" hidden>Ver más</button>
            </div>
        </div>
        <button type="button" id="btn-cerrar-sesion" data-api="<?= htmlspecialchars($baseUrl) ?>/api">Cerrar sesión</button>
        <a href="index.php?page=perfil" class="perfil-foto" aria-label="Mi perfil" title="Mi perfil">
            <?php if (!empty($usuario['url_foto'])): ?>
                <img src="<?= htmlspecialchars($usuario['url_foto']) ?>"
                    alt="Foto de <?= htmlspecialchars($usuario['nombre'] ?? 'usuario') ?>">
            <?php else: ?>
                <span><?= htmlspecialchars(mb_strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1))) ?></span>
            <?php endif; ?>
        </a>
    <?php else: ?>
        <button type="button" aria-label="Notificaciones">
            <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>
            </svg>
        </button>
        <a href="index.php?page=login&volver=inicio" class="btn-login">Iniciar sesión</a>
    <?php endif; ?>
</div>