//Misma ruta base que usa perfilUsuario.js
const URL_API_PROFESORES = 'http://localhost/proyecto-red-social/api/profesores';

const TEXTO_ESTADO = {
    PENDIENTE: 'Primera revisión',
    EN_SEGUNDA_REVISION: 'Segunda revisión',
    APROBADA: 'Aprobada',
    RECHAZADA: 'Rechazada',
    CANCELADA: 'Cancelada'
};
const REVISABLES = ['PENDIENTE', 'EN_SEGUNDA_REVISION'];

const filtroEstado = document.getElementById('filtroEstado');
const filtroBusqueda = document.getElementById('filtroBusqueda');
const filtroOrden = document.getElementById('filtroOrden');
const lista = document.getElementById('listaSolicitudes');
const resumen = document.getElementById('resumen');

/*-----------------------------------------------------------------------------------------
                                        UTILIDADES
-----------------------------------------------------------------------------------------*/
//Crea un elemento usando textContent (evita inyectar HTML con datos de usuarios)
function el(etiqueta, clase, texto) {
    const nodo = document.createElement(etiqueta);
    if (clase) nodo.className = clase;
    if (texto !== undefined && texto !== null) nodo.textContent = texto;
    return nodo;
}

//Convierte 'YYYY-MM-DD HH:MM:SS' de MySQL en Date
function aFecha(texto) {
    return texto ? new Date(texto.replace(' ', 'T')) : null;
}
function formatearFecha(texto) {
    const fecha = aFecha(texto);
    return fecha ? fecha.toLocaleDateString('es-SV', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
}

function dato(titulo, valor, clase) {
    const caja = el('div');
    caja.appendChild(el('span', null, titulo));
    caja.appendChild(el('strong', clase, valor));
    return caja;
}

/*-----------------------------------------------------------------------------------------
                                    CARGA DE SOLICITUDES
-----------------------------------------------------------------------------------------*/
async function pedirSolicitudes(estado, busqueda, orden) {
    const params = new URLSearchParams({ orden });
    if (estado) params.set('estado', estado);
    if (busqueda) params.set('busqueda', busqueda);

    const respuesta = await fetch(`${URL_API_PROFESORES}?${params}`, { credentials: 'same-origin' });
    const datos = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(datos.Mensaje || 'No se pudieron obtener las solicitudes');
    }
    //La API devuelve '' cuando no hay resultados
    return Array.isArray(datos.Aplicaciones) ? datos.Aplicaciones : [];
}

let numeroPeticion = 0;

async function cargarSolicitudes() {
    const miPeticion = ++numeroPeticion;
    const estado = filtroEstado.value;
    const busqueda = filtroBusqueda.value.trim();
    const orden = filtroOrden.value;

    resumen.textContent = 'Cargando solicitudes...';

    try {
        let solicitudes;

        //"Por revisar" junta las dos etapas en una sola lista
        if (estado === 'POR_REVISAR') {
            const [primera, segunda] = await Promise.all(
                REVISABLES.map(e => pedirSolicitudes(e, busqueda, orden))
            );
            solicitudes = [...primera, ...segunda].sort((a, b) =>
                orden === 'ASC'
                    ? aFecha(a.fecha_aplicacion) - aFecha(b.fecha_aplicacion)
                    : aFecha(b.fecha_aplicacion) - aFecha(a.fecha_aplicacion)
            );
        } else {
            solicitudes = await pedirSolicitudes(estado, busqueda, orden);
        }

        //Si el usuario cambio un filtro mientras cargaba, se ignora esta respuesta
        if (miPeticion !== numeroPeticion) return;

        mostrarSolicitudes(solicitudes);
    } catch (error) {
        if (miPeticion !== numeroPeticion) return;
        console.error('Error:', error);
        lista.replaceChildren();
        resumen.textContent = error.message || 'Error de conexión';
    }
}

/*-----------------------------------------------------------------------------------------
                                    TARJETAS
-----------------------------------------------------------------------------------------*/
function mostrarSolicitudes(solicitudes) {
    lista.replaceChildren();

    if (solicitudes.length === 0) {
        resumen.textContent = 'No hay solicitudes con estos filtros.';
        return;
    }

    resumen.textContent = solicitudes.length === 1 ? '1 solicitud' : `${solicitudes.length} solicitudes`;
    solicitudes.forEach(s => lista.appendChild(crearTarjeta(s)));
}

