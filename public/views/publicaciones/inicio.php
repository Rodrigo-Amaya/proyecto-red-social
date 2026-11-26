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

        <?php require __DIR__ . '/../shared/header.php'; ?>
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

                <form id="form-publicacion">
                    <select id="pub-materia" name="id_materia" required>
                        <option value="">Elige una materia</option>
                    </select>

                    <input type="text" id="pub-titulo" name="titulo"
                           placeholder="Título de tu pregunta" maxlength="200" required>

                    <textarea id="pub-mensaje" name="mensaje"
                              placeholder=" Describe ¿Con qué necesitas ayuda?" maxlength="5000" required></textarea>

                    <div class="adjuntos">
                        <p class="adjuntos-titulo">Archivos adjuntos</p>
                        <ul id="pub-adjuntos" class="adjuntos-lista"></ul>
                    </div>

                    <p id="pub-estado" class="form-estado" role="alert" hidden></p>

                    <div class="form-acciones">
                        <input type="file" id="pub-archivos" multiple hidden
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp">
                        <button type="button" id="pub-adjuntar" title="Adjuntar archivos" aria-label="Adjuntar archivos">
                            <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            <span class="adjuntar-texto">Adjuntar</span>
                        </button>
                        <button type="submit" id="pub-enviar">+ Publicar tarea</button>
                    </div>
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

</section>