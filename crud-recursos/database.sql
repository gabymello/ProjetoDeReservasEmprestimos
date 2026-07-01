-- =========================================================
-- Banco de dados: CRUD de Recursos
-- =========================================================

CREATE DATABASE IF NOT EXISTS crud_recursos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crud_recursos;

-- ---------------------------------------------------------
-- Tabela: categorias
-- OBS: Não foi informada a estrutura real de "categoria" no
-- seu sistema. Criei uma tabela simples de catálogo.
-- Se você já possui essa tabela, apague este bloco e ajuste
-- a FK em "recursos" para apontar para a tabela existente.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: setores
-- OBS: mesma observação da tabela de categorias acima.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: recursos
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    categoria_id INT NULL,          -- nullable: nem todo recurso precisa ter categoria definida
    setor_id INT NULL,              -- nullable: nem todo recurso precisa ter setor definido
    foto VARCHAR(255) NULL,         -- caminho do arquivo de imagem
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_recurso_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_recurso_setor
        FOREIGN KEY (setor_id) REFERENCES setores(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Dados de exemplo (opcional - pode apagar)
-- ---------------------------------------------------------
INSERT INTO categorias (nome) VALUES ('Equipamento'), ('Software'), ('Material de Escritório');
INSERT INTO setores (nome) VALUES ('Administrativo'), ('TI'), ('Produção');
