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

-- Table des personnes
-- plateformes et applications : valeurs séparées par des virgules (simple)
CREATE TABLE IF NOT EXISTS personne (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  numero           VARCHAR(10)  NOT NULL,
  civilite         VARCHAR(15)  NOT NULL,
  nom_prenom       VARCHAR(80)  NOT NULL,
  adresse          VARCHAR(150) NOT NULL,
  code_postal      VARCHAR(10)  NOT NULL,
  localite         VARCHAR(60)  NOT NULL,
  pays             VARCHAR(40)  NOT NULL,
  plateformes      VARCHAR(100) DEFAULT '',
  applications     VARCHAR(255) DEFAULT '',
  nationalite_code VARCHAR(3)   NOT NULL,
  photo            VARCHAR(100) DEFAULT NULL,
  -- Relation : personne.nationalite_code -> nationalite.code
  CONSTRAINT fk_personne_nationalite
    FOREIGN KEY (nationalite_code) REFERENCES nationalite(code)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
