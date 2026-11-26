const feed = document.getElementById('feed');

if (feed) {
    iniciarFeed(feed);
}

function iniciarFeed(feed) {
    const api = feed.dataset.api;
    const nav = document.querySelector('.filtro-materias');
    const vacio = document.getElementById('feed-vacio');
    const vacioTitulo = document.getElementById('feed-vacio-titulo');
    const vacioTexto = document.getElementById('feed-vacio-texto');
    const error = document.getElementById('feed-error');
    const centinela = document.getElementById('feed-centinela');

    const LIMITE = 15;

    const haySesion = feed.dataset.sesion === '1';
    const urlLogin = feed.dataset.login;

    // Carrusel de materias
    const flechaAnterior = document.getElementById('filtro-anterior');
    const flechaSiguiente = document.getElementById('filtro-siguiente');

    const ICONO_GUARDAR = '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';

    function actualizarFlechas() {
        const maximo = nav.scrollWidth - nav.clientWidth;
        flechaAnterior.disabled = nav.scrollLeft <= 1;
        flechaSiguiente.disabled = nav.scrollLeft >= maximo - 1;
    }

    function desplazar(direccion) {
        nav.scrollBy({ left: direccion * nav.clientWidth * 0.8, behavior: 'smooth' });
    }

    flechaAnterior.addEventListener('click', () => desplazar(-1));
    flechaSiguiente.addEventListener('click', () => desplazar(1));
    nav.addEventListener('scroll', actualizarFlechas, { passive: true });
    window.addEventListener('resize', actualizarFlechas);

    let materiaActual = null;
    let cursor = null;
    let hayMas = true;
    let cargando = false;
    let version = 0;

    // Crea un elemento con texto seguro
    function crear(etiqueta, clase, texto) {
        const nodo = document.createElement(etiqueta);
        if (clase) nodo.className = clase;
        if (texto !== undefined && texto !== null) nodo.textContent = texto;
        return nodo;
    }

    // Foto del autor, o su inicial si no tiene foto
    function crearAvatar(url, nombre) {
        if (url) {
            const img = crear('img', 'avatar');
            img.src = url;
            img.alt = 'Foto de ' + (nombre || 'usuario');
            return img;
        }
        return crear('span', 'avatar avatar-inicial', (nombre || 'U').charAt(0).toUpperCase());
    }

    // Contenedor de publicaciones
    function crearTarjeta(p) {
        const articulo = crear('article', 'publicacion');
        articulo.dataset.materia = p.id_materia;
        articulo.dataset.id = p.id_publicacion;
        const urlDetalle = 'index.php?page=publicacion&id=' + encodeURIComponent(p.id_publicacion);

        const cabecera = crear('header', 'publicacion-autor');
        cabecera.appendChild(crearAvatar(p.url_foto, p.autor));

        const info = crear('div', 'publicacion-info');
        info.appendChild(crear('strong', '', p.autor || 'Usuario eliminado'));
        info.appendChild(crear('small', '', p.nivel || ''));
        cabecera.appendChild(info);

        if (p.materia) {
            cabecera.appendChild(crear('span', 'publicacion-materia', p.materia));
        }

        articulo.appendChild(cabecera);
        const titulo = crear('p', 'publicacion-titulo');
        const enlaceTitulo = crear('a', 'publicacion-enlace', p.titulo);
        enlaceTitulo.href = urlDetalle;
        titulo.appendChild(enlaceTitulo);
        articulo.appendChild(titulo);
        articulo.appendChild(crear('p', '', p.mensaje));

        const acciones = crear('footer', 'publicacion-acciones');

        const responder = crear('button', 'btn-responder', 'Responder');
        responder.type = 'button';

        const guardar = crear('button', 'btn-guardar');
        guardar.type = 'button';
        guardar.title = 'Guardar publicación';
        guardar.setAttribute('aria-label', 'Guardar publicación');
        guardar.innerHTML = ICONO_GUARDAR; // constante propia, sin datos del servidor

        responder.addEventListener('click', () => {
            window.location.href = haySesion ? urlDetalle : urlLogin;
        });

        if (!haySesion) {
            guardar.addEventListener('click', () => { window.location.href = urlLogin; });
        }

        const total = Number(p.total_comentarios) || 0;
        const contador = crear('a', 'publicacion-comentarios', total === 1 ? '1 comentario' : total + ' comentarios');
        contador.href = urlDetalle;

        acciones.appendChild(contador);
        acciones.appendChild(responder);
        acciones.appendChild(guardar);
        articulo.appendChild(acciones);

        return articulo;
    }

    function actualizarVacio() {
        const hayTarjetas = feed.querySelector('.publicacion') !== null;
        if (hayTarjetas) {
            vacio.hidden = true;
            return;
        }
        if (materiaActual === null) {
            vacioTitulo.textContent = '¡Tu comunidad comienza aquí!';
            vacioTexto.textContent = 'Aún no hay publicaciones. Sé la primera persona en compartir una pregunta o ayudar a alguien.';
        } else {
            vacioTitulo.textContent = 'Sin publicaciones';
            vacioTexto.textContent = 'Aún no hay publicaciones en esta materia.';
        }
        vacio.hidden = false;
    }

    function mostrarError(mensaje) {
        error.querySelector('p').textContent = mensaje;
        error.hidden = false;
    }

    // Funcion para paginacion del feed
    async function cargarMas() {
        if (cargando || !hayMas) return;
        cargando = true;
        error.hidden = true;
        const miVersion = version;

        try {
            const params = new URLSearchParams({ limite: LIMITE });
            if (cursor !== null) params.set('antes_de', cursor);
            if (materiaActual !== null) params.set('materia', materiaActual);

            const respuesta = await fetch(`${api}/publicaciones/feed?${params}`);
            const datos = await respuesta.json();

            if (miVersion !== version) return; // el usuario cambió de filtro mientras tanto

            if (!respuesta.ok || !datos.success) {
                throw new Error(datos.mensaje || 'No se pudo cargar el feed');
            }

            datos.publicaciones.forEach(p => feed.insertBefore(crearTarjeta(p), centinela));
            hayMas = datos.hay_mas;
            cursor = datos.siguiente_cursor;
            actualizarVacio();
        } catch (e) {
            if (miVersion !== version) return;
            mostrarError(e instanceof SyntaxError ? 'Respuesta inválida del servidor' : e.message);
            return;
        } finally {
            if (miVersion === version) cargando = false;
        }

        if (hayMas) {
            observador.unobserve(centinela);
            observador.observe(centinela);
        }
    }

    function reiniciar() {
        version++;
        cursor = null;
        hayMas = true;
        cargando = false;
        feed.querySelectorAll('.publicacion').forEach(p => p.remove());
        vacio.hidden = true;
        error.hidden = true;
        cargarMas();
    }

    // Scroll infinito por paginacion
    const observador = new IntersectionObserver(entradas => {
        if (entradas[0].isIntersecting) cargarMas();
    }, { rootMargin: '200px' });

    // Filtro por materia
    nav.addEventListener('click', evento => {
        const boton = evento.target.closest('button');
        if (!boton) return;

        nav.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        boton.classList.add('active');

        materiaActual = boton.dataset.materia === 'todas' ? null : Number(boton.dataset.materia);
        reiniciar();
    });

    error.querySelector('button').addEventListener('click', cargarMas);

    async function cargarMaterias() {
        try {
            const respuesta = await fetch(`${api}/materias`);
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.success) throw new Error(datos.mensaje);

            datos.materias.forEach(m => {
                const boton = crear('button', '', m.nombre);
                boton.type = 'button';
                boton.dataset.materia = m.id_materia;
                nav.appendChild(boton);
            });
            actualizarFlechas();
        } catch (e) {
            console.error('No se pudieron cargar las materias:', e.message);
        }
    }

    cargarMaterias();
    cargarMas();
    observador.observe(centinela);
}