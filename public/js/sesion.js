// Cierre de sesión: se carga en todas las páginas con layout.
(function () {
    const botonSalir = document.getElementById('btn-cerrar-sesion');

    if (!botonSalir) return;

    botonSalir.addEventListener('click', async () => {
        try {
            await fetch(botonSalir.dataset.api + '/auth/logout', { method: 'POST' });
        } catch (e) {
            console.error('No se pudo cerrar la sesión:', e.message);
        }
        window.location.href = 'index.php?page=inicio';
    });
})();