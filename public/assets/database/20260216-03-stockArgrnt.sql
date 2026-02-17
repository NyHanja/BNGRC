CREATE TABLE IF NOT EXISTS stockArgent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idVille INT NOT NULL UNIQUE,
    quantite BIGINT NOT NULL DEFAULT 0,
    dateCreation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateModification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (idVille) REFERENCES bngrc_villes(id) ON DELETE CASCADE
);

-- Traçage des répartitions d'argent quand les besoins d'argent sont satisfaits
CREATE TABLE IF NOT EXISTS repartitionArgent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idBesoin INT NOT NULL,
    montantReparti BIGINT NOT NULL COMMENT 'Montant total réparti',
    dateRepartition TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idBesoin) REFERENCES bngrc_besoins(id) ON DELETE CASCADE
);

-- Détails de la répartition : montant reçu par chaque ville
CREATE TABLE IF NOT EXISTS detailsRepartition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idRepartition INT NOT NULL,
    idVille INT NOT NULL,
    montantRecu BIGINT NOT NULL COMMENT 'Montant reçu lors de la répartition',
    FOREIGN KEY (idRepartition) REFERENCES repartitionArgent(id) ON DELETE CASCADE,
    FOREIGN KEY (idVille) REFERENCES bngrc_villes(id) ON DELETE CASCADE
);
