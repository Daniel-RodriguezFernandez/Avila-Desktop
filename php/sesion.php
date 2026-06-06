<?php

class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(int $id, string $nombre): void
    {
        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario_nombre'] = $nombre;
    }

    public static function logout(): void
    {
        unset($_SESSION['usuario_id'], $_SESSION['usuario_nombre'], $_SESSION['carrito']);
    }

    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function getUsuarioId(): int
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    public static function getUsuarioNombre(): string
    {
        return (string) ($_SESSION['usuario_nombre'] ?? '');
    }


    /**
     * Guarda un valor de un solo uso (mensaje flash) que se leerá
     * en la siguiente petición tras una redirección.
     *
     * @param mixed $valor
     */
    public static function setFlash(string $clave, $valor): void
    {
        $_SESSION['flash'][$clave] = $valor;
    }

    /**
     * Lee y elimina un valor flash. Devuelve null si no existe.
     *
     * @return mixed
     */
    public static function getFlash(string $clave)
    {
        if (!isset($_SESSION['flash'][$clave])) {
            return null;
        }

        $valor = $_SESSION['flash'][$clave];
        unset($_SESSION['flash'][$clave]);

        return $valor;
    }
}