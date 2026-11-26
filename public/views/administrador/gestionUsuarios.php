<?php
    // Vista incluida desde index.php (?page=gestion-usuarios).
    // index.php ya exige sesión iniciada y rol administrador (leído de la base);
    // la API (/usuarios/gestion y PATCH /usuarios/{id}) vuelve a validarlo.
    $seccionAdmin = 'usuarios';
    ob_start(); 
 ?>
    <form id="formFiltrosUsuarios" onsubmit="return false;">
        <label for="filtroEstadoUsuario">Estado:</label>
        <select id="filtroEstadoUsuario">
            <option value="" selected>Todos</option>
            <option value="0">Activos</option>
            <option value="1">Bloqueados</option>
        </select>

        <label for="filtroRolUsuario">Rol:</label>
        <select id="filtroRolUsuario">
            <option value="" selected>Todos</option>
        </select>

        <label for="filtroBusquedaUsuario">Buscar:</label>
        <div class="contenedorInputs">
            <input type="text" id="filtroBusquedaUsuario" placeholder="Nombre o correo">
        </div>

        <label for="filtroOrdenUsuario">Ordenar:</label>
        <select id="filtroOrdenUsuario">
            <option value="" selected>Más recientes primero</option>
            <option value="ASC">Nombre (A-Z)</option>
            <option value="DESC">Nombre (Z-A)</option>
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
    <title>Gestión de usuarios</title>
</head>
<body>
    <!--Datos del administrador y filtros-->
    <div id="datosBasicos">
        <?php require __DIR__ . '/_encabezadoAdmin.php'; ?>
    </div>

    <!--Listado de usuarios-->
    <div id="panelUsuarios">
        <h1>Gestión de usuarios</h1>
        <p id="resumenUsuarios">Cargando usuarios...</p>
        <div id="listaUsuarios"></div>
    </div>

    <!--Panel de acción (cambiar rol / bloquear / desbloquear)-->
    <div id="panelAccionUsuario">
        <button id="botonSalirAccion" type="button" onclick="cerrarPanelAccion()">X</button>
        <form id="formAccionUsuario">
            <h2 id="tituloAccion"></h2>
            <p id="subtituloAccion"></p>
            <div id="campoRolAccion" hidden>
                <label for="rolAccion">Nuevo rol:</label>
                <select id="rolAccion"></select>
            </div>
            <p id="errorAccion" role="alert" hidden></p>
            <button type="submit" id="botonConfirmarAccion">Confirmar</button>
        </form>
    </div>
    <div id="opacidadUsuarios" onclick="cerrarPanelAccion()"></div>

    <script src="<?= $baseUrl ?>/public/js/gestionUsuarios.js"></script>
</body>
</html>