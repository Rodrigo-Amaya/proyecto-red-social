/*-----------------------------------------------------------------------------------------
                                FORMULARIO DE ENVIO LOGIN
-----------------------------------------------------------------------------------------*/
const formularioLogin = document.getElementById('formLogin');

formularioLogin.addEventListener('submit', async (e) => {
    e.preventDefault();

    //Datos enviados del formulario
    const correo = document.getElementById('correoLogin').value;
    const contrasena = document.getElementById('contrasenaLogin').value;

    const datos = new FormData();

    if(!validacionCorreo(correo)){
        document.getElementById('errorLogin').style.display = "flex";
        document.getElementById('errorLogin').textContent = 'Formato de correo inválido';
        return;
    }
    datos.append('correo', correo);
    datos.append('contrasena', contrasena);

    try{

        //Envio de los datos a la API
        const response = await fetch(
            'http://localhost/proyecto-red-social/api/usuarios/iniciarSesion',
            {
                method: 'POST',
                body: datos
            }
        );

        const texto = await response.text();
        const resultado = JSON.parse(texto);

        //Si hay algun error interno
        if (!response.ok) {
            document.getElementById('errorLogin').style.display = "flex";
            document.getElementById('errorLogin').textContent = resultado.Mensaje;
            return;
        }

        //Pasamos a la pagina de inicio
        window.location.href = '../publicaciones/inicio.php';
    }
    catch(error){
        console.error('Error al conectar con la API:', error);

        document.getElementById('errorLogin').style.display = "flex";
        document.getElementById('errorLogin').textContent =
            'No se pudo conectar con el servidor';
    }
});

function validacionCorreo(correo){
    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regexCorreo.test(correo.trim());
}
/*-----------------------------------------------------------------------------------------
                               FORMULARIO DE ENVIO REGISTRO
-----------------------------------------------------------------------------------------*/

const formularioRegistro = document.getElementById('formRegistro');

formularioRegistro.addEventListener('submit', async (e) => {
    e.preventDefault();

    //Datos enviados del formulario
    const nombre = formularioRegistro.elements['nombre'].value;
    const correo = formularioRegistro.elements['correo'].value;
    const contrasena = formularioRegistro.elements['contrasena'].value;
    const contrasenaValidar = formularioRegistro.elements['contrasenaValidar'].value;
    const nivelEstudio = formularioRegistro.elements['nivelEstudio'].value;

    const datos = new FormData();

    datos.append('nombre', nombre);
    datos.append('correo', correo);
    datos.append('contrasena', contrasena);
    datos.append('contrasenaValidar', contrasenaValidar);
    datos.append('nivelEstudio', nivelEstudio);

    if(nivelEstudio === ''){
        document.getElementById('errorRegistro').style.display = "flex";
        document.getElementById('errorRegistro').textContent = 'Seleccione un nivel de estudio';
        return;
    }

    if(!validacionCorreo(correo)){
        document.getElementById('errorRegistro').style.display = "flex";
        document.getElementById('errorRegistro').textContent = 'Formato de correo inválido';
        return;
    }

    try{
        //Envio de los datos a la API
        const response = await fetch(
            'http://localhost/proyecto-red-social/api/usuarios/',
            {
                method: 'POST',
                body: datos
            }
        );

        const texto = await response.text();
        const resultado = JSON.parse(texto);

        //Si hay algun error interno
        if (!response.ok) {
            document.getElementById('errorRegistro').style.display = "flex";
            document.getElementById('errorRegistro').textContent = resultado.Mensaje;
            return;
        }

        alert("Se ha registrado correctamente, puedes Inicar Sesion");
        location.reload();
    }
    catch(error){
        console.error('Error al conectar con la API:', error);

        document.getElementById('errorRegistro').style.display = "flex";
        document.getElementById('errorRegistro').textContent =
            'No se pudo conectar con el servidor';
    }
});
