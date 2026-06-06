<?php
mysqli_report(MYSQLI_REPORT_OFF);

class Configuracion
{
    protected $server = "localhost";
    protected $user = "DBUSER2026";
    protected $pass = "DBPWD2026";
    protected $dbname = "reservas_turismo";

    protected $conn;

    protected $tablas = [
        "tipos_recurso",
        "usuarios",
        "recursos_turisticos",
        "reservas",
        "lineas_reserva",
    ];

    public function __construct()
    {
        $this->conn = @new mysqli($this->server, $this->user, $this->pass);
        if ($this->conn->connect_error) {
            echo "<p>Conexión fallida: " . htmlspecialchars($this->conn->connect_error) . "</p>";
            return;
        }

        if (!$this->safeSelectDB()) {
            if ($this->crearBD()) {
                if (!$this->safeSelectDB()) {
                    echo "<p>Error: la base de datos se creó pero no se pudo seleccionar.</p>";
                    return;
                }
            } else {
                echo "<p>No se pudo crear la base de datos desde el fichero SQL.</p>";
                return;
            }
        }
        $this->conn->set_charset("utf8mb4");
    }

    private function safeSelectDB(): bool
    {
        try {
            return @$this->conn->select_db($this->dbname);
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    public function crearBD(): bool
    {
        $tempConn = @new mysqli($this->server, $this->user, $this->pass);
        if ($tempConn->connect_error) {
            echo "<p>Error conexión temporal: " . htmlspecialchars($tempConn->connect_error) . "</p>";
            return false;
        }
        $tempConn->set_charset("utf8mb4");

        $path = __DIR__ . DIRECTORY_SEPARATOR . "esquema.sql";
        if (!is_file($path) || !is_readable($path)) {
            echo "<p>No se encontró esquema.sql en $path</p>";
            $tempConn->close();
            return false;
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            echo "<p>Error leyendo el archivo SQL</p>";
            $tempConn->close();
            return false;
        }

        $ok = true;
        foreach (explode(";", $sql) as $sentencia) {
            $sentencia = trim($sentencia);
            if ($sentencia === "") {
                continue;
            }
            if (preg_match('/^(CREATE\s+USER|GRANT|FLUSH\s+PRIVILEGES)/i', $sentencia)) {
                continue;
            }
            if (!$tempConn->query($sentencia)) {
                if (in_array($tempConn->errno, [1007, 1050, 1060, 1061, 1062], true)) {
                    continue;
                }
                echo "<p>Error al ejecutar SQL: " . htmlspecialchars($tempConn->error) . "</p>";
                $ok = false;
                break;
            }
        }

        $tempConn->close();
        return $ok;
    }

    public function reiniciarBD(): bool
    {
        if (!($this->conn instanceof mysqli)) {
            echo "<p>Error: sin conexión activa.</p>";
            return false;
        }

        $this->conn->begin_transaction();
        try {
            $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
            foreach ($this->tablas as $t) {
                $t_esc = $this->conn->real_escape_string($t);
                if (!$this->conn->query("TRUNCATE TABLE `$t_esc`")) {
                    throw new Exception("Error truncando $t: " . $this->conn->error);
                }
            }
            $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            return false;
        }
    }

    public function eliminarBD(): bool
    {
        if ($this->conn instanceof mysqli) {
            @$this->conn->close();
            $this->conn = null;
        }

        $tempConn = @new mysqli($this->server, $this->user, $this->pass);
        if ($tempConn->connect_error) {
            return false;
        }

        $res = $tempConn->query("DROP DATABASE IF EXISTS `$this->dbname`");
        if (!$res) {
            echo "<p>Error al eliminar la base de datos: " . htmlspecialchars($tempConn->error) . "</p>";
        }
        $tempConn->close();
        return (bool) $res;
    }

    private function bindParams(mysqli_stmt $stmt, string $types, array $vals): void
    {
        $params = [$types];
        foreach ($vals as $k => $v) {
            $params[] = &$vals[$k];
        }
        call_user_func_array([$stmt, "bind_param"], $params);
    }

    public function inicializarBD(): bool
    {
        if (!($this->conn instanceof mysqli)) {
            echo "<p>Error: sin conexión activa.</p>";
            return false;
        }

        $seed = [
            [
                "file" => "tipo-recurso.csv",
                "sql" => "INSERT INTO tipos_recurso (id, nombre) VALUES (?, ?)",
                "types" => "is",
                "cols" => 2,
            ],
            [
                "file" => "usuarios.csv",
                "sql" => "INSERT INTO usuarios (id, email, password_hash, nombre, apellidos) VALUES (?, ?, ?, ?, ?)",
                "types" => "issss",
                "cols" => 5,
            ],
            [
                "file" => "recursos-turisticos.csv",
                "sql" => "INSERT INTO recursos_turisticos (id, tipo_id, nombre, descripcion, plazas, fecha_inicio, fecha_fin, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "types" => "iississd",
                "cols" => 8,
            ],
            [
                "file" => "reservas.csv",
                "sql" => "INSERT INTO reservas (id, usuario_id, fecha_reserva, estado, total) VALUES (?, ?, ?, ?, ?)",
                "types" => "iissd",
                "cols" => 5,
            ],
            [
                "file" => "lineas-reserva.csv",
                "sql" => "INSERT INTO lineas_reserva (id, reserva_id, recurso_id, num_plazas, subtotal) VALUES (?, ?, ?, ?, ?)",
                "types" => "iiiid",
                "cols" => 5,
            ],
        ];

        if (!$this->reiniciarBD()) {
            return false;
        }

        $this->conn->begin_transaction();
        try {
            $this->conn->query("SET FOREIGN_KEY_CHECKS=0");

            foreach ($seed as $t) {
                $path = __DIR__ . DIRECTORY_SEPARATOR . $t["file"];
                if (!is_file($path) || !is_readable($path)) {
                    throw new Exception("No se encontró el CSV: " . $t["file"]);
                }

                $handle = fopen($path, "r");
                if (!$handle) {
                    throw new Exception("No se pudo abrir el CSV: " . $t["file"]);
                }

                $stmt = $this->conn->prepare($t["sql"]);
                if (!$stmt) {
                    fclose($handle);
                    throw new Exception("Error preparando INSERT para " . $t["file"] . ": " . $this->conn->error);
                }

                $primera = true;
                while (($data = fgetcsv($handle, 0, ",", '"')) !== false) {
                    if ($primera) {
                        $primera = false;
                        continue;
                    }
                    if (count($data) === 1 && trim((string) $data[0]) === "") {
                        continue;
                    }
                    if (count($data) < $t["cols"]) {
                        continue;
                    }

                    $vals = array_slice($data, 0, $t["cols"]);
                    $this->bindParams($stmt, $t["types"], $vals);
                    if (!$stmt->execute()) {
                        $stmt->close();
                        fclose($handle);
                        throw new Exception("Error insertando en " . $t["file"] . ": " . $stmt->error);
                    }
                }

                $stmt->close();
                fclose($handle);
            }

            $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            return false;
        }
    }

    public function exportarCSV(): bool
    {
        if (headers_sent()) {
            echo "<p>No se pueden enviar cabeceras CSV: ya se envió contenido.</p>";
            return false;
        }
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reservas_turismo.csv"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            echo "<p>No se pudo abrir el stream de salida.</p>";
            return false;
        }

        foreach ($this->tablas as $tabla) {
            $result = $this->conn->query("SELECT * FROM `$tabla`");
            if (!$result) {
                fputcsv($output, ["<error en la consulta de $tabla: " . $this->conn->error . ">"]);
                continue;
            }
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, array_merge([$tabla], array_values($row)), ",", '"');
            }
            $result->free();
        }

