<?php
// Estos datos serán enviados por el controlador.
$usuario = $usuario ?? null;
$materias = $materias ?? [];
$publicaciones = $publicaciones ?? [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inicio | Red Social Educativa</title>

    <link rel="stylesheet" href="public/css/inicio.css">
</head>

<body>

    <div class="layout">

        <!-- BARRA LATERAL -->
        <aside class="sidebar">

            <div class="logo">
                <h2>Mano a Mano</h2>
            </div>

            <nav class="menu">
                <a href="/inicio" class="activo">Inicio</a>
                <a href="/explorar">Explorar tareas</a>
                <a href="/mis-tareas">Mis tareas</a>
                <a href="/guardados">Guardados</a>
            </nav>

            <div class="sidebar-info">
                <h3>Lo que sabes hacer puede ayudar a alguien.</h3>
                <p>Así nos cuidamos.</p>
            </div>

        </aside>


        <!-- CONTENIDO PRINCIPAL -->
        <main class="contenido">

            <!-- BARRA SUPERIOR -->
            <header class="topbar">

                <input
                    type="search"
                    placeholder="Busca una tarea, una habilidad o una persona"
                >

                <div class="perfil">
                    <span>Notificaciones</span>
                    <span>Mi perfil</span>
                </div>

            </header>


            <!-- BIENVENIDA -->
            <section class="bienvenida">

                <h1>
                    Bienvenido/a,
                    <?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?>.
                </h1>

                <p>Las pequeñas ayudas hacen una gran comunidad.</p>

            </section>


            <!-- CREAR PUBLICACIÓN -->
            <section class="crear-publicacion">

                <form action="/publicaciones/crear" method="POST">

                    <textarea
                        name="mensaje"
                        placeholder="¿Con qué necesitas ayuda?"
                        required
                    ></textarea>

                    <button type="submit">
                        + Publicar tarea
                    </button>

                </form>

            </section>


            <!-- FILTROS -->
            <section class="filtros">

                <button class="activo">Todas</button>

                <?php foreach ($materias as $materia): ?>

                    <button>
                        <?= htmlspecialchars($materia['nombre']) ?>
                    </button>

                <?php endforeach; ?>

            </section>


            <!-- FEED -->
            <section class="feed">

                <?php if (empty($publicaciones)): ?>

                    <div class="feed-vacio">

                        <h2>¡Tu comunidad comienza aquí!</h2>

                        <p>
                            Aún no hay publicaciones.
                            Sé la primera persona en compartir una pregunta
                            o ayudar a alguien.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach ($publicaciones as $publicacion): ?>

                        <article class="publicacion">

                            <h3>
                                <?= htmlspecialchars($publicacion['titulo']) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($publicacion['mensaje']) ?>
                            </p>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>

        </main>

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
                    Perfiles verificados, valoraciones reales
                    y una comunidad que se cuida.
                </p>

            </section>

        </aside>
    </div>

</body>
</html>