<?php
$usuario = $usuario ?? null;
?>

<section class="inicio">
    <header class="page-header">
        <h1>Publicaciones guardadas</h1>
    </header>

    <section
        class="feed"
        id="lista-guardados"
        data-api="<?= htmlspecialchars($baseUrl) ?>/api"
        data-usuario="<?= htmlspecialchars((string)($usuario['id_usuario'] ?? '')) ?>"
    >
        <div class="feed-vacio" id="guardados-vacio" hidden>
            <h2>No tienes publicaciones guardadas</h2>
            <p>Cuando guardes una publicación, aparecerá aquí.</p>
        </div>

        <div class="feed-error" id="guardados-error" hidden>
            <p></p>
            <button type="button">Reintentar</button>
        </div>
    </section>
</section>