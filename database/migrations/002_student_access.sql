CREATE TABLE turma_acesso (
 tenant_id BIGINT UNSIGNED NOT NULL,
 turma_id BIGINT UNSIGNED NOT NULL,
 individual BOOLEAN NOT NULL DEFAULT TRUE,
 compartilhado BOOLEAN NOT NULL DEFAULT FALSE,
 remoto BOOLEAN NOT NULL DEFAULT FALSE,
 senha_hash VARCHAR(255) NULL,
 versao INT UNSIGNED NOT NULL DEFAULT 1,
 PRIMARY KEY (tenant_id,turma_id),
 FOREIGN KEY (tenant_id,turma_id) REFERENCES turmas(tenant_id,id),
 CHECK (compartilhado=0 OR senha_hash IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE acesso_desafios (
 id BINARY(16) PRIMARY KEY,
 codigo_hash BINARY(32) NOT NULL UNIQUE,
 navegador_hash BINARY(32) NOT NULL,
 tenant_id BIGINT UNSIGNED NULL,
 turma_id BIGINT UNSIGNED NULL,
 aluno_id BIGINT UNSIGNED NULL,
 expira_em DATETIME NOT NULL,
 autorizado_em DATETIME NULL,
 consumido_em DATETIME NULL,
 KEY ix_expira (expira_em),
 FOREIGN KEY (tenant_id,turma_id,aluno_id) REFERENCES matriculas(tenant_id,turma_id,aluno_id),
 CHECK ((tenant_id IS NULL AND turma_id IS NULL AND aluno_id IS NULL) OR (tenant_id IS NOT NULL AND turma_id IS NOT NULL AND aluno_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
