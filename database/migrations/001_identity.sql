-- MySQL 8 / InnoDB. Initial migration; apply once through migration runner.
CREATE TABLE tenants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(180) NOT NULL,
 status ENUM('ativo','suspenso') NOT NULL DEFAULT 'ativo',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE usuarios (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NULL,
 papel ENUM('admin','professor','aluno') NOT NULL,
 nome VARCHAR(180) NOT NULL,
 login VARCHAR(100) NOT NULL UNIQUE,
 email VARCHAR(254) NULL,
 senha_hash VARCHAR(255) NOT NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 trocar_senha BOOLEAN NOT NULL DEFAULT TRUE,
 session_version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_usuario_tenant (tenant_id,id),
 CONSTRAINT fk_usuario_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
 CONSTRAINT ck_usuario_papel CHECK ((papel='admin' AND tenant_id IS NULL) OR (papel<>'admin' AND tenant_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE turmas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 nome VARCHAR(120) NOT NULL,
 etapa TINYINT UNSIGNED NOT NULL,
 ano_letivo SMALLINT UNSIGNED NOT NULL,
 arquivada BOOLEAN NOT NULL DEFAULT FALSE,
 UNIQUE KEY uq_turma_tenant (tenant_id,id),
 KEY ix_turma_ano (tenant_id,ano_letivo,arquivada),
 CONSTRAINT ck_etapa CHECK (etapa BETWEEN 0 AND 9),
 FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE matriculas (
 tenant_id BIGINT UNSIGNED NOT NULL,
 turma_id BIGINT UNSIGNED NOT NULL,
 aluno_id BIGINT UNSIGNED NOT NULL,
 ativa BOOLEAN NOT NULL DEFAULT TRUE,
 PRIMARY KEY (tenant_id,turma_id,aluno_id),
 KEY ix_matricula_aluno (tenant_id,aluno_id),
 FOREIGN KEY (tenant_id,turma_id) REFERENCES turmas(tenant_id,id),
 FOREIGN KEY (tenant_id,aluno_id) REFERENCES usuarios(tenant_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Application must verify aluno role before enrollment.
CREATE TABLE auditoria (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NULL,
 ator_id BIGINT UNSIGNED NULL,
 evento VARCHAR(100) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_auditoria_tenant (tenant_id,created_at),
 FOREIGN KEY (tenant_id) REFERENCES tenants(id),
 FOREIGN KEY (ator_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
