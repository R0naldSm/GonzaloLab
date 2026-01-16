-- ============================================
-- PROCEDIMIENTOS ALMACENADOS - GonzaloLabs
-- Seguridad: Prevención SQL Injection
-- ============================================

USE gonzalolabs_db;

DELIMITER $$

-- ============================================
-- SP: Crear Usuario con Encriptación
-- ============================================
DROP PROCEDURE IF EXISTS sp_crear_usuario$$
CREATE PROCEDURE sp_crear_usuario(
    IN p_username VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_password_hash VARCHAR(255),
    IN p_nombre_completo VARCHAR(255),
    IN p_cedula VARCHAR(20),
    IN p_rol ENUM('administrador', 'analistaL', 'medico', 'paciente'),
    IN p_creado_por INT,
    IN p_encryption_key VARCHAR(255),
    OUT p_resultado INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje = 'Error al crear usuario';
    END;
    
    START TRANSACTION;
    
    -- Verificar si el usuario ya existe
    IF EXISTS(SELECT 1 FROM usuarios WHERE username = p_username OR email = p_email) THEN
        SET p_resultado = 0;
        SET p_mensaje = 'El usuario o email ya existe';
        ROLLBACK;
    ELSE
        -- Insertar con datos encriptados
        INSERT INTO usuarios (
            username, 
            email, 
            password_hash, 
            nombre_completo, 
            cedula, 
            rol, 
            creado_por,
            estado
        ) VALUES (
            p_username,
            p_email,
            p_password_hash,
            AES_ENCRYPT(p_nombre_completo, p_encryption_key),
            AES_ENCRYPT(p_cedula, p_encryption_key),
            p_rol,
            p_creado_por,
            'activo'
        );
        
        SET p_resultado = LAST_INSERT_ID();
        SET p_mensaje = 'Usuario creado exitosamente';
        
        -- Auditoría
        INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, username)
        VALUES ('usuarios', 'INSERT', p_resultado, p_creado_por, p_username);
        
        COMMIT;
    END IF;
END$$

-- ============================================
-- SP: Crear Paciente con Datos Encriptados
-- ============================================
DROP PROCEDURE IF EXISTS sp_crear_paciente$$
CREATE PROCEDURE sp_crear_paciente(
    IN p_cedula VARCHAR(20),
    IN p_nombres VARCHAR(255),
    IN p_apellidos VARCHAR(255),
    IN p_fecha_nacimiento DATE,
    IN p_genero ENUM('M', 'F', 'Otro'),
    IN p_telefono VARCHAR(20),
    IN p_email VARCHAR(100),
    IN p_direccion TEXT,
    IN p_tipo_sangre VARCHAR(5),
    IN p_registrado_por INT,
    IN p_encryption_key VARCHAR(255),
    OUT p_resultado INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje = 'Error al crear paciente';
    END;
    
    START TRANSACTION;
    
    -- Verificar si el paciente existe
    IF EXISTS(SELECT 1 FROM pacientes WHERE cedula = AES_ENCRYPT(p_cedula, p_encryption_key) AND eliminado = FALSE) THEN
        -- Retornar ID del paciente existente
        SELECT id_paciente INTO p_resultado 
        FROM pacientes 
        WHERE cedula = AES_ENCRYPT(p_cedula, p_encryption_key) AND eliminado = FALSE;
        
        SET p_mensaje = 'Paciente ya existe';
        COMMIT;
    ELSE
        INSERT INTO pacientes (
            cedula,
            nombres,
            apellidos,
            fecha_nacimiento,
            genero,
            telefono,
            email,
            direccion,
            tipo_sangre,
            registrado_por
        ) VALUES (
            AES_ENCRYPT(p_cedula, p_encryption_key),
            AES_ENCRYPT(p_nombres, p_encryption_key),
            AES_ENCRYPT(p_apellidos, p_encryption_key),
            p_fecha_nacimiento,
            p_genero,
            AES_ENCRYPT(p_telefono, p_encryption_key),
            AES_ENCRYPT(p_email, p_encryption_key),
            p_direccion,
            p_tipo_sangre,
            p_registrado_por
        );
        
        SET p_resultado = LAST_INSERT_ID();
        SET p_mensaje = 'Paciente creado exitosamente';
        
        -- Auditoría
        INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id)
        VALUES ('pacientes', 'INSERT', p_resultado, p_registrado_por);
        
        COMMIT;
    END IF;
