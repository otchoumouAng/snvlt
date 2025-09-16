*******************************************
TABLES DU PROJET

*******************************************

-- metier.aut_demande_document definition

-- Drop table

-- DROP TABLE metier.aut_demande_document;

CREATE TABLE metier.aut_demande_document (
	id serial4 NOT NULL,
	demande_id int4 NOT NULL,
	document_id int4 NOT NULL,
	CONSTRAINT aut_demande_document_pkey PRIMARY KEY (id)
);


-- metier.aut_demande_document foreign keys

ALTER TABLE metier.aut_demande_document ADD CONSTRAINT fk_demande FOREIGN KEY (demande_id) REFERENCES metier.aut_nouvelle_demande(id);
ALTER TABLE metier.aut_demande_document ADD CONSTRAINT fk_document FOREIGN KEY (document_id) REFERENCES metier.aut_document(id);


***

-- metier.aut_document definition

-- Drop table

-- DROP TABLE metier.aut_document;

CREATE TABLE metier.aut_document (
	id serial4 NOT NULL,
	nom varchar(255) NOT NULL,
	statut varchar(255) NOT NULL,
	"path" varchar(255) NOT NULL,
	type_document_id int4 NOT NULL,
	desactivate bool DEFAULT false NOT NULL,
	created_at timestamp(0) NOT NULL,
	created_by varchar(255) NOT NULL,
	updated_at timestamp(0) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT aut_document_pkey PRIMARY KEY (id),
	CONSTRAINT chk_aut_document_statut CHECK (((statut)::text = ANY ((ARRAY['soumis'::character varying, 'accepté'::character varying, 'rejeté'::character varying])::text[])))
);


-- metier.aut_document foreign keys

ALTER TABLE metier.aut_document ADD CONSTRAINT fk_type_document FOREIGN KEY (type_document_id) REFERENCES metier.aut_type_document(id);


***


-- metier.aut_etape_validation definition

-- Drop table

-- DROP TABLE metier.aut_etape_validation;

CREATE TABLE metier.aut_etape_validation (
	id serial4 NOT NULL,
	nom varchar(255) NOT NULL,
	date_traitement timestamp(0) NULL,
	statut varchar(50) NOT NULL,
	ordre int4 NOT NULL,
	details text NULL,
	demande_id int4 NOT NULL,
	CONSTRAINT aut_etape_validation_pkey PRIMARY KEY (id)
);


-- metier.aut_etape_validation foreign keys

ALTER TABLE metier.aut_etape_validation ADD CONSTRAINT fk_demande FOREIGN KEY (demande_id) REFERENCES metier.aut_nouvelle_demande(id);


***


-- metier.aut_nouvelle_demande definition

-- Drop table

-- DROP TABLE metier.aut_nouvelle_demande;

CREATE TABLE metier.aut_nouvelle_demande (
	id serial4 NOT NULL,
	operateur_id int4 NOT NULL,
	code_suivie varchar(20) NOT NULL,
	statut varchar(255) DEFAULT 'créé'::character varying NOT NULL,
	description varchar(255) NULL,
	type_demande_id int4 NOT NULL,
	desactivate bool DEFAULT false NOT NULL,
	created_at timestamp(0) NOT NULL,
	created_by varchar(255) NOT NULL,
	updated_at timestamp(0) NULL,
	updated_by varchar(255) NULL,
	type_paiement_id int4 NULL,
	CONSTRAINT aut_nouvelle_demande_pkey PRIMARY KEY (id),
	CONSTRAINT chk_aut_nouvelle_demande_statut CHECK (((statut)::text = ANY ((ARRAY['créé'::character varying, 'en cours'::character varying, 'rejeté'::character varying, 'accepté'::character varying])::text[])))
);
CREATE INDEX idx_3f4958a93a4b83a4 ON metier.aut_nouvelle_demande USING btree (operateur_id);
CREATE INDEX idx_5e52a44d6c12483d ON metier.aut_nouvelle_demande USING btree (type_paiement_id);


-- metier.aut_nouvelle_demande foreign keys

ALTER TABLE metier.aut_nouvelle_demande ADD CONSTRAINT fk_3f4958a93a4b83a4 FOREIGN KEY (operateur_id) REFERENCES public."user"(id);
ALTER TABLE metier.aut_nouvelle_demande ADD CONSTRAINT fk_5e52a44d6c12483d FOREIGN KEY (type_paiement_id) REFERENCES metier.pay_type_paiement(id);
ALTER TABLE metier.aut_nouvelle_demande ADD CONSTRAINT fk_type_demande FOREIGN KEY (type_demande_id) REFERENCES metier.aut_type_demande(id);




