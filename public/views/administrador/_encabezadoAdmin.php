<?php
// Parte común de las vistas de administrador (misma estructura que perfilUsuarios.php).
// Variables esperadas: $usuario y $baseUrl (index.php), $seccionAdmin ('solicitudes'|'usuarios')
// y, opcional, $filtrosHtml (formulario de filtros propio de cada vista).
$fotoAdmin = $usuario['url_foto'] ?: 'documentos/usuarios/imagenes/blank-profile-picture.png';
?>
<button id="btnRegresar" type="button" onclick="location.href='index.php?page=perfil'">Regresar</button>
<div id="panelDatosBasicos">
    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($fotoAdmin) ?>" alt="Foto de perfil" width="150px">
    <h2><?= htmlspecialchars($usuario['nombre']) ?></h2>
    <p id="rol">Administrador</p>

    <nav class="menuAdmin">
        <a href="index.php?page=solicitudes" class="<?= $seccionAdmin === 'solicitudes' ? 'activo' : '' ?>">Solicitudes</a>
        <a href="index.php?page=gestion-usuarios" class="<?= $seccionAdmin === 'usuarios' ? 'activo' : '' ?>">Usuarios</a>
    </nav>

    <?= $filtrosHtml ?? '' ?>
</div>
<script>
    window.APP_CONFIG = {
        baseUrl: <?= json_encode($baseUrl) ?>,
        api: <?= json_encode($baseUrl . '/api') ?>,
        idUsuario: <?= json_encode((int)$usuario['id_usuario']) ?>
    };
</script>
<script src="<?= $baseUrl ?>/public/js/adminComun.js"></script>