END$$

-- ============================================
-- SP: Buscar Paciente por Cédula
-- ============================================
DROP PROCEDURE IF EXISTS sp_buscar_paciente_cedula$$
CREATE PROCEDURE sp_buscar_paciente_cedula(
    IN p_cedula VARCHAR(20),
    IN p_encryption_key VARCHAR(255)
)
BEGIN
    SELECT 
        id_paciente,
        AES_DECRYPT(cedula, p_encryption_key) as cedula,
        AES_DECRYPT(nombres, p_encryption_key) as nombres,
        AES_DECRYPT(apellidos, p_encryption_key) as apellidos,
        fecha_nacimiento,
        genero,
        AES_DECRYPT(telefono, p_encryption_key) as telefono,
        AES_DECRYPT(email, p_encryption_key) as email,
        direccion,
        tipo_sangre,
        fecha_registro
    FROM pacientes
    WHERE cedula = AES_ENCRYPT(p_cedula, p_encryption_key)
      AND eliminado = FALSE;
END$$

-- ============================================
-- SP: Crear Orden
-- ============================================
DROP PROCEDURE IF EXISTS sp_crear_orden$$
CREATE PROCEDURE sp_crear_orden(
    IN p_id_paciente INT,
    IN p_id_medico INT,
    IN p_examenes JSON,
    IN p_tipo_atencion ENUM('control', 'urgencia', 'normal'),
    IN p_observaciones TEXT,
    IN p_creado_por INT,
    OUT p_resultado INT,
    OUT p_numero_orden VARCHAR(20),
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_idx INT DEFAULT 0;
    DECLARE v_total_examenes INT;
    DECLARE v_id_examen INT;
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_fecha_estimada DATETIME;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje = 'Error al crear orden';
    END;
    
    START TRANSACTION;
    
    -- Generar número de orden único
    SET p_numero_orden = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 9999), 4, '0'));
    
    -- Crear la orden
    INSERT INTO ordenes (
        numero_orden,
        id_paciente,
        id_medico,
        fecha_orden,
        fecha_toma_muestra,
        estado,
        tipo_atencion,
        observaciones_recibo,
        creado_por
    ) VALUES (
        p_numero_orden,
        p_id_paciente,
        p_id_medico,
        NOW(),
        NOW(),
        'creada',
        p_tipo_atencion,
        p_observaciones,
        p_creado_por
    );
    
    SET p_resultado = LAST_INSERT_ID();
    
    -- Insertar exámenes de la orden
    SET v_total_examenes = JSON_LENGTH(p_examenes);
    
    WHILE v_idx < v_total_examenes DO
        SET v_id_examen = JSON_UNQUOTE(JSON_EXTRACT(p_examenes, CONCAT('$[', v_idx, '].id_examen')));
        
        -- Obtener precio y tiempo de entrega
        SELECT precio, 
               CASE 
                   WHEN tiempo_entrega_min IS NOT NULL THEN DATE_ADD(NOW(), INTERVAL tiempo_entrega_min MINUTE)
                   ELSE DATE_ADD(NOW(), INTERVAL tiempo_entrega_dias DAY)
               END
        INTO v_precio, v_fecha_estimada
        FROM examenes
        WHERE id_examen = v_id_examen AND eliminado = FALSE;
        
        -- Insertar examen en la orden
        INSERT INTO orden_examenes (
            id_orden,
            id_examen,
            precio_unitario,
            fecha_resultado_estimada
        ) VALUES (
            p_resultado,
            v_id_examen,
            v_precio,
            v_fecha_estimada
        );
        
        SET v_total = v_total + v_precio;
        SET v_idx = v_idx + 1;
    END WHILE;
    
    -- Actualizar total de la orden
    UPDATE ordenes SET total_pagar = v_total WHERE id_orden = p_resultado;
    
    SET p_mensaje = 'Orden creada exitosamente';
    
    -- Auditoría
    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id)
    VALUES ('ordenes', 'INSERT', p_resultado, p_creado_por);
    
    COMMIT;
END$$