***


-- metier.aut_type_demande definition

-- Drop table

-- DROP TABLE metier.aut_type_demande;

CREATE TABLE metier.aut_type_demande (
	id serial4 NOT NULL,
	designation varchar(255) NOT NULL,
	desactivate bool DEFAULT false NOT NULL,
	created_at timestamp(0) NOT NULL,
	created_by varchar(255) NOT NULL,
	updated_at timestamp(0) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT aut_type_demande_pkey PRIMARY KEY (id)
);



***


-- metier.aut_type_demande_detail definition

-- Drop table

-- DROP TABLE metier.aut_type_demande_detail;

CREATE TABLE metier.aut_type_demande_detail (
	id serial4 NOT NULL,
	type_demande_id int4 NOT NULL,
	type_document_id int4 NOT NULL,
	CONSTRAINT aut_type_demande_detail_pkey PRIMARY KEY (id)
);


-- metier.aut_type_demande_detail foreign keys

ALTER TABLE metier.aut_type_demande_detail ADD CONSTRAINT fk_type_demande FOREIGN KEY (type_demande_id) REFERENCES metier.aut_type_demande(id);
ALTER TABLE metier.aut_type_demande_detail ADD CONSTRAINT fk_type_document FOREIGN KEY (type_document_id) REFERENCES metier.aut_type_document(id);


***


-- metier.aut_type_document definition

-- Drop table

-- DROP TABLE metier.aut_type_document;

CREATE TABLE metier.aut_type_document (
	id serial4 NOT NULL,
	designation varchar(255) NOT NULL,
	desactivate bool DEFAULT false NOT NULL,
	created_at timestamp(0) NOT NULL,
	created_by varchar(255) NOT NULL,
	updated_at timestamp(0) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT aut_type_document_pkey PRIMARY KEY (id)
);



***


-- metier.aut_validation_action definition

-- Drop table

-- DROP TABLE metier.aut_validation_action;

CREATE TABLE metier.aut_validation_action (
	id serial4 NOT NULL,
	demande_id int4 NOT NULL,
	validator_id int4 NOT NULL,
	statut varchar(255) NOT NULL,
	note text NULL,
	signature_path varchar(255) NULL,
	desactivate bool DEFAULT false NOT NULL,
	created_at timestamp(0) NOT NULL,
	created_by varchar(255) NOT NULL,
	updated_at timestamp(0) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT aut_validation_action_pkey PRIMARY KEY (id)
);


-- metier.aut_validation_action foreign keys

ALTER TABLE metier.aut_validation_action ADD CONSTRAINT fk_demande FOREIGN KEY (demande_id) REFERENCES metier.aut_nouvelle_demande(id);


***


-- metier.pay_ref_categories_activite definition

-- Drop table

-- DROP TABLE metier.pay_ref_categories_activite;

CREATE TABLE metier.pay_ref_categories_activite (
	id serial4 NOT NULL,
	libelle varchar(255) NOT NULL,
	created_at timestamp(0) NOT NULL,
	updated_at timestamp(0) NULL,
	desactivate bool DEFAULT false NULL,
	created_by varchar(255) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT pay_ref_categories_activite_pkey PRIMARY KEY (id)
);


***


-- metier.pay_ref_types_demandeur definition

-- Drop table

-- DROP TABLE metier.pay_ref_types_demandeur;

CREATE TABLE metier.pay_ref_types_demandeur (
	id serial4 NOT NULL,
	libelle varchar(255) NOT NULL,
	created_at timestamp(0) NOT NULL,
	updated_at timestamp(0) NULL,
	desactivate bool DEFAULT false NULL,
	created_by varchar(255) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT pay_ref_types_demandeur_pkey PRIMARY KEY (id)
);


***

-- metier.pay_ref_types_service definition

-- Drop table

-- DROP TABLE metier.pay_ref_types_service;

CREATE TABLE metier.pay_ref_types_service (
	id serial4 NOT NULL,
	libelle varchar(255) NOT NULL,
	created_at timestamp(0) NOT NULL,
	updated_at timestamp(0) NULL,
	desactivate bool DEFAULT false NULL,
	created_by varchar(255) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT pay_ref_types_service_pkey PRIMARY KEY (id)
);


***


-- metier.pay_trans_catalogue_services definition

-- Drop table

-- DROP TABLE metier.pay_trans_catalogue_services;

