(function () {
    const { api, baseUrl, idUsuario } = window.APP_CONFIG;
    const FOTO_POR_DEFECTO = 'documentos/usuarios/imagenes/blank-profile-picture.png';

    const $ = (id) => document.getElementById(id);
    let roles = [];
    let accionActual = null;   // { tipo: 'rol' | 'bloqueo', usuario }
    let temporizador = null;

    /* ---------- utilidades ---------- */

    // Crea elementos con textContent (los nombres/correos vienen de usuarios: nunca innerHTML)
    function el(etiqueta, atributos = {}, ...hijos) {
        const nodo = document.createElement(etiqueta);
        for (const [k, v] of Object.entries(atributos)) {
            if (k === 'class') nodo.className = v;
            else if (k.startsWith('on')) nodo.addEventListener(k.slice(2), v);
            else nodo.setAttribute(k, v);
        }
        for (const h of hijos) nodo.append(h);
        return nodo;
    }

    function mensajeDe(datos, porDefecto) {
        return datos?.Mensaje ?? datos?.mensaje ?? datos?.['Mensaje:'] ?? porDefecto;
    }

    function formatearFecha(texto) {
        const fecha = new Date(String(texto).replace(' ', 'T'));
        return isNaN(fecha) ? '' : fecha.toLocaleDateString('es-SV', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    async function pedir(url, opciones = {}) {
        const respuesta = await fetch(url, opciones);
        let datos = null;
        try { datos = await respuesta.json(); } catch (e) { /* respuesta sin JSON */ }
        if (respuesta.status === 401) { location.href = 'index.php?page=login&volver=gestion-usuarios'; }
        return { ok: respuesta.ok && datos?.success, datos };
    }

    /* ---------- listado ---------- */

    async function cargarUsuarios() {
        const parametros = new URLSearchParams();
        const filtros = {
            busqueda: $('filtroBusquedaUsuario').value.trim(),
            rol: $('filtroRolUsuario').value,
            bloqueado: $('filtroEstadoUsuario').value,
            orden: $('filtroOrdenUsuario').value
        };
        for (const [k, v] of Object.entries(filtros)) if (v !== '') parametros.set(k, v);

        $('resumenUsuarios').textContent = 'Cargando usuarios...';
        try {
            const { ok, datos } = await pedir(`${api}/usuarios/gestion?${parametros}`);
            if (!ok) {
                $('resumenUsuarios').textContent = mensajeDe(datos, 'No se pudieron cargar los usuarios');
                $('listaUsuarios').replaceChildren();
                return;
            }
            if (Array.isArray(datos.Roles)) {
                roles = datos.Roles;
                llenarSelectsDeRol();
            }
            pintarUsuarios(Array.isArray(datos.Usuarios) ? datos.Usuarios : []);
        } catch (e) {
            $('resumenUsuarios').textContent = 'Error de conexión con el servidor';
        }
    }

    function llenarSelectsDeRol() {
        const filtro = $('filtroRolUsuario');
        if (filtro.options.length === 1) {
            for (const r of roles) filtro.append(el('option', { value: r.id_rol }, r.nombre));
        }
    }

    function pintarUsuarios(usuarios) {
        const lista = $('listaUsuarios');
        lista.replaceChildren();
        $('resumenUsuarios').textContent = usuarios.length === 0
            ? 'No hay usuarios con esos filtros.'
            : `${usuarios.length} usuario${usuarios.length === 1 ? '' : 's'}`;

        for (const u of usuarios) lista.append(tarjetaUsuario(u));
    }

    // Inicial del nombre (soporta tildes, Ñ y emojis)
    function inicialDe(nombre) {
        const primera = Array.from(String(nombre ?? '').trim())[0];
        return primera ? primera.toLocaleUpperCase('es') : '?';
    }

    function crearInicial(nombre) {
        return el('div', { class: 'usuarioInicial', 'aria-hidden': 'true' }, inicialDe(nombre));
    }

    // Foto del usuario, o su inicial si no tiene foto o la imagen no carga
    function crearAvatar(u) {
        const url = u.url_foto || '';
        const tieneFoto = url !== '' && !url.includes('blank-profile-picture');

        if (!tieneFoto) return crearInicial(u.nombre);

        const img = el('img', {
            class: 'usuarioFoto',
            src: `${baseUrl}/${url}`,
            alt: '',
            width: '56', height: '56'
        });
        // Si la imagen falla (archivo borrado, ruta mala), se reemplaza por la inicial
        img.addEventListener('error', () => img.replaceWith(crearInicial(u.nombre)), { once: true });
        return img;
    }

    function tarjetaUsuario(u) {
        const bloqueado = Number(u.bloqueo_usuario) === 1;
        const esYo = Number(u.id_usuario) === idUsuario;

        const foto = crearAvatar(u);

        const acciones = el('div', { class: 'usuarioAcciones' });
        if (esYo) {
            acciones.append(el('span', { class: 'etiquetaYo' }, 'Tu cuenta'));
        } else {
            acciones.append(
                el('button', { type: 'button', onclick: () => abrirPanelAccion('rol', u) }, 'Cambiar rol'),
                el('button', {
                    type: 'button',
                    class: bloqueado ? 'botonDesbloquear' : 'botonBloquear',
                    onclick: () => abrirPanelAccion('bloqueo', u)
                }, bloqueado ? 'Desbloquear' : 'Bloquear')
            );
        }

        return el('article', { class: 'tarjetaUsuario' + (bloqueado ? ' usuarioBloqueado' : '') },
            foto,
            el('div', { class: 'usuarioDatos' },
                el('h3', {}, u.nombre),
                el('p', { class: 'usuarioCorreo' }, u.correo),
                el('p', { class: 'usuarioMeta' },
                    [u.nivel, u.fecha_registro ? 'Registro: ' + formatearFecha(u.fecha_registro) : '']
                        .filter(Boolean).join(' · '))
            ),
            el('div', { class: 'usuarioEtiquetas' },
                el('span', { class: 'etiquetaRol' }, u.rol),
                el('span', { class: bloqueado ? 'etiquetaEstado bloqueado' : 'etiquetaEstado activo' },
                    bloqueado ? 'Bloqueado' : 'Activo')
            ),
            acciones
        );
    }

    /* ---------- panel de acción ---------- */

    window.abrirPanelAccion = function (tipo, usuario) {
        accionActual = { tipo, usuario };
        const bloqueado = Number(usuario.bloqueo_usuario) === 1;
        $('errorAccion').hidden = true;
        $('campoRolAccion').hidden = tipo !== 'rol';

        if (tipo === 'rol') {
            $('tituloAccion').textContent = 'Cambiar rol';
            $('subtituloAccion').textContent = `${usuario.nombre} (${usuario.correo}) tiene el rol ${usuario.rol}.`;
            const select = $('rolAccion');
            select.replaceChildren(...roles.map(r => el('option', { value: r.id_rol }, r.nombre)));
            select.value = String(usuario.id_rol);
            $('botonConfirmarAccion').textContent = 'Guardar rol';
        } else {
            $('tituloAccion').textContent = bloqueado ? 'Desbloquear usuario' : 'Bloquear usuario';
            $('subtituloAccion').textContent = bloqueado
                ? `${usuario.nombre} podrá volver a iniciar sesión.`
                : `${usuario.nombre} no podrá iniciar sesión y se cerrará su acceso.`;
            $('botonConfirmarAccion').textContent = bloqueado ? 'Desbloquear' : 'Bloquear';
        }
        $('panelAccionUsuario').classList.add('visible');
        $('opacidadUsuarios').classList.add('visible');
    };

    window.cerrarPanelAccion = function () {
        accionActual = null;
        $('panelAccionUsuario').classList.remove('visible');
        $('opacidadUsuarios').classList.remove('visible');
    };

    async function confirmarAccion(evento) {
        evento.preventDefault();
        if (!accionActual) return;

        const { tipo, usuario } = accionActual;
        const cuerpo = tipo === 'rol'
            ? { rol: Number($('rolAccion').value) }
            : { bloqueo: Number(usuario.bloqueo_usuario) === 1 ? 0 : 1 };

        const boton = $('botonConfirmarAccion');
        boton.disabled = true;
        try {
            const { ok, datos } = await pedir(`${api}/usuarios/${usuario.id_usuario}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cuerpo)
            });
            if (!ok) {
                $('errorAccion').textContent = mensajeDe(datos, 'No se pudo completar la acción');
                $('errorAccion').hidden = false;
                return;
            }
            cerrarPanelAccion();
            cargarUsuarios();
        } catch (e) {
            $('errorAccion').textContent = 'Error de conexión con el servidor';
            $('errorAccion').hidden = false;
        } finally {
            boton.disabled = false;
        }
    }

    /* ---------- eventos ---------- */

    $('formAccionUsuario').addEventListener('submit', confirmarAccion);
    for (const id of ['filtroEstadoUsuario', 'filtroRolUsuario', 'filtroOrdenUsuario']) {
        $(id).addEventListener('change', cargarUsuarios);
    }
    $('filtroBusquedaUsuario').addEventListener('input', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(cargarUsuarios, 300);
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarPanelAccion(); });

    cargarUsuarios();
})();