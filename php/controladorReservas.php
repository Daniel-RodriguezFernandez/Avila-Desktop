<?php

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/usuario.php';
require_once __DIR__ . '/recursoTuristico.php';
require_once __DIR__ . '/carrito.php';
require_once __DIR__ . '/reserva.php';

class ControladorReservas
{
    // --- Registro (se muestra en línea, sin redirección) ---
    private array $errores = [];
    private array $datos = [
        'email' => '',
        'nombre' => '',
        'apellidos' => '',
    ];
    private string $mensajeExito = '';

    // --- Recursos turísticos ---
    /** @var RecursoTuristico[] */
    private array $recursos = [];
    private string $errorRecursos = '';

    // --- Mensajes flash (tras redirección) ---
    private string $flashExito = '';
    private string $flashError = '';
    private array $loginErrores = [];
    private string $loginEmail = '';

    // --- Presupuesto (carrito) ---
    /** @var array<int, array{recurso: RecursoTuristico, plazas: int, subtotal: float}> */
    private array $lineasPresupuesto = [];
    private float $totalPresupuesto = 0.0;

    // --- Reservas del usuario ---
    /** @var Reserva[] */
    private array $misReservas = [];
    private string $errorReservas = '';


    public function procesar(): void
    {
        Sesion::iniciar();

        $esPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $accion = $_POST['accion'] ?? '';

        if ($esPost) {
            // Las acciones que escriben o cambian estado usan
            // POST-Redirect-GET para evitar reenvíos al refrescar.
            switch ($accion) {
                case 'registrar':
                    $this->registrarUsuario();
                    break;
                case 'login':
                    $this->iniciarSesion();   // redirige y termina
                case 'logout':
                    $this->cerrarSesion();    // redirige y termina
                case 'agregar':
                    $this->agregarAlCarrito();
                case 'quitar':
                    $this->quitarDelCarrito();
                case 'vaciar':
                    $this->vaciarCarrito();
                case 'confirmar':
                    $this->confirmarReserva();
                case 'anular':
                    $this->anularReserva();
            }
        }

        // Fase de presentación (GET, o tras registrar que no redirige).
        $this->cargarFlash();
        $this->cargarRecursos();
        $this->cargarPresupuesto();
        $this->cargarMisReservas();
    }


    // ------------------------------------------------------------------
    // Acciones
    // ------------------------------------------------------------------

    private function registrarUsuario(): void
    {
        $email = trim($_POST['email'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

        $this->datos = [
            'email' => $email,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
        ];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errores[] = 'Introduce un correo electrónico válido.';
        }
        if ($nombre === '') {
            $this->errores[] = 'El nombre es obligatorio.';
        }
        if ($apellidos === '') {
            $this->errores[] = 'Los apellidos son obligatorios.';
        }
        if (mb_strlen($password) < 8) {
            $this->errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $password2) {
            $this->errores[] = 'Las contraseñas no coinciden.';
        }

        if (!empty($this->errores)) {
            return;
        }

        try {
            if (Usuario::existeEmail($email)) {
                $this->errores[] = 'Ya existe una cuenta registrada con ese correo electrónico.';
                return;
            }

            $usuario = new Usuario($email, $nombre, $apellidos);
            $usuario->registrar($password);

            $this->mensajeExito = 'Usuario registrado correctamente. '
                . 'Ya puedes iniciar sesión para realizar reservas.';

            $this->datos = ['email' => '', 'nombre' => '', 'apellidos' => ''];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->errores[] = 'Ya existe una cuenta registrada con ese correo electrónico.';
            } else {
                $this->errores[] = 'No se ha podido completar el registro. Inténtalo de nuevo más tarde.';
            }
        }
    }


