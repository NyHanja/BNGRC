create database flight;
use flight;

CREATE TABLE produit(
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50),
    prix DECIMAL(15,2),
    images VARCHAR(50)
);

INSERT INTO produit (nom, prix, images) VALUES 
("Toyota Corolla", 130000000, "toyota"), 
("Ford Mustang", 208000000, "ford"), 
("BMW Série 3", 208000000, "bmw"),
("Mercedes Classe C", 208000000, "mercedes"),
("Audi A4", 182000000, "audi"),
("Honda Civic", 156000000, "honda"),
("Hyundai Tucson", 182000000, "hyundai"),
("Volkswagen Golf", 130000000, "volkswagen"),
("Renault Clio", 104000000, "renault"),
("Nissan Qashqai", 156000000, "nissan");