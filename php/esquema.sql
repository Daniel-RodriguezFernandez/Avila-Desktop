
CREATE DATABASE IF NOT EXISTS reservas_turismo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE reservas_turismo;

CREATE TABLE usuarios (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    apellidos       VARCHAR(100) NOT NULL,
    fecha_registro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tipos_recurso (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre  VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tipos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recursos_turisticos (
    id            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    tipo_id       INT UNSIGNED   NOT NULL,
    nombre        VARCHAR(150)   NOT NULL,
    descripcion   TEXT           NOT NULL,
    plazas        INT UNSIGNED   NOT NULL,
    fecha_inicio  DATETIME       NOT NULL,
    fecha_fin     DATETIME       NOT NULL,
    precio        DECIMAL(10,2)  NOT NULL,
    PRIMARY KEY (id),
    KEY idx_recursos_tipo (tipo_id),
    CONSTRAINT fk_recursos_tipo
        FOREIGN KEY (tipo_id) REFERENCES tipos_recurso (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE reservas (
    id             INT UNSIGNED                  NOT NULL AUTO_INCREMENT,
    usuario_id     INT UNSIGNED                  NOT NULL,
    fecha_reserva  DATETIME                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado         ENUM('confirmada','anulada')  NOT NULL DEFAULT 'confirmada',
    total          DECIMAL(10,2)                 NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_reservas_usuario (usuario_id),
    CONSTRAINT fk_reservas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lineas_reserva (
    id          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    reserva_id  INT UNSIGNED   NOT NULL,
    recurso_id  INT UNSIGNED   NOT NULL,
    num_plazas  INT UNSIGNED   NOT NULL DEFAULT 1,
    subtotal    DECIMAL(10,2)  NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lineas_reserva (reserva_id),
    KEY idx_lineas_recurso (recurso_id),
    CONSTRAINT fk_lineas_reserva
        FOREIGN KEY (reserva_id) REFERENCES reservas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lineas_recurso
        FOREIGN KEY (recurso_id) REFERENCES recursos_turisticos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;