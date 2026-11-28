const listaGuardados = document.getElementById('lista-guardados');

if (listaGuardados) {
    iniciarGuardados(listaGuardados);
}

function iniciarGuardados(contenedor) {
    const api = contenedor.dataset.api;
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

    function crearAvatar(url, nombre) {
        if (url) {
            const img = crear('img', 'avatar');
            img.src = url;
            img.alt = 'Foto de ' + (nombre || 'usuario');
            return img;
        }
        return crear('span', 'avatar avatar-inicial', (nombre || 'U').charAt(0).toUpperCase());
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

    function crearTarjeta(publicacion) {
        const articulo = crear('article', 'publicacion');
        articulo.dataset.id = publicacion.id_publicacion;

        const cabecera = crear('header', 'publicacion-autor');

        cabecera.appendChild(crearAvatar(publicacion.url_foto, publicacion.autor));

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

        const urlDetalle = 'index.php?page=publicacion&id=' + encodeURIComponent(publicacion.id_publicacion);

        if (publicacion.titulo) {
            const titulo = crear('p', 'publicacion-titulo');
            const enlaceTitulo = crear('a', 'publicacion-enlace', publicacion.titulo);
            enlaceTitulo.href = urlDetalle;
            titulo.appendChild(enlaceTitulo);
            articulo.appendChild(titulo);
        }

        articulo.appendChild(
            crear('p', '', publicacion.mensaje)
        );

        agregarDocumentos(articulo, publicacion.documentos);

        const acciones = crear('footer', 'publicacion-acciones');

        const quitar = crear('button', 'btn-quitar-guardado', 'Quitar de guardados');
        quitar.type = 'button';

        quitar.addEventListener('click', () => {
            quitarGuardado(publicacion.id_publicacion, articulo, quitar);
        });

        const verDetalle = crear('a', 'enlace-detalle', 'Ver detalle');
        verDetalle.href = urlDetalle;

        acciones.appendChild(verDetalle);
        acciones.appendChild(quitar);
        articulo.appendChild(acciones);

        return articulo;
    }

    async function quitarGuardado(idPublicacion, tarjeta, boton) {
        boton.disabled = true;

        try {
            const respuesta = await fetch(
                `${api}/publicaciones/${idPublicacion}/guardar`,
                { method: 'DELETE' }
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
            const respuesta = await fetch(`${api}/publicaciones/guardadas`);

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