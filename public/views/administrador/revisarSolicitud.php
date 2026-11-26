<?php
    // Vista incluida desde index.php (?page=solicitudes).
    // index.php ya exige sesión iniciada y rol administrador (leído de la base);
    // la API vuelve a validarlo al aprobar o rechazar.
    $seccionAdmin = 'solicitudes';

    ob_start(); ?>
    <form id="formFiltros" onsubmit="return false;">
        <label for="filtroEstado">Estado:</label>
        <select id="filtroEstado">
            <option value="POR_REVISAR" selected>Por revisar</option>
            <option value="PENDIENTE">Primera revisión</option>
            <option value="EN_SEGUNDA_REVISION">Segunda revisión</option>
            <option value="APROBADA">Aprobadas</option>
            <option value="RECHAZADA">Rechazadas</option>
            <option value="CANCELADA">Canceladas</option>
            <option value="">Todas</option>
        </select>

        <label for="filtroBusqueda">Buscar:</label>
        <div class="contenedorInputs">
            <input type="text" id="filtroBusqueda" placeholder="Nombre, universidad o carrera">
        </div>

        <label for="filtroOrden">Ordenar por fecha:</label>
        <select id="filtroOrden">
            <option value="ASC" selected>Más antiguas primero</option>
            <option value="DESC">Más recientes primero</option>
        </select>
    </form>
    <?php $filtrosHtml = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/perfilUsuario.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/gestionUsuarios.css">
    <title>Solicitudes de profesores</title>
</head>
<body>
    <!--Datos del administrador y filtros-->
    <div id="datosBasicos">
        <?php require __DIR__ . '/_encabezadoAdmin.php'; ?>
    </div>

    <!--Listado de solicitudes-->
    <div id="panelSolicitudes">
        <h1>Solicitudes para ser profesor</h1>
        <p id="resumen">Cargando solicitudes...</p>
        <div id="listaSolicitudes"></div>
    </div>

    <!--Panel de revision (aprobar / rechazar)-->
    <div id="panelRevision">
        <button id="botonSalir" onclick="cerrarPanelRevision()">X</button>
        <form id="formRevision">
            <h2 id="tituloRevision"></h2>
            <p id="subtituloRevision"></p>
            <textarea id="comentarioRevision" rows="4" maxlength="1000"></textarea>
            <button type="submit" id="botonConfirmarRevision">Confirmar</button>
        </form>
    </div>
    <div id="opacidad" onclick="cerrarPanelRevision()"></div>

    <script src="<?= $baseUrl ?>/public/js/admin.js"></script>
</body>
</html>