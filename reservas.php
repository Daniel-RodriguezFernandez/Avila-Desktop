<?php
require_once __DIR__ . '/php/controladorReservas.php';

$controlador = new ControladorReservas();
$controlador->procesar();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Ávila-Reservas</title>
    <meta name="author" content="Daniel Rodríguez Fernández" />
    <meta name="description" content="Central de reservas de recursos turísticos de Ávila Desktop" />
    <meta name="keywords" content="Ávila, Avila, Avila Desktop, Ávila Desktop, Desktop, Reservas, Turismo" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="estilo/estilo.css" />
    <link rel="stylesheet" href="estilo/layout.css" />
    <link rel="icon" type="image/x-icon" href="multimedia/favicon.ico" />
</head>

<body>
    <header>
        <h1><a href="index.html">Ávila Desktop</a></h1>
    </header>
    <nav>
        <a href="index.html">Inicio</a>
        <a href="gastronomia.html">Gastronomía</a>
        <a href="rutas.html">Rutas</a>
        <a href="meteorologia.html">Meteorología</a>
        <a href="juego.html">Juego</a>
        <a href="reservas.php" class="activo">Reservas</a>
        <a href="ayuda.html">Ayuda</a>
    </nav>
    <main>
        <p>Estás en: <a href="index.html">Inicio</a> &rsaquo; Reservas</p>

        <?php if ($controlador->getFlashExito() !== ''): ?>
            <p role="status"><?= ControladorReservas::esc($controlador->getFlashExito()) ?></p>
        <?php endif; ?>
        <?php if ($controlador->getFlashError() !== ''): ?>
            <p role="alert"><?= ControladorReservas::esc($controlador->getFlashError()) ?></p>
        <?php endif; ?>

        <?php if ($controlador->estaAutenticado()): ?>

            <section>
                <h2>Tu sesión</h2>
                <p>Hola, <?= ControladorReservas::esc($controlador->getUsuarioNombre()) ?>.
                    Ya puedes reservar recursos turísticos.</p>
                <form action="reservas.php" method="post">
                    <input type="hidden" name="accion" value="logout" />
                    <button type="submit">Cerrar sesión</button>
                </form>
            </section>

        <?php else: ?>

            <section>
                <h2>Iniciar sesión</h2>
                <p>Accede con tu cuenta para realizar reservas:</p>

                <?php if (!empty($controlador->getLoginErrores())): ?>
                    <section>
                        <h4>No se ha podido iniciar sesión</h4>
                        <ul>
                            <?php foreach ($controlador->getLoginErrores() as $error): ?>
                                <li><?= ControladorReservas::esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <form action="reservas.php" method="post">
                    <input type="hidden" name="accion" value="login" />
                    <p>
                        <label for="login-email">Correo electrónico</label>
                        <input type="email" id="login-email" name="email" value="<?= $controlador->getLoginEmail() ?>"
                            autocomplete="email" maxlength="255" required />
                    </p>
                    <p>
                        <label for="login-password">Contraseña</label>
                        <input type="password" id="login-password" name="password" autocomplete="current-password"
                            required />
                    </p>
                    <p>
                        <button type="submit">Entrar</button>
                    </p>
                </form>
            </section>

            <section>
                <h2>Registro de Usuario</h2>
                <p>¿Aún no tienes cuenta? Créala rellenando el siguiente formulario:</p>

                <?php if ($controlador->getMensajeExito() !== ''): ?>
                    <p role="status"><?= ControladorReservas::esc($controlador->getMensajeExito()) ?></p>
                <?php endif; ?>

                <?php if ($controlador->tieneErrores()): ?>
                    <section>
                        <h4>Revisa los siguientes campos</h4>
                        <ul>
                            <?php foreach ($controlador->getErrores() as $error): ?>
                                <li><?= ControladorReservas::esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <form action="reservas.php" method="post">
                    <input type="hidden" name="accion" value="registrar" />

                    <p>
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" value="<?= $controlador->valor('nombre') ?>"
                            autocomplete="given-name" maxlength="100" required />
                    </p>

                    <p>
                        <label for="apellidos">Apellidos</label>
                        <input type="text" id="apellidos" name="apellidos" value="<?= $controlador->valor('apellidos') ?>"
                            autocomplete="family-name" maxlength="100" required />
                    </p>

                    <p>
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="<?= $controlador->valor('email') ?>"
                            autocomplete="email" maxlength="255" required />
                    </p>

                    <p>
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" minlength="8"
                            required />
                    </p>

                    <p>
                        <label for="password2">Repite la contraseña</label>
                        <input type="password" id="password2" name="password2" autocomplete="new-password" minlength="8"
                            required />
                    </p>

                    <p>
                        <button type="submit">Registrarse</button>
                    </p>
                </form>
            </section>

        <?php endif; ?>

        <section>
            <h2>Recursos turísticos disponibles</h2>
            <p>Estos son los recursos turísticos que puedes reservar:</p>

            <?php if (!$controlador->estaAutenticado()): ?>
                <p>Inicia sesión para poder reservar estos recursos.</p>
            <?php endif; ?>

            <?php if ($controlador->getErrorRecursos() !== ''): ?>
                <p role="alert"><?= ControladorReservas::esc($controlador->getErrorRecursos()) ?></p>
            <?php elseif (empty($controlador->getRecursos())): ?>
                <p>No hay recursos turísticos disponibles en este momento.</p>
            <?php else: ?>
                <?php foreach ($controlador->getRecursos() as $recurso): ?>
                    <article>
                        <h3><?= ControladorReservas::esc($recurso->getNombre()) ?></h3>
                        <p><?= ControladorReservas::esc($recurso->getDescripcion()) ?></p>
                        <dl>
                            <dt>Tipo</dt>
                            <dd><?= ControladorReservas::esc($recurso->getTipo()) ?></dd>

                            <dt>Plazas</dt>
                            <dd><?= $recurso->getPlazas() ?></dd>

                            <dt>Inicio</dt>
                            <dd><?= ControladorReservas::fecha($recurso->getFechaInicio()) ?></dd>

                            <dt>Fin</dt>
                            <dd><?= ControladorReservas::fecha($recurso->getFechaFin()) ?></dd>

                            <dt>Precio</dt>
                            <dd><?= ControladorReservas::precio($recurso->getPrecio()) ?> por plaza</dd>
                        </dl>

                        <?php if ($controlador->estaAutenticado()): ?>
                            <details>
                                <summary>Reservar</summary>
                                <form action="reservas.php" method="post">
                                    <input type="hidden" name="accion" value="agregar" />
                                    <input type="hidden" name="recurso_id" value="<?= $recurso->getId() ?>" />
                                    <p>
                                        <label for="plazas-<?= $recurso->getId() ?>">Número de plazas</label>
                                        <input type="number" id="plazas-<?= $recurso->getId() ?>" name="num_plazas" value="1"
                                            min="1" max="<?= $recurso->getPlazas() ?>" required />
                                    </p>
                                    <p>
                                        <button type="submit">Añadir al presupuesto</button>
                                    </p>
                                </form>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <?php if ($controlador->estaAutenticado()): ?>

            <section>
                <h2>Tu presupuesto</h2>

                <?php if (!$controlador->tienePresupuesto()): ?>
                    <p>Todavía no has añadido ningún recurso al presupuesto.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Plazas</th>
                                <th>Precio/plaza</th>
                                <th>Subtotal</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($controlador->getLineasPresupuesto() as $linea): ?>
                                <tr>
                                    <td><?= ControladorReservas::esc($linea['recurso']->getNombre()) ?></td>
                                    <td><?= $linea['plazas'] ?></td>
                                    <td><?= ControladorReservas::precio($linea['recurso']->getPrecio()) ?></td>
                                    <td><?= ControladorReservas::precio($linea['subtotal']) ?></td>
                                    <td>
                                        <form action="reservas.php" method="post">
                                            <input type="hidden" name="accion" value="quitar" />
                                            <input type="hidden" name="recurso_id" value="<?= $linea['recurso']->getId() ?>" />
                                            <button type="submit">Quitar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total</th>
                                <td><?= ControladorReservas::precio($controlador->getTotalPresupuesto()) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <form action="reservas.php" method="post">
                        <input type="hidden" name="accion" value="vaciar" />
                        <button type="submit">Vaciar presupuesto</button>
                    </form>
                    <form action="reservas.php" method="post">
                        <input type="hidden" name="accion" value="confirmar" />
                        <button type="submit">Confirmar reserva</button>
                    </form>
                <?php endif; ?>
            </section>

            <section>
                <h2>Mis reservas</h2>

                <?php if ($controlador->getErrorReservas() !== ''): ?>
                    <p role="alert"><?= ControladorReservas::esc($controlador->getErrorReservas()) ?></p>
                <?php elseif (empty($controlador->getMisReservas())): ?>
                    <p>Todavía no tienes ninguna reserva.</p>
                <?php else: ?>
                    <?php foreach ($controlador->getMisReservas() as $reserva): ?>
                        <article>
                            <h3>Reserva n.º <?= $reserva->getId() ?>
                                (<?= ControladorReservas::esc($reserva->getEstado()) ?>)</h3>
                            <p>
                                Fecha: <?= ControladorReservas::fecha($reserva->getFechaReserva()) ?> &middot;
                                Total: <?= ControladorReservas::precio($reserva->getTotal()) ?>
                            </p>
                            <ul>
                                <?php foreach ($reserva->getLineas() as $linea): ?>
                                    <li>
                                        <?= ControladorReservas::esc($linea->getRecursoNombre()) ?> &mdash;
                                        <?= $linea->getNumPlazas() ?> plaza(s) &mdash;
                                        <?= ControladorReservas::precio($linea->getSubtotal()) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($reserva->estaConfirmada()): ?>
                                <form action="reservas.php" method="post">
                                    <input type="hidden" name="accion" value="anular" />
                                    <input type="hidden" name="reserva_id" value="<?= $reserva->getId() ?>" />
                                    <button type="submit">Anular reserva</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        <?php endif; ?>
    </main>

</body>

</html>