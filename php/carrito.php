<?php

require_once __DIR__ . '/recursoTuristico.php';


class Carrito
{
    /**
     * Añade plazas de un recurso al presupuesto. Si el recurso ya estaba,
     * suma las plazas, sin superar nunca el límite total del recurso.
     */
    public static function agregar(int $recursoId, int $numPlazas): void
    {
        if ($numPlazas < 1) {
            return;
        }

        $recurso = RecursoTuristico::buscarPorId($recursoId);
        if ($recurso === null) {
            return;
        }

        $actual = (int) ($_SESSION['carrito'][$recursoId] ?? 0);
        $_SESSION['carrito'][$recursoId] = min($actual + $numPlazas, $recurso->getPlazas());
    }

    public static function quitar(int $recursoId): void
    {
        unset($_SESSION['carrito'][$recursoId]);
    }

    public static function vaciar(): void
    {
        unset($_SESSION['carrito']);
    }

    public static function estaVacio(): bool
    {
        return empty($_SESSION['carrito']);
    }


    /**
     * Reconstruye las líneas del presupuesto con datos actuales de la BD.
     *
     * @return array<int, array{recurso: RecursoTuristico, plazas: int, subtotal: float}>
     */
    public static function getLineas(): array
    {
        $lineas = [];

        foreach (($_SESSION['carrito'] ?? []) as $recursoId => $plazas) {
            $recurso = RecursoTuristico::buscarPorId((int) $recursoId);
            if ($recurso === null) {
                // El recurso ya no existe: lo descartamos del presupuesto.
                continue;
            }

            $plazas = (int) $plazas;
            $lineas[] = [
                'recurso' => $recurso,
                'plazas' => $plazas,
                'subtotal' => $recurso->getPrecio() * $plazas,
            ];
        }

        return $lineas;
    }

    public static function getTotal(): float
    {
        $total = 0.0;
        foreach (self::getLineas() as $linea) {
            $total += $linea['subtotal'];
        }

        return $total;
    }
}