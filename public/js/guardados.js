const listaGuardados = document.getElementById('lista-guardados');

if (listaGuardados) {
    iniciarGuardados(listaGuardados);
}

function iniciarGuardados(contenedor) {
    const api = contenedor.dataset.api;
    const idUsuario = Number(contenedor.dataset.usuario);
    const vacio = document.getElementById('guardados-vacio');
    const error = document.getElementById('guardados-error');

    function crear(etiqueta, clase, texto) {
        const elemento = document.createElement(etiqueta);

        if (clase) elemento.className = clase;
        if (texto !== undefined && texto !== null) {
            elemento.textContent = texto;
        }

        return elemento;
    }

    function agregarDocumentos(contenedor, documentos) {
        if (!Array.isArray(documentos) || documentos.length === 0) return;

        const lista = crear('div', 'archivos-publicacion');

        documentos.forEach(documento => {
            const enlace = crear('a', 'archivo-enlace', documento.nombre_archivo);
            enlace.href = documento.url_archivo;
            enlace.target = '_blank';
            enlace.rel = 'noopener noreferrer';

            lista.appendChild(enlace);
        });

        contenedor.appendChild(lista);
    }

    function crearComentario(comentario) {
        const elemento = crear('article', 'comentario-guardado');

        const cabecera = crear('div', 'comentario-autor');

        cabecera.appendChild(
            crear('strong', '', comentario.autor || 'Usuario')
        );

        if (comentario.fecha) {
            cabecera.appendChild(
                crear('small', '', comentario.fecha)
            );
        }

        elemento.appendChild(cabecera);

        if (comentario.titulo) {
            elemento.appendChild(
                crear('h4', '', comentario.titulo)
            );
        }

        elemento.appendChild(
            crear('p', 'comentario-mensaje', comentario.mensaje)
        );

        agregarDocumentos(elemento, comentario.documentos);

        const likes = crear(
            'small',
            'comentario-likes',
            `Me gusta: ${comentario.total_likes ?? 0}`
        );

        elemento.appendChild(likes);

        if (Array.isArray(comentario.respuestas) && comentario.respuestas.length > 0) {
            const respuestas = crear('div', 'respuestas-guardadas');

            comentario.respuestas.forEach(respuesta => {
                respuestas.appendChild(crearComentario(respuesta));
            });

            elemento.appendChild(respuestas);
        }

        return elemento;
    }

    function crearTarjeta(publicacion) {
        const articulo = crear('article', 'publicacion');
        articulo.dataset.id = publicacion.id_publicacion;

        const cabecera = crear('header', 'publicacion-autor');

        if (publicacion.url_foto) {
            const foto = document.createElement('img');
            foto.src = publicacion.url_foto;
            foto.alt = `Foto de ${publicacion.autor || 'usuario'}`;
            cabecera.appendChild(foto);
        }

        const info = crear('div', 'publicacion-info');

        info.appendChild(
            crear('strong', '', publicacion.autor || 'Usuario')
        );

        info.appendChild(
            crear('small', '', publicacion.nivel || '')
        );

        cabecera.appendChild(info);

        if (publicacion.materia) {
            cabecera.appendChild(
                crear('span', 'publicacion-materia', publicacion.materia)
            );
        }

        articulo.appendChild(cabecera);

        if (publicacion.titulo) {
            articulo.appendChild(
                crear('p', 'publicacion-titulo', publicacion.titulo)
            );
        }

        articulo.appendChild(
            crear('p', '', publicacion.mensaje)
        );

        agregarDocumentos(articulo, publicacion.documentos);

        const likes = crear(
            'p',
            'publicacion-likes',
            `Me gusta: ${publicacion.total_likes ?? 0}`
        );

        articulo.appendChild(likes);

        if (Array.isArray(publicacion.comentarios) && publicacion.comentarios.length > 0) {
            const seccionComentarios = crear('section', 'comentarios-guardados');

            seccionComentarios.appendChild(
                crear('h3', '', 'Comentarios y respuestas')
            );

            publicacion.comentarios.forEach(comentario => {
                seccionComentarios.appendChild(crearComentario(comentario));
            });

            articulo.appendChild(seccionComentarios);
        }

        const acciones = crear('footer', 'publicacion-acciones');

        const quitar = crear('button', 'btn-quitar-guardado', 'Quitar de guardados');
        quitar.type = 'button';

        quitar.addEventListener('click', () => {
            quitarGuardado(publicacion.id_publicacion, articulo, quitar);
        });

        acciones.appendChild(quitar);
        articulo.appendChild(acciones);

        return articulo;
    }

    async function quitarGuardado(idPublicacion, tarjeta, boton) {
        boton.disabled = true;

        try {
            const respuesta = await fetch(
                `${api}/publicaciones/${idPublicacion}/guardar`,
                {
                    method: 'DELETE',
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
                throw new Error(datos.mensaje || 'No se pudo quitar la publicación');
            }

            tarjeta.remove();

            if (!contenedor.querySelector('.publicacion')) {
                vacio.hidden = false;
            }

        } catch (e) {
            alert(e.message);
            boton.disabled = false;
        }
    }

    async function cargarGuardados() {
        error.hidden = true;
        vacio.hidden = true;

        contenedor.querySelectorAll('.publicacion').forEach(p => p.remove());

        try {
            const respuesta = await fetch(
                `${api}/publicaciones/guardadas?id_usuario=${idUsuario}`
            );

            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.success) {
                throw new Error(datos.mensaje || 'No se pudieron cargar las publicaciones');
            }

            if (!Array.isArray(datos.publicaciones) || datos.publicaciones.length === 0) {
                vacio.hidden = false;
                return;
            }

            datos.publicaciones.forEach(publicacion => {
                contenedor.appendChild(crearTarjeta(publicacion));
            });

        } catch (e) {
            error.querySelector('p').textContent = e.message;
            error.hidden = false;
        }
    }

    error.querySelector('button').addEventListener('click', cargarGuardados);

    cargarGuardados();
}