function mostrarContrasena(icono){
    const input = icono.previousElementSibling;
    if (icono.classList.contains('bx-show')) {
        icono.classList.replace('bx-show', 'bx-hide');
        input.type = "text";
    } else {
        icono.classList.replace('bx-hide', 'bx-show');
        input.type = "password";
    }
}

const contrasenas = document.querySelectorAll('.inputContrasena');
contrasenas.forEach((inputContrasena) => {
    inputContrasena.addEventListener("copy", (e) => e.preventDefault());
    inputContrasena.addEventListener("paste", (e) => e.preventDefault());
    inputContrasena.addEventListener("cut", (e) => e.preventDefault());
});