    private function iniciarSesion(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $errores = [];
        if ($email === '') {
            $errores[] = 'Introduce tu correo electrónico.';
        }
        if ($password === '') {
            $errores[] = 'Introduce tu contraseña.';
        }

        if (empty($errores)) {
            try {
                $usuario = Usuario::autenticar($email, $password);
                if ($usuario === null) {
                    $errores[] = 'Correo o contraseña incorrectos.';
                } else {
                    Sesion::login($usuario->getId(), $usuario->getNombre());
                    Sesion::setFlash('exito', 'Has iniciado sesión correctamente.');
                }
            } catch (PDOException $e) {
                $errores[] = 'No se ha podido iniciar sesión. Inténtalo más tarde.';
            }
        }

        if (!empty($errores)) {
            Sesion::setFlash('loginErrores', $errores);
            Sesion::setFlash('loginEmail', $email);
        }

        $this->redirigir();
    }


    private function cerrarSesion(): void
    {
        Sesion::logout();
        Sesion::setFlash('exito', 'Has cerrado sesión.');
        $this->redirigir();
    }


    private function agregarAlCarrito(): void
    {
        if (!Sesion::estaAutenticado()) {
            Sesion::setFlash('error', 'Debes iniciar sesión para reservar.');
            $this->redirigir();
        }

        $recursoId = (int) ($_POST['recurso_id'] ?? 0);
        $numPlazas = (int) ($_POST['num_plazas'] ?? 0);

        if ($recursoId <= 0 || $numPlazas <= 0) {
            Sesion::setFlash('error', 'Selecciona un número de plazas válido.');
            $this->redirigir();
        }

        try {
            Carrito::agregar($recursoId, $numPlazas);
            Sesion::setFlash('exito', 'Recurso añadido a tu presupuesto.');
        } catch (PDOException $e) {
            Sesion::setFlash('error', 'No se ha podido añadir el recurso al presupuesto.');
        }

        $this->redirigir();
    }


    private function quitarDelCarrito(): void
    {
        if (Sesion::estaAutenticado()) {
            $recursoId = (int) ($_POST['recurso_id'] ?? 0);
            if ($recursoId > 0) {
                Carrito::quitar($recursoId);
                Sesion::setFlash('exito', 'Recurso eliminado del presupuesto.');
            }
        }

        $this->redirigir();
    }


    private function vaciarCarrito(): void
    {
        if (Sesion::estaAutenticado()) {
            Carrito::vaciar();
            Sesion::setFlash('exito', 'Presupuesto vaciado.');
        }

        $this->redirigir();
    }


    private function confirmarReserva(): void
    {
        if (!Sesion::estaAutenticado()) {
            Sesion::setFlash('error', 'Debes iniciar sesión para reservar.');
            $this->redirigir();
        }

        try {
            if (Carrito::estaVacio()) {
                Sesion::setFlash('error', 'No tienes recursos en el presupuesto.');
            } else {
                $reservaId = Reserva::crearDesdeCarrito(Sesion::getUsuarioId());
                Sesion::setFlash('exito', 'Reserva n.º ' . $reservaId . ' confirmada correctamente.');
            }
        } catch (RuntimeException $e) {
            Sesion::setFlash('error', $e->getMessage());
        } catch (PDOException $e) {
            Sesion::setFlash('error', 'No se ha podido completar la reserva. Inténtalo más tarde.');
        }

        $this->redirigir();
    }


    private function anularReserva(): void
    {
        if (!Sesion::estaAutenticado()) {
            Sesion::setFlash('error', 'Debes iniciar sesión.');
            $this->redirigir();
        }

        $reservaId = (int) ($_POST['reserva_id'] ?? 0);

        try {
            if ($reservaId > 0 && Reserva::anular($reservaId, Sesion::getUsuarioId())) {
                Sesion::setFlash('exito', 'Reserva anulada correctamente.');
            } else {
                Sesion::setFlash('error', 'No se ha podido anular la reserva.');
            }
        } catch (PDOException $e) {
            Sesion::setFlash('error', 'No se ha podido anular la reserva. Inténtalo más tarde.');
        }

        $this->redirigir();
    }


