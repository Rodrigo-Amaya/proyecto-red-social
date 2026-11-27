function abrirPanelFoto(){
    document.getElementById("panelCambioFoto").style.display = "flex";
    document.getElementById("opacidad").style.display = "flex";
}
function cerrarPanelFoto(){
    document.getElementById("panelCambioFoto").style.display = "none";
    document.getElementById("opacidad").style.display = "none";
}

function irASolicitudes(base){
    window.location.href = base+'/public/views/administrador/revisarSolicitud.php';
}

function quieroSerProfesor(base){
    window.location.href = base + '/index.php?page=aplicacion-profesor';
}

function regresar(enlace){
    window.location.href = enlace;
}

const API = window.APP_CONFIG.api;
const API_PERFIL = window.APP_CONFIG?.api;

/*-----------------------------------------------------------------------------------------
                                FORMULARIO DE ACTUALIZACIÓN
-----------------------------------------------------------------------------------------*/
const formularioActualizar = document.getElementById('formActualizarInfo');

formularioActualizar.addEventListener('submit', async (e) => {
    e.preventDefault();

    //Datos enviados del formulario
    const nombre = document.getElementById('nombreActualizar').value;
    const correo = document.getElementById('correoActualizar').value;
    const contrasenaAnterior = document.getElementById('contrasenaActual').value;
    const contrasena = document.getElementById('contrasenaNueva').value;
    const contrasenaValidar = document.getElementById('contrasenaValidar').value;
    const nivel = document.getElementById('nivelEstudio').value;

    //JSON
    const datos = {
        nombre: nombre || null,
        correo: correo || null,
        contrasenaAnterior: contrasenaAnterior || null,
        contrasenaNueva: contrasena || null,
        contrasenaNuevaValidar: contrasenaValidar || null,
        nivelEstudio: nivel !== '' ? Number(nivel) : null
    };

    try {
        const respuesta = await fetch(`${API_PERFIL}/usuarios/${idUsuario}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok) {
            alert(resultado.Mensaje);
            return;
        }

        alert(resultado.Mensaje);
        location.reload();

    } catch (error) {
        console.error('Error:', error);
    }
});

/*-----------------------------------------------------------------------------------------
                                FORMULARIO FOTO PERFIL
-----------------------------------------------------------------------------------------*/
const formularioFoto = document.getElementById('formFotoPerfil');

formularioFoto.addEventListener('submit', async (e) => {
    e.preventDefault();

    const imagen = document.getElementById('imagenPerfil').files[0];

    if (!imagen) {
        alert('Selecciona una imagen');
        return;
    }

    //Datos enviados del formulario
    const datos = new FormData();
    datos.append('imagen', imagen);

    try {
        const respuesta = await fetch(`${API_PERFIL}/usuarios/${idUsuario}/cambiarFoto`, {
            method: 'POST',
            credentials: 'same-origin',
            body: datos
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok) {
            alert(resultado.Mensaje);
            return;
        }

        alert(resultado.Mensaje);
        location.reload();

    } catch (error) {
        console.error('Error:', error);
    }
});