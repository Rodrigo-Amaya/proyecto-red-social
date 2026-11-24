/*-----------------------------------------------------------------------------------------
                                FORMULARIO DE ENVIO CORREO
-----------------------------------------------------------------------------------------*/
const formularioCorreo = document.getElementById('formCorreoRecuperacion');

formularioCorreo.addEventListener('submit', async (e) => {
    e.preventDefault();

    //Datos enviados del formulario
    const correo = document.getElementById('correoRecuperacion').value;

    const datos = new FormData();

    if(!validacionCorreo(correo)){
        alert("Formato no valido para un correo.")
        return;
    }
    datos.append('correoRecuperacion', correo);

    try{

        //Envio de los datos a la API
        const response = await fetch(
            'http://localhost/proyecto-red-social/api/usuarios/reseteoContrasena',
            {
                method: 'POST',
                body: datos
            }
        );

        const texto = await response.text();
        const resultado = JSON.parse(texto);

        //Si hay algun error interno
        if (!response.ok) {
            alert("Correo inválido.")
            return;
        }

        alert("El correo ha sido enviado, tiene 30 minutos para su cambio de contraseña.");
    }
    catch(error){
        console.error('Error al conectar con la API:', error);

        alert("No se pudo conectar con el servidor.")
    }
});

function validacionCorreo(correo){
    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regexCorreo.test(correo.trim());
}