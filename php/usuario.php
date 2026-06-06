<?php

require_once __DIR__ . '/database.php';


class Usuario
{
    private int $id;
    private string $email;
    private string $nombre;
    private string $apellidos;


    public function __construct(string $email, string $nombre, string $apellidos, int $id = 0)
    {
        $this->email = $email;
        $this->nombre = $nombre;
        $this->apellidos = $apellidos;
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellidos(): string
    {
        return $this->apellidos;
    }


    public static function existeEmail(string $email): bool
    {
        $sql = 'SELECT 1 FROM usuarios WHERE email = :email LIMIT 1';
        $sentencia = Database::getConexion()->prepare($sql);
        $sentencia->execute([':email' => $email]);

        return $sentencia->fetchColumn() !== false;
    }


    public function registrar(string $passwordPlano): int
    {

        $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO usuarios (email, password_hash, nombre, apellidos)
                VALUES (:email, :hash, :nombre, :apellidos)';

        $conexion = Database::getConexion();
        $sentencia = $conexion->prepare($sql);
        $sentencia->execute([
            ':email' => $this->email,
            ':hash' => $hash,
            ':nombre' => $this->nombre,
            ':apellidos' => $this->apellidos,
        ]);

        $this->id = (int) $conexion->lastInsertId();

        return $this->id;
    }


    /**
     * Comprueba las credenciales contra la base de datos.
     * Devuelve el Usuario autenticado o null si email/contraseña no son correctos.
     */
    public static function autenticar(string $email, string $passwordPlano): ?Usuario
    {
        $sql = 'SELECT id, email, password_hash, nombre, apellidos
                FROM usuarios WHERE email = :email LIMIT 1';

        $sentencia = Database::getConexion()->prepare($sql);
        $sentencia->execute([':email' => $email]);
        $fila = $sentencia->fetch();

        if ($fila === false) {
            return null;
        }

        if (!password_verify($passwordPlano, $fila['password_hash'])) {
            return null;
        }

        return new self(
            (string) $fila['email'],
            (string) $fila['nombre'],
            (string) $fila['apellidos'],
            (int) $fila['id']
        );
    }
}