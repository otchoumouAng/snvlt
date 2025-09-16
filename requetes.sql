-- Menu principale
INSERT INTO metier.menu (id,nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id,'Paiements', 'mdi-wood', NULL, NULL);

-- Sous menu du menu paiement: il me faut aussi ajouter leur classname_menu
INSERT INTO metier.menu (id, nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id, 'Demande d''actes', 'mdi-wood', 128, NULL);

INSERT INTO metier.menu (id, nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id, 'Suivre mes paiements', 'mdi-wood', 128, NULL);


-- Sous menu dans la table de reference: il me faut aussi ajouter leur classname_menu
INSERT INTO metier.menu (id, nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id, 'Gestion de service', 'mdi-wood', id_du_menu_reference, NULL);

###
INSERT INTO metier.menu (id, nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id, 'Type demandeur', 'mdi-wood', id_du_menu_reference, NULL);

INSERT INTO metier.menu (id, nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id, 'Type de paiement', 'mdi-wood', id_du_menu_reference, NULL);

INSERT INTO metier.menu (id, nom_menu, icon_menu, parent_menu, classname_menu)
VALUES (id, 'Type de service', 'mdi-wood', id_du_menu_reference, NULL);
###


####################
-- 1. Supprimer l'ancienne colonne 'titre' de la table des nouvelles demandes.
ALTER TABLE metier.aut_nouvelle_demande DROP COLUMN titre;

-- 2. Ajouter la nouvelle colonne pour la clé étrangère vers le type de paiement.
--    La colonne est nullable, comme défini dans l'entité.
ALTER TABLE metier.aut_nouvelle_demande ADD COLUMN type_paiement_id INTEGER NULL;

-- 3. Ajouter la contrainte de clé étrangère.
--    Le nom de la contrainte (FK_...) peut être adapté selon vos conventions.
ALTER TABLE metier.aut_nouvelle_demande 
ADD CONSTRAINT FK_5E52A44D6C12483D FOREIGN KEY (type_paiement_id) 
REFERENCES metier.pay_type_paiement (id);

-- 4. (Optionnel) Ajouter un index sur la nouvelle colonne pour de meilleures performances.
CREATE INDEX IDX_5E52A44D6C12483D ON metier.aut_nouvelle_demande (type_paiement_id);


ALTER TABLE metier.pay_trans_catalogue_services
DROP CONSTRAINT IF EXISTS pay_trans_catalogue_services_categorie_activite_id_fkey;

ALTER TABLE metier.pay_trans_catalogue_services
ADD CONSTRAINT fk_catalogue_services_type_demande
FOREIGN KEY (categorie_activite_id)
REFERENCES metier.aut_type_demande (id);
