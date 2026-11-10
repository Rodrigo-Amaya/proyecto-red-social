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
            <button>
                <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>
                </svg>
            </button>
            <a href="index.php?page=perfil" class="perfil-foto" aria-label="Mi perfil" title="Mi perfil">
                <?php if (!empty($usuario['url_foto'])): ?>
                    <img src="<?= htmlspecialchars($usuario['url_foto']) ?>"
                        alt="Foto de <?= htmlspecialchars($usuario['nombre'] ?? 'usuario') ?>">
                <?php else: ?>
                    <span><?= htmlspecialchars(mb_strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1))) ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="page-grid">
        <div class="page-body">
            <!-- BIENVENIDA -->
            <section class="bienvenida">
                <h1> Bienvenido/a, <?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?>.</h1>
                <p>Las pequeñas ayudas hacen una gran comunidad.</p>
            </section>


            <!-- CREAR PUBLICACIÓN -->
            <section class="crear-publicacion">

                <form action="/publicaciones/crear" method="POST">
                    <textarea name="mensaje" 
                    placeholder="¿Con qué necesitas ayuda?" required></textarea>

                    <button type="submit">+ Publicar tarea</button>
                </form>

            </section>

            <!-- FILTROS -->
            <nav class="filtro-materias">
                <button class="active" data-materia="todas">Todas</button>
                <?php foreach ($materias as $materia): ?>
                    <button data-materia="<?= (int)$materia['id_materia'] ?>">
                    <?= htmlspecialchars($materia['nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </nav>


            <!-- FEED -->
            <section class="feed">

                <?php if (empty($publicaciones)): ?>

                    <div class="feed-vacio">
                        <h2>¡Tu comunidad comienza aquí!</h2>
                        <p>
                            Aún no hay publicaciones.
                            Sé la primera persona en compartir una pregunta o ayudar a alguien.
                        </p>
                    </div>

                <?php else: ?>
                    <?php foreach ($publicaciones as $publicacion): ?>
                        <article class="publicacion" data-materia="<?= (int)$publicacion['id_materia'] ?>">
                            <header class="publicacion-autor">
                                <?php if (!empty($publicacion['url_foto'])): ?>
                                    <img src="<?= htmlspecialchars($publicacion['url_foto']) ?>"
                                        alt="Foto de <?= htmlspecialchars($publicacion['autor'] ?? 'usuario') ?>">
                                <?php endif; ?>

                                <div class="publicacion-info">
                                    <strong><?= htmlspecialchars($publicacion['autor'] ?? 'Usuario eliminado') ?></strong>
                                    <small><?= htmlspecialchars($publicacion['materia'] ?? '') ?></small>
                                </div>
                            </header>

                            <p class="publicacion-titulo"><?= htmlspecialchars($publicacion['titulo']) ?></p>
                            <p><?= htmlspecialchars($publicacion['mensaje']) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

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