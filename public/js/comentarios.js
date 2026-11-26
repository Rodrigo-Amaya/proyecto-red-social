// Detalle de publicación: carga la publicación y su hilo de comentarios.
(function () {
    const detalle = document.getElementById('detalle');
    if (!detalle) return;

    const api = detalle.dataset.api;
    const idPublicacion = detalle.dataset.id;
    const haySesion = detalle.dataset.sesion === '1';
    const idUsuario = Number(detalle.dataset.usuario);
    const urlLogin = detalle.dataset.login;

    const cargando = document.getElementById('detalle-cargando');
    const error = document.getElementById('detalle-error');
    const articulo = document.getElementById('detalle-publicacion');
    const seccion = document.getElementById('comentarios');
    const titulo = document.getElementById('comentarios-titulo');
    const formPrincipal = document.getElementById('comentario-form');
    const errorForm = document.getElementById('comentario-form-error');
    const vacio = document.getElementById('comentarios-vacio');
    const lista = document.getElementById('comentarios-lista');

    const urlComentarios = `${api}/publicaciones/${idPublicacion}/comentario`;

    // Formulario inline abierto (responder o editar): solo uno a la vez
    let cierreActual = null;

    // Hilo de respuestas que se muestran
    const LOTE = 3;
    const visiblesPorHilo = new Map();

    // Crea un elemento con texto seguro
    function crear(etiqueta, clase, texto) {
        const nodo = document.createElement(etiqueta);
        if (clase) nodo.className = clase;
        if (texto !== undefined && texto !== null) nodo.textContent = texto;
        return nodo;
    }

    // Petición a la API: siempre devuelve { ok, status, datos }
    async function pedir(url, opciones) {
        let respuesta;
        try {
            respuesta = await fetch(url, opciones);
        } catch (e) {
            return { ok: false, status: 0, datos: { mensaje: 'No se pudo conectar con el servidor' } };
        }
        try {
            const datos = await respuesta.json();
            return { ok: respuesta.ok && datos.success === true, status: respuesta.status, datos };
        } catch (e) {
            return { ok: false, status: respuesta.status, datos: { mensaje: 'Respuesta inválida del servidor' } };
        }
    }

    function mensajeDe(resultado) {
        if (resultado.status === 401) return 'Tu sesión expiró. Inicia sesión de nuevo.';
        return resultado.datos.mensaje || 'Ocurrió un error inesperado';
    }

    function formatearFecha(texto) {
        if (!texto) return '';
        const fecha = new Date(texto.replace(' ', 'T'));
        if (isNaN(fecha.getTime())) return texto;
        return fecha.toLocaleString('es', { dateStyle: 'medium', timeStyle: 'short' });
    }

    function crearAvatar(url, nombre) {
        if (url) {
            const img = crear('img', 'avatar');
            img.src = url;
            img.alt = 'Foto de ' + (nombre || 'usuario');
            return img;
        }
        return crear('span', 'avatar avatar-inicial', (nombre || 'U').charAt(0).toUpperCase());
    }

    // ---------- Estados de la página ----------

    function mostrarError(mensaje) {
        cargando.hidden = true;
        articulo.hidden = true;
        seccion.hidden = true;
        error.querySelector('p').textContent = mensaje;
        error.hidden = false;
    }

    // ---------- Publicación ----------

    function mostrarPublicacion(p) {
        articulo.replaceChildren();

        const cabecera = crear('header', 'publicacion-autor');
        cabecera.appendChild(crearAvatar(p.url_foto, p.autor));

        const info = crear('div', 'publicacion-info');
        info.appendChild(crear('strong', '', p.autor || 'Usuario'));
        info.appendChild(crear('small', '', p.nivel || ''));
        cabecera.appendChild(info);

        if (p.materia) {
            cabecera.appendChild(crear('span', 'publicacion-materia', p.materia));
        }

        articulo.appendChild(cabecera);
        articulo.appendChild(crear('h1', 'publicacion-titulo', p.titulo));
        articulo.appendChild(crear('p', 'publicacion-texto', p.mensaje));

        // Adjuntos como enlaces simples 
        const archivos = [p.archivo_1, p.archivo_2, p.archivo_3].filter(Boolean);
        if (archivos.length > 0) {
            const caja = crear('div', 'publicacion-adjuntos');
            archivos.forEach((url, i) => {
                const enlace = crear('a', '', 'Archivo ' + (i + 1));
                enlace.href = url;
                enlace.target = '_blank';
                enlace.rel = 'noopener';
                caja.appendChild(enlace);
            });
            articulo.appendChild(caja);
        }

        articulo.appendChild(crear('small', 'publicacion-fecha', formatearFecha(p.fecha)));
        document.title = p.titulo;
    }

    // ---------- Formularios (principal, responder y editar) ----------

    function crearFormulario(opc) {
        const form = crear('form', 'comentario-form comentario-form-inline');
        form.noValidate = true;

        let inputTitulo = null;
        if (opc.conTitulo) {
            inputTitulo = crear('input');
            inputTitulo.type = 'text';
            inputTitulo.maxLength = 150;
            inputTitulo.placeholder = 'Título (opcional)';
            inputTitulo.value = opc.titulo || '';
            form.appendChild(inputTitulo);
        }

        const area = crear('textarea');
        area.maxLength = 5000;
        area.required = true;
        area.placeholder = opc.placeholder;
        area.value = opc.mensaje || '';
        form.appendChild(area);

        const mensajeError = crear('p', 'comentario-form-error');
        mensajeError.hidden = true;
        form.appendChild(mensajeError);

        const botones = crear('div', 'comentario-form-botones');
        const enviar = crear('button', '', opc.textoBoton);
        enviar.type = 'submit';
        const cancelar = crear('button', 'btn-secundario', 'Cancelar');
        cancelar.type = 'button';
        cancelar.addEventListener('click', opc.alCancelar);
        botones.appendChild(enviar);
        botones.appendChild(cancelar);
        form.appendChild(botones);

        form.addEventListener('submit', async evento => {
            evento.preventDefault();
            const mensaje = area.value.trim();

            if (mensaje === '') {
                mensajeError.textContent = 'El mensaje es obligatorio';
                mensajeError.hidden = false;
                return;
            }

            enviar.disabled = true;
            mensajeError.hidden = true;

            const resultado = await opc.alEnviar({
                titulo: inputTitulo ? inputTitulo.value.trim() : '',
                mensaje
            });

            // Si salió bien, el hilo se recarga y este formulario desaparece solo
            if (!resultado.ok) {
                mensajeError.textContent = resultado.mensaje;
                mensajeError.hidden = false;
                enviar.disabled = false;
            }
        });

        return form;
    }

    // ---------- Acciones contra la API ----------

    async function enviarComentario(datos, idPadre, idRaiz = null) {
        const cuerpo = new FormData();
        cuerpo.append('titulo', datos.titulo);
        cuerpo.append('mensaje', datos.mensaje);
        if (idPadre !== null) cuerpo.append('id_comentario_padre', idPadre);

        const resultado = await pedir(urlComentarios, { method: 'POST', body: cuerpo });
        if (!resultado.ok) return { ok: false, mensaje: mensajeDe(resultado) };

        // Nueva respuesta visible
        if (idRaiz !== null) visiblesPorHilo.set(idRaiz, Number.MAX_SAFE_INTEGER);

        await recargarComentarios();
        return { ok: true };
    }

    async function guardarEdicion(comentario, datos) {
        const resultado = await pedir(`${urlComentarios}/${comentario.id_comentario}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo: datos.titulo, mensaje: datos.mensaje })
        });
        if (!resultado.ok) return { ok: false, mensaje: mensajeDe(resultado) };

        await recargarComentarios();
        return { ok: true };
    }

    async function eliminarComentario(comentario, boton) {
        if (!window.confirm('¿Eliminar este comentario?')) return;

        boton.disabled = true;
        const resultado = await pedir(`${urlComentarios}/${comentario.id_comentario}`, { method: 'DELETE' });

        if (!resultado.ok) {
            window.alert(mensajeDe(resultado));
            boton.disabled = false;
            return;
        }
        await recargarComentarios();
    }

    // ---------- Hilo de comentarios ----------

    function contar(nodos) {
        return nodos.reduce((total, n) => total + (n.eliminado ? 0 : 1) + contar(n.respuestas || []), 0);
    }

    function crearComentario(c, etiquetaPadre, idRaiz) {
        const nodo = crear('article', 'comentario');
        nodo.dataset.id = c.id_comentario;

        const caja = crear('div', 'comentario-caja');

        if (c.eliminado) {
            nodo.classList.add('comentario-eliminado');
            caja.appendChild(crear('p', 'comentario-texto', c.mensaje));
            caja.appendChild(crear('small', 'comentario-fecha', formatearFecha(c.fecha)));
        } else {
            const cabecera = crear('header', 'comentario-autor');
            cabecera.appendChild(crearAvatar(c.url_foto, c.autor));

            const info = crear('div', 'comentario-info');
            info.appendChild(crear('strong', '', c.autor || 'Usuario'));
            info.appendChild(crear('small', '', [c.nivel, formatearFecha(c.fecha)].filter(Boolean).join(' · ')));
            cabecera.appendChild(info);
            caja.appendChild(cabecera);

            if (etiquetaPadre) {
                caja.appendChild(crear('p', 'comentario-en-respuesta', '↪ ' + etiquetaPadre));
            }

            const contenido = crear('div', 'comentario-contenido');
            if (c.titulo) contenido.appendChild(crear('p', 'comentario-titulo', c.titulo));
            contenido.appendChild(crear('p', 'comentario-texto', c.mensaje));
            caja.appendChild(contenido);

            const acciones = crear('footer', 'comentario-acciones');

            const responder = crear('button', 'btn-responder', 'Responder');
            responder.type = 'button';
            responder.addEventListener('click', () => {
                if (!haySesion) {
                    window.location.href = urlLogin;
                    return;
                }
                if (cierreActual) cierreActual();

                const form = crearFormulario({
                    conTitulo: false,
                    placeholder: 'Escribe tu respuesta...',
                    textoBoton: 'Responder',
                    alCancelar: cerrar,
                    alEnviar: datos => enviarComentario(datos, c.id_comentario, idRaiz)
                });

                function cerrar() {
                    form.remove();
                    if (cierreActual === cerrar) cierreActual = null;
                }

                caja.appendChild(form);
                cierreActual = cerrar;
                form.querySelector('textarea').focus();
            });
            acciones.appendChild(responder);

            // Editar y eliminar solo para el dueño (la regla real está en la API)
            if (haySesion && c.id_usuario === idUsuario) {
                const editar = crear('button', 'btn-secundario', 'Editar');
                editar.type = 'button';
                editar.addEventListener('click', () => {
                    if (cierreActual) cierreActual();

                    const form = crearFormulario({
                        conTitulo: true,
                        titulo: c.titulo || '',
                        mensaje: c.mensaje,
                        placeholder: 'Edita tu comentario...',
                        textoBoton: 'Guardar',
                        alCancelar: cerrar,
                        alEnviar: datos => guardarEdicion(c, datos)
                    });

                    function cerrar() {
                        form.remove();
                        contenido.hidden = false;
                        if (cierreActual === cerrar) cierreActual = null;
                    }

                    contenido.hidden = true;
                    contenido.after(form);
                    cierreActual = cerrar;
                    form.querySelector('textarea').focus();
                });

                const eliminar = crear('button', 'btn-secundario', 'Eliminar');
                eliminar.type = 'button';
                eliminar.addEventListener('click', () => eliminarComentario(c, eliminar));

                acciones.appendChild(editar);
                acciones.appendChild(eliminar);
            }

            caja.appendChild(acciones);
        }

         nodo.appendChild(caja);

        return nodo;
    }

    // Lista plana de respuestas
    function aplanar(respuestas, padre, esRaiz, salida, idRaiz) {
        respuestas.forEach(r => {
            let etiqueta = null;
            if (!esRaiz) {
                etiqueta = 'en respuesta a ' + (padre.eliminado ? 'un comentario eliminado' : (padre.autor || 'Usuario'));
            }
            salida.push(crearComentario(r, etiqueta, idRaiz));
            aplanar(r.respuestas || [], r, false, salida, idRaiz);
        });
    }

    // Comentario raíz con las respuestas de su hilo mostradas por lote
    function crearRaiz(c) {
        const idRaiz = c.id_comentario;
        const nodo = crearComentario(c, null, idRaiz);

        const descendientes = [];
        aplanar(c.respuestas || [], c, true, descendientes, idRaiz);

        if (descendientes.length === 0) return nodo;

        const total = descendientes.length;
        const hijos = crear('div', 'comentario-respuestas');
        const controles = crear('div', 'comentario-hilo-controles');
        nodo.appendChild(hijos);
        nodo.appendChild(controles);

        function boton(texto, alPulsar) {
            const b = crear('button', 'btn-hilo', texto);
            b.type = 'button';
            b.addEventListener('click', alPulsar);
            return b;
        }

        function pintar() {
            const visibles = Math.min(visiblesPorHilo.get(idRaiz) || 0, total);
            const restantes = total - visibles;

            hijos.replaceChildren(...descendientes.slice(0, visibles));
            hijos.hidden = visibles === 0;
            controles.replaceChildren();

            if (restantes > 0) {
                const texto = visibles === 0
                    ? (total === 1 ? 'Ver 1 respuesta' : 'Ver ' + total + ' respuestas')
                    : 'Ver ' + Math.min(LOTE, restantes) + ' más';
                controles.appendChild(boton(texto, () => {
                    visiblesPorHilo.set(idRaiz, Math.min(visibles + LOTE, total));
                    pintar();
                }));
            }

            if (visibles > 0) {
                controles.appendChild(boton('Ocultar respuestas', () => {
                    visiblesPorHilo.set(idRaiz, 0);
                    pintar();
                }));
            }
        }

        pintar();
        return nodo;
    }

    function mostrarComentarios(arbol) {
        cierreActual = null; // el DOM se reconstruye: cualquier formulario abierto desaparece
        lista.replaceChildren();

        const total = contar(arbol);
        titulo.textContent = total === 1 ? '1 comentario' : total + ' comentarios';
        vacio.hidden = arbol.length > 0;

        arbol.forEach(c => lista.appendChild(crearRaiz(c)));
    }

    async function recargarComentarios() {
        const resultado = await pedir(urlComentarios);
        if (!resultado.ok) {
            lista.replaceChildren(crear('p', 'comentarios-error', mensajeDe(resultado)));
            return false;
        }
        mostrarComentarios(resultado.datos.comentarios || []);
        return true;
    }

    // ---------- Carga inicial ----------

    async function cargar() {
        cargando.hidden = false;
        error.hidden = true;
        articulo.hidden = true;
        seccion.hidden = true;

        const [rPublicacion, rComentarios] = await Promise.all([
            pedir(`${api}/publicaciones/${idPublicacion}`),
            pedir(urlComentarios)
        ]);

        if (!rPublicacion.ok) return mostrarError(mensajeDe(rPublicacion));
        if (!rComentarios.ok) return mostrarError(mensajeDe(rComentarios));

        mostrarPublicacion(rPublicacion.datos.publicacion);
        mostrarComentarios(rComentarios.datos.comentarios || []);

        cargando.hidden = true;
        articulo.hidden = false;
        seccion.hidden = false;
    }

    // ---------- Formulario principal ----------

    if (formPrincipal) {
        formPrincipal.addEventListener('submit', async evento => {
            evento.preventDefault();

            const boton = formPrincipal.querySelector('button[type="submit"]');
            const mensaje = formPrincipal.elements.mensaje.value.trim();

            if (mensaje === '') {
                errorForm.textContent = 'El mensaje es obligatorio';
                errorForm.hidden = false;
                return;
            }

            boton.disabled = true;
            errorForm.hidden = true;

            const resultado = await enviarComentario({
                titulo: formPrincipal.elements.titulo.value.trim(),
                mensaje
            }, null);

            if (resultado.ok) {
                formPrincipal.reset();
            } else {
                errorForm.textContent = resultado.mensaje;
                errorForm.hidden = false;
            }
            boton.disabled = false;
        });
    }

    error.querySelector('button').addEventListener('click', cargar);

    cargar();
})();