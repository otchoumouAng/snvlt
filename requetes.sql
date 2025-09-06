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


-- metier.pay_ref_regimes_fiscaux definition

-- Drop table

-- DROP TABLE metier.pay_ref_regimes_fiscaux;

CREATE TABLE metier.pay_ref_regimes_fiscaux (
	id serial4 NOT NULL,
	libelle varchar(255) NOT NULL,
	created_at timestamp(0) NOT NULL,
	updated_at timestamp(0) NULL,
	desactivate bool DEFAULT false NULL,
	created_by varchar(255) NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT pay_ref_regimes_fiscaux_pkey PRIMARY KEY (id)
);



-- metier.pay_ref_types_demande definition

-- Drop table

-- DROP TABLE metier.pay_ref_types_demande;

CREATE TABLE metier.pay_ref_types_demande (
	id serial4 NOT NULL,
	libelle varchar(255) NOT NULL,
	desactivate bool DEFAULT false NOT NULL,
	created_at timestamp NOT NULL,
	created_by varchar(255) NOT NULL,
	updated_at timestamp NULL,
	updated_by varchar(255) NULL,
	CONSTRAINT pay_ref_types_demande_libelle_key UNIQUE (libelle),
	CONSTRAINT pay_ref_types_demande_pkey PRIMARY KEY (id)
);


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
	CONSTRAINT pay_trans_catalogue_services_code_service_key UNIQUE (code_service),
	CONSTRAINT pay_trans_catalogue_services_pkey PRIMARY KEY (id)
);


-- metier.pay_trans_catalogue_services foreign keys

ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_categorie_activite_id_fkey FOREIGN KEY (categorie_activite_id) REFERENCES metier.pay_ref_categories_activite(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_regime_fiscal_id_fkey FOREIGN KEY (regime_fiscal_id) REFERENCES metier.pay_ref_regimes_fiscaux(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_type_demande_id_fkey FOREIGN KEY (type_demande_id) REFERENCES metier.aut_type_demande(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_type_demandeur_id_fkey FOREIGN KEY (type_demandeur_id) REFERENCES metier.pay_ref_types_demandeur(id);
ALTER TABLE metier.pay_trans_catalogue_services ADD CONSTRAINT pay_trans_catalogue_services_type_service_id_fkey FOREIGN KEY (type_service_id) REFERENCES metier.pay_ref_types_service(id);


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
	CONSTRAINT chk_statut CHECK (((statut)::text = ANY ((ARRAY['EN_ATTENTE_AVIS'::character varying, 'AVIS_GENERE'::character varying, 'ECHEC_AVIS'::character varying, 'PAYE'::character varying, 'ANNULE'::character varying])::text[]))),
	CONSTRAINT pay_trans_transactions_identifiant_key UNIQUE (identifiant),
	CONSTRAINT pay_trans_transactions_pkey PRIMARY KEY (id)
);


-- metier.pay_trans_transactions foreign keys

ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT fk_transaction_type_demande FOREIGN KEY (type_demande_id) REFERENCES metier.pay_ref_types_demande(id);
ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT pay_trans_transactions_service_id_fkey FOREIGN KEY (service_id) REFERENCES metier.pay_trans_catalogue_services(id);