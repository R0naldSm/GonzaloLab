-- ================================================================
--  GonzaloLabs — Base de Datos Unificada v3.0
--  MySQL 8.0+ · InnoDB · utf8mb4
--
--  Incluye: Tablas + Índices + Constraints + Triggers +
--           Procedimientos Almacenados + Eventos + Vistas +
--           Datos iniciales
--
--  MODELO DE SEGURIDAD:
--  ① PII cifrado AES-256 en VARBINARY (nunca VARCHAR)
--  ② cedula_hash SHA2-256 para búsqueda sin descifrar
--  ③ Tokens de 64 hex-chars (32 bytes random desde PHP)
--  ④ CHECK constraints en todos los campos enum-like
--  ⑤ Tabla auditoria inmutable (triggers bloquean UPDATE/DELETE)
--  ⑥ Triggers de auditoría automática en tablas críticas
--  ⑦ Event Scheduler limpia tokens expirados cada hora
--  ⑧ Usuario de BD de mínimo privilegio (sin DDL en prod)
--  ⑨ STRICT_TRANS_TABLES — sin valores truncados silenciosos
--  ⑩ SPs usan EXIT HANDLER + ROLLBACK en toda operación
-- ================================================================

-- ──────────────────────────────────────────────────────────────
--  SECCIÓN 0 — CONFIGURACIÓN DE SESIÓN
-- ──────────────────────────────────────────────────────────────
SET @OLD_UNIQUE_CHECKS      = @@UNIQUE_CHECKS,      UNIQUE_CHECKS      = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE           = @@SQL_MODE;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS gonzalolabs_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gonzalolabs_db;


-- ──────────────────────────────────────────────────────────────
--  SECCIÓN 1 — USUARIO DE APLICACIÓN (mínimo privilegio)
-- ──────────────────────────────────────────────────────────────
/*
   Ejecutar como root antes del primer despliegue.
   Reemplazar 'CLAVE_FUERTE' por una cadena generada con:
       openssl rand -base64 32

   CREATE USER IF NOT EXISTS 'gonzalolabs_app'@'localhost'
       IDENTIFIED WITH caching_sha2_password BY 'CLAVE_FUERTE';

   GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE
       ON gonzalolabs_db.*
       TO 'gonzalolabs_app'@'localhost';

   -- Quitar DELETE en auditoría a nivel de usuario también
   REVOKE DELETE ON gonzalolabs_db.auditoria
       FROM 'gonzalolabs_app'@'localhost';

   FLUSH PRIVILEGES;
*/


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 2 — TABLAS
-- ══════════════════════════════════════════════════════════════

-- ── 2.1 USUARIOS ─────────────────────────────────────────────
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id_usuario          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    username            VARCHAR(30)     NOT NULL,
    email               VARCHAR(180)    NOT NULL,
    password_hash       VARCHAR(255)    NOT NULL   COMMENT 'bcrypt — Security::hashPassword()',
    nombre_completo     VARBINARY(255)  NULL       COMMENT 'AES_ENCRYPT(nombre, key)',
    cedula              VARBINARY(64)   NULL       COMMENT 'AES_ENCRYPT(cedula, key)',
    rol                 VARCHAR(20)     NOT NULL   DEFAULT 'paciente',
    estado              VARCHAR(20)     NOT NULL   DEFAULT 'activo',
    intentos_fallidos   TINYINT UNSIGNED NOT NULL  DEFAULT 0,
    ultimo_acceso       DATETIME        NULL,
    token_recuperacion  CHAR(64)        NULL       UNIQUE COMMENT 'bin2hex(random_bytes(32))',
    token_expiracion    DATETIME        NULL,
    creado_por          INT UNSIGNED    NULL,
    eliminado           TINYINT(1)      NOT NULL   DEFAULT 0,
    fecha_creacion      DATETIME        NOT NULL   DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion  DATETIME        NOT NULL   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_usr_rol    CHECK (rol    IN ('administrador','analistaL','medico','paciente')),
    CONSTRAINT chk_usr_estado CHECK (estado IN ('activo','inactivo','bloqueado')),
    CONSTRAINT chk_usr_intent CHECK (intentos_fallidos <= 20),

    UNIQUE KEY uq_username  (username),
    UNIQUE KEY uq_email     (email),
    INDEX idx_usr_rol       (rol),
    INDEX idx_usr_estado    (estado),
    INDEX idx_usr_eliminado (eliminado),
    INDEX idx_usr_creador   (creado_por),

    FOREIGN KEY fk_usr_creado_por (creado_por)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuentas de acceso. PII cifrado AES-256.';


