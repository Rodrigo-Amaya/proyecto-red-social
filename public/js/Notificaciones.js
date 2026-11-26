(function () {
    const raiz = document.getElementById('notificaciones');
    if (!raiz) return;

    const api = raiz.dataset.api;
    const base = raiz.dataset.base;

    const boton = document.getElementById('notif-boton');
    const panel = document.getElementById('notif-panel');
    const contador = document.getElementById('notif-contador');
    const lista = document.getElementById('notif-lista');
    const vacio = document.getElementById('notif-vacio');
    const error = document.getElementById('notif-error');
    const botonMas = document.getElementById('notif-mas');
    const botonTodas = document.getElementById('notif-leer-todas');

    const LIMITE = 15;
    const INTERVALO_CONTADOR = 30000; // 30 s

    let cursor = null;
    let cargando = false;

    /* ---------- utilidades ---------- */

    // Crea elementos con texto seguro (los nombres y títulos los escriben los usuarios)
    function crear(etiqueta, clase, texto) {
        const nodo = document.createElement(etiqueta);
        if (clase) nodo.className = clase;
        if (texto !== undefined && texto !== null) nodo.textContent = texto;
        return nodo;
    }

    async function pedir(ruta, opciones = {}) {
        const respuesta = await fetch(api + ruta, opciones);
        const datos = await respuesta.json();
        if (!respuesta.ok || !datos.success) {
            throw new Error(datos.mensaje || datos.Mensaje || 'Error en la solicitud');
        }
        return datos;
    }

    function tiempoRelativo(texto) {
        const fecha = new Date(String(texto).replace(' ', 'T'));
        if (isNaN(fecha)) return '';
        const segundos = Math.max(0, Math.floor((Date.now() - fecha.getTime()) / 1000));
        if (segundos < 60) return 'hace un momento';
        const minutos = Math.floor(segundos / 60);
        if (minutos < 60) return `hace ${minutos} min`;
        const horas = Math.floor(minutos / 60);
        if (horas < 24) return `hace ${horas} h`;
        const dias = Math.floor(horas / 24);
        if (dias < 7) return `hace ${dias} d`;
        return fecha.toLocaleDateString('es-SV', { day: 'numeric', month: 'short' });
    }

    function urlFoto(ruta) {
        if (!ruta) return null;
        return /^(https?:)?\/\//.test(ruta) || ruta.startsWith('/') ? ruta : `${base}/${ruta}`;
    }

    /* ---------- contenido de cada tipo ---------- */

    function mensajeDe(n) {
        const titulo = n.publicacion_titulo ? `«${n.publicacion_titulo}»` : 'tu publicación';
        switch (n.tipo) {
            case 'COMENTARIO':
                return [n.actor, ` comentó en tu publicación ${titulo}`];
            case 'LIKE_COMENTARIO':
                return [n.actor, ` le dio like a tu comentario en ${titulo}`];
            case 'GUARDADO':
                return [n.actor, ` guardó tu publicación ${titulo}`];
            case 'RESPUESTA':
                return [n.actor, ` respondió a tu comentario en ${titulo}`];
            case 'NUEVA_SOLICITUD':
                return [n.actor, ' envió una solicitud para ser profesor'];
            default:
                return [n.actor, ' tiene una novedad para ti'];
        }
    }

    // Único sitio que decide a dónde lleva cada notificación.
    function urlDestino(n) {
        if (n.tipo === 'NUEVA_SOLICITUD') return 'index.php?page=solicitudes';
        if (n.id_publicacion) {
            return 'index.php?page=publicacion&id=' + encodeURIComponent(n.id_publicacion);
        }
        return 'index.php?page=inicio';
    }

    function crearItem(n) {
        const li = crear('li', 'notif-item' + (n.leida ? '' : ' no-leida'));
        li.dataset.id = n.id_notificacion;

        const enlace = crear('a', 'notif-enlace-item');
        enlace.href = urlDestino(n);

        const foto = urlFoto(n.actor_foto);
        if (foto) {
            const img = document.createElement('img');
            img.src = foto;
            img.alt = '';
            enlace.appendChild(img);
        } else {
            enlace.appendChild(crear('span', 'notif-inicial', (n.actor || 'U').charAt(0).toUpperCase()));
        }

        const cuerpo = crear('div', 'notif-cuerpo');
        const [quien, que] = mensajeDe(n);
        const texto = crear('p', 'notif-texto');
        texto.appendChild(crear('strong', '', quien));
        texto.appendChild(document.createTextNode(que));
        cuerpo.appendChild(texto);
        cuerpo.appendChild(crear('small', '', tiempoRelativo(n.fecha)));
        enlace.appendChild(cuerpo);

        enlace.addEventListener('click', () => {
            if (li.classList.contains('no-leida')) {
                li.classList.remove('no-leida');
                // keepalive: la petición termina aunque la página cambie
                fetch(`${api}/notificaciones/${n.id_notificacion}`, { method: 'PATCH', keepalive: true })
                    .then(() => actualizarContador())
                    .catch(() => {});
            }
        });

        li.appendChild(enlace);
        return li;
    }

    /* ---------- carga ---------- */

    function pintarContador(total) {
        contador.hidden = total <= 0;
        contador.textContent = total > 99 ? '99+' : String(total);
        boton.setAttribute('aria-label', total > 0 ? `Notificaciones (${total} sin leer)` : 'Notificaciones');
    }

    async function actualizarContador() {
        try {
            const datos = await pedir('/notificaciones/contador');
            pintarContador(datos.no_leidas);
        } catch (e) { /* silencioso: es solo el indicador */ }
    }

    async function cargar(reiniciar) {
        if (cargando) return;
        cargando = true;
        error.hidden = true;

        if (reiniciar) {
            cursor = null;
            lista.replaceChildren();
        }

        try {
            const params = new URLSearchParams({ limite: LIMITE });
            if (cursor !== null) params.set('antes_de', cursor);

            const datos = await pedir(`/notificaciones?${params}`);
            datos.notificaciones.forEach(n => lista.appendChild(crearItem(n)));

            cursor = datos.siguiente_cursor;
            botonMas.hidden = !datos.hay_mas;
            vacio.hidden = lista.children.length > 0;
            pintarContador(datos.no_leidas);
        } catch (e) {
            error.hidden = false;
        } finally {
            cargando = false;
        }
    }

    async function marcarTodas() {
        try {
            await pedir('/notificaciones/leer-todas', { method: 'POST' });
            lista.querySelectorAll('.no-leida').forEach(li => li.classList.remove('no-leida'));
            pintarContador(0);
        } catch (e) {
            error.hidden = false;
        }
    }

    /* ---------- abrir / cerrar ---------- */

    function abrir() {
        panel.hidden = false;
        boton.setAttribute('aria-expanded', 'true');
        cargar(true);
    }

    function cerrar() {
        panel.hidden = true;
        boton.setAttribute('aria-expanded', 'false');
    }

    boton.addEventListener('click', () => (panel.hidden ? abrir() : cerrar()));
    botonMas.addEventListener('click', () => cargar(false));
    botonTodas.addEventListener('click', marcarTodas);

    document.addEventListener('click', (e) => {
        if (!panel.hidden && !raiz.contains(e.target)) cerrar();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) {
            cerrar();
            boton.focus();
        }
    });

    /* ---------- indicador de no leídas ---------- */

    actualizarContador();
    setInterval(() => {
        if (document.visibilityState === 'visible') actualizarContador();
    }, INTERVALO_CONTADOR);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') actualizarContador();
    });
})();