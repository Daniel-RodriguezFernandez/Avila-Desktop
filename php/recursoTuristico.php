<?php

require_once __DIR__ . '/database.php';


class RecursoTuristico
{
    private int $id;
    private string $tipo;
    private string $nombre;
    private string $descripcion;
    private int $plazas;
    private string $fechaInicio;
    private string $fechaFin;
    private float $precio;


    public function __construct(
        int $id,
        string $tipo,
        string $nombre,
        string $descripcion,
        int $plazas,
        string $fechaInicio,
        string $fechaFin,
        float $precio
    ) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->plazas = $plazas;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->precio = $precio;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getPlazas(): int
    {
        return $this->plazas;
    }

    public function getFechaInicio(): string
    {
        return $this->fechaInicio;
    }

    public function getFechaFin(): string
    {
        return $this->fechaFin;
    }

    public function getPrecio(): float
    {
        return $this->precio;
    }


    /**
     * Devuelve todos los recursos turísticos disponibles, junto con el
     * nombre de su tipo, ordenados por fecha de inicio.
     *
     * @return RecursoTuristico[]
     */
    public static function listarTodos(): array
    {
        $sql = 'SELECT r.id, t.nombre AS tipo, r.nombre, r.descripcion,
                       r.plazas, r.fecha_inicio, r.fecha_fin, r.precio
                FROM recursos_turisticos r
                INNER JOIN tipos_recurso t ON t.id = r.tipo_id
                ORDER BY r.fecha_inicio ASC, r.nombre ASC';

        $sentencia = Database::getConexion()->query($sql);

        $recursos = [];
        while ($fila = $sentencia->fetch()) {
            $recursos[] = new self(
                (int) $fila['id'],
                (string) $fila['tipo'],
                (string) $fila['nombre'],
                (string) $fila['descripcion'],
                (int) $fila['plazas'],
                (string) $fila['fecha_inicio'],
                (string) $fila['fecha_fin'],
                (float) $fila['precio']
            );
        }

        return $recursos;
    }


    /**
     * Busca un recurso turístico por su identificador.
     * Devuelve null si no existe.
     */
    public static function buscarPorId(int $id): ?RecursoTuristico
    {
        $sql = 'SELECT r.id, t.nombre AS tipo, r.nombre, r.descripcion,
                       r.plazas, r.fecha_inicio, r.fecha_fin, r.precio
                FROM recursos_turisticos r
                INNER JOIN tipos_recurso t ON t.id = r.tipo_id
                WHERE r.id = :id
                LIMIT 1';

        $sentencia = Database::getConexion()->prepare($sql);
        $sentencia->execute([':id' => $id]);
        $fila = $sentencia->fetch();

        if ($fila === false) {
            return null;
        }

        return new self(
            (int) $fila['id'],
            (string) $fila['tipo'],
            (string) $fila['nombre'],
            (string) $fila['descripcion'],
            (int) $fila['plazas'],
            (string) $fila['fecha_inicio'],
            (string) $fila['fecha_fin'],
            (float) $fila['precio']
        );
    }
}