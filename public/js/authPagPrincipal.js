function validacionCorreo(correo){
    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regexCorreo.test(correo.trim());
}

function mostrarError(id, mensaje){
    const caja = document.getElementById(id);
    caja.textContent = mensaje;
    caja.style.display = "flex";
}

function ocultarError(id){
    const caja = document.getElementById(id);
    caja.textContent = '';
    caja.style.display = "none";
}

// Envía la petición y devuelve { ok, datos }. Lanza error si la respuesta no es JSON
async function enviar(url, opciones){
    const respuesta = await fetch(url, opciones);
    const datos = await respuesta.json();
    return { ok: respuesta.ok && datos.success === true, datos };
}

const libro = document.getElementById('libro');
const base = libro.dataset.base;
const api = base + '/api';
const volver = libro.dataset.volver || 'inicio';

/*-----------------------------------------------------------------------------------------
                                ALTERNAR LOGIN / REGISTRO
-----------------------------------------------------------------------------------------*/
function mostrarPanel() {
    const registro = window.location.hash === '#registro';
    libro.classList.toggle('modo-registro', registro);
    libro.classList.toggle('modo-login', !registro);
    document.title = registro ? "t'ASK | Registro" : "t'ASK | Iniciar sesión";
}

window.addEventListener('hashchange', mostrarPanel);
mostrarPanel();

/*-----------------------------------------------------------------------------------------
                                NIVELES DE ESTUDIO
-----------------------------------------------------------------------------------------*/
async function cargarNiveles(){
    const select = document.getElementById('nivelEstudio');
    try {
        const { ok, datos } = await enviar(api + '/niveles');
        if (!ok) throw new Error(datos.mensaje);

        datos.niveles.forEach(n => {
            const opcion = document.createElement('option');
            opcion.value = n.id_nivel;
            opcion.textContent = n.nombre;
            select.appendChild(opcion);
        });
    } catch (error) {
        console.error('No se pudieron cargar los niveles:', error.message);
        mostrarError('errorRegistro', 'No se pudieron cargar los niveles de estudio');
    }
}

/*-----------------------------------------------------------------------------------------
                                FORMULARIO DE LOGIN
-----------------------------------------------------------------------------------------*/
document.getElementById('formLogin').addEventListener('submit', async (e) => {
    e.preventDefault();
    ocultarError('errorLogin');

    const correo = document.getElementById('correoLogin').value.trim();
    const contrasena = document.getElementById('contrasenaLogin').value;

    if (!validacionCorreo(correo)) {
        mostrarError('errorLogin', 'Formato de correo inválido');
        return;
    }

    try {
        const { ok, datos } = await enviar(api + '/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ correo, contrasena })
        });

        if (!ok) {
            mostrarError('errorLogin', datos.mensaje || 'No se pudo iniciar sesión');
            return;
        }

        // Sesión iniciada: se vuelve a la página que se pedía
        window.location.href = base + '/index.php?page=' + encodeURIComponent(volver);
    } catch (error) {
        console.error('Error al conectar con la API:', error);
        mostrarError('errorLogin', 'No se pudo conectar con el servidor');
    }
});

/*-----------------------------------------------------------------------------------------
                                FORMULARIO DE REGISTRO
-----------------------------------------------------------------------------------------*/
const formularioRegistro = document.getElementById('formRegistro');

formularioRegistro.addEventListener('submit', async (e) => {
    e.preventDefault();
    ocultarError('errorRegistro');

    const nombre = formularioRegistro.elements['nombre'].value.trim();
    const correo = formularioRegistro.elements['correo'].value.trim();
    const contrasena = formularioRegistro.elements['contrasena'].value;
    const contrasenaValidar = formularioRegistro.elements['contrasenaValidar'].value;
    const nivelEstudio = formularioRegistro.elements['nivelEstudio'].value;

    if (nivelEstudio === '') {
        mostrarError('errorRegistro', 'Seleccione un nivel de estudio');
        return;
    }

    if (!validacionCorreo(correo)) {
        mostrarError('errorRegistro', 'Formato de correo inválido');
        return;
    }

    // El backend valida las reglas; esto solo evita una petición inútil
    if (contrasena !== contrasenaValidar) {
        mostrarError('errorRegistro', 'Las contraseñas no coinciden');
        return;
    }

    const datosForm = new FormData();
    datosForm.append('nombre', nombre);
    datosForm.append('correo', correo);
    datosForm.append('contrasena', contrasena);
    datosForm.append('contrasenaValidar', contrasenaValidar);
    datosForm.append('nivelEstudio', nivelEstudio);

    try {
        const { ok, datos } = await enviar(api + '/usuarios', {
            method: 'POST',
            body: datosForm
        });

        if (!ok) {
            mostrarError('errorRegistro', datos.mensaje || 'No se pudo completar el registro');
            return;
        }

        alert("Se ha registrado correctamente, ya puedes iniciar sesión");
        formularioRegistro.reset();
        window.location.hash = '#login'; // hashchange cambia el panel
    } catch (error) {
        console.error('Error al conectar con la API:', error);
        mostrarError('errorRegistro', 'No se pudo conectar con el servidor');
    }
});

cargarNiveles();