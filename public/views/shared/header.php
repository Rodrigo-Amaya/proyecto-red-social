<?php
// Encabezado compartido (notificaciones, sesión y perfil).
$usuario = $usuario ?? null;
$baseUrl = $baseUrl ?? '';
?>

<div class="perfil">
    <?php if ($usuario): ?>
        <div id="notificaciones" class="notificaciones"
            data-api="<?= htmlspecialchars($baseUrl) ?>/api"
            data-base="<?= htmlspecialchars($baseUrl) ?>">

            <button type="button" id="notif-boton" aria-label="Notificaciones"
                    aria-expanded="false" aria-controls="notif-panel">
                <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>
                </svg>
                <span id="notif-contador" class="notif-contador" hidden>0</span>
            </button>

            <div id="notif-panel" class="notif-panel" hidden>
                <div class="notif-cabecera">
                    <h3>Notificaciones</h3>
                    <button type="button" id="notif-leer-todas">Marcar todas como leídas</button>
                </div>

                <ul id="notif-lista" class="notif-lista"></ul>

                <p id="notif-vacio" class="notif-vacio" hidden>No tienes notificaciones.</p>
                <p id="notif-error" class="notif-error" hidden>No se pudieron cargar las notificaciones.</p>

                <button type="button" id="notif-mas" class="notif-mas" hidden>Ver más</button>
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
        <a href="index.php?page=login&volver=inicio" class="btn-login">Iniciar sesión</a>
    <?php endif; ?>
</div>