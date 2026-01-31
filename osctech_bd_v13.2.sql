SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema osctech
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `osctech` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `osctech`;

-- -----------------------------------------------------
-- Tabela OSC
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc` (
  `id`                 INT          NOT NULL AUTO_INCREMENT,
  `nome`               VARCHAR(45)  NULL     DEFAULT NULL,
  `sigla`              VARCHAR(10)  NULL     DEFAULT NULL,
  `email`              VARCHAR(120) NULL     DEFAULT NULL,
  `telefone`           VARCHAR(15)  NULL     DEFAULT NULL,
  `instagram`          VARCHAR(45)  NULL     DEFAULT NULL,
  `missao`             LONGTEXT     NULL     DEFAULT NULL,
  `visao`              LONGTEXT     NULL     DEFAULT NULL,
  `valores`            LONGTEXT     NULL     DEFAULT NULL,
  `historia`           LONGTEXT     NULL     DEFAULT NULL,
  `oque_faz`           LONGTEXT     NULL     DEFAULT NULL,
  `cnpj`               VARCHAR(14)  NULL     DEFAULT NULL,
  `nome_fantasia`      VARCHAR(60)  NULL     DEFAULT NULL,
  `razao_social`       VARCHAR(60)  NULL     DEFAULT NULL,
  `responsavel`        VARCHAR(100) NULL     DEFAULT NULL,
  `ano_cnpj`           VARCHAR(45)  NULL     DEFAULT NULL,
  `ano_fundacao`       VARCHAR(45)  NULL     DEFAULT NULL,
  `situacao_cadastral` VARCHAR(30)  NULL     DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;

-- -----------------------------------------------------
-- Tabela OSC_ATIVIDADE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc_atividade` (
  `id`           INT          NOT NULL AUTO_INCREMENT,
  `osc_id`       INT          NOT NULL,
  `cnae`         VARCHAR(120) NULL     DEFAULT NULL,
  `area_atuacao` LONGTEXT     NULL     DEFAULT NULL,
  `subarea`      VARCHAR(120) NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_osc_atividade_osc1_idx` (`osc_id` ASC),
  CONSTRAINT `fk_osc_atividade_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela ENVOLVIDO_OSC
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `envolvido_osc` (
  `id`       INT           NOT NULL AUTO_INCREMENT,
  `osc_id`   INT           NOT NULL,
  `foto`     VARCHAR(255)  NULL     DEFAULT NULL,
  `nome`     VARCHAR(100)  NOT NULL,
  `telefone` VARCHAR(15)   NULL     DEFAULT NULL,
  `email`    VARCHAR(100)  NULL     DEFAULT NULL,
  `funcao`   ENUM('DIRETOR','COORDENADOR','FINANCEIRO','MARKETING','RH', 'PARTICIPANTE') NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_envolvido_osc_osc1_idx` (`osc_id` ASC),
  CONSTRAINT `fk_envolvido_osc_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela PROJETO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `projeto` (
  `id`            INT             NOT NULL AUTO_INCREMENT,
  `osc_id`        INT             NOT NULL,
  `nome`          VARCHAR(120)    NOT NULL,
  `email`         VARCHAR(120)    NULL     DEFAULT NULL,
  `telefone`      VARCHAR(15)     NULL     DEFAULT NULL,
  `logo`          VARCHAR(255)    NOT NULL,
  `img_descricao` VARCHAR(255)    NOT NULL,
  `descricao`     LONGTEXT        NULL     DEFAULT NULL,
  `depoimento`    VARCHAR(120)    NULL     DEFAULT NULL,
  `data_inicio`   DATE            NOT NULL,
  `data_fim`      DATE            NULL     DEFAULT NULL,
  `status`        ENUM('EXECUCAO','ENCERRADO','PLANEJAMENTO','PENDENTE') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_projeto_id_osc` (`id` ASC, `osc_id` ASC),
  INDEX `fk_projeto_osc1_idx` (`osc_id` ASC),
  CONSTRAINT `fk_projeto_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela EVENTO_OFICINA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `evento_oficina` (
  `id`            INT                       NOT NULL AUTO_INCREMENT,
  `projeto_id`    INT                       NOT NULL,
  `pai_id`        INT                       NULL     DEFAULT NULL,
  `tipo`          ENUM('EVENTO', 'OFICINA') NULL     DEFAULT NULL,
  `img_capa`      VARCHAR(255)              NULL     DEFAULT NULL,
  `nome`          VARCHAR(120)              NULL     DEFAULT NULL,
  `descricao`     LONGTEXT                  NULL     DEFAULT NULL,
  `data_inicio`   DATE                      NULL     DEFAULT NULL,
  `data_fim`      DATE                      NULL     DEFAULT NULL,
  `status`        ENUM('EXECUCAO','ENCERRADO','PLANEJAMENTO','PENDENTE') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_eo_id_proj` (`id` ASC, `projeto_id` ASC),
  INDEX `fk_evento_oficina_evento_oficina1_idx` (`pai_id` ASC),
  INDEX `fk_evento_oficina_projeto1_idx` (`projeto_id` ASC),
  CONSTRAINT `fk_evento_oficina_evento_oficina1`
    FOREIGN KEY (`pai_id`)
    REFERENCES `evento_oficina` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_evento_oficina_projeto1`
    FOREIGN KEY (`projeto_id`)
    REFERENCES `projeto` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela ENDERECO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `endereco` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `descricao`   VARCHAR(60)  NULL DEFAULT NULL,
  `cep`         VARCHAR(8)   NULL DEFAULT NULL,
  `cidade`      VARCHAR(45)  NULL DEFAULT NULL,
  `logradouro`  VARCHAR(45)  NULL DEFAULT NULL,
  `bairro`      VARCHAR(45)  NULL DEFAULT NULL,
  `numero`      VARCHAR(6)   NULL DEFAULT NULL,
  `complemento` VARCHAR(45)  NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela ENDERECO_OSC
-- -----------------------------------------------------
  CREATE TABLE IF NOT EXISTS `endereco_osc` (
  `osc_id`      INT         NOT NULL,
  `endereco_id` INT         NOT NULL,
  `situacao`    VARCHAR(30) NOT NULL,
  `principal`   TINYINT(1)  NOT NULL DEFAULT 0,
  PRIMARY KEY (`osc_id`, `endereco_id`),
  INDEX `idx_osc_endereco_endereco` (`endereco_id`),
  CONSTRAINT `fk_endereco_osc_osc`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_endereco_osc_endereco`
    FOREIGN KEY (`endereco_id`)
    REFERENCES `endereco` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;

-- -----------------------------------------------------
-- Tabela ENDERECO_PROJETO
-- -----------------------------------------------------
  CREATE TABLE IF NOT EXISTS `endereco_projeto` (
  `projeto_id`  INT NOT NULL,
  `endereco_id` INT NOT NULL,
  `principal`   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`projeto_id`, `endereco_id`),
  INDEX `idx_projeto_endereco_endereco` (`endereco_id`),
  CONSTRAINT `fk_endereco_projeto_projeto`
    FOREIGN KEY (`projeto_id`)
    REFERENCES `projeto` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_endereco_projeto_endereco`
    FOREIGN KEY (`endereco_id`)
    REFERENCES `endereco` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela ENDERECO_EVENTO_OFICINA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `endereco_evento_oficina` (
  `evento_oficina_id` INT NOT NULL,
  `endereco_id`       INT NOT NULL,
  `principal`   TINYINT(1)  NOT NULL DEFAULT 0,
  PRIMARY KEY (`evento_oficina_id`, `endereco_id`),
  INDEX `idx_eo_endereco_endereco` (`endereco_id`),
  CONSTRAINT `fk_eo_endereco_evento_oficina`
    FOREIGN KEY (`evento_oficina_id`)
    REFERENCES `evento_oficina` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_eo_endereco_endereco`
    FOREIGN KEY (`endereco_id`)
    REFERENCES `endereco` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela ENVOLVIDO_PROJETO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `envolvido_projeto` (
  `envolvido_osc_id` INT         NOT NULL,
  `projeto_id`       INT         NOT NULL,
  `funcao`           ENUM('DIRETOR','COORDENADOR','FINANCEIRO','MARKETING','RH', 'PARTICIPANTE') NOT NULL,
  `data_inicio`      DATE NULL DEFAULT NULL,
  `data_fim`         DATE NULL DEFAULT NULL,
  `salario`          DECIMAL(10,2) NULL DEFAULT NULL,
  `ativo`            TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`envolvido_osc_id`, `projeto_id`),
  INDEX `fk_ep_projeto_idx` (`projeto_id` ASC),
  CONSTRAINT `fk_ep_envolvido_osc`
    FOREIGN KEY (`envolvido_osc_id`)
    REFERENCES `envolvido_osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ep_projeto`
    FOREIGN KEY (`projeto_id`)
    REFERENCES `projeto` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela ENVOLVIDO_EVENTO_OFICINA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `envolvido_evento_oficina` (
  `envolvido_osc_id`  INT         NOT NULL,
  `evento_oficina_id` INT         NOT NULL,
  `projeto_id`        INT         NOT NULL,
  `funcao`            VARCHAR(60) NULL     DEFAULT NULL,
  PRIMARY KEY (`envolvido_osc_id`, `evento_oficina_id`, `projeto_id`),
  INDEX `fk_eeo_envolvido_projeto_idx` (`envolvido_osc_id` ASC, `projeto_id` ASC),
  INDEX `fk_eeo_eo_idx` (`evento_oficina_id` ASC),
  CONSTRAINT `fk_eeo_envolvido_projeto`
    FOREIGN KEY (`envolvido_osc_id`, `projeto_id`)
    REFERENCES `envolvido_projeto` (`envolvido_osc_id`, `projeto_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_eeo_eo`
    FOREIGN KEY (`evento_oficina_id`)
    REFERENCES `evento_oficina` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela CORES
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cores` (
  `id_cores` INT         NOT NULL AUTO_INCREMENT,
  `osc_id`   INT         NOT NULL,
  `cor1`     VARCHAR(10) NULL     DEFAULT NULL,
  `cor2`     VARCHAR(10) NULL     DEFAULT NULL,
  `cor3`     VARCHAR(10) NULL     DEFAULT NULL,
  `cor4`     VARCHAR(10) NULL     DEFAULT NULL,
  `cor5`     VARCHAR(10) NULL     DEFAULT NULL,
  PRIMARY KEY (`id_cores`),
  INDEX `fk_cores_osc1_idx` (`osc_id` ASC),
  CONSTRAINT `fk_cores_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela DOCUMENTO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `documento` (
  `id_documento`   INT          NOT NULL AUTO_INCREMENT,
  `osc_id`         INT          NOT NULL,
  `projeto_id`     INT          NULL     DEFAULT NULL,
  `categoria`      ENUM('INSTITUCIONAL','CERTIDAO','CONTABIL','EXECUCAO','ESPECIFICOS') NOT NULL,
  `subtipo`        VARCHAR(45)  NOT NULL,   -- ESTATUTO, ATA, CND_FEDERAL, CND_ESTADUAL, CND_MUNICIPAL, FGTS, TRABALHISTA, BALANCO_PATRIMONIAL, DRE, PLANO_TRABALHO, PLANILHA_ORCAMENTARIA, TERMO_COLABORACAO, APTIDAO
  `descricao`      VARCHAR(100) NULL,
  `link`           VARCHAR(100) NULL,
  `ano_referencia` YEAR         NULL,
  `documento`      TINYTEXT     NULL,
  `data_upload`    DATETIME     NOT NULL DEFAULT       CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_documento`),
  INDEX `fk_documento_osc_idx` (`osc_id` ASC),
  INDEX `fk_documento_projeto_idx` (`projeto_id` ASC),
  INDEX `idx_categoria_subtipo` (`categoria` ASC, `subtipo` ASC),
  INDEX `idx_ano_referencia` (`ano_referencia` ASC),
  CONSTRAINT `fk_documento_osc`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_documento_projeto`
    FOREIGN KEY (`projeto_id`)
    REFERENCES `projeto` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela EDITAL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `edital` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `osc_id`     INT          NOT NULL,
  `projeto_id` INT          NOT NULL,
  `descricao`  VARCHAR(45)  NULL     DEFAULT NULL,
  `caminho`    VARCHAR(255) NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_edital_osc1_idx` (`osc_id` ASC),
  INDEX `fk_edital_projeto1_idx` (`projeto_id` ASC),
  CONSTRAINT `fk_edital_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_edital_projeto1`
    FOREIGN KEY (`projeto_id`)
    REFERENCES `projeto` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `imovel` (
  `id`          INT         NOT NULL AUTO_INCREMENT,
  `osc_id`      INT         NOT NULL,
  `endereco_id` INT         NULL     DEFAULT NULL,
  `situacao`    VARCHAR(60) NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_imovel_osc1_idx` (`osc_id`),
  INDEX `idx_imovel_endereco` (`endereco_id`),
  CONSTRAINT `fk_imovel_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_imovel_endereco`
    FOREIGN KEY (`endereco_id`)
    REFERENCES `endereco` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB
  AUTO_INCREMENT=1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Tabela TEMPLATE_WEB
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `template_web` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `osc_id`        INT          NOT NULL,
  `cores_id`      INT          NOT NULL,
  `descricao`     VARCHAR(45)  NULL     DEFAULT NULL,
  `logo_simples`  VARCHAR(255) NULL     DEFAULT NULL,
  `logo_completa` VARCHAR(255) NULL     DEFAULT NULL,
  `banner1`       VARCHAR(255) NULL     DEFAULT NULL,
  `banner2`       VARCHAR(255) NULL     DEFAULT NULL,
  `banner3`       VARCHAR(255) NULL     DEFAULT NULL,
  `label_banner`  VARCHAR(255) NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_template_web_osc1_idx` (`osc_id` ASC),
  INDEX `fk_template_web_cores1_idx` (`cores_id` ASC),
  CONSTRAINT `fk_template_web_cores1`
    FOREIGN KEY (`cores_id`)
    REFERENCES `cores` (`id_cores`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_template_web_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;

-- -----------------------------------------------------
-- Tabela USUARIO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuario` (
  `id`               INT          NOT NULL AUTO_INCREMENT,
  `nome`             VARCHAR(100) NOT NULL,
  `email`            VARCHAR(150) NOT NULL,
  `senha`            VARCHAR(255) NOT NULL,
  `tipo`             ENUM('OSC_TECH_ADMIN', 'OSC_MASTER') NOT NULL,
  `osc_id`           INT NULL,
  `ativo`            TINYINT(1)   NOT NULL DEFAULT 1,
  `data_criacao`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_usuario_email` (`email` ASC),
  INDEX `fk_usuario_osc_idx` (`osc_id` ASC),
  CONSTRAINT `fk_usuario_osc`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci;

-- -----------------------------------------------------
-- Usuário padrão OscTech
-- -----------------------------------------------------
INSERT INTO usuario (nome, email, senha, tipo, osc_id, ativo)
VALUES (
  'Administrador OscTech',
  'admin@osctech.com',
  '$2y$10$gYD5.Gy0vPRH6tEC3odP.Ok.JSpE.qMi4hjDp6VX6KwejHTXDK.cO',
  'OSC_TECH_ADMIN',
  NULL,
  1
);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
