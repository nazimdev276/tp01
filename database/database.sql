-- ============================================================
-- TP01 - PAW : base de données
-- Importer ce fichier dans phpMyAdmin (onglet "Importer")
-- ============================================================
CREATE DATABASE IF NOT EXISTS tp01_paw
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tp01_paw;

-- Table des nationalités : code X(3) et libellé X(40)
CREATE TABLE IF NOT EXISTS nationalite (
  code    VARCHAR(3)  NOT NULL PRIMARY KEY,
  libelle VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- Une dizaine de nationalités de départ
INSERT IGNORE INTO nationalite (code, libelle) VALUES
  ('DZ', 'ALGERIE'),
  ('FR', 'FRANCE'),
  ('BE', 'BELGIQUE'),
  ('TN', 'TUNISIE'),
  ('MA', 'MAROC'),
  ('DE', 'ALLEMAGNE'),
  ('IT', 'ITALIE'),
  ('ES', 'ESPAGNE'),
  ('CA', 'CANADA'),
  ('US', 'ETATS-UNIS');

-- Filières d'inscription
CREATE TABLE IF NOT EXISTS filiere (
  code    VARCHAR(10) NOT NULL PRIMARY KEY,
  libelle VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO filiere (code, libelle) VALUES
  ('TC', 'TC'),
  ('2SC', '2SC'),
  ('3ISIL', '3ISIL'),
  ('3SI', '3SI'),
  ('M1', 'M1'),
  ('M2ISI', 'M2ISI'),
  ('M2WIC', 'M2WIC'),
  ('M2RSSI', 'M2RSSI'),
  ('1ING', '1ING'),
  ('2ING', '2ING'),
  ('3ING', '3ING'),
  ('4ING', '4ING');

-- Table des modules d'enseignement
CREATE TABLE IF NOT EXISTS module (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  code             VARCHAR(20)  NOT NULL UNIQUE,
  designation      VARCHAR(100) NOT NULL,
  coefficient      DECIMAL(5,2) NOT NULL,
  volume_horaire   SMALLINT UNSIGNED NOT NULL,
  filiere_code     VARCHAR(10)  NOT NULL,
  CONSTRAINT fk_module_filiere
    FOREIGN KEY (filiere_code) REFERENCES filiere(code)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Table des moyens de transport
CREATE TABLE IF NOT EXISTS moyen_transport (
  libelle      VARCHAR(20)  NOT NULL PRIMARY KEY,
  description  VARCHAR(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

ALTER TABLE moyen_transport
  ADD COLUMN IF NOT EXISTS description VARCHAR(500) NOT NULL DEFAULT '';

INSERT IGNORE INTO moyen_transport (libelle) VALUES
  ('Bus'),
  ('Avion'),
  ('Bateau'),
  ('Train');

-- Table des sports
CREATE TABLE IF NOT EXISTS sport (
  libelle VARCHAR(40) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

INSERT IGNORE INTO sport (libelle) VALUES
  ('Football'),
  ('Basketball'),
  ('Tennis'),
  ('Natation'),
  ('Athlétisme');

-- Table des enseignants
CREATE TABLE IF NOT EXISTS enseignant (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  numero           VARCHAR(10)  NOT NULL,
  civilite         VARCHAR(15)  NOT NULL,
  nom_prenom       VARCHAR(80)  NOT NULL,
  adresse          VARCHAR(150) NOT NULL,
  date_naissance   DATE         NOT NULL,
  lieu_naissance   VARCHAR(80)  NOT NULL,
  pays             VARCHAR(40)  NOT NULL,
  grade            VARCHAR(20)  NOT NULL,
  specialite       VARCHAR(40)  NOT NULL
) ENGINE=InnoDB;

-- Table des personnes
-- plateformes, modules et applications : valeurs séparées par des virgules (simple)
CREATE TABLE IF NOT EXISTS personne (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  numero           VARCHAR(10)  NOT NULL,
  type_foyer       VARCHAR(15)  NOT NULL DEFAULT 'Individuelle',
  civilite         VARCHAR(15)  NOT NULL,
  nom_prenom       VARCHAR(80)  NOT NULL,
  adresse          VARCHAR(150) NOT NULL,
  code_postal      VARCHAR(10)  NOT NULL,
  localite         VARCHAR(60)  NOT NULL,
  pays             VARCHAR(40)  NOT NULL,
  plateformes      VARCHAR(100) DEFAULT '',
  modules          VARCHAR(100) NOT NULL DEFAULT '',
  applications     VARCHAR(255) DEFAULT '',
  voiture          VARCHAR(100) NOT NULL DEFAULT '',
  transport        VARCHAR(20)  NOT NULL DEFAULT '',
  groupe_sanguin   VARCHAR(3)   NOT NULL DEFAULT '',
  sport            VARCHAR(40)  NOT NULL DEFAULT '',
  activites        VARCHAR(100) NOT NULL DEFAULT '',
  description      VARCHAR(500) NOT NULL DEFAULT '',
  nationalite_code VARCHAR(3)   NOT NULL,
  filiere_code     VARCHAR(10)  DEFAULT NULL,
  photo            VARCHAR(100) DEFAULT NULL,
  -- Relation : personne.nationalite_code -> nationalite.code
  CONSTRAINT fk_personne_nationalite
    FOREIGN KEY (nationalite_code) REFERENCES nationalite(code)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_personne_filiere
    FOREIGN KEY (filiere_code) REFERENCES filiere(code)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Table dédiée aux voyageurs
CREATE TABLE IF NOT EXISTS voyageur (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  numero           VARCHAR(10)  NOT NULL,
  type_foyer       VARCHAR(15)  NOT NULL DEFAULT 'Individuelle',
  civilite         VARCHAR(15)  NOT NULL,
  nom_prenom       VARCHAR(80)  NOT NULL,
  adresse          VARCHAR(150) NOT NULL,
  code_postal      VARCHAR(10)  NOT NULL,
  pays             VARCHAR(40)  NOT NULL,
  filiere_code     VARCHAR(10)  DEFAULT NULL,
  transport        VARCHAR(255) NOT NULL DEFAULT '',
  description      VARCHAR(500) NOT NULL DEFAULT '',
  photo            VARCHAR(100) DEFAULT NULL,
  CONSTRAINT fk_voyageur_filiere
    FOREIGN KEY (filiere_code) REFERENCES filiere(code)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Mise à niveau des bases déjà créées
ALTER TABLE personne
  ADD COLUMN IF NOT EXISTS modules VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS voiture VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS transport VARCHAR(20) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS groupe_sanguin VARCHAR(3) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS sport VARCHAR(40) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS activites VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS filiere_code VARCHAR(10) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS type_foyer VARCHAR(15) NOT NULL DEFAULT 'Individuelle',
  ADD COLUMN IF NOT EXISTS description VARCHAR(500) NOT NULL DEFAULT '';

-- Ajoute la clé étrangère aux bases existantes sans la recréer à chaque import.
SET @filiere_fk_absente = (
  SELECT COUNT(*) = 0
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'personne'
    AND CONSTRAINT_NAME = 'fk_personne_filiere'
);
SET @filiere_fk_sql = IF(
  @filiere_fk_absente,
  'ALTER TABLE personne ADD CONSTRAINT fk_personne_filiere FOREIGN KEY (filiere_code) REFERENCES filiere(code) ON UPDATE CASCADE ON DELETE RESTRICT',
  'SELECT 1'
);
PREPARE filiere_fk_stmt FROM @filiere_fk_sql;
EXECUTE filiere_fk_stmt;
DEALLOCATE PREPARE filiere_fk_stmt;

ALTER TABLE personne
  MODIFY COLUMN voiture VARCHAR(100) NOT NULL DEFAULT '',
  MODIFY COLUMN transport VARCHAR(255) NOT NULL DEFAULT '';
