<?php

function seedPalabrasClave(mysqli $conn): void
{
    // Agrupadas por nombre de materia
    $palabrasPorMateria = [
        'Matematicas' => ['matematicas', 'numeros', 'operaciones', 'suma', 'resta', 'multiplicacion', 'division', 'fracciones', 'porcentajes', 'potencias', 'raices', 'problemas matematicos', 'expresiones numericas', 'conversion de unidades'],
        'Lenguaje y Literatura' => ['lenguaje', 'literatura', 'gramatica', 'ortografia', 'redaccion', 'lectura', 'comprension lectora', 'analisis literario', 'poesia', 'novela', 'cuento', 'poema', 'sintaxis', 'acentuacion', 'figuras literarias', 'generos literarios', 'comunicacion'],
        'Ciencias Sociales' => ['ciencias sociales', 'sociedad', 'historia', 'cultura', 'politica', 'sociologia', 'antropologia', 'democracia', 'derechos humanos', 'civilizaciones', 'globalizacion', 'poblacion', 'organizacion social', 'movimientos sociales', 'independencia', 'revolucion', 'geopolitica'],
        'Ingles' => ['ingles', 'english', 'grammar', 'vocabulary', 'pronunciation', 'reading', 'writing', 'listening', 'speaking', 'verbs', 'tenses', 'present simple', 'past simple', 'future tense', 'irregular verbs', 'adjectives', 'prepositions', 'translation'],
        'Computacion' => ['computacion', 'informatica', 'computadoras', 'hardware', 'software', 'sistemas operativos', 'procesadores', 'memoria ram', 'dispositivos', 'perifericos', 'sistemas informaticos', 'ofimatica', 'windows', 'linux', 'archivos', 'algoritmos', 'tecnologia'],
        'Ciencias Naturales' => ['ciencias naturales', 'naturaleza', 'materia', 'energia', 'seres vivos', 'medio ambiente', 'ecosistemas', 'recursos naturales', 'cuerpo humano', 'agua', 'aire', 'suelo', 'contaminacion', 'ciclo del agua', 'sistema solar', 'planetas', 'fenomenos naturales'],
        'Biologia' => ['biologia', 'celula', 'genetica', 'adn', 'arn', 'evolucion', 'fotosintesis', 'mitosis', 'meiosis', 'bacterias', 'microorganismos', 'anatomia', 'fisiologia', 'biodiversidad', 'ecosistema', 'organismos', 'reproduccion celular', 'tejidos'],
        'Quimica' => ['quimica', 'atomos', 'moleculas', 'elementos quimicos', 'tabla periodica', 'enlaces quimicos', 'reacciones quimicas', 'compuestos', 'soluciones', 'mezclas', 'acidos', 'bases', 'ph', 'estequiometria', 'mol', 'oxidacion', 'reduccion', 'configuracion electronica'],
        'Fisica' => ['fisica', 'movimiento', 'velocidad', 'aceleracion', 'fuerza', 'energia', 'trabajo mecanico', 'gravedad', 'dinamica', 'cinematica', 'termodinamica', 'electricidad', 'magnetismo', 'ondas', 'leyes de newton', 'presion', 'densidad', 'optica'],
        'Estadistica' => ['estadistica', 'media', 'mediana', 'moda', 'varianza', 'desviacion estandar', 'probabilidad', 'muestreo', 'poblacion estadistica', 'distribucion normal', 'frecuencia', 'histogramas', 'regresion', 'correlacion', 'graficos estadisticos', 'tablas de frecuencia', 'percentiles', 'cuartiles'],
        'Geometria' => ['geometria', 'triangulos', 'cuadrilateros', 'circulos', 'circunferencia', 'perimetro', 'area', 'volumen', 'angulos', 'poligonos', 'figuras geometricas', 'geometria analitica', 'geometria espacial', 'rectas', 'segmentos', 'teorema de pitagoras', 'paralelas', 'perpendiculares'],
        'Algebra' => ['algebra', 'ecuaciones', 'inecuaciones', 'polinomios', 'monomios', 'binomios', 'factorizacion', 'exponentes', 'radicales', 'variables', 'expresiones algebraicas', 'sistemas de ecuaciones', 'productos notables', 'despeje', 'terminos semejantes', 'ecuaciones lineales', 'ecuaciones cuadraticas'],
        'Calculo' => ['calculo', 'derivadas', 'integrales', 'limites', 'continuidad', 'funciones', 'diferenciacion', 'integracion', 'regla de la cadena', 'maximos y minimos', 'series', 'sucesiones', 'calculo diferencial', 'calculo integral', 'derivadas parciales', 'integrales definidas', 'integrales indefinidas'],
        'Programacion' => ['programacion', 'codigo', 'algoritmos', 'variables', 'funciones', 'condicionales', 'ciclos', 'bucles', 'arreglos', 'matrices', 'objetos', 'clases', 'programacion orientada a objetos', 'java', 'python', 'c++', 'estructuras de datos', 'depuracion', 'recursividad'],
        'Bases de Datos' => ['base de datos', 'bases de datos', 'mysql', 'sql', 'mariadb', 'postgresql', 'mongodb', 'consultas', 'select', 'insert', 'update', 'delete', 'normalizacion', 'claves primarias', 'claves foraneas', 'modelo entidad relacion', 'procedimientos almacenados', 'indices', 'relaciones'],
        'Redes Informaticas' => ['redes informaticas', 'redes', 'direccion ip', 'direccion mac', 'mascara de subred', 'subredes', 'router', 'switch', 'servidores', 'dns', 'dhcp', 'tcp', 'udp', 'modelo osi', 'topologias de red', 'cableado estructurado', 'ethernet', 'wifi', 'configuracion de redes'],
        'Desarrollo Web' => ['desarrollo web', 'html', 'css', 'javascript', 'php', 'frontend', 'backend', 'fullstack', 'apis', 'responsive design', 'servidores web', 'formularios', 'frameworks', 'laravel', 'react', 'xampp', 'dom', 'ajax', 'bootstrap'],
        'Inteligencia Artificial' => ['inteligencia artificial', 'ia', 'machine learning', 'deep learning', 'aprendizaje automatico', 'redes neuronales', 'procesamiento de lenguaje natural', 'chatbots', 'modelos de lenguaje', 'algoritmos inteligentes', 'vision artificial', 'entrenamiento de modelos', 'datos de entrenamiento', 'predicciones', 'automatizacion', 'inteligencia generativa'],
        'Ciberseguridad' => ['ciberseguridad', 'seguridad informatica', 'hacking etico', 'phishing', 'malware', 'ransomware', 'firewall', 'criptografia', 'vulnerabilidades', 'autenticacion', 'contraseñas', 'redes seguras', 'inyeccion sql', 'ingenieria social', 'proteccion de datos', 'ataques informaticos', 'control de acceso', 'auditoria de seguridad'],
        'Electronica' => ['electronica', 'circuitos', 'resistencias', 'capacitores', 'transistores', 'diodos', 'voltaje', 'corriente', 'amperaje', 'soldadura', 'arduino', 'microcontroladores', 'circuitos digitales', 'ley de ohm', 'protoboard', 'sensores', 'semiconductores', 'componentes electronicos'],
        'Contabilidad' => ['contabilidad', 'activo', 'pasivo', 'patrimonio', 'balance general', 'libro diario', 'libro mayor', 'asientos contables', 'estados financieros', 'depreciacion', 'inventario', 'cuentas por cobrar', 'cuentas por pagar', 'debe y haber', 'conciliacion bancaria', 'estado de resultados', 'partida doble'],
        'Administracion de Empresas' => ['administracion', 'empresa', 'gestion empresarial', 'recursos humanos', 'liderazgo', 'organizacion', 'planificacion', 'direccion', 'control administrativo', 'productividad', 'estrategia empresarial', 'toma de decisiones', 'gestion de personal', 'procesos administrativos', 'estructura organizacional', 'administracion estrategica'],
        'Economia' => ['economia', 'oferta', 'demanda', 'inflacion', 'deflacion', 'mercado', 'microeconomia', 'macroeconomia', 'pib', 'desempleo', 'impuestos', 'comercio', 'produccion', 'consumo', 'politica monetaria', 'tipo de cambio', 'recesion', 'crecimiento economico'],
        'Emprendimiento' => ['emprendimiento', 'emprendedor', 'negocio', 'plan de negocios', 'modelo de negocio', 'innovacion', 'startup', 'marketing', 'clientes', 'ventas', 'propuesta de valor', 'mercado objetivo', 'idea de negocio', 'estrategia comercial', 'financiamiento', 'competencia', 'producto', 'servicio'],
        'Educacion Financiera' => ['educacion financiera', 'ahorro', 'presupuesto', 'inversion', 'intereses', 'prestamos', 'deudas', 'tarjetas de credito', 'finanzas personales', 'ingresos', 'gastos', 'rentabilidad', 'credito', 'planificacion financiera', 'interes compuesto', 'fondo de emergencia', 'ahorro mensual', 'educacion economica'],
        'Filosofia' => ['filosofia', 'pensamiento', 'etica', 'moral', 'logica', 'metafisica', 'epistemologia', 'existencialismo', 'filosofos', 'conocimiento', 'razonamiento', 'argumentacion', 'platon', 'aristoteles', 'socrates'],
        'Psicologia' => ['psicologia', 'comportamiento', 'conducta', 'emociones', 'memoria', 'aprendizaje', 'personalidad', 'desarrollo humano', 'cognicion', 'motivacion', 'percepcion', 'pensamiento', 'inteligencia', 'psicoanalisis', 'psicologia social'],
        'Arte' => ['arte', 'dibujo', 'pintura', 'escultura', 'artes plasticas', 'arte visual', 'fotografia', 'ilustracion', 'colores', 'perspectiva', 'diseño', 'creatividad', 'pintores', 'historia del arte', 'manualidades'],
        'Musica' => ['musica', 'instrumentos', 'guitarra', 'piano', 'violin', 'bateria', 'canto', 'voz', 'melodia', 'armonia', 'ritmo', 'notas musicales', 'partituras', 'composicion', 'solfeo'],
        'Educacion Fisica' => ['educacion fisica', 'deporte', 'ejercicio', 'entrenamiento', 'actividad fisica', 'atletismo', 'futbol', 'baloncesto', 'voleibol', 'natacion', 'resistencia', 'flexibilidad', 'fuerza', 'calentamiento', 'condicion fisica'],
        'Educacion Ambiental' => ['educacion ambiental', 'medio ambiente', 'ecologia', 'contaminacion', 'reciclaje', 'sostenibilidad', 'biodiversidad', 'cambio climatico', 'calentamiento global', 'conservacion', 'ecosistemas', 'recursos naturales', 'deforestacion', 'energias renovables', 'desarrollo sostenible'],
        'Geografia' => ['geografia', 'mapas', 'continentes', 'paises', 'capitales', 'relieve', 'rios', 'oceanos', 'montañas', 'clima', 'territorio', 'coordenadas', 'poblacion', 'geografia fisica', 'geografia humana'],
        'Historia' => ['historia', 'acontecimientos historicos', 'civilizaciones', 'edad antigua', 'edad media', 'edad moderna', 'edad contemporanea', 'revoluciones', 'guerras', 'independencia', 'colonizacion', 'imperios', 'historia universal', 'historia de el salvador', 'personajes historicos'],
        'Derecho' => ['derecho', 'leyes', 'constitucion', 'justicia', 'legislacion', 'derechos humanos', 'derecho penal', 'derecho civil', 'derecho laboral', 'derecho constitucional', 'codigos legales', 'jurisprudencia', 'normas juridicas', 'contratos', 'procesos judiciales'],
        'Medicina' => ['medicina', 'anatomia', 'fisiologia', 'enfermedades', 'diagnostico', 'tratamientos', 'farmacologia', 'patologia', 'microbiologia', 'salud', 'medicamentos', 'sistema nervioso', 'sistema circulatorio', 'medicina general', 'investigacion medica'],
        'Enfermeria' => ['enfermeria', 'cuidados', 'pacientes', 'signos vitales', 'primeros auxilios', 'curaciones', 'inyecciones', 'administracion de medicamentos', 'bioseguridad', 'atencion al paciente', 'procedimientos de enfermeria', 'higiene hospitalaria', 'triaje', 'salud publica', 'cuidados intensivos'],
        'Ingenieria' => ['ingenieria', 'calculo', 'fisica', 'mecanica', 'electricidad', 'electronica', 'estructuras', 'diseño industrial', 'termodinamica', 'resistencia de materiales', 'ingenieria civil', 'ingenieria industrial', 'ingenieria mecanica', 'ingenieria electrica', 'ingenieria de sistemas'],
        'Otros' => ['otros', 'general', 'proyectos', 'tareas', 'investigaciones', 'exposiciones', 'presentaciones', 'actividades', 'resumenes', 'trabajos academicos', 'estudios', 'educacion', 'aprendizaje', 'consultas', 'ayuda'],
    ];

    $stmtMateria = $conn->prepare("SELECT id_materia FROM materias WHERE nombre = ?");

    $stmt = $conn->prepare(
        "INSERT INTO palabras_clave (id_materia, palabra)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE palabra = VALUES(palabra)"
    );

    foreach ($palabrasPorMateria as $nombreMateria => $palabras) {
        $stmtMateria->bind_param("s", $nombreMateria);
        $stmtMateria->execute();
        $idMateria = $stmtMateria->get_result()->fetch_assoc()["id_materia"] ?? null;
        if (!$idMateria) {
            throw new Exception("No se encontró la materia: $nombreMateria. Ejecuta primero seedMaterias.");
        }
        $idMateria = (int)$idMateria;

        foreach ($palabras as $palabra) {
            $stmt->bind_param("is", $idMateria, $palabra);
            $stmt->execute();
        }
    }

    $stmtMateria->close();
    $stmt->close();

    echo "Palabras clave insertadas correctamente.\n";
}