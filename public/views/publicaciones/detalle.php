<?php
$usuario = $usuario ?? null;
$idPublicacion = (int)($idPublicacion ?? 0);
?>

<section class="detalle">
    <header class="page-header">
        <a href="index.php?page=inicio" class="enlace-volver">← Volver al inicio</a>

        <?php require __DIR__ . '/../shared/header.php'; ?>
    </header>

    <div class="detalle-contenido" id="detalle"
         data-api="<?= htmlspecialchars($baseUrl) ?>/api"
         data-id="<?= $idPublicacion ?>"
         data-sesion="<?= $usuario ? '1' : '0' ?>"
         data-usuario="<?= $usuario ? (int)$usuario['id_usuario'] : 0 ?>"
         data-login="<?= htmlspecialchars($baseUrl) ?>/index.php?page=login&amp;volver=inicio">

        <!-- Estados de la página -->
        <p class="detalle-cargando" id="detalle-cargando">Cargando publicación...</p>

        <div class="detalle-error" id="detalle-error" hidden>
            <p></p>
            <button type="button">Reintentar</button>
        </div>

        <!-- PUBLICACIÓN -->
        <article class="publicacion publicacion-detalle" id="detalle-publicacion" hidden></article>

        <!-- COMENTARIOS -->
        <section class="comentarios" id="comentarios" hidden>
            <h2 id="comentarios-titulo">Comentarios</h2>

            <?php if ($usuario): ?>
                <form class="comentario-form" id="comentario-form" novalidate>
                    <input type="text" name="titulo" maxlength="150"
                           placeholder="Título (opcional)">
                    <textarea name="mensaje" maxlength="5000" required
                              placeholder="Escribe tu respuesta..."></textarea>
                    <p class="comentario-form-error" id="comentario-form-error" hidden></p>
                    <button type="submit">Comentar</button>
                </form>
            <?php else: ?>
                <div class="invitacion-login">
                    <p>Inicia sesión para comentar.</p>
                    <a href="index.php?page=login&volver=inicio" class="btn-login">Iniciar sesión</a>
                </div>
            <?php endif; ?>

            <div class="comentarios-vacio" id="comentarios-vacio" hidden>
                <p>Aún no hay comentarios. ¡Sé la primera persona en responder!</p>
            </div>

            <div class="comentarios-lista" id="comentarios-lista"></div>
        </section>
    </div>
</section>