    private function redirigir(): void
    {
        header('Location: reservas.php');
        exit;
    }


    // ------------------------------------------------------------------
    // Carga de datos para la vista
    // ------------------------------------------------------------------

    private function cargarFlash(): void
    {
        $this->flashExito = (string) (Sesion::getFlash('exito') ?? '');
        $this->flashError = (string) (Sesion::getFlash('error') ?? '');
        $this->loginErrores = (array) (Sesion::getFlash('loginErrores') ?? []);
        $this->loginEmail = (string) (Sesion::getFlash('loginEmail') ?? '');
    }

    private function cargarRecursos(): void
    {
        try {
            $this->recursos = RecursoTuristico::listarTodos();
        } catch (PDOException $e) {
            $this->errorRecursos = 'No se ha podido cargar la lista de recursos turísticos. '
                . 'Inténtalo de nuevo más tarde.';
        }
    }

    private function cargarPresupuesto(): void
    {
        if (!Sesion::estaAutenticado()) {
            return;
        }

        try {
            $this->lineasPresupuesto = Carrito::getLineas();
            $total = 0.0;
            foreach ($this->lineasPresupuesto as $linea) {
                $total += $linea['subtotal'];
            }
            $this->totalPresupuesto = $total;
        } catch (PDOException $e) {
            $this->lineasPresupuesto = [];
            $this->totalPresupuesto = 0.0;
        }
    }

    private function cargarMisReservas(): void
    {
        if (!Sesion::estaAutenticado()) {
            return;
        }

        try {
            $this->misReservas = Reserva::listarPorUsuario(Sesion::getUsuarioId());
        } catch (PDOException $e) {
            $this->errorReservas = 'No se han podido cargar tus reservas.';
        }
    }


    // ------------------------------------------------------------------
    // Accesores para la vista
    // ------------------------------------------------------------------

    public function estaAutenticado(): bool
    {
        return Sesion::estaAutenticado();
    }

    public function getUsuarioNombre(): string
    {
        return Sesion::getUsuarioNombre();
    }

    public function tieneErrores(): bool
    {
        return !empty($this->errores);
    }

    public function getErrores(): array
    {
        return $this->errores;
    }

    public function getMensajeExito(): string
    {
        return $this->mensajeExito;
    }

    public function getFlashExito(): string
    {
        return $this->flashExito;
    }

    public function getFlashError(): string
    {
        return $this->flashError;
    }

    public function getLoginErrores(): array
    {
        return $this->loginErrores;
    }

    public function getLoginEmail(): string
    {
        return self::esc($this->loginEmail);
    }

    /** @return RecursoTuristico[] */
    public function getRecursos(): array
    {
        return $this->recursos;
    }

    public function getErrorRecursos(): string
    {
        return $this->errorRecursos;
    }

    /** @return array<int, array{recurso: RecursoTuristico, plazas: int, subtotal: float}> */
    public function getLineasPresupuesto(): array
    {
        return $this->lineasPresupuesto;
    }

    public function getTotalPresupuesto(): float
    {
        return $this->totalPresupuesto;
    }

    public function tienePresupuesto(): bool
    {
        return !empty($this->lineasPresupuesto);
    }

    /** @return Reserva[] */
    public function getMisReservas(): array
    {
        return $this->misReservas;
    }

    public function getErrorReservas(): string
    {
        return $this->errorReservas;
    }


    // ------------------------------------------------------------------
    // Utilidades de formato/escape
    // ------------------------------------------------------------------

    public function valor(string $campo): string
    {
        return self::esc($this->datos[$campo] ?? '');
    }

    public static function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    public static function fecha(string $fechaSql): string
    {
        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaSql);

        return $fecha !== false ? $fecha->format('d/m/Y H:i') : $fechaSql;
    }

    public static function precio(float $valor): string
    {
        return number_format($valor, 2, ',', '.') . ' &euro;';
    }
}