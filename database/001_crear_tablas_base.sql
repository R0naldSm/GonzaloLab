-- ============================================
-- BASE DE DATOS: GonzaloLabs
-- Sistema de Gestión de Laboratorio Clínico
-- ============================================

CREATE DATABASE IF NOT EXISTS gonzalolabs_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE gonzalolabs_db;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARBINARY(255) NOT NULL, -- Encriptado
    cedula VARBINARY(128) NOT NULL, -- Encriptado
    rol ENUM('administrador', 'analistaL', 'medico', 'paciente') NOT NULL,
    estado ENUM('activo', 'inactivo', 'bloqueado') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    ultimo_acceso DATETIME,
    intentos_fallidos INT DEFAULT 0,
    token_recuperacion VARCHAR(255),
    token_expiracion DATETIME,
    eliminado BOOLEAN DEFAULT FALSE,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: pacientes
-- ============================================
CREATE TABLE pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARBINARY(128) UNIQUE NOT NULL, -- Encriptado
    nombres VARBINARY(255) NOT NULL, -- Encriptado
    apellidos VARBINARY(255) NOT NULL, -- Encriptado
    fecha_nacimiento DATE,
    genero ENUM('M', 'F', 'Otro'),
    telefono VARBINARY(128), -- Encriptado
    email VARBINARY(128), -- Encriptado
    direccion TEXT,
    tipo_sangre VARCHAR(5),
    alergias TEXT,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    registrado_por INT,
    eliminado BOOLEAN DEFAULT FALSE,
    INDEX idx_cedula (cedula),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: categorias_examenes
