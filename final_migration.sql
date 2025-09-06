CREATE TABLE metier.pay_type_paiement (
    id SERIAL NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    PRIMARY KEY(id)
);

INSERT INTO metier.pay_type_paiement (libelle) VALUES ('Nouvelle Demande'), ('Renouvellement');

ALTER TABLE metier.pay_trans_catalogue_services ADD type_paiement_id INT DEFAULT NULL;

ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT FK_83B2E33B438595B2 FOREIGN KEY (type_paiement_id) REFERENCES metier.pay_type_paiement (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_83B2E33B438595B2 ON metier.pay_trans_catalogue_services (type_paiement_id);

ALTER TABLE metier.pay_trans_transactions ADD type_paiement_id INT DEFAULT NULL;
ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT FK_33259837438595B2 FOREIGN KEY (type_paiement_id) REFERENCES metier.pay_type_paiement (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_33259837438595B2 ON metier.pay_trans_transactions (type_paiement_id);
