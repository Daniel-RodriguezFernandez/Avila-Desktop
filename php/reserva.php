<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/carrito.php';


class LineaReserva
{
    private string $recursoNombre;
    private int $numPlazas;
    private float $subtotal;

    public function __construct(string $recursoNombre, int $numPlazas, float $subtotal)
    {
        $this->recursoNombre = $recursoNombre;
        $this->numPlazas = $numPlazas;
        $this->subtotal = $subtotal;
    }

    public function getRecursoNombre(): string
    {
        return $this->recursoNombre;
    }

    public function getNumPlazas(): int
    {
        return $this->numPlazas;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }
}


class Reserva
{
    private int $id;
    private string $fechaReserva;
    private string $estado;
    private float $total;

    /** @var LineaReserva[] */
    private array $lineas;

    public function __construct(
        int $id,
        string $fechaReserva,
        string $estado,
        float $total,
        array $lineas = []
    ) {
        $this->id = $id;
        $this->fechaReserva = $fechaReserva;
        $this->estado = $estado;
        $this->total = $total;
        $this->lineas = $lineas;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFechaReserva(): string
    {
        return $this->fechaReserva;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    /** @return LineaReserva[] */
    public function getLineas(): array
    {
        return $this->lineas;
    }

    public function estaConfirmada(): bool
    {
        return $this->estado === 'confirmada';
    }


    /**
     * Crea una reserva (cabecera + líneas) a partir del presupuesto en sesión.
     * Toda la operación va en una transacción: o se guarda completa o no se guarda nada.
     * Vacía el carrito al terminar y devuelve el id de la reserva creada.
     *
     * @throws RuntimeException si el presupuesto está vacío.
     * @throws PDOException     si falla la base de datos.
     */
    public static function crearDesdeCarrito(int $usuarioId): int
    {
        $lineas = Carrito::getLineas();
        if (empty($lineas)) {
            throw new RuntimeException('El presupuesto está vacío.');
        }

        $total = Carrito::getTotal();
        $conexion = Database::getConexion();

        try {
            $conexion->beginTransaction();

            $sentenciaReserva = $conexion->prepare(
                'INSERT INTO reservas (usuario_id, total) VALUES (:usuario, :total)'
            );
            $sentenciaReserva->execute([
                ':usuario' => $usuarioId,
                ':total' => $total,
            ]);

            $reservaId = (int) $conexion->lastInsertId();

            $sentenciaLinea = $conexion->prepare(
                'INSERT INTO lineas_reserva (reserva_id, recurso_id, num_plazas, subtotal)
                 VALUES (:reserva, :recurso, :plazas, :subtotal)'
            );

            foreach ($lineas as $linea) {
                $sentenciaLinea->execute([
                    ':reserva' => $reservaId,
                    ':recurso' => $linea['recurso']->getId(),
                    ':plazas' => $linea['plazas'],
                    ':subtotal' => $linea['subtotal'],
                ]);
            }

            $conexion->commit();
        } catch (PDOException $e) {
            $conexion->rollBack();
            throw $e;
        }

        Carrito::vaciar();

        return $reservaId;
    }


    /**
     * Devuelve todas las reservas de un usuario, cada una con sus líneas.
     *
     * @return Reserva[]
     */
    public static function listarPorUsuario(int $usuarioId): array
    {
        $conexion = Database::getConexion();

        $sentenciaReservas = $conexion->prepare(
            'SELECT id, fecha_reserva, estado, total
             FROM reservas
             WHERE usuario_id = :usuario
             ORDER BY fecha_reserva DESC, id DESC'
        );
        $sentenciaReservas->execute([':usuario' => $usuarioId]);
        $filasReservas = $sentenciaReservas->fetchAll();

        if (empty($filasReservas)) {
            return [];
        }

        $sentenciaLineas = $conexion->prepare(
            'SELECT lr.num_plazas, lr.subtotal, rt.nombre AS recurso
             FROM lineas_reserva lr
             INNER JOIN recursos_turisticos rt ON rt.id = lr.recurso_id
             WHERE lr.reserva_id = :reserva
             ORDER BY lr.id ASC'
        );

        $reservas = [];
        foreach ($filasReservas as $fila) {
            $sentenciaLineas->execute([':reserva' => (int) $fila['id']]);

            $lineas = [];
            foreach ($sentenciaLineas->fetchAll() as $filaLinea) {
                $lineas[] = new LineaReserva(
                    (string) $filaLinea['recurso'],
                    (int) $filaLinea['num_plazas'],
                    (float) $filaLinea['subtotal']
                );
            }

            $reservas[] = new self(
                (int) $fila['id'],
                (string) $fila['fecha_reserva'],
                (string) $fila['estado'],
                (float) $fila['total'],
                $lineas
            );
        }

        return $reservas;
    }


    /**
     * Anula una reserva confirmada que pertenezca al usuario indicado.
     * Devuelve true si se anuló alguna fila.
     */
    public static function anular(int $reservaId, int $usuarioId): bool
    {
        $sql = 'UPDATE reservas
                SET estado = :anulada
                WHERE id = :id AND usuario_id = :usuario AND estado = :confirmada';

        $sentencia = Database::getConexion()->prepare($sql);
        $sentencia->execute([
            ':anulada' => 'anulada',
            ':id' => $reservaId,
            ':usuario' => $usuarioId,
            ':confirmada' => 'confirmada',
        ]);

        return $sentencia->rowCount() > 0;
    }
}