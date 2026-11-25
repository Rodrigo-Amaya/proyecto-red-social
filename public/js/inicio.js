const feed = document.getElementById('feed');

if (feed) {
    iniciarFeed(feed);
}

async function iniciarFeed(feed) {
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
    const idUsuario = Number(feed.dataset.usuario);
    const publicacionesGuardadas = new Set();

    // Carrusel de materias
    const flechaAnterior = document.getElementById('filtro-anterior');
    const flechaSiguiente = document.getElementById('filtro-siguiente');

    const ICONO_GUARDAR = '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';

    function actualizarIconoGuardar(boton, guardada) {
        boton.classList.toggle('guardada', guardada);
        boton.setAttribute('aria-pressed', String(guardada));
        boton.title = guardada ? 'Quitar de guardados' : 'Guardar publicación';

        const svg = boton.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill', guardada ? 'currentColor' : 'none');
        }
    }

    // Consultar cuáles publicaciones ya están guardadas
    async function cargarPublicacionesGuardadas() {
        if (!haySesion) return;

        try {
            const respuesta = await fetch(
                `${api}/publicaciones/guardadas?id_usuario=${idUsuario}`
            );

            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.success) {
                throw new Error(datos.mensaje || 'No se pudieron consultar los guardados');
            }

            datos.publicaciones.forEach(p => {
                publicacionesGuardadas.add(Number(p.id_publicacion));
            });

            feed.querySelectorAll('.publicacion').forEach(tarjeta => {
                const boton = tarjeta.querySelector('.btn-guardar');
                if (boton) {
                    actualizarIconoGuardar(
                        boton,
                        publicacionesGuardadas.has(Number(tarjeta.dataset.id))
                    );
                }
            });
        } catch (error) {
            console.error(error.message);
        }
    }

    // Guardar o quitar una publicación
    async function cambiarGuardado(boton, idPublicacion) {
        const yaGuardada = publicacionesGuardadas.has(idPublicacion);

        boton.disabled = true;

        try {
            const respuesta = await fetch(
                `${api}/publicaciones/${idPublicacion}/guardar`,
                {
                    method: yaGuardada ? 'DELETE' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_usuario: idUsuario
                    })
                }
            );

            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.success) {
                throw new Error(datos.mensaje || 'No se pudo actualizar el guardado');
            }

            if (yaGuardada) {
                publicacionesGuardadas.delete(idPublicacion);
            } else {
                publicacionesGuardadas.add(idPublicacion);
            }

            actualizarIconoGuardar(boton, !yaGuardada);

        } catch (error) {
            alert(error.message);
        } finally {
            boton.disabled = false;
        }
    }

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

    // Contenedor de publicaciones
    function crearTarjeta(p) {
        const articulo = crear('article', 'publicacion');
        articulo.dataset.materia = p.id_materia;
        articulo.dataset.id = p.id_publicacion;

        const cabecera = crear('header', 'publicacion-autor');
        if (p.url_foto) {
            const foto = document.createElement('img');
            foto.src = p.url_foto;
            foto.alt = 'Foto de ' + (p.autor || 'usuario');
            cabecera.appendChild(foto);
        }

        const info = crear('div', 'publicacion-info');
        info.appendChild(crear('strong', '', p.autor || 'Usuario eliminado'));
        info.appendChild(crear('small', '', p.nivel || ''));
        cabecera.appendChild(info);

        if (p.materia) {
            cabecera.appendChild(crear('span', 'publicacion-materia', p.materia));
        }

        articulo.appendChild(cabecera);
        articulo.appendChild(crear('p', 'publicacion-titulo', p.titulo));
        articulo.appendChild(crear('p', '', p.mensaje));

        const acciones = crear('footer', 'publicacion-acciones');

        const responder = crear('button', 'btn-responder', 'Responder');
        responder.type = 'button';

        const guardar = crear('button', 'btn-guardar');
        guardar.type = 'button';
        guardar.title = 'Guardar publicación';
        guardar.setAttribute('aria-label', 'Guardar publicación');
        guardar.innerHTML = ICONO_GUARDAR; // constante propia, sin datos del servidor

        const estaGuardada = publicacionesGuardadas.has(Number(p.id_publicacion));
        actualizarIconoGuardar(guardar, estaGuardada);

        if (haySesion) {
            guardar.addEventListener('click', () => {
                cambiarGuardado(guardar, Number(p.id_publicacion));
            });
        }

        if (!haySesion) {
            responder.addEventListener('click', () => { window.location.href = urlLogin; });
            guardar.addEventListener('click', () => { window.location.href = urlLogin; });
        }

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

    if (haySesion) {
        await cargarPublicacionesGuardadas();
    }

    cargarMas();
    observador.observe(centinela);
}

// Cerrar sesión
const botonSalir = document.getElementById('btn-cerrar-sesion');

if (botonSalir) {
    botonSalir.addEventListener('click', async () => {
        try {
            await fetch(botonSalir.dataset.api + '/auth/logout', { method: 'POST' });
        } catch (e) {
            console.error('No se pudo cerrar la sesión:', e.message);
        }
        window.location.href = 'index.php?page=inicio';
    });
}