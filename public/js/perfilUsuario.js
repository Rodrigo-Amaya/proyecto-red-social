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

const imagenPerfil = document.getElementById('imagenPerfil');
const nombreArchivo = document.getElementById('nombreArchivo');

imagenPerfil.addEventListener('change', () => {
    nombreArchivo.textContent = imagenPerfil.files.length > 0
        ? imagenPerfil.files[0].name
        : 'Ningún archivo seleccionado';
});

const API = window.APP_CONFIG.api;
const API_PERFIL = window.APP_CONFIG?.api;
// Elementos del formulario
const formularioActualizar = document.getElementById('formActualizarInfo');
const contrasenaNueva = document.getElementById('contrasenaNueva');
const contrasenaValidar = document.getElementById('contrasenaValidar');
const errorContrasena = document.getElementById('errorContrasenaNueva');
const mensajeRequisitos = document.getElementById('requisitosContrasena');

/*-----------------------------------------------------------------------------------------
                                ERRORES CONTRASEÑA
-----------------------------------------------------------------------------------------*/
const requisitos = {
    longitud: /.{8,}/,
    mayuscula: /[A-Z]/,
    numero: /\d/,
    especial: /[^A-Za-z0-9]/
};

// Validar mientras escribe la nueva contraseña
contrasenaNueva.addEventListener('input', () => {
    const valor = contrasenaNueva.value;
    const mensaje = document.getElementById('requisitosContrasena');

    if (valor.length < 8) {
        mensaje.textContent = 'La contraseña debe tener al menos 8 caracteres.';
        mensaje.style.color = 'red';
    } 
    else if (!/[A-Z]/.test(valor)) {
        mensaje.textContent = 'La contraseña debe tener al menos una mayúscula.';
        mensaje.style.color = 'red';
    } 
    else if (!/\d/.test(valor)) {
        mensaje.textContent = 'La contraseña debe tener al menos un número.';
        mensaje.style.color = 'red';
    } 
    else if (!/[^A-Za-z0-9]/.test(valor)) {
        mensaje.textContent = 'La contraseña debe tener al menos un carácter especial.';
        mensaje.style.color = 'red';
    } 
    else {
        mensaje.textContent = 'La contraseña cumple todos los requisitos';
        mensaje.style.color = 'green';
    }

    validarCoincidencia();
});

// Validar mientras escribe la confirmación
contrasenaValidar.addEventListener('input', validarCoincidencia);

function validarCoincidencia() {
    if (contrasenaValidar.value === '') {
        errorContrasena.textContent = '';
        return false;
    }

    if (contrasenaNueva.value !== contrasenaValidar.value) {
        errorContrasena.textContent = 'Las contraseñas no coinciden';
        errorContrasena.style.color = 'red';
        return false;
    }

    errorContrasena.textContent = 'Las contraseñas coinciden';
    errorContrasena.style.color = 'green';
    return true;
}

// Comprobar todos los requisitos
function contrasenaValida() {
    const valor = contrasenaNueva.value;

    return Object.values(requisitos).every(regex => regex.test(valor));
}

/*-----------------------------------------------------------------------------------------
                                FORMULARIO DE ACTUALIZACIÓN
-----------------------------------------------------------------------------------------*/


formularioActualizar.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (contrasenaNueva.value !== '') {

        if (!contrasenaValida()) {
            alert('La nueva contraseña no cumple los requisitos.');
            return;
        }

        if (contrasenaNueva.value !== contrasenaValidar.value) {
            alert('Las contraseñas no coinciden.');
            return;
        }

        if(document.getElementById('contrasenaActual').value === ''){
            alert('Ingrese su contraseña actual.');
            return;
        }
    }

    //Datos enviados del formulario
    const nombre = document.getElementById('nombreActualizar').value;
    const correo = document.getElementById('correoActualizar').value;
    const contrasenaAnterior = document.getElementById('contrasenaActual').value;
    const contrasena = document.getElementById('contrasenaNueva').value;
    const contrasenaValidacion = contrasenaValidar.value;
    const nivel = document.getElementById('nivelEstudio').value;

    //JSON
    const datos = {
        nombre: nombre || null,
        correo: correo || null,
        contrasenaAnterior: contrasenaAnterior || null,
        contrasenaNueva: contrasena || null,
        contrasenaNuevaValidar: contrasenaValidacion || null,
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