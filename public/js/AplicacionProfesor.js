//Ajusta esta ruta a donde tengas montada tu API
const URL_API_PROFESORES = '/api/profesores';
const TAMANO_MAXIMO_MB = 5;

const formulario = document.getElementById('formAplicacionProfesor');
const cajaError = document.getElementById('errorProfesor');
const cajaExito = document.getElementById('exitoProfesor');
const boton = formulario.querySelector('button[type="submit"]');

function mostrarMensaje(caja, texto) {
    cajaError.style.display = 'none';
    cajaExito.style.display = 'none';
    caja.textContent = texto;
    caja.style.display = 'flex';
    caja.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

formulario.addEventListener('submit', async (e) => {
    e.preventDefault();

    const archivo = document.getElementById('titulo').files[0];
    const anio = parseInt(document.getElementById('anio_graduacion').value, 10);

    //Validaciones en cliente (el servidor vuelve a validar todo)
    if (!archivo) {
        mostrarMensaje(cajaError, 'Debes adjuntar tu título');
        return;
    }
    if (archivo.size > TAMANO_MAXIMO_MB * 1024 * 1024) {
        mostrarMensaje(cajaError, `El título no puede pesar más de ${TAMANO_MAXIMO_MB} MB`);
        return;
    }
    if (anio < 1950 || anio > new Date().getFullYear()) {
        mostrarMensaje(cajaError, 'Año de graduación inválido');
        return;
    }

    boton.disabled = true;

    try {
        const respuesta = await fetch('http://localhost/proyecto-red-social/api/profesores/', {
            method: 'POST',
            body: new FormData(formulario),
            credentials: 'same-origin'
        });
        const datos = await respuesta.json();

        if (datos.success) {
            mostrarMensaje(cajaExito, datos.Mensaje);
            formulario.reset();
        } else {
            mostrarMensaje(cajaError, datos.Mensaje || 'No se pudo enviar la solicitud');
            boton.disabled = false;
        }
    } catch (error) {
        mostrarMensaje(cajaError, 'Error de conexión, inténtalo de nuevo');
        boton.disabled = false;
    }
});