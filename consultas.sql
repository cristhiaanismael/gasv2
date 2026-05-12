SELECT * FROM inggeinc_marvifet.edificios;
CREATE TABLE IF NOT EXISTS movimientos (
  id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
  id_departamento INT NOT NULL,
  tipo ENUM('cargo','pago','ajuste') NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  referencia_id INT NULL,
  referencia_tipo VARCHAR(20) NULL, -- 'lectura' | 'abono' | 'manual'
  descripcion VARCHAR(255) NULL,
  fecha DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dep_fecha (id_departamento, fecha),
  INDEX idx_ref (referencia_tipo, referencia_id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS estado_cuenta (
  id_departamento INT PRIMARY KEY,
  saldo_actual DECIMAL(12,2) NOT NULL DEFAULT 0,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


INSERT INTO movimientos (
  id_departamento, tipo, monto,
  referencia_id, referencia_tipo, descripcion, fecha
)
SELECT
  l.id_departamento,
  'cargo',
  ROUND(IFNULL(l.monto,0) + IFNULL(l.cuota_admin,0) + IFNULL(l.cargos_add,0), 2) AS monto,
  l.id_lectura,
  'lectura',
  CONCAT('Consumo periodo ', l.periodo),
  l.fecha_register
FROM lectura l;



INSERT INTO movimientos (
  id_departamento, tipo, monto,
  referencia_id, referencia_tipo, descripcion, fecha
)
SELECT
  a.id_departamento,
  'pago',
  ROUND(IFNULL(a.cantidad_abonada,0), 2),
  a.id_abono,
  'abono',
  'Abono cliente',
  a.fecha_registro
FROM abonos a;


INSERT INTO estado_cuenta (id_departamento, saldo_actual)
SELECT
  m.id_departamento,
  ROUND(SUM(
    CASE WHEN m.tipo='cargo' THEN m.monto
         WHEN m.tipo='pago'  THEN -m.monto
         ELSE m.monto END
  ),2) AS saldo
FROM movimientos m
GROUP BY m.id_departamento
ON DUPLICATE KEY UPDATE saldo_actual = VALUES(saldo_actual);



SELECT
  m.id_departamento,
  ROUND(SUM(CASE WHEN m.tipo='cargo' THEN m.monto
                 WHEN m.tipo='pago'  THEN -m.monto
                 ELSE m.monto END),2) AS saldo_nuevo
FROM movimientos m
GROUP BY m.id_departamento;


-- último registro por depto (según fecha_registro)
WITH ult AS (
  SELECT l.*
  FROM lectura l
  JOIN (
    SELECT id_departamento, MAX(fecha_register) AS maxf
    FROM lectura
    GROUP BY id_departamento
  ) t
  ON l.id_departamento = t.id_departamento AND l.fecha_register = t.maxf
)
SELECT
  u.id_departamento,
  u.total_a_pagar AS saldo_viejo,
  n.saldo_nuevo,
  ROUND(u.total_a_pagar - n.saldo_nuevo,2) AS diferencia
FROM ult u
JOIN (
  SELECT id_departamento,
         ROUND(SUM(CASE WHEN tipo='cargo' THEN monto
                        WHEN tipo='pago'  THEN -monto END),2) AS saldo_nuevo
  FROM movimientos
  GROUP BY id_departamento
) n ON n.id_departamento = u.id_departamento
WHERE ABS(ROUND(u.total_a_pagar - n.saldo_nuevo,2)) > 0.01;


DELIMITER $$

CREATE TRIGGER trg_mov_insert
AFTER INSERT ON movimientos
FOR EACH ROW
BEGIN
    INSERT INTO estado_cuenta (id_departamento, saldo_actual)
    VALUES (
        NEW.id_departamento,
        CASE 
            WHEN NEW.tipo = 'cargo' THEN NEW.monto
            WHEN NEW.tipo = 'pago' THEN -NEW.monto
            ELSE NEW.monto
        END
    )
    ON DUPLICATE KEY UPDATE
        saldo_actual = saldo_actual + 
        CASE 
            WHEN NEW.tipo = 'cargo' THEN NEW.monto
            WHEN NEW.tipo = 'pago' THEN -NEW.monto
            ELSE NEW.monto
        END;
END$$

DELIMITER ;


DELIMITER $$

CREATE TRIGGER trg_mov_delete
AFTER DELETE ON movimientos
FOR EACH ROW
BEGIN
    UPDATE estado_cuenta
    SET saldo_actual = saldo_actual -
        CASE 
            WHEN OLD.tipo = 'cargo' THEN OLD.monto
            WHEN OLD.tipo = 'pago' THEN -OLD.monto
            ELSE OLD.monto
        END
    WHERE id_departamento = OLD.id_departamento;
END$$

DELIMITER ;


DELIMITER $$

CREATE TRIGGER trg_mov_update
AFTER UPDATE ON movimientos
FOR EACH ROW
BEGIN
    -- Revertir el valor anterior
    UPDATE estado_cuenta
    SET saldo_actual = saldo_actual -
        CASE 
            WHEN OLD.tipo = 'cargo' THEN OLD.monto
            WHEN OLD.tipo = 'pago' THEN -OLD.monto
            ELSE OLD.monto
        END
    WHERE id_departamento = OLD.id_departamento;

    -- Aplicar el nuevo valor
    INSERT INTO estado_cuenta (id_departamento, saldo_actual)
    VALUES (
        NEW.id_departamento,
        CASE 
            WHEN NEW.tipo = 'cargo' THEN NEW.monto
            WHEN NEW.tipo = 'pago' THEN -NEW.monto
            ELSE NEW.monto
        END
    )
    ON DUPLICATE KEY UPDATE
        saldo_actual = saldo_actual + 
        CASE 
            WHEN NEW.tipo = 'cargo' THEN NEW.monto
            WHEN NEW.tipo = 'pago' THEN -NEW.monto
            ELSE NEW.monto
        END;
END$$

DELIMITER ;