function crearTarjeta(s) {
    const tarjeta = el('div', 'solicitud');

    //Cabecera: nombre, correo y estado
    const cabecera = el('div', 'solicitudCabecera');
    const titulo = el('div');
    titulo.appendChild(el('h2', null, s.nombre_usuario));
    titulo.appendChild(el('p', 'solicitudCorreo', s.correo));
    cabecera.appendChild(titulo);
    cabecera.appendChild(el('span', `estado ${s.estado}`, TEXTO_ESTADO[s.estado] || s.estado));
    tarjeta.appendChild(cabecera);

    //Datos academicos
    const revisable = REVISABLES.includes(s.estado);
    const fechaLimite = s.estado === 'EN_SEGUNDA_REVISION' ? s.segunda_revision_fecha : s.primera_revision_fecha;
    const vencida = revisable && aFecha(fechaLimite) < new Date();

    const datos = el('div', 'datosSolicitud');
    datos.appendChild(dato('Universidad', s.universidad));
    datos.appendChild(dato('Carrera', s.carrera));
    datos.appendChild(dato('Grado académico', s.grado_academico));
    datos.appendChild(dato('Año de graduación', s.anio_graducacion));
    datos.appendChild(dato('Teléfono de la universidad', s.telefono_universidad || '—'));
    datos.appendChild(dato('Solicitó el', formatearFecha(s.fecha_aplicacion)));
    if (revisable) {
        datos.appendChild(dato('Revisar antes de', formatearFecha(fechaLimite) + (vencida ? ' (vencida)' : ''), vencida ? 'vencida' : ''));
    }
    tarjeta.appendChild(datos);

    //Titulo adjunto
    if (s.url_titulo) {
        const enlace = el('a', 'enlaceTitulo', 'Ver título adjunto');
        enlace.href = `../../../${s.url_titulo}`;
        enlace.target = '_blank';
        enlace.rel = 'noopener';
        tarjeta.appendChild(enlace);
    }

    //Razon de rechazo
    if (s.estado === 'RECHAZADA' && s.razon_rechazo) {
        tarjeta.appendChild(el('p', 'razonRechazo', `Motivo del rechazo: ${s.razon_rechazo}`));
    }

    //Acciones
    if (revisable) {
        const etapa = s.estado === 'PENDIENTE' ? 1 : 2;
        const acciones = el('div', 'accionesSolicitud');

        const aprobar = el('button', 'btnAprobar', etapa === 1 ? 'Aprobar primera revisión' : 'Aprobar definitivamente');
        aprobar.type = 'button';
        aprobar.addEventListener('click', () => abrirPanelRevision(s, 'APROBADA', etapa));

        const rechazar = el('button', 'btnRechazar', 'Rechazar');
        rechazar.type = 'button';
        rechazar.addEventListener('click', () => abrirPanelRevision(s, 'RECHAZADA', etapa));

        acciones.append(aprobar, rechazar);
        tarjeta.appendChild(acciones);
    }

    return tarjeta;
}

/*-----------------------------------------------------------------------------------------
                                    PANEL DE REVISION
-----------------------------------------------------------------------------------------*/
const panelRevision = document.getElementById('panelRevision');
const formRevision = document.getElementById('formRevision');
const comentario = document.getElementById('comentarioRevision');
const botonConfirmar = document.getElementById('botonConfirmarRevision');

let revisionActual = null;

function abrirPanelRevision(solicitud, decision, etapa) {
    revisionActual = { id: solicitud.id_aplicacion, decision };

    const aprobando = decision === 'APROBADA';
    const ultima = aprobando && etapa === 2;

    document.getElementById('tituloRevision').textContent =
        `${aprobando ? 'Aprobar' : 'Rechazar'} solicitud de ${solicitud.nombre_usuario}`;

    document.getElementById('subtituloRevision').textContent = !aprobando
        ? 'Indica la razón del rechazo. El usuario podrá verla.'
        : ultima
            ? 'Al confirmar, el usuario pasará a ser profesor.'
            : 'La solicitud pasará a segunda revisión.';

    comentario.placeholder = aprobando ? 'Comentario (opcional)' : 'Razón del rechazo (obligatoria)';
    comentario.value = '';

    botonConfirmar.textContent = aprobando ? 'Confirmar aprobación' : 'Confirmar rechazo';
    botonConfirmar.className = aprobando ? 'btnAprobar' : 'btnRechazar';
    botonConfirmar.disabled = false;

    panelRevision.style.display = 'flex';
    document.getElementById('opacidad').style.display = 'flex';
    comentario.focus();
}

function cerrarPanelRevision() {
    panelRevision.style.display = 'none';
    document.getElementById('opacidad').style.display = 'none';
    revisionActual = null;
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarPanelRevision();
});

formRevision.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!revisionActual) return;

    const texto = comentario.value.trim();

    if (revisionActual.decision === 'RECHAZADA' && texto === '') {
        alert('Debes indicar la razón del rechazo');
        return;
    }

    botonConfirmar.disabled = true;

    try {
        const respuesta = await fetch(`${URL_API_PROFESORES}/${revisionActual.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                decision: revisionActual.decision,
                comentario: texto
            })
        });

        const resultado = await respuesta.json();
        alert(resultado.Mensaje);

        if (!respuesta.ok) {
            botonConfirmar.disabled = false;
            return;
        }

        cerrarPanelRevision();
        cargarSolicitudes();

    } catch (error) {
        console.error('Error:', error);
        botonConfirmar.disabled = false;
    }
});

/*-----------------------------------------------------------------------------------------
                                        FILTROS
-----------------------------------------------------------------------------------------*/
let temporizador;
filtroBusqueda.addEventListener('input', () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(cargarSolicitudes, 400);
});
filtroEstado.addEventListener('change', cargarSolicitudes);
filtroOrden.addEventListener('change', cargarSolicitudes);

cargarSolicitudes();