<?php
// Estos datos serán enviados por el controlador.
$usuario = $usuario ?? null;
$materias = $materias ?? [];
$publicaciones = $publicaciones ?? [];
?>

<section class="inicio">
    <!-- BARRA SUPERIOR -->
    <header class="page-header">
        <input type="search"
            placeholder="Busca una tarea, una habilidad o una persona">

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
                <button>
                    <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>
                </svg>
                </button>
                <a href="index.php?page=login&volver=inicio" class="btn-login">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-grid">
        <div class="page-body">
            <!-- BIENVENIDA -->
            <section class="bienvenida">
                <?php if ($usuario): ?>
                    <h1>Bienvenido/a, <?= htmlspecialchars($usuario['nombre']) ?>.</h1>
                <?php else: ?>
                    <h1>Bienvenido a t'ASK.</h1>
                <?php endif; ?>
                <p>Las pequeñas ayudas hacen una gran comunidad.</p>
            </section>


            <!-- CREAR PUBLICACIÓN -->
            <?php if (!$usuario): ?>
            <section class="invitacion-login">
                <p>Inicia sesión para publicar, responder y guardar tareas.</p>
                <a href="index.php?page=login&volver=inicio" class="btn-login">Iniciar sesión</a>
            </section>
            <?php else: ?>
            <section id="crear-publicacion">

                <form action="/publicaciones/crear" method="POST">
                    <textarea name="mensaje" 
                    placeholder="¿Con qué necesitas ayuda?" required></textarea>

                    <button type="submit">+ Publicar tarea</button>
                </form>

            </section>
            <?php endif; ?>

            <!-- FILTROS MATERIA: Carrusel -->
            <div class="filtro-carrusel">
                <button type="button" class="carrusel-flecha" id="filtro-anterior" aria-label="Materias anteriores" disabled>
                    <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>

                <nav class="filtro-materias">
                    <button type="button" class="active" data-materia="todas">Todas</button>
                </nav>

                <button type="button" class="carrusel-flecha" id="filtro-siguiente" aria-label="Materias siguientes">
                    <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>


            <!-- FEED -->
            <section class="feed" id="feed"
                     data-api="<?= htmlspecialchars($baseUrl) ?>/api"
                     data-sesion="<?= $usuario ? '1' : '0' ?>"
                     data-usuario="<?= $usuario ? (int)$usuario['id_usuario'] : 0 ?>"
                     data-login="<?= htmlspecialchars($baseUrl) ?>/index.php?page=login&amp;volver=inicio">
                <div class="feed-vacio" id="feed-vacio" hidden>
                    <h2 id="feed-vacio-titulo"></h2>
                    <p id="feed-vacio-texto"></p>
                </div>

                <div class="feed-error" id="feed-error" hidden>
                    <p></p>
                    <button type="button">Reintentar</button>
                </div>

                <div id="feed-centinela"></div>

            </section>

        </div>
        <aside class="panel-derecho">

            <section class="estadisticas">
                <h2>
                    Un poco de tu tiempo.<br>
                    Mucho para alguien.
                </h2>

                <div class="estadisticas-contenido">
                    <div>
                        <strong>0</strong>
                        <p>Tareas resueltas esta semana</p>
                    </div>

                    <div>
                        <strong>0</strong>
                        <p>Personas echando una mano</p>
                    </div>
                </div>
            </section>

            <section class="confianza">
                <h3>La confianza se construye</h3>
                <p>
                    Perfiles verificados, valoraciones reales y una comunidad que se cuida.
                </p>
            </section>

        </aside>
    </div>

    <?php if ($usuario): ?>
        <script src="<?= htmlspecialchars($baseUrl) ?>/public/js/notificaciones.js" defer></script>
    <?php endif; ?>

</section>