        fclose($output);
        return true;
    }

    public function importarCSV($file): bool
    {
        if (!$file || $file["error"] !== UPLOAD_ERR_OK) {
            echo "<p>Error subiendo el archivo CSV.</p>";
            return false;
        }

        $handle = fopen($file["tmp_name"], "r");
        if (!$handle) {
            echo "<p>No se pudo abrir el archivo CSV.</p>";
            return false;
        }

        $bom = pack('H*', 'EFBBBF');
        $firstLine = fgets($handle);
        if ($firstLine !== false && substr($firstLine, 0, 3) === $bom) {
            $tmp = tmpfile();
            fwrite($tmp, substr($firstLine, 3));
            while (!feof($handle)) {
                fwrite($tmp, fread($handle, 8192));
            }
            fclose($handle);
            rewind($tmp);
            $handle = $tmp;
        } else {
            rewind($handle);
        }

        if (!$this->reiniciarBD()) {
            fclose($handle);
            return false;
        }

        $mapa = [
            "tipos_recurso" => [
                "sql" => "INSERT INTO tipos_recurso (id, nombre) VALUES (?, ?)",
                "types" => "is",
                "cols" => 2,
            ],
            "usuarios" => [
                "sql" => "INSERT INTO usuarios (id, email, password_hash, nombre, apellidos, fecha_registro) VALUES (?, ?, ?, ?, ?, ?)",
                "types" => "isssss",
                "cols" => 6,
            ],
            "recursos_turisticos" => [
                "sql" => "INSERT INTO recursos_turisticos (id, tipo_id, nombre, descripcion, plazas, fecha_inicio, fecha_fin, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "types" => "iississd",
                "cols" => 8,
            ],
            "reservas" => [
                "sql" => "INSERT INTO reservas (id, usuario_id, fecha_reserva, estado, total) VALUES (?, ?, ?, ?, ?)",
                "types" => "iissd",
                "cols" => 5,
            ],
            "lineas_reserva" => [
                "sql" => "INSERT INTO lineas_reserva (id, reserva_id, recurso_id, num_plazas, subtotal) VALUES (?, ?, ?, ?, ?)",
                "types" => "iiiid",
                "cols" => 5,
            ],
        ];

        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");

        while (($data = fgetcsv($handle, 0, ",", '"')) !== false) {
            if (empty($data[0])) {
                continue;
            }
            $tabla = $data[0];
            if (!isset($mapa[$tabla])) {
                continue;
            }
            $def = $mapa[$tabla];
            $vals = array_slice($data, 1, $def["cols"]);
            if (count($vals) < $def["cols"]) {
                continue;
            }

            try {
                $stmt = $this->conn->prepare($def["sql"]);
                if (!$stmt) {
                    continue;
                }
                $this->bindParams($stmt, $def["types"], $vals);
                $stmt->execute();
                $stmt->close();
            } catch (Exception $e) {
                echo "<p>Error al insertar datos en $tabla: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }

        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        fclose($handle);
        return true;
    }


    public function __destruct()
    {
        if ($this->conn instanceof mysqli) {
            @$this->conn->close();
            $this->conn = null;
        }
    }
}

$config = new Configuracion();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["crear"])) {
        $msg = $config->crearBD() ? "Estructura de la base de datos creada." : "Error al crear la estructura.";
    } elseif (isset($_POST["inicializar"])) {
        $msg = $config->inicializarBD() ? "Datos de ejemplo cargados correctamente." : "Error al cargar los datos de ejemplo.";
    } elseif (isset($_POST["reiniciar"])) {
        $msg = $config->reiniciarBD() ? "Base de datos reiniciada (tablas vaciadas)." : "Error al reiniciar.";
    } elseif (isset($_POST["eliminar"])) {
        $msg = $config->eliminarBD() ? "Base de datos eliminada." : "Error al eliminar.";
    } elseif (isset($_POST["exportar"])) {
        $config->exportarCSV();
        exit;
    } elseif (isset($_POST["importar"])) {
        $msg = $config->importarCSV($_FILES["csvfile"] ?? null)
            ? "Importación completada con éxito."
            : "Error al importar los datos del archivo CSV. Asegúrate de adjuntar un archivo válido.";
    }
}
?>
<!DOCTYPE HTML>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Ávila</title>
    <meta name="author" content="Daniel Rodríguez Fernández" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="Página de configuración de la base de datos de la central de reservas de recursos turísticos" />
    <meta name="keywords" content="reservas, turismo, recursos turísticos, base de datos" />
    <link rel="stylesheet" href="../estilo/estilo.css" />
    <link rel="stylesheet" href="../estilo/layout.css" />
    <link rel="icon" type="image/x-icon" href="../multimedia/favicon.ico" />
</head>

<body>
    <h1>Configuración de la base de datos</h1>
    <main>
        <?php if (isset($msg))
            echo "<p>$msg</p>"; ?>

        <form method="post" enctype="multipart/form-data">
            <button name="crear" type="submit">Crear estructura (ejecutar esquema.sql)</button>
            <button name="inicializar" type="submit">Inicializar datos de ejemplo (cargar CSV)</button>
            <button name="reiniciar" type="submit">Reiniciar base de datos (vaciar tablas)</button>
            <button name="eliminar" type="submit">Eliminar base de datos</button>
            <button name="exportar" type="submit">Exportar datos (.csv)</button>

            <label for="csvfile">Importar datos desde CSV:</label>
            <input type="file" id="csvfile" name="csvfile" accept=".csv" />
            <button name="importar" type="submit">Importar CSV</button>
        </form>
    </main>
</body>

</html>