-- ── 2.2 PACIENTES ─────────────────────────────────────────────
DROP TABLE IF EXISTS pacientes;
CREATE TABLE pacientes (
    id_paciente         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    cedula              VARBINARY(64)   NOT NULL   COMMENT 'AES_ENCRYPT(cedula, key)',
    cedula_hash         CHAR(64)        NOT NULL   COMMENT 'SHA2(cedula,256) — para búsqueda exacta',
    nombres             VARBINARY(255)  NOT NULL   COMMENT 'AES_ENCRYPT(nombres, key)',
    apellidos           VARBINARY(255)  NOT NULL   COMMENT 'AES_ENCRYPT(apellidos, key)',
    telefono            VARBINARY(128)  NULL       COMMENT 'AES_ENCRYPT(telefono, key)',
    email               VARBINARY(255)  NULL       COMMENT 'AES_ENCRYPT(email, key)',
    fecha_nacimiento    DATE            NULL,
    genero              CHAR(1)         NULL       COMMENT 'M | F | O',
    tipo_sangre         VARCHAR(5)      NULL,
    estado_civil        VARCHAR(15)     NULL,
    direccion           TEXT            NULL,
    alergias            TEXT            NULL,
    observaciones       TEXT            NULL,
    registrado_por      INT UNSIGNED    NULL,
    eliminado           TINYINT(1)      NOT NULL   DEFAULT 0,
    fecha_registro      DATETIME        NOT NULL   DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion  DATETIME        NOT NULL   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_pac_genero CHECK (genero IN ('M','F','O') OR genero IS NULL),

    UNIQUE KEY uq_cedula_hash  (cedula_hash),
    INDEX idx_pac_registrador  (registrado_por),
    INDEX idx_pac_eliminado    (eliminado),
    INDEX idx_pac_nacimiento   (fecha_nacimiento),

    FOREIGN KEY fk_pac_registrado_por (registrado_por)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pacientes. PII cifrado AES-256. cedula_hash para búsqueda segura.';


-- ── 2.3 CATEGORÍAS DE EXÁMENES ───────────────────────────────
DROP TABLE IF EXISTS categorias_examenes;
CREATE TABLE categorias_examenes (
    id_categoria        INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(100)    NOT NULL,
    descripcion         TEXT            NULL,
    color_hex           CHAR(7)         NOT NULL   DEFAULT '#06b6d4',
    icono               VARCHAR(50)     NULL       COMMENT 'Bootstrap Icons class',
    orden_visualizacion TINYINT UNSIGNED NOT NULL  DEFAULT 0,
    eliminado           TINYINT(1)      NOT NULL   DEFAULT 0,

    CONSTRAINT chk_cat_color CHECK (color_hex REGEXP '^#[0-9A-Fa-f]{6}$'),
    UNIQUE KEY uq_cat_nombre (nombre),
    INDEX idx_cat_orden (orden_visualizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.4 EXÁMENES ─────────────────────────────────────────────
DROP TABLE IF EXISTS examenes;
CREATE TABLE examenes (
    id_examen               INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_categoria            INT UNSIGNED    NOT NULL,
    codigo                  VARCHAR(20)     NOT NULL,
    nombre                  VARCHAR(200)    NOT NULL,
    descripcion             TEXT            NULL,
    precio                  DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    tiempo_entrega_min      SMALLINT UNSIGNED NULL,
    tiempo_entrega_dias     TINYINT UNSIGNED  NULL,
    requiere_ayuno          TINYINT(1)      NOT NULL  DEFAULT 0,
    instrucciones_paciente  TEXT            NULL,
    metodo_analisis         VARCHAR(200)    NULL,
    activo                  TINYINT(1)      NOT NULL  DEFAULT 1,
    eliminado               TINYINT(1)      NOT NULL  DEFAULT 0,
    fecha_creacion          DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion      DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_exa_precio CHECK (precio >= 0),
    UNIQUE KEY uq_examen_codigo (codigo),
    INDEX idx_exa_categoria (id_categoria),
    INDEX idx_exa_activo    (activo, eliminado),

    FOREIGN KEY fk_exa_categoria (id_categoria)
        REFERENCES categorias_examenes(id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.5 PARÁMETROS DE EXAMEN ─────────────────────────────────
DROP TABLE IF EXISTS parametros_examen;
CREATE TABLE parametros_examen (
    id_parametro        INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_examen           INT UNSIGNED    NOT NULL,
    nombre_parametro    VARCHAR(200)    NOT NULL,
    codigo_parametro    VARCHAR(20)     NULL       COMMENT 'Código corto para importación CSV',
    unidad_medida       VARCHAR(50)     NULL,
    tipo_dato           VARCHAR(25)     NOT NULL   DEFAULT 'numerico',
    opciones_seleccion  JSON            NULL       COMMENT 'Array de opciones si tipo_dato=seleccion',
    descripcion         VARCHAR(500)    NULL,
    orden_visualizacion SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    activo              TINYINT(1)      NOT NULL   DEFAULT 1,
    eliminado           TINYINT(1)      NOT NULL   DEFAULT 0,

    CONSTRAINT chk_param_tipo CHECK (
        tipo_dato IN ('numerico','texto','seleccion','booleano','positivo_negativo')
    ),
    INDEX idx_param_examen  (id_examen),
    INDEX idx_param_activo  (activo, eliminado),
    INDEX idx_param_codigo  (codigo_parametro),

    FOREIGN KEY fk_param_examen (id_examen)
        REFERENCES examenes(id_examen) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.6 VALORES DE REFERENCIA ────────────────────────────────
DROP TABLE IF EXISTS valores_referencia;
CREATE TABLE valores_referencia (
    id_referencia       INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_parametro        INT UNSIGNED    NOT NULL,
    genero              CHAR(6)         NOT NULL   DEFAULT 'Ambos',
    edad_min            TINYINT UNSIGNED NULL,
    edad_max            TINYINT UNSIGNED NULL,
    valor_min_normal    DECIMAL(15,4)   NULL,
    valor_max_normal    DECIMAL(15,4)   NULL,
    valor_min_critico   DECIMAL(15,4)   NULL,
    valor_max_critico   DECIMAL(15,4)   NULL,
    descripcion_rango   VARCHAR(500)    NULL,
    activo              TINYINT(1)      NOT NULL   DEFAULT 1,

    CONSTRAINT chk_vref_genero   CHECK (genero IN ('M','F','Ambos')),
    CONSTRAINT chk_vref_edades   CHECK (edad_min IS NULL OR edad_max IS NULL OR edad_min <= edad_max),
    CONSTRAINT chk_vref_normales CHECK (
        valor_min_normal IS NULL OR valor_max_normal IS NULL OR valor_min_normal <= valor_max_normal
    ),
    INDEX idx_vref_parametro (id_parametro, activo),
    INDEX idx_vref_genero    (genero),

    FOREIGN KEY fk_vref_parametro (id_parametro)
        REFERENCES parametros_examen(id_parametro) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.7 ÓRDENES ──────────────────────────────────────────────
DROP TABLE IF EXISTS ordenes;
CREATE TABLE ordenes (
    id_orden                    INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    numero_orden                VARCHAR(25)     NOT NULL,
    id_paciente                 INT UNSIGNED    NOT NULL,
    id_medico                   INT UNSIGNED    NULL,
    fecha_orden                 DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    fecha_toma_muestra          DATETIME        NULL,
    estado                      VARCHAR(25)     NOT NULL  DEFAULT 'creada',
    tipo_atencion               VARCHAR(15)     NOT NULL  DEFAULT 'normal',
    observaciones_recibo        TEXT            NULL,
    observaciones_laboratorista TEXT            NULL,
    observaciones_resultados    TEXT            NULL,
    total_pagar                 DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    estado_pago                 VARCHAR(15)     NOT NULL  DEFAULT 'pendiente',
    metodo_pago                 VARCHAR(30)     NULL,
    creado_por                  INT UNSIGNED    NULL,
    modificado_por              INT UNSIGNED    NULL,
    eliminado                   TINYINT(1)      NOT NULL  DEFAULT 0,
    fecha_creacion              DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion          DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_ord_estado      CHECK (estado IN ('creada','en_proceso','resultados_cargados','validada','publicada','cancelada')),
    CONSTRAINT chk_ord_atencion    CHECK (tipo_atencion IN ('normal','urgencia','control')),
    CONSTRAINT chk_ord_estado_pago CHECK (estado_pago IN ('pendiente','pagado','anulado')),
    CONSTRAINT chk_ord_total       CHECK (total_pagar >= 0),

    UNIQUE KEY uq_numero_orden  (numero_orden),
    INDEX idx_ord_paciente      (id_paciente),
    INDEX idx_ord_medico        (id_medico),
    INDEX idx_ord_estado        (estado, eliminado),
    INDEX idx_ord_fecha         (fecha_orden),
    INDEX idx_ord_estado_pago   (estado_pago),
    INDEX idx_ord_creador       (creado_por),

    FOREIGN KEY fk_ord_paciente   (id_paciente)    REFERENCES pacientes(id_paciente)  ON DELETE RESTRICT  ON UPDATE CASCADE,
    FOREIGN KEY fk_ord_medico     (id_medico)      REFERENCES usuarios(id_usuario)    ON DELETE SET NULL  ON UPDATE CASCADE,
    FOREIGN KEY fk_ord_creado_por (creado_por)     REFERENCES usuarios(id_usuario)    ON DELETE SET NULL  ON UPDATE CASCADE,
    FOREIGN KEY fk_ord_modif_por  (modificado_por) REFERENCES usuarios(id_usuario)    ON DELETE SET NULL  ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.8 ORDEN_EXAMENES ───────────────────────────────────────
DROP TABLE IF EXISTS orden_examenes;
CREATE TABLE orden_examenes (
    id_orden_examen         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_orden                INT UNSIGNED    NOT NULL,
    id_examen               INT UNSIGNED    NOT NULL,
    precio_unitario         DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    estado                  VARCHAR(20)     NOT NULL  DEFAULT 'pendiente',
    fecha_resultado_estimada DATETIME       NULL,
    fecha_resultado_real    DATETIME        NULL,

    CONSTRAINT chk_oe_estado CHECK (estado IN ('pendiente','en_proceso','completado','cancelado')),
    CONSTRAINT chk_oe_precio CHECK (precio_unitario >= 0),

    UNIQUE KEY uq_orden_examen (id_orden, id_examen),
    INDEX idx_oe_orden  (id_orden),
    INDEX idx_oe_examen (id_examen),
    INDEX idx_oe_estado (estado),

    FOREIGN KEY fk_oe_orden  (id_orden)  REFERENCES ordenes(id_orden)  ON DELETE CASCADE  ON UPDATE CASCADE,
    FOREIGN KEY fk_oe_examen (id_examen) REFERENCES examenes(id_examen) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.9 RESULTADOS ───────────────────────────────────────────
DROP TABLE IF EXISTS resultados;
CREATE TABLE resultados (
    id_resultado        INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_orden_examen     INT UNSIGNED    NOT NULL,
    id_parametro        INT UNSIGNED    NOT NULL,
    valor_resultado     VARCHAR(500)    NOT NULL,
    es_critico          TINYINT(1)      NOT NULL  DEFAULT 0,
    metodo_carga        VARCHAR(20)     NOT NULL  DEFAULT 'manual',
    validado            TINYINT(1)      NOT NULL  DEFAULT 0,
    validado_por        INT UNSIGNED    NULL,
    fecha_validacion    DATETIME        NULL,
    cargado_por         INT UNSIGNED    NULL,
    fecha_carga         DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion  DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    version             SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    CONSTRAINT chk_res_metodo CHECK (metodo_carga IN ('manual','importacion')),

    UNIQUE KEY uq_resultado_param (id_orden_examen, id_parametro),
    INDEX idx_res_orden_examen (id_orden_examen),
    INDEX idx_res_parametro    (id_parametro),
    INDEX idx_res_critico      (es_critico, validado),
    INDEX idx_res_fecha        (fecha_carga),
    INDEX idx_res_cargado_por  (cargado_por),

    FOREIGN KEY fk_res_orden_examen (id_orden_examen) REFERENCES orden_examenes(id_orden_examen) ON DELETE CASCADE  ON UPDATE CASCADE,
    FOREIGN KEY fk_res_parametro    (id_parametro)    REFERENCES parametros_examen(id_parametro) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY fk_res_cargado_por  (cargado_por)     REFERENCES usuarios(id_usuario)             ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY fk_res_validado_por (validado_por)    REFERENCES usuarios(id_usuario)             ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.10 COTIZACIONES ────────────────────────────────────────
DROP TABLE IF EXISTS cotizaciones;
CREATE TABLE cotizaciones (
    id_cotizacion       INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    numero_cotizacion   VARCHAR(25)     NOT NULL,
    id_paciente         INT UNSIGNED    NULL,
    nombre_cliente      VARCHAR(200)    NULL,
    fecha_cotizacion    DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    fecha_validez       DATE            NULL,
    subtotal            DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    descuento           DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    total               DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    estado              VARCHAR(15)     NOT NULL  DEFAULT 'vigente',
    observaciones       TEXT            NULL,
    creado_por          INT UNSIGNED    NULL,
    eliminado           TINYINT(1)      NOT NULL  DEFAULT 0,
    fecha_creacion      DATETIME        NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_cot_estado    CHECK (estado IN ('vigente','aceptada','rechazada','expirada')),
    CONSTRAINT chk_cot_descuento CHECK (descuento >= 0 AND descuento <= subtotal),
    CONSTRAINT chk_cot_total     CHECK (total >= 0),

    UNIQUE KEY uq_numero_cotizacion (numero_cotizacion),
    INDEX idx_cot_paciente  (id_paciente),
    INDEX idx_cot_estado    (estado, eliminado),
    INDEX idx_cot_fecha     (fecha_cotizacion),
    INDEX idx_cot_creador   (creado_por),

    FOREIGN KEY fk_cot_paciente   (id_paciente) REFERENCES pacientes(id_paciente)  ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY fk_cot_creado_por (creado_por)  REFERENCES usuarios(id_usuario)    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.11 COTIZACION_EXAMENES ─────────────────────────────────
DROP TABLE IF EXISTS cotizacion_examenes;
CREATE TABLE cotizacion_examenes (
    id_cotizacion_examen INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion       INT UNSIGNED    NOT NULL,
    id_examen           INT UNSIGNED    NOT NULL,
    cantidad            TINYINT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario     DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,
    subtotal            DECIMAL(10,2)   NOT NULL  DEFAULT 0.00,

    CONSTRAINT chk_ce_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_ce_precio   CHECK (precio_unitario >= 0),

    UNIQUE KEY uq_cot_examen (id_cotizacion, id_examen),
    INDEX idx_ce_cotizacion (id_cotizacion),
    INDEX idx_ce_examen     (id_examen),

    FOREIGN KEY fk_ce_cotizacion (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE  ON UPDATE CASCADE,
    FOREIGN KEY fk_ce_examen     (id_examen)     REFERENCES examenes(id_examen)         ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2.12 TOKENS QR ───────────────────────────────────────────
DROP TABLE IF EXISTS acceso_resultados_publico;
CREATE TABLE acceso_resultados_publico (
    id_acceso           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_orden            INT UNSIGNED    NOT NULL,
    token_acceso        CHAR(64)        NOT NULL   COMMENT 'bin2hex(random_bytes(32)) desde PHP',
    fecha_generacion    DATETIME        NOT NULL   DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion    DATETIME        NOT NULL,
    activo              TINYINT(1)      NOT NULL   DEFAULT 1,
    intentos_acceso     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_acceso       DATETIME        NULL,
    ip_ultimo_acceso    VARCHAR(45)     NULL       COMMENT 'VARCHAR(45) soporta IPv4 e IPv6',

    CONSTRAINT chk_arp_expiracion CHECK (fecha_expiracion > fecha_generacion),
    CONSTRAINT chk_arp_intentos   CHECK (intentos_acceso <= 1000),

    UNIQUE KEY uq_token_acceso (token_acceso),
    INDEX idx_arp_orden    (id_orden, activo),
    INDEX idx_arp_expira   (fecha_expiracion, activo),

    FOREIGN KEY fk_arp_orden (id_orden) REFERENCES ordenes(id_orden) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens QR de acceso público. Event scheduler invalida los expirados.';


-- ── 2.13 AUDITORÍA (tabla inmutable) ─────────────────────────
DROP TABLE IF EXISTS auditoria;
CREATE TABLE auditoria (
    id_auditoria        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabla               VARCHAR(60)     NOT NULL,
    operacion           VARCHAR(10)     NOT NULL,
    id_registro         INT UNSIGNED    NULL,
    usuario_id          INT UNSIGNED    NULL,
    username            VARCHAR(30)     NULL       COMMENT 'Snapshot del momento del evento',
    ip_address          VARCHAR(45)     NULL,
    user_agent          VARCHAR(500)    NULL,
    datos_anteriores    JSON            NULL,
    datos_nuevos        JSON            NULL,
    fecha_hora          DATETIME(3)     NOT NULL   DEFAULT CURRENT_TIMESTAMP(3)
                                                   COMMENT 'Precisión de ms para orden correcto',

    CONSTRAINT chk_aud_operacion CHECK (operacion IN ('INSERT','UPDATE','DELETE')),

    INDEX idx_aud_tabla      (tabla),
    INDEX idx_aud_usuario    (usuario_id),
    INDEX idx_aud_fecha      (fecha_hora),
    INDEX idx_aud_operacion  (operacion),
    INDEX idx_aud_registro   (tabla, id_registro)
    -- Sin FK a usuarios: el registro sobrevive si se elimina el usuario
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bitácora inmutable — triggers bloquean UPDATE y DELETE.';


-- ── 2.14 CONFIGURACIÓN DEL SISTEMA ───────────────────────────
DROP TABLE IF EXISTS configuracion_sistema;
CREATE TABLE configuracion_sistema (
    id_config           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    clave               VARCHAR(80)     NOT NULL,
    valor               TEXT            NULL,
    tipo                VARCHAR(15)     NOT NULL   DEFAULT 'texto',
    descripcion         VARCHAR(300)    NULL,
    modificado_por      INT UNSIGNED    NULL,
    fecha_modificacion  DATETIME        NOT NULL   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_cfg_tipo CHECK (tipo IN ('texto','numero','booleano','json')),
    UNIQUE KEY uq_cfg_clave (clave),
    INDEX idx_cfg_tipo (tipo),

    FOREIGN KEY fk_cfg_modificado_por (modificado_por)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 3 — TRIGGERS
-- ══════════════════════════════════════════════════════════════
DELIMITER $$

-- 3.1 Control de versión en resultados (sólo si cambia el valor)
DROP TRIGGER IF EXISTS trg_resultados_version $$
CREATE TRIGGER trg_resultados_version
    BEFORE UPDATE ON resultados FOR EACH ROW
BEGIN
    IF NEW.valor_resultado <> OLD.valor_resultado THEN
        SET NEW.version = OLD.version + 1;
    END IF;
END $$

-- 3.2 Auditoría automática de resultados modificados
DROP TRIGGER IF EXISTS trg_resultados_auditoria $$
CREATE TRIGGER trg_resultados_auditoria
    AFTER UPDATE ON resultados FOR EACH ROW
BEGIN
    IF NEW.valor_resultado <> OLD.valor_resultado THEN
        INSERT INTO auditoria
            (tabla, operacion, id_registro, datos_anteriores, datos_nuevos)
        VALUES (
            'resultados', 'UPDATE', NEW.id_resultado,
            JSON_OBJECT(
                'valor', OLD.valor_resultado,
                'es_critico', OLD.es_critico,
                'version', OLD.version
            ),
            JSON_OBJECT(
                'valor', NEW.valor_resultado,
                'es_critico', NEW.es_critico,
                'version', NEW.version
            )
        );
    END IF;
END $$

-- 3.3 Auditoría automática de cambios de rol/estado en usuarios
DROP TRIGGER IF EXISTS trg_usuarios_auditoria $$
CREATE TRIGGER trg_usuarios_auditoria
    AFTER UPDATE ON usuarios FOR EACH ROW
BEGIN
    IF NEW.rol <> OLD.rol OR NEW.estado <> OLD.estado THEN
        INSERT INTO auditoria
            (tabla, operacion, id_registro, username, datos_anteriores, datos_nuevos)
        VALUES (
            'usuarios', 'UPDATE', NEW.id_usuario, NEW.username,
            JSON_OBJECT('estado', OLD.estado, 'rol', OLD.rol, 'intentos_fallidos', OLD.intentos_fallidos),
            JSON_OBJECT('estado', NEW.estado, 'rol', NEW.rol, 'intentos_fallidos', NEW.intentos_fallidos)
        );
    END IF;
END $$

-- 3.4 Auditoría automática de cambios de estado en órdenes
DROP TRIGGER IF EXISTS trg_ordenes_auditoria $$
CREATE TRIGGER trg_ordenes_auditoria
    AFTER UPDATE ON ordenes FOR EACH ROW
BEGIN
    IF NEW.estado <> OLD.estado OR NEW.estado_pago <> OLD.estado_pago THEN
        INSERT INTO auditoria
            (tabla, operacion, id_registro, usuario_id, datos_anteriores, datos_nuevos)
        VALUES (
            'ordenes', 'UPDATE', NEW.id_orden, NEW.modificado_por,
            JSON_OBJECT('estado', OLD.estado, 'estado_pago', OLD.estado_pago),
            JSON_OBJECT('estado', NEW.estado, 'estado_pago', NEW.estado_pago)
        );
    END IF;
END $$

-- 3.5 Tabla auditoría INMUTABLE — bloquear UPDATE
DROP TRIGGER IF EXISTS trg_auditoria_no_update $$
CREATE TRIGGER trg_auditoria_no_update
    BEFORE UPDATE ON auditoria FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La tabla auditoria es inmutable. No se permiten modificaciones.';
END $$

-- 3.6 Tabla auditoría INMUTABLE — bloquear DELETE
DROP TRIGGER IF EXISTS trg_auditoria_no_delete $$
CREATE TRIGGER trg_auditoria_no_delete
    BEFORE DELETE ON auditoria FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La tabla auditoria es inmutable. No se permiten eliminaciones.';
END $$

DELIMITER ;


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 4 — PROCEDIMIENTOS ALMACENADOS
-- ══════════════════════════════════════════════════════════════
DELIMITER $$

-- ────────────────────────────────────────────────────────────
-- 4.1 sp_crear_usuario
--     Crea un usuario con PII cifrado. Verifica duplicados.
--     p_encryption_key: pasar via prepared statement desde PHP
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_crear_usuario $$
CREATE PROCEDURE sp_crear_usuario(
    IN  p_username          VARCHAR(30),
    IN  p_email             VARCHAR(180),
    IN  p_password_hash     VARCHAR(255),
    IN  p_nombre_completo   VARCHAR(255),
    IN  p_cedula            VARCHAR(20),
    IN  p_rol               VARCHAR(20),
    IN  p_creado_por        INT UNSIGNED,
    IN  p_encryption_key    VARCHAR(255),
    OUT p_resultado         INT UNSIGNED,
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = 'Error interno al crear usuario';
    END;

    IF p_rol NOT IN ('administrador','analistaL','medico','paciente') THEN
        SET p_resultado = 0;
        SET p_mensaje   = 'Rol inválido';
        LEAVE sp_crear_usuario;
    END IF;

    START TRANSACTION;

    IF EXISTS (SELECT 1 FROM usuarios WHERE username = p_username AND eliminado = 0) THEN
        SET p_resultado = 0;
        SET p_mensaje   = 'El nombre de usuario ya existe';
        ROLLBACK;
    ELSEIF EXISTS (SELECT 1 FROM usuarios WHERE email = p_email AND eliminado = 0) THEN
        SET p_resultado = 0;
        SET p_mensaje   = 'El email ya está registrado';
        ROLLBACK;
    ELSE
        INSERT INTO usuarios
            (username, email, password_hash, nombre_completo, cedula, rol, estado, creado_por)
        VALUES (
            p_username, p_email, p_password_hash,
            AES_ENCRYPT(p_nombre_completo, p_encryption_key),
            IF(p_cedula IS NOT NULL AND p_cedula <> '', AES_ENCRYPT(p_cedula, p_encryption_key), NULL),
            p_rol, 'activo', p_creado_por
        );

        SET p_resultado = LAST_INSERT_ID();
        SET p_mensaje   = 'Usuario creado exitosamente';

        INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, username, datos_nuevos)
        VALUES ('usuarios', 'INSERT', p_resultado, p_creado_por, p_username,
                JSON_OBJECT('rol', p_rol, 'email', p_email));

        COMMIT;
    END IF;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.2 sp_crear_paciente
--     Registra paciente con PII cifrado y cedula_hash para
--     búsqueda segura sin descifrar. Si ya existe, retorna su ID.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_crear_paciente $$
CREATE PROCEDURE sp_crear_paciente(
    IN  p_cedula            VARCHAR(20),
    IN  p_nombres           VARCHAR(255),
    IN  p_apellidos         VARCHAR(255),
    IN  p_fecha_nacimiento  DATE,
    IN  p_genero            CHAR(1),
    IN  p_telefono          VARCHAR(20),
    IN  p_email             VARCHAR(100),
    IN  p_direccion         TEXT,
    IN  p_tipo_sangre       VARCHAR(5),
    IN  p_alergias          TEXT,
    IN  p_observaciones     TEXT,
    IN  p_registrado_por    INT UNSIGNED,
    IN  p_encryption_key    VARCHAR(255),
    OUT p_resultado         INT UNSIGNED,
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE v_cedula_hash CHAR(64);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = 'Error interno al crear paciente';
    END;

    SET v_cedula_hash = SHA2(p_cedula, 256);

    START TRANSACTION;

    -- Verificar si ya existe por hash de cédula
    IF EXISTS (SELECT 1 FROM pacientes WHERE cedula_hash = v_cedula_hash AND eliminado = 0) THEN
        SELECT id_paciente INTO p_resultado
        FROM pacientes WHERE cedula_hash = v_cedula_hash AND eliminado = 0 LIMIT 1;
        SET p_mensaje = 'Paciente ya existe con esa cédula';
        COMMIT;
    ELSE
        INSERT INTO pacientes (
            cedula, cedula_hash, nombres, apellidos,
            fecha_nacimiento, genero, telefono, email,
            direccion, tipo_sangre, alergias, observaciones, registrado_por
        ) VALUES (
            AES_ENCRYPT(p_cedula,    p_encryption_key),
            v_cedula_hash,
            AES_ENCRYPT(p_nombres,   p_encryption_key),
            AES_ENCRYPT(p_apellidos, p_encryption_key),
            p_fecha_nacimiento, p_genero,
            IF(p_telefono  IS NOT NULL AND p_telefono  <> '', AES_ENCRYPT(p_telefono,  p_encryption_key), NULL),
            IF(p_email     IS NOT NULL AND p_email     <> '', AES_ENCRYPT(p_email,     p_encryption_key), NULL),
            p_direccion, p_tipo_sangre, p_alergias, p_observaciones,
            p_registrado_por
        );

        SET p_resultado = LAST_INSERT_ID();
        SET p_mensaje   = 'Paciente registrado exitosamente';

        INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id)
        VALUES ('pacientes', 'INSERT', p_resultado, p_registrado_por);

        COMMIT;
    END IF;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.3 sp_buscar_paciente_cedula
--     Búsqueda por cédula usando cedula_hash (sin descifrar toda
--     la tabla). Requiere @GL_KEY definido en la sesión o
--     pasarlo como parámetro.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_buscar_paciente_cedula $$
CREATE PROCEDURE sp_buscar_paciente_cedula(
    IN p_cedula          VARCHAR(20),
    IN p_encryption_key  VARCHAR(255)
)
BEGIN
    SELECT
        p.id_paciente,
        CAST(AES_DECRYPT(p.cedula,    p_encryption_key) AS CHAR) AS cedula,
        CAST(AES_DECRYPT(p.nombres,   p_encryption_key) AS CHAR) AS nombres,
        CAST(AES_DECRYPT(p.apellidos, p_encryption_key) AS CHAR) AS apellidos,
        CAST(AES_DECRYPT(p.telefono,  p_encryption_key) AS CHAR) AS telefono,
        CAST(AES_DECRYPT(p.email,     p_encryption_key) AS CHAR) AS email,
        p.fecha_nacimiento, p.genero, p.tipo_sangre,
        p.estado_civil, p.direccion, p.alergias, p.observaciones
    FROM pacientes p
    WHERE p.cedula_hash = SHA2(p_cedula, 256)
      AND p.eliminado = 0
    LIMIT 1;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.4 sp_buscar_pacientes_nombre
--     Retorna lista para autocompletado AJAX. Descifra en BD
--     y filtra los primeros 200 activos (el filtro final se
--     hace en PHP por la naturaleza del cifrado).
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_buscar_pacientes_nombre $$
CREATE PROCEDURE sp_buscar_pacientes_nombre(
    IN p_encryption_key VARCHAR(255),
    IN p_limite         INT
)
BEGIN
    SET p_limite = IFNULL(p_limite, 200);
    SELECT
        p.id_paciente,
        CAST(AES_DECRYPT(p.cedula,    p_encryption_key) AS CHAR) AS cedula,
        CAST(AES_DECRYPT(p.nombres,   p_encryption_key) AS CHAR) AS nombres,
        CAST(AES_DECRYPT(p.apellidos, p_encryption_key) AS CHAR) AS apellidos,
        CAST(AES_DECRYPT(p.telefono,  p_encryption_key) AS CHAR) AS telefono,
        p.fecha_nacimiento, p.genero, p.tipo_sangre
    FROM pacientes p
    WHERE p.eliminado = 0
    ORDER BY p.fecha_registro DESC
    LIMIT p_limite;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.5 sp_crear_orden
--     Crea una orden con sus exámenes en transacción atómica.
--     Procesa array JSON de exámenes. Genera número de orden
--     basado en el contador total (igual que PHP).
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_crear_orden $$
CREATE PROCEDURE sp_crear_orden(
    IN  p_id_paciente       INT UNSIGNED,
    IN  p_id_medico         INT UNSIGNED,
    IN  p_examenes_json     JSON,
    IN  p_tipo_atencion     VARCHAR(15),
    IN  p_observaciones     TEXT,
    IN  p_creado_por        INT UNSIGNED,
    OUT p_id_orden          INT UNSIGNED,
    OUT p_numero_orden      VARCHAR(25),
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE v_total         DECIMAL(10,2) DEFAULT 0;
    DECLARE v_idx           INT DEFAULT 0;
    DECLARE v_total_exa     INT;
    DECLARE v_id_examen     INT UNSIGNED;
    DECLARE v_precio        DECIMAL(10,2);
    DECLARE v_entrega_min   SMALLINT;
    DECLARE v_entrega_dias  TINYINT;
    DECLARE v_fecha_est     DATETIME;
    DECLARE v_contador      INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_orden     = 0;
        SET p_numero_orden = NULL;
        SET p_mensaje      = 'Error al crear la orden';
    END;

    IF p_tipo_atencion NOT IN ('normal','urgencia','control') THEN
        SET p_id_orden = 0; SET p_mensaje = 'Tipo de atención inválido';
        LEAVE sp_crear_orden;
    END IF;

    START TRANSACTION;

    -- Generar número de orden (consistente con Orden::generarNumero())
    SELECT COUNT(*) + 1 INTO v_contador FROM ordenes;
    SET p_numero_orden = CONCAT('ORD-', DATE_FORMAT(NOW(),'%Y%m%d'), '-',
                                LPAD(v_contador, 5, '0'));

    INSERT INTO ordenes
        (numero_orden, id_paciente, id_medico, fecha_orden, fecha_toma_muestra,
         estado, tipo_atencion, observaciones_recibo, creado_por)
    VALUES
        (p_numero_orden, p_id_paciente, p_id_medico, NOW(), NOW(),
         'creada', p_tipo_atencion, p_observaciones, p_creado_por);

    SET p_id_orden      = LAST_INSERT_ID();
    SET v_total_exa     = JSON_LENGTH(p_examenes_json);

    WHILE v_idx < v_total_exa DO
        SET v_id_examen = JSON_UNQUOTE(JSON_EXTRACT(p_examenes_json,
                            CONCAT('$[', v_idx, '].id_examen')));

        SELECT precio, tiempo_entrega_min, tiempo_entrega_dias
        INTO v_precio, v_entrega_min, v_entrega_dias
        FROM examenes
        WHERE id_examen = v_id_examen AND activo = 1 AND eliminado = 0
        LIMIT 1;

        IF v_precio IS NULL THEN
            ROLLBACK;
            SET p_id_orden = 0;
            SET p_mensaje  = CONCAT('Examen ID ', v_id_examen, ' no encontrado o inactivo');
            LEAVE sp_crear_orden;
        END IF;

        SET v_fecha_est = CASE
            WHEN v_entrega_min  IS NOT NULL THEN DATE_ADD(NOW(), INTERVAL v_entrega_min MINUTE)
            WHEN v_entrega_dias IS NOT NULL THEN DATE_ADD(NOW(), INTERVAL v_entrega_dias DAY)
            ELSE NULL
        END;

        INSERT INTO orden_examenes
            (id_orden, id_examen, precio_unitario, fecha_resultado_estimada)
        VALUES (p_id_orden, v_id_examen, v_precio, v_fecha_est);

        SET v_total = v_total + v_precio;
        SET v_idx   = v_idx + 1;
    END WHILE;

    UPDATE ordenes SET total_pagar = v_total WHERE id_orden = p_id_orden;

    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id,
                           datos_nuevos)
    VALUES ('ordenes', 'INSERT', p_id_orden, p_creado_por,
            JSON_OBJECT('numero', p_numero_orden, 'total', v_total,
                        'examenes', v_total_exa));

    SET p_mensaje = 'Orden creada exitosamente';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.6 sp_cargar_resultado
--     Inserta o actualiza un resultado individual evaluando
--     automáticamente si es crítico contra valores_referencia.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_cargar_resultado $$
CREATE PROCEDURE sp_cargar_resultado(
    IN  p_id_orden_examen   INT UNSIGNED,
    IN  p_id_parametro      INT UNSIGNED,
    IN  p_valor             VARCHAR(500),
    IN  p_cargado_por       INT UNSIGNED,
    IN  p_metodo_carga      VARCHAR(20),
    OUT p_id_resultado      INT UNSIGNED,
    OUT p_es_critico        TINYINT(1),
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE v_tipo_dato     VARCHAR(25);
    DECLARE v_valor_num     DECIMAL(15,4);
    DECLARE v_min_critico   DECIMAL(15,4);
    DECLARE v_max_critico   DECIMAL(15,4);
    DECLARE v_existente     INT UNSIGNED DEFAULT NULL;
    DECLARE v_id_orden      INT UNSIGNED;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_resultado = 0;
        SET p_mensaje      = 'Error al cargar resultado';
    END;

    IF p_metodo_carga NOT IN ('manual','importacion') THEN
        SET p_metodo_carga = 'manual';
    END IF;

    -- Evaluación de criticidad
    SET p_es_critico = 0;

    SELECT tipo_dato INTO v_tipo_dato
    FROM parametros_examen WHERE id_parametro = p_id_parametro LIMIT 1;

    IF v_tipo_dato = 'numerico' AND p_valor REGEXP '^-?[0-9]+(\\.[0-9]+)?$' THEN
        SET v_valor_num = CAST(p_valor AS DECIMAL(15,4));

        SELECT valor_min_critico, valor_max_critico
        INTO v_min_critico, v_max_critico
        FROM valores_referencia
        WHERE id_parametro = p_id_parametro AND activo = 1
        ORDER BY genero DESC LIMIT 1;

        IF (v_min_critico IS NOT NULL AND v_valor_num < v_min_critico) OR
           (v_max_critico IS NOT NULL AND v_valor_num > v_max_critico) THEN
            SET p_es_critico = 1;
        END IF;
    END IF;

    START TRANSACTION;

    -- Obtener id_orden para actualizar estado
    SELECT oe.id_orden INTO v_id_orden
    FROM orden_examenes oe WHERE oe.id_orden_examen = p_id_orden_examen LIMIT 1;

    -- Verificar si ya existe
    SELECT id_resultado INTO v_existente
    FROM resultados
    WHERE id_orden_examen = p_id_orden_examen AND id_parametro = p_id_parametro
    LIMIT 1;

    IF v_existente IS NOT NULL THEN
        UPDATE resultados SET
            valor_resultado = p_valor,
            es_critico      = p_es_critico,
            cargado_por     = p_cargado_por,
            metodo_carga    = p_metodo_carga,
            validado        = 0,
            fecha_carga     = NOW()
        WHERE id_resultado = v_existente;
        SET p_id_resultado = v_existente;
    ELSE
        INSERT INTO resultados
            (id_orden_examen, id_parametro, valor_resultado,
             es_critico, fecha_carga, cargado_por, metodo_carga)
        VALUES
            (p_id_orden_examen, p_id_parametro, p_valor,
             p_es_critico, NOW(), p_cargado_por, p_metodo_carga);
        SET p_id_resultado = LAST_INSERT_ID();
    END IF;

    -- Marcar examen como completado
    UPDATE orden_examenes SET estado = 'completado'
    WHERE id_orden_examen = p_id_orden_examen;

    -- Actualizar estado de la orden
    CALL sp_actualizar_estado_orden(v_id_orden, p_cargado_por);

    SET p_mensaje = IF(p_es_critico = 1,
        'Resultado cargado — ⚠ VALOR CRÍTICO DETECTADO',
        'Resultado cargado exitosamente');

    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.7 sp_actualizar_estado_orden
--     Recalcula el estado de la orden según los exámenes:
--     todos completados → resultados_cargados
--     al menos uno completado → en_proceso
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_actualizar_estado_orden $$
CREATE PROCEDURE sp_actualizar_estado_orden(
    IN p_id_orden       INT UNSIGNED,
    IN p_usuario_id     INT UNSIGNED
)
BEGIN
    DECLARE v_total      INT DEFAULT 0;
    DECLARE v_completados INT DEFAULT 0;

    SELECT COUNT(*), SUM(estado = 'completado')
    INTO v_total, v_completados
    FROM orden_examenes WHERE id_orden = p_id_orden;

    IF v_total > 0 AND v_completados = v_total THEN
        UPDATE ordenes SET estado = 'resultados_cargados', modificado_por = p_usuario_id
        WHERE id_orden = p_id_orden AND estado IN ('creada','en_proceso');
    ELSEIF v_completados > 0 THEN
        UPDATE ordenes SET estado = 'en_proceso', modificado_por = p_usuario_id
        WHERE id_orden = p_id_orden AND estado = 'creada';
    END IF;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.8 sp_validar_resultados_orden
--     Marca todos los resultados de una orden como validados
--     y cambia el estado de la orden a 'validada'.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_validar_resultados_orden $$
CREATE PROCEDURE sp_validar_resultados_orden(
    IN  p_id_orden      INT UNSIGNED,
    IN  p_validado_por  INT UNSIGNED,
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'Error al validar resultados';
    END;

    START TRANSACTION;

    UPDATE resultados r
    JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
    SET r.validado = 1, r.fecha_validacion = NOW(), r.validado_por = p_validado_por
    WHERE oe.id_orden = p_id_orden;

    UPDATE ordenes SET estado = 'validada', modificado_por = p_validado_por
    WHERE id_orden = p_id_orden AND eliminado = 0;

    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_nuevos)
    VALUES ('ordenes', 'UPDATE', p_id_orden, p_validado_por,
            JSON_OBJECT('accion', 'validar_resultados'));

    SET p_mensaje = 'Resultados validados correctamente';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.9 sp_publicar_orden
--     Publica la orden y genera/renueva el token QR en una
--     sola transacción atómica.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_publicar_orden $$
CREATE PROCEDURE sp_publicar_orden(
    IN  p_id_orden          INT UNSIGNED,
    IN  p_usuario_id        INT UNSIGNED,
    IN  p_token             CHAR(64),
    IN  p_minutos_expira    INT,
    OUT p_resultado         VARCHAR(10),
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR';
        SET p_mensaje   = 'Error al publicar orden';
    END;

    START TRANSACTION;

    UPDATE ordenes SET estado = 'publicada', modificado_por = p_usuario_id
    WHERE id_orden = p_id_orden AND eliminado = 0;

    -- Desactivar tokens anteriores de esta orden
    UPDATE acceso_resultados_publico SET activo = 0 WHERE id_orden = p_id_orden;

    -- Insertar nuevo token QR
    INSERT INTO acceso_resultados_publico
        (id_orden, token_acceso, fecha_expiracion, activo)
    VALUES
        (p_id_orden, p_token,
         DATE_ADD(NOW(), INTERVAL p_minutos_expira MINUTE), 1);

    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_nuevos)
    VALUES ('ordenes', 'UPDATE', p_id_orden, p_usuario_id,
            JSON_OBJECT('estado','publicada','token', p_token));

    SET p_resultado = 'OK';
    SET p_mensaje   = 'Orden publicada y token QR generado';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.10 sp_generar_token_acceso
--      Genera un token de acceso público para una orden.
--      El token llega ya generado desde PHP (random_bytes).
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_generar_token_acceso $$
CREATE PROCEDURE sp_generar_token_acceso(
    IN  p_id_orden          INT UNSIGNED,
    IN  p_token             CHAR(64),
    IN  p_minutos_expira    INT,
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'Error al generar token';
    END;

    START TRANSACTION;

    UPDATE acceso_resultados_publico SET activo = 0 WHERE id_orden = p_id_orden;

    INSERT INTO acceso_resultados_publico
        (id_orden, token_acceso, fecha_expiracion, activo)
    VALUES
        (p_id_orden, p_token,
         DATE_ADD(NOW(), INTERVAL p_minutos_expira MINUTE), 1);

    SET p_mensaje = 'Token generado exitosamente';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.11 sp_validar_token_acceso
--      Verifica validez del token, registra el acceso y
--      retorna id_orden si es válido.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_validar_token_acceso $$
CREATE PROCEDURE sp_validar_token_acceso(
    IN  p_token         CHAR(64),
    IN  p_ip_acceso     VARCHAR(45),
    OUT p_valido        TINYINT(1),
    OUT p_id_orden      INT UNSIGNED
)
BEGIN
    DECLARE v_expiracion DATETIME;

    SET p_valido   = 0;
    SET p_id_orden = NULL;

    SELECT id_orden, fecha_expiracion INTO p_id_orden, v_expiracion
    FROM acceso_resultados_publico
    WHERE token_acceso = p_token AND activo = 1
    LIMIT 1;

    IF p_id_orden IS NOT NULL THEN
        IF NOW() <= v_expiracion THEN
            SET p_valido = 1;
            UPDATE acceso_resultados_publico SET
                intentos_acceso  = intentos_acceso + 1,
                ultimo_acceso    = NOW(),
                ip_ultimo_acceso = p_ip_acceso
            WHERE token_acceso = p_token;
        ELSE
            UPDATE acceso_resultados_publico SET activo = 0
            WHERE token_acceso = p_token;
            SET p_id_orden = NULL;
        END IF;
    END IF;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.12 sp_registrar_pago
--      Registra el pago de una orden. Verifica que no esté
--      ya pagada o anulada antes de actualizar.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_registrar_pago $$
CREATE PROCEDURE sp_registrar_pago(
    IN  p_id_orden      INT UNSIGNED,
    IN  p_metodo_pago   VARCHAR(30),
    IN  p_usuario_id    INT UNSIGNED,
    OUT p_resultado     VARCHAR(10),
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE v_estado_actual VARCHAR(15);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR';
        SET p_mensaje   = 'Error al registrar pago';
    END;

    SELECT estado_pago INTO v_estado_actual
    FROM ordenes WHERE id_orden = p_id_orden AND eliminado = 0 LIMIT 1;

    IF v_estado_actual IS NULL THEN
        SET p_resultado = 'ERROR'; SET p_mensaje = 'Orden no encontrada';
        LEAVE sp_registrar_pago;
    END IF;

    IF v_estado_actual = 'pagado' THEN
        SET p_resultado = 'ERROR'; SET p_mensaje = 'La orden ya fue pagada';
        LEAVE sp_registrar_pago;
    END IF;

    IF v_estado_actual = 'anulado' THEN
        SET p_resultado = 'ERROR'; SET p_mensaje = 'No se puede cobrar una orden anulada';
        LEAVE sp_registrar_pago;
    END IF;

    START TRANSACTION;

    UPDATE ordenes SET
        estado_pago    = 'pagado',
        metodo_pago    = p_metodo_pago,
        modificado_por = p_usuario_id
    WHERE id_orden = p_id_orden;

    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_nuevos)
    VALUES ('ordenes', 'UPDATE', p_id_orden, p_usuario_id,
            JSON_OBJECT('accion','pago','metodo', p_metodo_pago));

    SET p_resultado = 'OK';
    SET p_mensaje   = 'Pago registrado exitosamente';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.13 sp_resetear_password
--      Genera nueva clave hasheada para el usuario. Desbloquea
--      automáticamente si estaba bloqueado.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_resetear_password $$
CREATE PROCEDURE sp_resetear_password(
    IN  p_id_usuario    INT UNSIGNED,
    IN  p_nuevo_hash    VARCHAR(255),
    IN  p_realizado_por INT UNSIGNED,
    OUT p_resultado     VARCHAR(10),
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR';
        SET p_mensaje   = 'Error al resetear contraseña';
    END;

    IF NOT EXISTS (SELECT 1 FROM usuarios WHERE id_usuario = p_id_usuario AND eliminado = 0) THEN
        SET p_resultado = 'ERROR'; SET p_mensaje = 'Usuario no encontrado';
        LEAVE sp_resetear_password;
    END IF;

    START TRANSACTION;

    UPDATE usuarios SET
        password_hash       = p_nuevo_hash,
        token_recuperacion  = NULL,
        token_expiracion    = NULL,
        intentos_fallidos   = 0,
        estado              = CASE WHEN estado = 'bloqueado' THEN 'activo' ELSE estado END
    WHERE id_usuario = p_id_usuario;

    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_nuevos)
    VALUES ('usuarios', 'UPDATE', p_id_usuario, p_realizado_por,
            JSON_OBJECT('accion', 'reset_password'));

    SET p_resultado = 'OK';
    SET p_mensaje   = 'Contraseña reseteada exitosamente';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.14 sp_desbloquear_usuario
--      Desbloquea cuenta y reinicia contador de intentos.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_desbloquear_usuario $$
CREATE PROCEDURE sp_desbloquear_usuario(
    IN  p_id_usuario    INT UNSIGNED,
    IN  p_realizado_por INT UNSIGNED,
    OUT p_resultado     VARCHAR(10),
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR';
        SET p_mensaje   = 'Error al desbloquear usuario';
    END;

    START TRANSACTION;

    UPDATE usuarios SET
        estado            = 'activo',
        intentos_fallidos = 0
    WHERE id_usuario = p_id_usuario AND eliminado = 0;

    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_nuevos)
    VALUES ('usuarios', 'UPDATE', p_id_usuario, p_realizado_por,
            JSON_OBJECT('accion','desbloquear'));

    SET p_resultado = 'OK';
    SET p_mensaje   = 'Cuenta desbloqueada';
    COMMIT;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.15 sp_dashboard_stats
--      Estadísticas completas del período para DashboardController.
--      Devuelve 4 result sets: órdenes, pacientes, críticos, por_estado.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_dashboard_stats $$
CREATE PROCEDURE sp_dashboard_stats(
    IN p_desde DATE,
    IN p_hasta DATE
)
BEGIN
    -- Result set 1: KPIs de órdenes
    SELECT
        COUNT(*)                                            AS total,
        SUM(estado IN ('creada','en_proceso'))              AS pendientes,
        SUM(estado IN ('resultados_cargados','validada','publicada')) AS completadas,
        COALESCE(SUM(CASE WHEN estado_pago='pagado' THEN total_pagar ELSE 0 END),0) AS ingresos_cobrados,
        COALESCE(SUM(total_pagar),0)                       AS ingresos_total
    FROM ordenes
    WHERE DATE(fecha_orden) BETWEEN p_desde AND p_hasta AND eliminado = 0;

    -- Result set 2: pacientes únicos atendidos
    SELECT COUNT(DISTINCT id_paciente) AS total
    FROM ordenes
    WHERE DATE(fecha_orden) BETWEEN p_desde AND p_hasta AND eliminado = 0;

    -- Result set 3: valores críticos del período
    SELECT COUNT(*) AS total
    FROM resultados r
    JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
    JOIN ordenes o ON oe.id_orden = o.id_orden
    WHERE r.es_critico = 1
      AND DATE(o.fecha_orden) BETWEEN p_desde AND p_hasta
      AND o.eliminado = 0;

    -- Result set 4: distribución por estado
    SELECT estado, COUNT(*) AS cantidad
    FROM ordenes
    WHERE DATE(fecha_orden) BETWEEN p_desde AND p_hasta AND eliminado = 0
    GROUP BY estado;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.16 sp_produccion_analistas
--      Para ReporteController::index() — producción del equipo.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_produccion_analistas $$
CREATE PROCEDURE sp_produccion_analistas(
    IN  p_desde          DATE,
    IN  p_hasta          DATE,
    IN  p_encryption_key VARCHAR(255)
)
BEGIN
    SELECT
        u.id_usuario,
        u.username,
        CAST(AES_DECRYPT(u.nombre_completo, p_encryption_key) AS CHAR) AS nombre_completo,
        COUNT(DISTINCT o.id_orden)    AS ordenes_atendidas,
        COUNT(DISTINCT r.id_resultado) AS resultados_cargados
    FROM usuarios u
    LEFT JOIN ordenes o
        ON o.creado_por = u.id_usuario
        AND DATE(o.fecha_orden) BETWEEN p_desde AND p_hasta
        AND o.eliminado = 0
    LEFT JOIN resultados r
        ON r.cargado_por = u.id_usuario
        AND DATE(r.fecha_carga) BETWEEN p_desde AND p_hasta
    WHERE u.rol IN ('administrador','analistaL')
      AND u.eliminado = 0
    GROUP BY u.id_usuario
    ORDER BY ordenes_atendidas DESC;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.17 sp_examenes_por_categoria
--      Para ReporteController — distribución por categoría.
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_examenes_por_categoria $$
CREATE PROCEDURE sp_examenes_por_categoria(
    IN p_desde DATE,
    IN p_hasta DATE
)
BEGIN
    SELECT
        cat.id_categoria,
        cat.nombre  AS categoria,
        cat.color_hex,
        COUNT(oe.id_orden_examen) AS total
    FROM orden_examenes oe
    JOIN ordenes o  ON oe.id_orden   = o.id_orden
    JOIN examenes e ON oe.id_examen  = e.id_examen
    JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
    WHERE o.eliminado = 0
      AND DATE(o.fecha_orden) BETWEEN p_desde AND p_hasta
    GROUP BY cat.id_categoria
    ORDER BY total DESC;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.18 sp_ingresos_por_dia
--      Para ReporteController::estadisticas(tipo=ingresos).
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_ingresos_por_dia $$
CREATE PROCEDURE sp_ingresos_por_dia(
    IN p_desde DATE,
    IN p_hasta DATE
)
BEGIN
    SELECT
        DATE(fecha_orden)                AS dia,
        COUNT(*)                         AS ordenes,
        SUM(estado_pago = 'pagado')      AS pagadas,
        COALESCE(SUM(CASE WHEN estado_pago='pagado' THEN total_pagar ELSE 0 END),0) AS ingresos
    FROM ordenes
    WHERE DATE(fecha_orden) BETWEEN p_desde AND p_hasta
      AND eliminado = 0
    GROUP BY dia
    ORDER BY dia;
END $$


-- ────────────────────────────────────────────────────────────
-- 4.19 sp_criticos_por_periodo
--      Para ReporteController::estadisticas(tipo=criticos).
-- ────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_criticos_por_periodo $$
CREATE PROCEDURE sp_criticos_por_periodo(
    IN  p_desde          DATE,
    IN  p_hasta          DATE,
    IN  p_encryption_key VARCHAR(255)
)
BEGIN
    SELECT
        DATE(o.fecha_orden)  AS dia,
        o.numero_orden,
        e.nombre             AS nombre_examen,
        pe.nombre_parametro,
        r.valor_resultado,
        r.validado,
        vr.valor_min_critico,
        vr.valor_max_critico,
        CAST(AES_DECRYPT(p.nombres,   p_encryption_key) AS CHAR) AS pac_nombres,
        CAST(AES_DECRYPT(p.apellidos, p_encryption_key) AS CHAR) AS pac_apellidos
    FROM resultados r
    JOIN parametros_examen pe ON r.id_parametro    = pe.id_parametro
    LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
    JOIN orden_examenes oe ON r.id_orden_examen    = oe.id_orden_examen
    JOIN examenes e        ON oe.id_examen         = e.id_examen
    JOIN ordenes o         ON oe.id_orden          = o.id_orden
    JOIN pacientes p       ON o.id_paciente        = p.id_paciente
    WHERE r.es_critico = 1
      AND DATE(o.fecha_orden) BETWEEN p_desde AND p_hasta
      AND o.eliminado = 0
    ORDER BY o.fecha_orden DESC;
END $$


DELIMITER ;


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 5 — EVENTOS PROGRAMADOS
--  Requiere en my.cnf:  event_scheduler = ON
-- ══════════════════════════════════════════════════════════════
SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS evt_tokens_qr_expirados;
CREATE EVENT evt_tokens_qr_expirados
    ON SCHEDULE EVERY 1 HOUR
    STARTS (CURRENT_TIMESTAMP + INTERVAL 5 MINUTE)
    COMMENT 'Desactiva tokens QR expirados'
    DO
        UPDATE acceso_resultados_publico
            SET activo = 0
            WHERE activo = 1 AND fecha_expiracion < NOW();

DROP EVENT IF EXISTS evt_tokens_recuperacion_expirados;
CREATE EVENT evt_tokens_recuperacion_expirados
    ON SCHEDULE EVERY 2 HOUR
    COMMENT 'Invalida tokens de recuperación de contraseña expirados'
    DO
        UPDATE usuarios
            SET token_recuperacion = NULL, token_expiracion = NULL
            WHERE token_expiracion IS NOT NULL AND token_expiracion < NOW();

DROP EVENT IF EXISTS evt_expirar_cotizaciones;
CREATE EVENT evt_expirar_cotizaciones
    ON SCHEDULE EVERY 1 DAY
    STARTS (DATE(CURRENT_TIMESTAMP + INTERVAL 1 DAY))
    COMMENT 'Marca cotizaciones vencidas como expiradas'
    DO
        UPDATE cotizaciones
            SET estado = 'expirada'
            WHERE estado = 'vigente'
              AND fecha_validez IS NOT NULL
              AND fecha_validez < CURDATE()
              AND eliminado = 0;


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 6 — VISTAS
-- ══════════════════════════════════════════════════════════════

-- Vista de órdenes sin PII (para reportes sin descifrar)
CREATE OR REPLACE VIEW v_ordenes_resumen AS
    SELECT o.id_orden, o.numero_orden, o.fecha_orden,
           o.estado, o.tipo_atencion,
           o.total_pagar, o.estado_pago, o.metodo_pago,
           o.creado_por, o.eliminado,
           p.id_paciente, p.fecha_nacimiento, p.genero,
           p.tipo_sangre, p.cedula_hash
    FROM ordenes o
    JOIN pacientes p ON o.id_paciente = p.id_paciente
    WHERE o.eliminado = 0;

-- Vista de exámenes más solicitados (alias total_solicitado para reportes)
CREATE OR REPLACE VIEW v_examenes_solicitados AS
    SELECT
        e.id_examen, e.nombre, e.codigo, e.precio,
        c.nombre AS categoria, c.color_hex,
        COUNT(oe.id_orden_examen) AS total_solicitado
    FROM orden_examenes oe
    JOIN examenes e  ON oe.id_examen    = e.id_examen
    JOIN categorias_examenes c ON e.id_categoria = c.id_categoria
    JOIN ordenes o   ON oe.id_orden     = o.id_orden
    WHERE o.eliminado = 0
    GROUP BY e.id_examen;

-- Vista de valores críticos pendientes de validar (sin PII)
CREATE OR REPLACE VIEW v_criticos_pendientes AS
    SELECT
        r.id_resultado, r.valor_resultado, r.fecha_carga,
        pe.nombre_parametro, pe.unidad_medida,
        e.nombre AS nombre_examen,
        o.numero_orden, o.id_orden,
        vr.valor_min_critico, vr.valor_max_critico,
        p.id_paciente, p.cedula_hash
    FROM resultados r
    JOIN parametros_examen pe ON r.id_parametro       = pe.id_parametro
    LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
    JOIN orden_examenes oe ON r.id_orden_examen        = oe.id_orden_examen
    JOIN examenes e        ON oe.id_examen             = e.id_examen
    JOIN ordenes o         ON oe.id_orden              = o.id_orden
    JOIN pacientes p       ON o.id_paciente            = p.id_paciente
    WHERE r.es_critico = 1 AND r.validado = 0 AND o.eliminado = 0;

-- Vista de usuarios activos (sin PII)
CREATE OR REPLACE VIEW v_usuarios_activos AS
    SELECT id_usuario, username, email, rol, estado,
           ultimo_acceso, intentos_fallidos, fecha_creacion
    FROM usuarios
    WHERE eliminado = 0 AND estado = 'activo';


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 7 — DATOS INICIALES
-- ══════════════════════════════════════════════════════════════

-- 7.1 Configuración del sistema
INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion) VALUES
('lab_nombre',              'GonzaloLabs',                          'texto',   'Nombre del laboratorio'),
('lab_ruc',                 '0000000000001',                        'texto',   'RUC del laboratorio'),
('lab_direccion',           'Guayaquil, Ecuador',                   'texto',   'Dirección del laboratorio'),
('lab_telefono',            '042000000',                            'texto',   'Teléfono principal'),
('lab_email',               'lab@gonzalolabs.com',                  'texto',   'Email de contacto'),
('token_expiracion_horas',  '720',                                  'numero',  'Horas de validez del token QR (30 días)'),
('max_intentos_login',      '5',                                    'numero',  'Intentos fallidos antes de bloquear'),
('lockout_minutos',         '30',                                   'numero',  'Minutos de bloqueo tras intentos fallidos'),
('resultado_footer_texto',  'Este reporte es de uso médico exclusivo y confidencial.',
                                                                    'texto',   'Texto legal al pie de resultados'),
('iva_porcentaje',          '15',                                   'numero',  'IVA vigente Ecuador 2024'),
('moneda_simbolo',          '$',                                    'texto',   'Símbolo de moneda'),
('version_sistema',         '3.0.0',                               'texto',   'Versión del sistema');

-- 7.2 Categorías de exámenes
INSERT INTO categorias_examenes (nombre, descripcion, color_hex, icono, orden_visualizacion) VALUES
('Hematología',         'Exámenes de sangre y componentes sanguíneos',  '#ef4444', 'bi-droplet-fill',     1),
('Química Sanguínea',   'Glucosa, lípidos, función renal y hepática',   '#f97316', 'bi-flask-fill',       2),
('Hormonas',            'Tiroides, cortisol, función hormonal',         '#8b5cf6', 'bi-activity',         3),
('Microbiología',       'Cultivos, antibiogramas, parasitología',       '#10b981', 'bi-bug-fill',         4),
('Uroanálisis',         'Orina completa y urocultivo',                  '#06b6d4', 'bi-eyedropper',       5),
('Serología',           'HIV, hepatitis, RPR, toxoplasmosis',           '#3b82f6', 'bi-shield-check',     6),
('Inmunología',         'Anticuerpos, alergias, autoinmunidad',         '#ec4899', 'bi-heart-pulse',      7),
('Coagulación',         'TP, TTP, INR, fibrinógeno',                    '#f59e0b', 'bi-bandaid',          8),
('Marcadores Tumorales','PSA, CEA, CA125, AFP',                         '#6366f1', 'bi-clipboard2-pulse', 9),
('Genética Molecular',  'PCR, FISH, cariotipo',                         '#14b8a6', 'bi-diagram-3',        10);

-- 7.3 Exámenes de Hematología
INSERT INTO examenes (id_categoria, codigo, nombre, precio, tiempo_entrega_min, requiere_ayuno) VALUES
(1, 'HEM-001', 'Biometría Hemática Completa',   12.00, 60, 0),
(1, 'HEM-002', 'Grupo Sanguíneo y Factor Rh',    5.00, 30, 0),
(1, 'HEM-003', 'Velocidad de Sedimentación',      6.00, 60, 0),
(1, 'HEM-004', 'Recuento de Reticulocitos',        8.00, 60, 0),
(1, 'HEM-005', 'Frotis de Sangre Periférica',     10.00, 90, 0);

-- 7.4 Exámenes de Química Sanguínea
INSERT INTO examenes (id_categoria, codigo, nombre, precio, tiempo_entrega_min, requiere_ayuno, instrucciones_paciente) VALUES
(2, 'QUI-001', 'Glucosa en Ayunas',                     5.00,  30, 1, 'Ayuno mínimo 8 horas'),
(2, 'QUI-002', 'Perfil Lipídico Completo',             18.00,  60, 1, 'Ayuno 12 horas. No alcohol 48h antes'),
(2, 'QUI-003', 'Función Renal (BUN + Creatinina)',     12.00,  45, 0, NULL),
(2, 'QUI-004', 'Función Hepática Completa',            20.00,  60, 1, 'Ayuno 8 horas'),
(2, 'QUI-005', 'Ácido Úrico',                            6.00,  30, 1, 'Ayuno 8 horas'),
(2, 'QUI-006', 'Hemoglobina Glicosilada (HbA1c)',      14.00,  60, 0, NULL),
(2, 'QUI-007', 'Electrolitos (Na, K, Cl)',             15.00,  45, 0, NULL);

-- 7.5 Exámenes de Hormonas
INSERT INTO examenes (id_categoria, codigo, nombre, precio, tiempo_entrega_min, requiere_ayuno) VALUES
(3, 'HOR-001', 'TSH (Hormona Estimulante de Tiroides)', 18.00, 60, 1),
(3, 'HOR-002', 'T3 Libre',                              14.00, 60, 0),
(3, 'HOR-003', 'T4 Libre',                              14.00, 60, 0),
(3, 'HOR-004', 'Insulina en Ayunas',                    20.00, 60, 1),
(3, 'HOR-005', 'Cortisol en Ayunas',                    18.00, 60, 1);

-- 7.6 Parámetros de Biometría Hemática (HEM-001)
SET @hbc = (SELECT id_examen FROM examenes WHERE codigo = 'HEM-001');
INSERT INTO parametros_examen (id_examen, codigo_parametro, nombre_parametro, unidad_medida, tipo_dato, orden_visualizacion) VALUES
(@hbc, 'HB',    'Hemoglobina',                    'g/dL',       'numerico', 1),
(@hbc, 'HTO',   'Hematocrito',                    '%',          'numerico', 2),
(@hbc, 'GR',    'Glóbulos Rojos',                 'x10⁶/µL',   'numerico', 3),
(@hbc, 'GB',    'Glóbulos Blancos',               'x10³/µL',   'numerico', 4),
(@hbc, 'PLQ',   'Plaquetas',                      'x10³/µL',   'numerico', 5),
(@hbc, 'VCM',   'Volumen Corpuscular Medio',       'fL',         'numerico', 6),
(@hbc, 'HCM',   'Hemoglobina Corpuscular Media',   'pg',         'numerico', 7),
(@hbc, 'CHCM',  'Conc. HCM',                       'g/dL',       'numerico', 8),
(@hbc, 'NEUT',  'Neutrófilos',                     '%',          'numerico', 9),
(@hbc, 'LINF',  'Linfocitos',                      '%',          'numerico', 10),
(@hbc, 'MONO',  'Monocitos',                       '%',          'numerico', 11),
(@hbc, 'EOSI',  'Eosinófilos',                     '%',          'numerico', 12),
(@hbc, 'BASO',  'Basófilos',                       '%',          'numerico', 13);

-- Rangos de referencia: Hemoglobina
SET @hb = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='HB' AND id_examen=@hbc);
INSERT INTO valores_referencia (id_parametro, genero, valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico, descripcion_rango) VALUES
(@hb, 'M',     13.5, 17.5, 7.0, 20.0, 'Hombres adultos — Fuente: OPS/Ref. Clín. Ecuador'),
(@hb, 'F',     12.0, 16.0, 7.0, 20.0, 'Mujeres adultas'),
(@hb, 'Ambos',  9.5, 16.5, 7.0, 20.0, 'Pediátrico 2-12 años (genérico)');

-- Rangos: Glóbulos Blancos
SET @gb = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='GB' AND id_examen=@hbc);
INSERT INTO valores_referencia (id_parametro, genero, valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico, descripcion_rango) VALUES
(@gb, 'Ambos', 4.5, 11.0, 2.0, 30.0, 'Leucocitos adultos');

-- Rangos: Plaquetas
SET @plq = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='PLQ' AND id_examen=@hbc);
INSERT INTO valores_referencia (id_parametro, genero, valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico, descripcion_rango) VALUES
(@plq, 'Ambos', 150, 400, 50, 1000, 'Trombocitos adultos');

-- 7.7 Parámetros Glucosa (QUI-001)
SET @glc_exa = (SELECT id_examen FROM examenes WHERE codigo = 'QUI-001');
INSERT INTO parametros_examen (id_examen, codigo_parametro, nombre_parametro, unidad_medida, tipo_dato) VALUES
(@glc_exa, 'GLUC', 'Glucosa en Ayunas', 'mg/dL', 'numerico');
SET @gluc = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='GLUC' AND id_examen=@glc_exa);
INSERT INTO valores_referencia (id_parametro, genero, valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico, descripcion_rango) VALUES
(@gluc, 'Ambos', 70, 100, 40, 500, 'Ayunas — Normal <100 / Prediabetes 100-125 / Diabetes ≥126');

-- 7.8 Parámetros Perfil Lipídico (QUI-002)
SET @lip = (SELECT id_examen FROM examenes WHERE codigo = 'QUI-002');
INSERT INTO parametros_examen (id_examen, codigo_parametro, nombre_parametro, unidad_medida, tipo_dato, orden_visualizacion) VALUES
(@lip, 'COL',  'Colesterol Total',   'mg/dL', 'numerico', 1),
(@lip, 'LDL',  'LDL Colesterol',     'mg/dL', 'numerico', 2),
(@lip, 'HDL',  'HDL Colesterol',     'mg/dL', 'numerico', 3),
(@lip, 'TRI',  'Triglicéridos',      'mg/dL', 'numerico', 4),
(@lip, 'VLDL', 'VLDL',              'mg/dL', 'numerico', 5);
SET @col = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='COL' AND id_examen=@lip);
SET @tri = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='TRI' AND id_examen=@lip);
INSERT INTO valores_referencia (id_parametro, genero, valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico, descripcion_rango) VALUES
(@col, 'Ambos', NULL, 200, NULL, 300, 'Deseable <200 / Limítrofe 200-239 / Alto ≥240'),
(@tri, 'Ambos', NULL, 150, NULL, 1000, 'Normal <150 / Alto ≥200 / Muy alto ≥500');

-- 7.9 Parámetros TSH (HOR-001)
SET @tsh_exa = (SELECT id_examen FROM examenes WHERE codigo = 'HOR-001');
INSERT INTO parametros_examen (id_examen, codigo_parametro, nombre_parametro, unidad_medida, tipo_dato) VALUES
(@tsh_exa, 'TSH', 'TSH (Hormona Tirotropina)', 'µUI/mL', 'numerico');
SET @tsh = (SELECT id_parametro FROM parametros_examen WHERE codigo_parametro='TSH' AND id_examen=@tsh_exa);
INSERT INTO valores_referencia (id_parametro, genero, valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico, descripcion_rango) VALUES
(@tsh, 'Ambos', 0.4, 4.0, 0.01, 25.0, 'Adultos. Hipertiroidismo <0.4 / Hipotiroidismo >4.0');

-- 7.10 Usuario administrador inicial
-- Contraseña: password  (hash bcrypt costo 12)
-- ⚠ CAMBIAR EN PRIMER LOGIN ⚠
INSERT INTO usuarios (username, email, password_hash, nombre_completo, cedula, rol, estado)
VALUES (
    'admin',
    'admin@gonzalolabs.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NULL, NULL,
    'administrador',
    'activo'
);


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 8 — RESTAURAR CONFIGURACIÓN
-- ══════════════════════════════════════════════════════════════
SET SQL_MODE             = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS   = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS        = @OLD_UNIQUE_CHECKS;


-- ══════════════════════════════════════════════════════════════
--  SECCIÓN 9 — CORRECCIONES REQUERIDAS EN PHP
-- ══════════════════════════════════════════════════════════════
/*
  ┌─────────────────────────────────────────────────────────────┐
  │  CAMBIOS OBLIGATORIOS PARA COMPATIBILIDAD CON v3.0          │
  ├─────────────────────────────────────────────────────────────┤
  │                                                             │
  │  1. app/models/Examen.php → masSolicitados()                │
  │     Cambiar alias:  AS total  →  AS total_solicitado        │
  │     (ReporteController y vista dashboard usan total_        │
  │      solicitado, la view v_examenes_solicitados también)    │
  │                                                             │
  │  2. app/models/Paciente.php → buscarPorCedula()             │
  │     Reemplazar:                                             │
  │       WHERE cedula = AES_ENCRYPT(?, '$key')                 │
  │     Por:                                                    │
  │       WHERE cedula_hash = SHA2(?, 256)                      │
  │                                                             │
  │  3. app/models/Paciente.php → crear()                       │
  │     Agregar campo al INSERT:                                │
  │       cedula_hash = SHA2(?, 256)                            │
  │     Y el parámetro correspondiente en el array de params    │
  │                                                             │
  │  4. app/models/Paciente.php → actualizar()                  │
  │     Si se permite actualizar la cédula, actualizar también  │
  │     el cedula_hash en el UPDATE.                            │
  │                                                             │
  │  5. CLAVE DE CIFRADO en .env                                │
  │     DB_ENCRYPTION_KEY=<32 bytes hex>                        │
  │     Generar con:  openssl rand -hex 32                      │
  │     NUNCA commitear este archivo al repositorio.            │
  │     Agregar .env al .gitignore.                             │
  │                                                             │
  │  6. event_scheduler en my.cnf / my.ini                      │
  │     Agregar:  event_scheduler = ON                          │
  │     O ejecutar una vez:  SET GLOBAL event_scheduler = ON;   │
  │                                                             │
  └─────────────────────────────────────────────────────────────┘
*/