-- ============================================
CREATE TABLE categorias_examenes (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    color_hex VARCHAR(7) DEFAULT '#007bff',
    orden_visualizacion INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    eliminado BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

-- Insertar categorías predefinidas
INSERT INTO categorias_examenes (nombre, color_hex, orden_visualizacion) VALUES
('Hematología', '#e74c3c', 1),
('Bioquímica', '#3498db', 2),
('Microbiología', '#2ecc71', 3),
('Inmunología', '#9b59b6', 4),
('Genética', '#f39c12', 5),
('Serología', '#1abc9c', 6),
('Toxicología', '#e67e22', 7),
('Inmunohematología', '#16a085', 8),
('Uroanálisis', '#27ae60', 9),
('Coprología', '#8e44ad', 10);

-- ============================================
-- TABLA: examenes
-- ============================================
CREATE TABLE examenes (
    id_examen INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    id_categoria INT NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2),
    tiempo_entrega_min INT COMMENT 'Minutos para exámenes rápidos',
    tiempo_entrega_dias INT COMMENT 'Días para exámenes estándar',
    requiere_ayuno BOOLEAN DEFAULT FALSE,
    instrucciones_paciente TEXT,
    metodo_analisis VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    eliminado BOOLEAN DEFAULT FALSE,
    INDEX idx_codigo (codigo),
    INDEX idx_categoria (id_categoria),
    FOREIGN KEY (id_categoria) REFERENCES categorias_examenes(id_categoria)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: parametros_examen
-- ============================================
CREATE TABLE parametros_examen (
    id_parametro INT AUTO_INCREMENT PRIMARY KEY,
    id_examen INT NOT NULL,
    nombre_parametro VARCHAR(200) NOT NULL,
    unidad_medida VARCHAR(50),
    orden_visualizacion INT DEFAULT 0,
    tipo_dato ENUM('numerico', 'texto', 'seleccion', 'positivo_negativo') DEFAULT 'numerico',
    opciones_seleccion TEXT COMMENT 'JSON con opciones si tipo_dato = seleccion',
    activo BOOLEAN DEFAULT TRUE,
    eliminado BOOLEAN DEFAULT FALSE,
    INDEX idx_examen (id_examen),
    FOREIGN KEY (id_examen) REFERENCES examenes(id_examen)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: valores_referencia
-- ============================================
CREATE TABLE valores_referencia (
    id_referencia INT AUTO_INCREMENT PRIMARY KEY,
    id_parametro INT NOT NULL,
    genero ENUM('M', 'F', 'Ambos') DEFAULT 'Ambos',
    edad_min INT COMMENT 'Edad mínima en años',
    edad_max INT COMMENT 'Edad máxima en años',
    valor_min_normal DECIMAL(15,4),
    valor_max_normal DECIMAL(15,4),
    valor_min_critico DECIMAL(15,4),
    valor_max_critico DECIMAL(15,4),
    descripcion_rango TEXT,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_parametro (id_parametro),
    FOREIGN KEY (id_parametro) REFERENCES parametros_examen(id_parametro)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: ordenes
-- ============================================
CREATE TABLE ordenes (
    id_orden INT AUTO_INCREMENT PRIMARY KEY,
    numero_orden VARCHAR(20) UNIQUE NOT NULL,
    id_paciente INT NOT NULL,
    id_medico INT,
    fecha_orden DATETIME NOT NULL,
    fecha_toma_muestra DATETIME,
    estado ENUM('creada', 'en_proceso', 'resultados_cargados', 'validada', 'publicada', 'expirada') DEFAULT 'creada',
    observaciones_recibo TEXT,
    observaciones_laboratorista TEXT,
    observaciones_resultados TEXT,
    tipo_atencion ENUM('control', 'urgencia', 'normal') DEFAULT 'normal',
    sucursal VARCHAR(100),
    total_pagar DECIMAL(10,2),
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'credito'),
    estado_pago ENUM('pendiente', 'pagado', 'anulado') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NOT NULL,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modificado_por INT,
    eliminado BOOLEAN DEFAULT FALSE,
    INDEX idx_numero_orden (numero_orden),
    INDEX idx_paciente (id_paciente),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_orden),
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente),
    FOREIGN KEY (id_medico) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: orden_examenes
-- ============================================
CREATE TABLE orden_examenes (
    id_orden_examen INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    id_examen INT NOT NULL,
    precio_unitario DECIMAL(10,2),
    estado ENUM('pendiente', 'en_proceso', 'completado') DEFAULT 'pendiente',
    fecha_resultado_estimada DATETIME,
    INDEX idx_orden (id_orden),
    INDEX idx_examen (id_examen),
    FOREIGN KEY (id_orden) REFERENCES ordenes(id_orden),
    FOREIGN KEY (id_examen) REFERENCES examenes(id_examen)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: resultados
-- ============================================
CREATE TABLE resultados (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    id_orden_examen INT NOT NULL,
    id_parametro INT NOT NULL,
    valor_resultado VARCHAR(500),
    es_critico BOOLEAN DEFAULT FALSE,
    observacion_critica TEXT,
    fecha_carga DATETIME NOT NULL,
    cargado_por INT NOT NULL,
    metodo_carga ENUM('manual', 'automatico', 'importacion') DEFAULT 'manual',
    validado BOOLEAN DEFAULT FALSE,
    fecha_validacion DATETIME,
    validado_por INT,
    version INT DEFAULT 1 COMMENT 'Control de versiones de resultados',
    INDEX idx_orden_examen (id_orden_examen),
    INDEX idx_parametro (id_parametro),
    INDEX idx_critico (es_critico),
    FOREIGN KEY (id_orden_examen) REFERENCES orden_examenes(id_orden_examen),
    FOREIGN KEY (id_parametro) REFERENCES parametros_examen(id_parametro),
    FOREIGN KEY (cargado_por) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (validado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: acceso_resultados_publico
-- ============================================
CREATE TABLE acceso_resultados_publico (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    token_acceso VARCHAR(255) UNIQUE NOT NULL,
    fecha_generacion DATETIME NOT NULL,
    fecha_expiracion DATETIME NOT NULL,
    intentos_acceso INT DEFAULT 0,
    ultimo_acceso DATETIME,
    ip_ultimo_acceso VARCHAR(45),
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_token (token_acceso),
    INDEX idx_orden (id_orden),
    FOREIGN KEY (id_orden) REFERENCES ordenes(id_orden)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: cotizaciones
-- ============================================
CREATE TABLE cotizaciones (
    id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    numero_cotizacion VARCHAR(20) UNIQUE NOT NULL,
    id_paciente INT,
    nombre_cliente VARCHAR(255),
    fecha_cotizacion DATETIME NOT NULL,
    fecha_validez DATE,
    subtotal DECIMAL(10,2),
    descuento DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2),
    estado ENUM('vigente', 'aceptada', 'rechazada', 'expirada') DEFAULT 'vigente',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NOT NULL,
    eliminado BOOLEAN DEFAULT FALSE,
    INDEX idx_numero (numero_cotizacion),
    INDEX idx_paciente (id_paciente),
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: cotizacion_examenes
-- ============================================
CREATE TABLE cotizacion_examenes (
    id_cotizacion_examen INT AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion INT NOT NULL,
    id_examen INT NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    INDEX idx_cotizacion (id_cotizacion),
    INDEX idx_examen (id_examen),
    FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion),
    FOREIGN KEY (id_examen) REFERENCES examenes(id_examen)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: auditoria
-- ============================================
CREATE TABLE auditoria (
    id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    operacion ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'ACCESO_DENEGADO') NOT NULL,
    id_registro INT,
    usuario_id INT,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tabla (tabla),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_hora),
    INDEX idx_operacion (operacion)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: configuracion_sistema
-- ============================================
CREATE TABLE configuracion_sistema (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descripcion TEXT,
    tipo_dato ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modificado_por INT,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- Configuraciones iniciales
INSERT INTO configuracion_sistema (clave, valor, descripcion) VALUES
('nombre_laboratorio', 'GonzaloLabs', 'Nombre del laboratorio'),
('tiempo_expiracion_rapido_min', '120', 'Tiempo expiración exámenes rápidos (minutos)'),
('tiempo_expiracion_estandar_dias', '30', 'Tiempo expiración exámenes estándar (días)'),
('max_intentos_login', '5', 'Máximo de intentos fallidos de login'),
('tiempo_bloqueo_min', '30', 'Tiempo de bloqueo después de intentos fallidos (minutos)'),
('alertas_criticas_email', 'true', 'Enviar alertas de valores críticos por email'),
('logo_laboratorio', '', 'Ruta del logo'),
('encryption_key', '', 'Clave de encriptación (generada automáticamente)');

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================
CREATE INDEX idx_paciente_nombre ON pacientes(nombres(50));
CREATE INDEX idx_orden_fecha_estado ON ordenes(fecha_orden, estado);
CREATE INDEX idx_resultado_critico_fecha ON resultados(es_critico, fecha_carga);

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista de órdenes con información del paciente
CREATE VIEW v_ordenes_completas AS
SELECT 
    o.id_orden,
    o.numero_orden,
    o.fecha_orden,
    o.estado,
    o.tipo_atencion,
    p.cedula,
    p.nombres,
    p.apellidos,
    u.nombre_completo as nombre_medico,
    o.total_pagar,
    o.estado_pago,
    COUNT(DISTINCT oe.id_examen) as total_examenes,
    SUM(CASE WHEN r.es_critico = TRUE THEN 1 ELSE 0 END) as valores_criticos
FROM ordenes o
JOIN pacientes p ON o.id_paciente = p.id_paciente
LEFT JOIN usuarios u ON o.id_medico = u.id_usuario
LEFT JOIN orden_examenes oe ON o.id_orden = oe.id_orden
LEFT JOIN resultados r ON oe.id_orden_examen = r.id_orden_examen
WHERE o.eliminado = FALSE
GROUP BY o.id_orden;

-- Vista de estadísticas del día
CREATE VIEW v_estadisticas_hoy AS
SELECT 
    DATE(o.fecha_orden) as fecha,
    COUNT(DISTINCT o.id_orden) as total_ordenes,
    COUNT(DISTINCT o.id_paciente) as total_pacientes,
    COUNT(DISTINCT oe.id_examen) as total_examenes,
    SUM(o.total_pagar) as ingresos_total,
    SUM(CASE WHEN o.estado = 'validada' THEN 1 ELSE 0 END) as ordenes_completadas,
    SUM(CASE WHEN r.es_critico = TRUE THEN 1 ELSE 0 END) as alertas_criticas
FROM ordenes o
LEFT JOIN orden_examenes oe ON o.id_orden = oe.id_orden
LEFT JOIN resultados r ON oe.id_orden_examen = r.id_orden_examen
WHERE o.eliminado = FALSE
  AND DATE(o.fecha_orden) = CURDATE()
GROUP BY DATE(o.fecha_orden);