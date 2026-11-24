const botones = document.querySelectorAll('.filtro-materias button');
const publicaciones = document.querySelectorAll('.publicacion');

botones.forEach(boton => {
    boton.addEventListener('click', () => {
        // Marcar el botón activo
        botones.forEach(b => b.classList.remove('active'));
        boton.classList.add('active');

        // Mostrar u ocultar publicaciones
        const materia = boton.dataset.materia;

        publicaciones.forEach(p => {
            const coincide = materia === 'todas' || p.dataset.materia === materia;
            p.hidden = !coincide;
        });
    });
});