CREATE TABLE metier.pay_trans_catalogue_services (
	id serial4 NOT NULL,
	code_service varchar(50) NOT NULL,
	designation varchar(255) NOT NULL,
	montant_fcfa numeric(10) NOT NULL,
	type_service_id int4 NOT NULL,
	categorie_activite_id int4 NOT NULL,
	type_demandeur_id int4 NULL,
	type_demande_id int4 NULL,
	regime_fiscal_id int4 NULL,
	note text NULL,
	created_at timestamp(0) NOT NULL,
	updated_at timestamp(0) NULL,
	desactivate bool DEFAULT false NULL,
	created_by varchar(255) NULL,
	updated_by varchar(255) NULL,
	type_paiement_id int4 NULL,
	CONSTRAINT pay_trans_catalogue_services_code_service_key UNIQUE (code_service),
	CONSTRAINT pay_trans_catalogue_services_pkey PRIMARY KEY (id)
);
CREATE INDEX idx_83b2e33b438595b2 ON metier.pay_trans_catalogue_services USING btree (type_paiement_id);


-- metier.pay_trans_catalogue_services foreign keys

ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT fk_83b2e33b438595b2 FOREIGN KEY (type_paiement_id) REFERENCES metier.pay_type_paiement(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT fk_catalogue_services_type_demande FOREIGN KEY (categorie_activite_id) REFERENCES metier.aut_type_demande(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_regime_fiscal_id_fkey FOREIGN KEY (regime_fiscal_id) REFERENCES metier.pay_ref_regimes_fiscaux(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_type_demande_id_fkey FOREIGN KEY (type_demande_id) REFERENCES metier.aut_type_demande(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_type_demandeur_id_fkey FOREIGN KEY (type_demandeur_id) REFERENCES metier.pay_ref_types_demandeur(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_type_service_id_fkey FOREIGN KEY (type_service_id) REFERENCES metier.pay_ref_types_service(id);


***


-- metier.pay_trans_transactions definition

-- Drop table

-- DROP TABLE metier.pay_trans_transactions;

CREATE TABLE metier.pay_trans_transactions (
	id serial4 NOT NULL,
	identifiant varchar(50) NOT NULL,
	service_id int4 NOT NULL,
	montant_fcfa numeric(10) NOT NULL,
	client_nom varchar(100) NOT NULL,
	client_prenom varchar(150) NOT NULL,
	telephone varchar(20) NULL,
	statut varchar(255) NOT NULL,
	tresorpay_response_code int4 NULL,
	tresorpay_response_message text NULL,
	created_at timestamp(0) NOT NULL,
	updated_at timestamp(0) NULL,
	desactivate bool DEFAULT false NULL,
	created_by varchar(255) NULL,
	updated_by varchar(255) NULL,
	type_demande_id int4 NULL,
	tresorpay_receipt_reference varchar(100) NULL,
	paid_at timestamp(0) NULL,
	payer_phone varchar(20) NULL,
	paid_amount numeric(10) NULL,
	type_paiement_id int4 NULL,
	CONSTRAINT chk_statut CHECK (((statut)::text = ANY ((ARRAY['EN_ATTENTE_AVIS'::character varying, 'AVIS_GENERE'::character varying, 'ECHEC_AVIS'::character varying, 'PAYE'::character varying, 'ANNULE'::character varying])::text[]))),
	CONSTRAINT pay_trans_transactions_identifiant_key UNIQUE (identifiant),
	CONSTRAINT pay_trans_transactions_pkey PRIMARY KEY (id)
);
CREATE INDEX idx_33259837438595b2 ON metier.pay_trans_transactions USING btree (type_paiement_id);


-- metier.pay_trans_transactions foreign keys

ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT fk_33259837438595b2 FOREIGN KEY (type_paiement_id) REFERENCES metier.pay_type_paiement(id);
ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT fk_transaction_type_demande FOREIGN KEY (type_demande_id) REFERENCES metier.pay_ref_types_demande(id);
ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT pay_trans_transactions_service_id_fkey FOREIGN KEY (service_id) REFERENCES metier.pay_trans_catalogue_services(id);



***


-- metier.pay_type_paiement definition

-- Drop table

-- DROP TABLE metier.pay_type_paiement;

CREATE TABLE metier.pay_type_paiement (
	id serial4 NOT NULL,
	libelle varchar(255) NOT NULL,
	CONSTRAINT pay_type_paiement_pkey PRIMARY KEY (id)
);





***

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


