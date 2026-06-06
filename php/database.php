<?php
class Database
{
    private const HOST = 'localhost';
    private const NOMBRE_BD = 'reservas_turismo';
    private const USUARIO = 'DBUSER2026';
    private const PASSWORD = 'DBPWD2026';
    private const CHARSET = 'utf8mb4';

    private static ?PDO $instancia = null;


    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getConexion(): PDO
    {
        if (self::$instancia === null) {
            $dsn = 'mysql:host=' . self::HOST
                . ';dbname=' . self::NOMBRE_BD
                . ';charset=' . self::CHARSET;

            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$instancia = new PDO($dsn, self::USUARIO, self::PASSWORD, $opciones);
        }

        return self::$instancia;
    }
}