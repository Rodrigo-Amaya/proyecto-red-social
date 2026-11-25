<?php
session_start();
    $id_usuario = $_SESSION['id_usuario'] ?? NULL;

    //Ajusta este ID al rol de administrador de tu tabla `roles` (el mismo de Profesor::ROL_ADMIN)
    const ROL_ADMIN = 3;

    //Sino hay sesion iniciada
    if (!$id_usuario) {
        header("Location: ../auth/authPage.php");
        exit;
    }

    //Solo administradores (la API vuelve a validarlo al aprobar o rechazar)
    if ((int)($_SESSION['id_rol'] ?? 0) !== ROL_ADMIN) {
        http_response_code(403);
        die("Acceso restringido a administradores");
    }

    $nombre = $_SESSION['nombre_usuario'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/perfilUsuario.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <title>Solicitudes de profesores</title>
</head>
<body>
    <!--Datos del administrador y filtros-->
    <div>
        <div id="panelDatosBasicos">
            <h2><?= htmlspecialchars($nombre) ?></h2>
            <p id="rol">Administrador</p>

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
        </div>
        <!--Ajusta la ruta de cerrarSesion.php segun tu estructura-->
        <form action="../usuarios/cerrarSesion.php" method="POST">
            <button type="submit" id="cerrarSesion">Cerrar sesión</button>
        </form>
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

    <script src="../../js/admin.js"></script>
</body>
</html>