-- ============================================
-- SP: Cargar Resultado y Verificar Críticos
-- ============================================
DROP PROCEDURE IF EXISTS sp_cargar_resultado$$
CREATE PROCEDURE sp_cargar_resultado(
    IN p_id_orden_examen INT,
    IN p_id_parametro INT,
    IN p_valor VARCHAR(500),
    IN p_cargado_por INT,
    IN p_metodo_carga ENUM('manual', 'automatico', 'importacion'),
    OUT p_resultado INT,
    OUT p_es_critico BOOLEAN,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_valor_num DECIMAL(15,4);
    DECLARE v_min_normal DECIMAL(15,4);
    DECLARE v_max_normal DECIMAL(15,4);
    DECLARE v_min_critico DECIMAL(15,4);
    DECLARE v_max_critico DECIMAL(15,4);
    DECLARE v_tipo_dato VARCHAR(50);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje = 'Error al cargar resultado';
    END;
    
    START TRANSACTION;
    
    -- Obtener tipo de dato del parámetro
    SELECT tipo_dato INTO v_tipo_dato
    FROM parametros_examen
    WHERE id_parametro = p_id_parametro;
    
    SET p_es_critico = FALSE;
    
    -- Verificar si es numérico y comparar con rangos
    IF v_tipo_dato = 'numerico' AND p_valor REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN
        SET v_valor_num = CAST(p_valor AS DECIMAL(15,4));
        
        -- Obtener valores de referencia
        SELECT valor_min_normal, valor_max_normal, valor_min_critico, valor_max_critico
        INTO v_min_normal, v_max_normal, v_min_critico, v_max_critico
        FROM valores_referencia
        WHERE id_parametro = p_id_parametro
        LIMIT 1;
        
        -- Verificar si es crítico
        IF (v_min_critico IS NOT NULL AND v_valor_num < v_min_critico) OR
           (v_max_critico IS NOT NULL AND v_valor_num > v_max_critico) THEN
            SET p_es_critico = TRUE;
        END IF;
    END IF;
    
    -- Insertar resultado
    INSERT INTO resultados (
        id_orden_examen,
        id_parametro,
        valor_resultado,
        es_critico,
        fecha_carga,
        cargado_por,
        metodo_carga
    ) VALUES (
        p_id_orden_examen,
        p_id_parametro,
        p_valor,
        p_es_critico,
        NOW(),
        p_cargado_por,
        p_metodo_carga
    );
    
    SET p_resultado = LAST_INSERT_ID();
    SET p_mensaje = IF(p_es_critico, 'Resultado cargado - VALOR CRÍTICO DETECTADO', 'Resultado cargado exitosamente');
    
    -- Auditoría
    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_nuevos)
    VALUES ('resultados', 'INSERT', p_resultado, p_cargado_por, JSON_OBJECT('es_critico', p_es_critico));
    
    COMMIT;
END$$

