<?php
    session_start();

    //Solo pueden aplicar usuarios con sesión iniciada
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: /public/views/auth/authPage.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/auth.css">
    <link rel="stylesheet" href="../../css/aplicacionProfesor.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>Aplicar como profesor</title>
</head>
<body>
    <div class="contenedorRecuperar contenedorProfesor">
        <!--ELEMENTOS DECORATIVOS-->
        <div class="hojasFondoRecuperar hojasFondoProfesor"></div>
        <!--ELEMENTOS PRINCIPALES-->
        <div class="subContenedorRecuperar subContenedorProfesor">
            <form id="formAplicacionProfesor" enctype="multipart/form-data">
                <h1>Aplicar como profesor</h1>
                <p>Completa tus datos académicos. Un administrador revisará tu solicitud en dos etapas.</p>

                <div id="errorProfesor"></div>
                <div id="exitoProfesor"></div>

                <div class="gridProfesor">
                    <!--universidad-->
                    <div class="campoProfesor campoCompleto">
                        <label for="universidad">Universidad:</label>
                        <div class="contenedorInputs">
                            <input type="text" name="universidad" id="universidad" maxlength="200" required>
                        </div>
                    </div>

                    <!--carrera-->
                    <div class="campoProfesor">
                        <label for="carrera">Carrera:</label>
                        <div class="contenedorInputs">
                            <input type="text" name="carrera" id="carrera" maxlength="150" required>
                        </div>
                    </div>

                    <!--grado academico-->
                    <div class="campoProfesor">
                        <label for="grado_academico">Grado académico:</label>
                        <select name="grado_academico" id="grado_academico" required>
                            <option value="" disabled selected>Selecciona uno</option>
                            <option value="Técnico">Técnico</option>
                            <option value="Licenciatura">Licenciatura</option>
                            <option value="Ingeniería">Ingeniería</option>
                            <option value="Maestría">Maestría</option>
                            <option value="Doctorado">Doctorado</option>
                        </select>
                    </div>

                    <!--año de graduacion-->
                    <div class="campoProfesor">
                        <label for="anio_graduacion">Año de graduación:</label>
                        <div class="contenedorInputs">
                            <input type="number" name="anio_graduacion" id="anio_graduacion" min="1950" max="<?= date('Y') ?>" required>
                        </div>
                    </div>

                    <!--telefono-->
                    <div class="campoProfesor">
                        <label for="telefono_universidad">Teléfono de la universidad (opcional):</label>
                        <div class="contenedorInputs">
                            <input type="tel" minlength="8" maxlength="8" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" name="telefono_universidad" id="telefono_universidad" maxlength="30">
                        </div>
                    </div>

                    <!--titulo-->
                    <div class="campoProfesor campoCompleto">
                        <label for="titulo">Título (PDF o imagen, máx. 5 MB):</label>
                        <div class="contenedorInputs">
                            <input type="file" name="titulo" id="titulo" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        </div>
                    </div>
                </div>
                <br>

                <button type="submit" class="btnSubmit">Enviar solicitud</button>
            </form>
        </div>
    </div>
    <script src="../../js/aplicacionProfesor.js"></script>
</body>
</html>