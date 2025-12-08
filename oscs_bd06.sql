SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema osc
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `osc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
USE `osc` ;

-- -----------------------------------------------------
-- Table `osc`.`ator`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`ator` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(60) NULL DEFAULT NULL,
  `telefone` VARCHAR(11) NULL DEFAULT NULL,
  `email` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`osc`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`osc` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `razao_social` VARCHAR(60) NULL DEFAULT NULL,
  `sigla` VARCHAR(10) NULL DEFAULT NULL,
  `email` VARCHAR(120) NULL DEFAULT NULL,
  `telefone` VARCHAR(11) NULL DEFAULT NULL,
  `cnpj` VARCHAR(14) NULL DEFAULT NULL,
  `nome_fantasia` VARCHAR(60) NULL DEFAULT NULL,
  `ano_fundacao` VARCHAR(45) NULL DEFAULT NULL,
  `ano_cnpj` VARCHAR(45) NULL DEFAULT NULL,
  `situacao_cadastral` VARCHAR(30) NULL DEFAULT NULL,
  `missao` LONGTEXT NULL DEFAULT NULL,
  `visao` LONGTEXT NULL DEFAULT NULL,
  `valores` LONGTEXT NULL DEFAULT NULL,
  `historia` LONGTEXT NULL DEFAULT NULL,
  `oque_faz` LONGTEXT NULL DEFAULT NULL,
  `status` VARCHAR(45) NULL DEFAULT NULL,
  `nome` VARCHAR(45) NULL DEFAULT NULL,
  `instagram` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`osc_atividade`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`osc_atividade` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `cnae` VARCHAR(120) NULL DEFAULT NULL,
  `area_atuacao` LONGTEXT NULL DEFAULT NULL,
  `subarea` VARCHAR(120) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_osc_atividade_osc1_idx` (`osc_id` ASC),
  CONSTRAINT `fk_osc_atividade_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`ator_osc`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`ator_osc` (
  `ator_id` INT NOT NULL,
  `osc_id` INT NOT NULL,
  `funcao` VARCHAR(60) NULL DEFAULT NULL,
  PRIMARY KEY (`ator_id`, `osc_id`),
  INDEX `fk_ator_has_osc_osc1_idx` (`osc_id` ASC) ,
  INDEX `fk_ator_has_osc_ator_idx` (`ator_id` ASC) ,
  CONSTRAINT `fk_ator_has_osc_ator`
    FOREIGN KEY (`ator_id`)
    REFERENCES `osc`.`ator` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ator_has_osc_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`projeto`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`projeto` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `nome` VARCHAR(120) NULL DEFAULT NULL,
  `sobre` LONGTEXT NULL DEFAULT NULL,
  `data_inicio` DATE NULL DEFAULT NULL,
  `data_fim` DATE NULL DEFAULT NULL,
  `status` VARCHAR(30) NULL DEFAULT NULL,
  `objetivos` LONGTEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_projeto_id_osc` (`id` ASC, `osc_id` ASC) ,
  INDEX `fk_projeto_osc1_idx` (`osc_id` ASC) ,
  CONSTRAINT `fk_projeto_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`ator_projeto`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`ator_projeto` (
  `ator_id` INT NOT NULL,
  `projeto_id` INT NOT NULL,
  `osc_id` INT NOT NULL,
  `funcao` VARCHAR(60) NULL DEFAULT NULL,
  PRIMARY KEY (`ator_id`, `projeto_id`, `osc_id`),
  INDEX `fk_ap_ator_osc_idx` (`ator_id` ASC, `osc_id` ASC) ,
  INDEX `fk_ap_projeto_osc_idx` (`projeto_id` ASC, `osc_id` ASC) ,
  INDEX `ix_ap_projeto` (`projeto_id` ASC, `osc_id` ASC) ,
  CONSTRAINT `fk_ap_ator_osc`
    FOREIGN KEY (`ator_id` , `osc_id`)
    REFERENCES `osc`.`ator_osc` (`ator_id` , `osc_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ap_projeto_osc`
    FOREIGN KEY (`projeto_id` , `osc_id`)
    REFERENCES `osc`.`projeto` (`id` , `osc_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`evento_oficina`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`evento_oficina` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `projeto_id` INT NOT NULL,
  `pai_id` INT NULL DEFAULT NULL,
  `tipo` VARCHAR(45) NULL DEFAULT NULL,
  `nome` VARCHAR(120) NULL DEFAULT NULL,
  `descricao` LONGTEXT NULL DEFAULT NULL,
  `caminho` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_eo_id_proj` (`id` ASC, `projeto_id` ASC) ,
  INDEX `fk_evento_oficina_evento_oficina1_idx` (`pai_id` ASC) ,
  INDEX `fk_evento_oficina_projeto1_idx` (`projeto_id` ASC) ,
  CONSTRAINT `fk_evento_oficina_evento_oficina1`
    FOREIGN KEY (`pai_id`)
    REFERENCES `osc`.`evento_oficina` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_evento_oficina_projeto1`
    FOREIGN KEY (`projeto_id`)
    REFERENCES `osc`.`projeto` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`ator_evento_oficina`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`ator_evento_oficina` (
  `ator_id` INT NOT NULL,
  `evento_oficina_id` INT NOT NULL,
  `projeto_id` INT NOT NULL,
  `funcao` VARCHAR(60) NULL DEFAULT NULL,
  PRIMARY KEY (`ator_id`, `evento_oficina_id`, `projeto_id`),
  INDEX `fk_aeo_eo_proj_idx` (`evento_oficina_id` ASC, `projeto_id` ASC) ,
  INDEX `fk_aeo_ator_projeto_idx` (`ator_id` ASC, `projeto_id` ASC) ,
  INDEX `ix_aeo_evento` (`evento_oficina_id` ASC, `projeto_id` ASC) ,
  CONSTRAINT `fk_aeo_ator_projeto`
    FOREIGN KEY (`ator_id` , `projeto_id`)
    REFERENCES `osc`.`ator_projeto` (`ator_id` , `projeto_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_aeo_eo_proj`
    FOREIGN KEY (`evento_oficina_id` , `projeto_id`)
    REFERENCES `osc`.`evento_oficina` (`id` , `projeto_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`cores`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`cores` (
  `id_cores` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `cor1` VARCHAR(10) NULL DEFAULT NULL,
  `cor2` VARCHAR(10) NULL DEFAULT NULL,
  `cor3` VARCHAR(10) NULL DEFAULT NULL,
  `cor4` VARCHAR(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id_cores`),
  INDEX `fk_cores_osc1_idx` (`osc_id` ASC),
  CONSTRAINT `fk_cores_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`documento`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`documento` (
  `id_documento` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `projeto_id` INT NULL DEFAULT NULL,
  `tipo` VARCHAR(45) NOT NULL,
  `documento` TINYTEXT NOT NULL,
  PRIMARY KEY (`id_documento`),

  INDEX `fk_documento_osc_idx` (`osc_id` ASC),
  INDEX `fk_documento_projeto_idx` (`projeto_id` ASC, `osc_id` ASC),

  CONSTRAINT `fk_documento_osc`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_documento_projeto`
    FOREIGN KEY (`projeto_id`, `osc_id`)
    REFERENCES `osc`.`projeto` (`id`, `osc_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`edital`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`edital` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `descricao` VARCHAR(45) NULL DEFAULT NULL,
  `caminho` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_edital_osc1_idx` (`osc_id` ASC) ,
  CONSTRAINT `fk_edital_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`imovel`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`imovel` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `cep` VARCHAR(8) NULL DEFAULT NULL,
  `cidade` VARCHAR(45) NULL DEFAULT NULL,
  `logradouro` VARCHAR(45) NULL DEFAULT NULL,
  `bairro` VARCHAR(45) NULL DEFAULT NULL,
  `numero` VARCHAR(5) NULL DEFAULT NULL,
  `situacao` VARCHAR(60) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_imovel_osc1_idx` (`osc_id` ASC) ,
  CONSTRAINT `fk_imovel_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table `osc`.`template_web`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `osc`.`template_web` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `osc_id` INT NOT NULL,
  `cores_id` INT NOT NULL,
  `descricao` VARCHAR(45) NULL DEFAULT NULL,
  `logo_simples` VARCHAR(255) NULL DEFAULT NULL,
  `logo_completa` VARCHAR(255) NULL DEFAULT NULL,
  `banner1` VARCHAR(255) NULL DEFAULT NULL,
  `banner2` VARCHAR(255) NULL DEFAULT NULL,
  `banner3` VARCHAR(255) NULL DEFAULT NULL,
  `label_banner` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_template_web_osc1_idx` (`osc_id` ASC),
  INDEX `fk_template_web_cores1_idx` (`cores_id` ASC),
  CONSTRAINT `fk_template_web_cores1`
    FOREIGN KEY (`cores_id`)
    REFERENCES `osc`.`cores` (`id_cores`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_template_web_osc1`
    FOREIGN KEY (`osc_id`)
    REFERENCES `osc`.`osc` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
