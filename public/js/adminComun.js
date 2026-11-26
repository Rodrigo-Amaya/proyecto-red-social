// Utilidades compartidas por las vistas de administrador.
async function cerrarSesionAdmin(evento) {
    evento.preventDefault();
    try {
        await fetch(window.APP_CONFIG.api + '/auth/logout', { method: 'POST' });
    } catch (e) { /* aunque falle la red, se vuelve al login */ }
    location.href = 'index.php?page=login';
    return false;
}