-- ============================================
-- SP: Generar Token de Acceso Público
-- ============================================
DROP PROCEDURE IF EXISTS sp_generar_token_acceso$$
CREATE PROCEDURE sp_generar_token_acceso(
    IN p_id_orden INT,
    IN p_tiempo_expiracion_minutos INT,
    OUT p_token VARCHAR(255),
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_token = NULL;
        SET p_mensaje = 'Error al generar token';
    END;
    
    START TRANSACTION;
    
    -- Generar token único
    SET p_token = SHA2(CONCAT(p_id_orden, NOW(), RAND()), 256);
    
    -- Insertar token
    INSERT INTO acceso_resultados_publico (
        id_orden,
        token_acceso,
        fecha_generacion,
        fecha_expiracion,
        activo
    ) VALUES (
        p_id_orden,
        p_token,
        NOW(),
        DATE_ADD(NOW(), INTERVAL p_tiempo_expiracion_minutos MINUTE),
        TRUE
    );
    
    SET p_mensaje = 'Token generado exitosamente';
    COMMIT;
END$$

-- ============================================
-- SP: Validar Token y Obtener Resultados
-- ============================================
DROP PROCEDURE IF EXISTS sp_validar_token_acceso$$
CREATE PROCEDURE sp_validar_token_acceso(
    IN p_token VARCHAR(255),
    IN p_ip_acceso VARCHAR(45),
    OUT p_valido BOOLEAN,
    OUT p_id_orden INT
)
BEGIN
    DECLARE v_fecha_expiracion DATETIME;
    DECLARE v_intentos INT;
    
    SET p_valido = FALSE;
    SET p_id_orden = NULL;
    
    -- Verificar token
    SELECT id_orden, fecha_expiracion, intentos_acceso
    INTO p_id_orden, v_fecha_expiracion, v_intentos
    FROM acceso_resultados_publico
    WHERE token_acceso = p_token
      AND activo = TRUE;
    
    IF p_id_orden IS NOT NULL THEN
        IF NOW() <= v_fecha_expiracion THEN
            SET p_valido = TRUE;
            
            -- Actualizar acceso
            UPDATE acceso_resultados_publico
            SET intentos_acceso = intentos_acceso + 1,
                ultimo_acceso = NOW(),
                ip_ultimo_acceso = p_ip_acceso
            WHERE token_acceso = p_token;
        ELSE
            -- Token expirado
            UPDATE acceso_resultados_publico
            SET activo = FALSE
            WHERE token_acceso = p_token;
        END IF;
    END IF;
END$$

-- ============================================
-- SP: Obtener Estadísticas Dashboard
-- ============================================
DROP PROCEDURE IF EXISTS sp_estadisticas_dashboard$$
CREATE PROCEDURE sp_estadisticas_dashboard(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    -- Total órdenes
    SELECT COUNT(*) as total_ordenes,
           SUM(CASE WHEN estado = 'validada' THEN 1 ELSE 0 END) as ordenes_completadas,
           SUM(CASE WHEN estado IN ('creada', 'en_proceso') THEN 1 ELSE 0 END) as ordenes_pendientes
    FROM ordenes
    WHERE DATE(fecha_orden) BETWEEN p_fecha_inicio AND p_fecha_fin
      AND eliminado = FALSE;
    
    -- Pacientes atendidos
    SELECT COUNT(DISTINCT id_paciente) as pacientes_unicos
    FROM ordenes
    WHERE DATE(fecha_orden) BETWEEN p_fecha_inicio AND p_fecha_fin
      AND eliminado = FALSE;
    
    -- Exámenes realizados
    SELECT COUNT(*) as total_examenes,
           e.nombre,
           c.nombre as categoria,
           COUNT(*) as cantidad
    FROM orden_examenes oe
    JOIN ordenes o ON oe.id_orden = o.id_orden
    JOIN examenes e ON oe.id_examen = e.id_examen
    JOIN categorias_examenes c ON e.id_categoria = c.id_categoria
    WHERE DATE(o.fecha_orden) BETWEEN p_fecha_inicio AND p_fecha_fin
      AND o.eliminado = FALSE
    GROUP BY e.id_examen
    ORDER BY cantidad DESC
    LIMIT 10;
    
    -- Valores críticos
    SELECT COUNT(*) as total_criticos
    FROM resultados r
    JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
    JOIN ordenes o ON oe.id_orden = o.id_orden
    WHERE r.es_critico = TRUE
      AND DATE(o.fecha_orden) BETWEEN p_fecha_inicio AND p_fecha_fin;
END$$

DELIMITER ;

-- ============================================
-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- ============================================

DELIMITER $$

-- Trigger para UPDATE en usuarios
DROP TRIGGER IF EXISTS trg_usuarios_update$$
CREATE TRIGGER trg_usuarios_update
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, datos_anteriores, datos_nuevos)
    VALUES (
        'usuarios',
        'UPDATE',
        NEW.id_usuario,
        NEW.id_usuario,
        JSON_OBJECT('estado', OLD.estado, 'rol', OLD.rol),
        JSON_OBJECT('estado', NEW.estado, 'rol', NEW.rol)
    );
END$$

-- Trigger para UPDATE en resultados (control de versiones)
DROP TRIGGER IF EXISTS trg_resultados_update$$
CREATE TRIGGER trg_resultados_update
BEFORE UPDATE ON resultados
FOR EACH ROW
BEGIN
    SET NEW.version = OLD.version + 1;
END$$

DELIMITER ;

-- ============================================
-- GRANTS Y PERMISOS (Ejemplo)
-- ============================================
-- Crear usuarios de base de datos con permisos limitados
-- GRANT SELECT, INSERT, UPDATE ON gonzalolabs_db.* TO 'lab_app'@'localhost' IDENTIFIED BY 'password_seguro';
-- GRANT EXECUTE ON gonzalolabs_db.* TO 'lab_app'@'localhost';