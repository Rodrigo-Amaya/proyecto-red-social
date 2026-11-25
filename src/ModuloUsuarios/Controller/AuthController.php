<?php

class AuthController implements Controlador
{
    public function __construct(
        private Usuario $modelo
    ){}

    public function control(string $metodo, array $partes): void
    {
        $sub = $partes[1] ?? null;

        if (isset($partes[2])){ noEncontrado(); }

        // Ruta /auth
        match ($sub) {
            'login'  => $metodo === 'POST' ? $this->login() : noPermitido(),
            'logout' => $metodo === 'POST' ? $this->logout() : noPermitido(),
            'perfil' => $metodo === 'GET' ? $this->perfil() : noPermitido(),
            default  => noEncontrado(),
        };
    }

    /* --- PETICIONES --- */
    // POST /auth/login
    private function login(): void
    {
        $data = leerJson();

        validarCamposRequeridos($data, ['correo', 'contrasena']);

        if (!is_string($data['correo']) || !is_string($data['contrasena'])) {
            respuesta(400, ['success' => false, 'mensaje' => 'Correo y contraseña deben ser texto']);
        }

        $usuario = $this->modelo->consultarUsuario(trim($data['correo']));

        // Mismo mensaje para correo inexistente y contraseña incorrecta
        if ($usuario === null || !password_verify($data['contrasena'], $usuario['contrasena'])) {
            respuesta(401, ['success' => false, 'mensaje' => 'Credenciales inválidas']);
        }

        // El bloqueo solo se informa si la contraseña era correcta
        if ((int)$usuario['bloqueo_usuario'] === 1) {
            respuesta(403, ['success' => false, 'mensaje' => 'Usuario bloqueado']);
        }

        abrirSesion();
        session_regenerate_id(true); // Evitar fijación de sesión
        $_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
        $_SESSION['rol']        = $usuario['rol'];

        respuesta(200, [
            'success' => true,
            'mensaje' => 'Sesión iniciada',
            'usuario' => [
                'id_usuario' => (int)$usuario['id_usuario'],
                'nombre'     => $usuario['nombre'],
                'rol'        => $usuario['rol'],
            ]
        ]);
    }

    // POST /auth/logout
    private function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        respuesta(200, ['success' => true, 'mensaje' => 'Sesión cerrada']);
    }

    // GET /auth/perfil (revalida en la base: bloqueo y rol)
    private function perfil(): void
    {
        $usuario = $this->modelo->obtenerUsuarioPorId(usuarioActual());
        if ($usuario === null) {
            // Usuario bloqueado o eliminado: invalida la sesión
            $_SESSION = [];
            session_destroy();
            respuesta(401, ['success' => false, 'mensaje' => 'Sesión inválida']);
        }

        // Si cambió su rol, la sesión se actualiza
        $_SESSION['rol'] = $usuario['rol'];

        respuesta(200, ['success' => true, 'mensaje' => 'Sesión activa', 'usuario' => $usuario]);
    }
}