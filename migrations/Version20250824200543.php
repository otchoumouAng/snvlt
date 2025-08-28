<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824200543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE perimetre_exploitation_forestiere_2015_gid_seq1 CASCADE');
        $this->addSql('CREATE TABLE metier.disponibilite_parc_billes (id INT NOT NULL, nom_vernaculaire VARCHAR(255) NOT NULL, nb_billes INT DEFAULT NULL, volume DOUBLE PRECISION DEFAULT NULL, code_usine INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE metier.disponibilite_parc_billons (id INT NOT NULL, nom_vernaculaire VARCHAR(255) NOT NULL, nb_billons INT DEFAULT NULL, volume DOUBLE PRECISION DEFAULT NULL, code_usine INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE metier.docs_stats (id INT NOT NULL, abv VARCHAR(100) NOT NULL, nb_delivres INT DEFAULT NULL, nb_saisi INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE document (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE essence_volume_top_10 (id INT NOT NULL, nom_vernaculaire VARCHAR(255) NOT NULL, cubage DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE metier.import_data (id INT NOT NULL, type_fichier_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by VARCHAR(255) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C3A5520412928ADB ON metier.import_data (type_fichier_id)');
        $this->addSql('CREATE TABLE metier.menu_permission (id INT NOT NULL, nom_menu VARCHAR(255) NOT NULL, icon_menu VARCHAR(100) NOT NULL, parent_menu INT NOT NULL, classname_menu VARCHAR(255) NOT NULL, code_groupe_id INT NOT NULL, id_permission INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE metier.performance_brh (id_performance INT NOT NULL, created_by VARCHAR(255) DEFAULT NULL, created_at DATE DEFAULT NULL, nb_ligne INT DEFAULT NULL, volume DOUBLE PRECISION NOT NULL, nb_brh INT DEFAULT NULL, PRIMARY KEY(id_performance))');
        $this->addSql('CREATE TABLE metier.performance_brh_jour (id_performance INT NOT NULL, created_at DATE DEFAULT NULL, nb_ligne INT DEFAULT NULL, volume DOUBLE PRECISION NOT NULL, nb_brh INT DEFAULT NULL, PRIMARY KEY(id_performance))');
        $this->addSql('CREATE TABLE metier.qt (id INT NOT NULL, id_exp INT DEFAULT NULL, numero_exp INT DEFAULT NULL, rs_exp VARCHAR(255) DEFAULT NULL, mrt_exp VARCHAR(255) DEFAULT NULL, numero_foret VARCHAR(255) DEFAULT NULL, id_usine INT DEFAULT NULL, date_chargementbrh DATE DEFAULT NULL, cubage DOUBLE PRECISION DEFAULT NULL, quota DOUBLE PRECISION DEFAULT NULL, rs_usine VARCHAR(255) DEFAULT NULL, tiers_quota DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE metier.quota_transferable (id INT NOT NULL, id_exp INT DEFAULT NULL, numero_exp INT DEFAULT NULL, rs_exp VARCHAR(255) DEFAULT NULL, numero_foret VARCHAR(255) DEFAULT NULL, cubage DOUBLE PRECISION DEFAULT NULL, quota DOUBLE PRECISION DEFAULT NULL, tiers_quota DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE statut_alerte (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE type_rapport (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE metier.import_data ADD CONSTRAINT FK_C3A5520412928ADB FOREIGN KEY (type_fichier_id) REFERENCES metier.type_document_statistique (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE metier.depot_commercant DROP CONSTRAINT fk_2b72a60213cf8b6');
        $this->addSql('ALTER TABLE metier.agreement_commercant DROP CONSTRAINT fk_2b72a60213cf8b6');
        $this->addSql('DROP TABLE metier.depot_commercant');
        $this->addSql('DROP TABLE metier.agreement_commercant');
        $this->addSql('ALTER TABLE metier.attribution_pv ALTER date_decision TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE metier.attribution_pv ALTER date_decision SET NOT NULL');
        $this->addSql('ALTER TABLE metier.attribution_pv ALTER reprise SET NOT NULL');
        $this->addSql('DROP INDEX idx_e1052a9fcba8bd2');
        $this->addSql('ALTER TABLE metier.documentbcbgfh DROP code_reprise_id');
        $this->addSql('ALTER INDEX metier.idx_df9a63a2faaaec0a RENAME TO IDX_DF9A63A29DBC4D6C');
        $this->addSql('ALTER TABLE metier.documentetate2 ALTER code_generation_id SET NOT NULL');
        $this->addSql('ALTER TABLE metier.exploitant DROP agreeboo');
        $this->addSql('ALTER TABLE metier.exportateur DROP agree');
        $this->addSql('ALTER TABLE metier.fiche_prospection ALTER date_inventaire TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE metier.inventaire_forestier ALTER date_inventaire TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE metier.lignepagebcbp DROP CONSTRAINT fk_606d998b89d40298');
        $this->addSql('DROP INDEX idx_606d998b89d40298');
        $this->addSql('ALTER TABLE metier.lignepagebcbp DROP exercice_id');
        $this->addSql('ALTER TABLE metier.lignepagebtgu ALTER updated_by SET NOT NULL');
        $this->addSql('ALTER TABLE metier.lignepagelje DROP CONSTRAINT fk_97452d2920a1d299');
        $this->addSql('DROP INDEX idx_97452d2920a1d299');
        $this->addSql('ALTER TABLE metier.lignepagelje DROP code_pagebtgu_id');
        $this->addSql('ALTER TABLE metier.pagebcbp DROP immatcamion');
        $this->addSql('ALTER TABLE metier.pagebcbp DROP soumettre');
        $this->addSql('ALTER TABLE metier.pagebcbp DROP photo');
        $this->addSql('ALTER TABLE metier.pagebcbp DROP nb_billes');
        $this->addSql('ALTER TABLE metier.pagebcbp DROP volume');
        $this->addSql('ALTER TABLE metier.pagebcbp DROP entre_lje');
        $this->addSql('ALTER TABLE metier.pagebtgu DROP fini');
        $this->addSql('ALTER TABLE metier.pagebtgu DROP entre_lje');
        $this->addSql('ALTER TABLE metier.pagebtgu DROP soumettre');
        $this->addSql('ALTER TABLE metier.pageetate2 ALTER volumetotal SET NOT NULL');
        $this->addSql('ALTER TABLE metier.pageetate2 ALTER created_by SET NOT NULL');
        $this->addSql('ALTER TABLE metier.pagefp ALTER code_generation_id SET NOT NULL');
        $this->addSql('ALTER TABLE metier.pagepdtdrv ALTER numpage_pdtdrv SET NOT NULL');
        $this->addSql('DROP INDEX perimetre_exploitation_forestiere_2015_geom_idx');
        $this->addSql('ALTER TABLE pef ALTER gid DROP DEFAULT');
        $this->addSql('ALTER TABLE pef ALTER numero_pef SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER zone_ SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER zone_ TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE pef ALTER aire_pef TYPE NUMERIC(10, 0)');
        $this->addSql('ALTER TABLE pef ALTER aire_pef SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER quotas TYPE NUMERIC(10, 0)');
        $this->addSql('ALTER TABLE pef ALTER quotas SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER ta SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER ts SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER tas SET NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER geom SET NOT NULL');
        $this->addSql('ALTER TABLE metier.reprise DROP motif');
        $this->addSql('ALTER TABLE metier.reprise ALTER numero_autorisation SET NOT NULL');
        $this->addSql('ALTER TABLE metier.reprise ALTER numero_autorisation TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE observateur.ticket ADD CONSTRAINT FK_6012DC1AF297FF5F FOREIGN KEY (code_cantonnement_id) REFERENCES metier.cantonnement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE observateur.ticket ADD CONSTRAINT FK_6012DC1AEE3BAA66 FOREIGN KEY (code_oi_id) REFERENCES metier.oi (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6012DC1AF297FF5F ON observateur.ticket (code_cantonnement_id)');
        $this->addSql('CREATE INDEX IDX_6012DC1AEE3BAA66 ON observateur.ticket (code_oi_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA pgagent');
        $this->addSql('CREATE SEQUENCE perimetre_exploitation_forestiere_2015_gid_seq1 INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE metier.depot_commercant (id INT NOT NULL, numero_dossier_id INT DEFAULT NULL, numero_depot VARCHAR(25) NOT NULL, date_creation DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, cc VARCHAR(50) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, rc VARCHAR(50) DEFAULT NULL, quartier VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_60E165291E191F5 ON metier.depot_commercant (numero_dossier_id)');
        $this->addSql('CREATE TABLE metier.agreement_commercant (id INT NOT NULL, code_commercant_id INT DEFAULT NULL, numero_dossier VARCHAR(25) NOT NULL, date_agreement DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, statut BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_93525DDCBEE9D0F5 ON metier.agreement_commercant (code_commercant_id)');
        $this->addSql('ALTER TABLE metier.depot_commercant ADD CONSTRAINT fk_2b72a60213cf8b6 FOREIGN KEY (numero_dossier_id) REFERENCES metier.agreement_commercant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE metier.agreement_commercant ADD CONSTRAINT fk_2b72a60213cf8b6 FOREIGN KEY (code_commercant_id) REFERENCES metier.commercant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE metier.import_data DROP CONSTRAINT FK_C3A5520412928ADB');
        $this->addSql('DROP TABLE metier.disponibilite_parc_billes');
        $this->addSql('DROP TABLE metier.disponibilite_parc_billons');
        $this->addSql('DROP TABLE metier.docs_stats');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE essence_volume_top_10');
        $this->addSql('DROP TABLE metier.import_data');
        $this->addSql('DROP TABLE metier.menu_permission');
        $this->addSql('DROP TABLE metier.performance_brh');
        $this->addSql('DROP TABLE metier.performance_brh_jour');
        $this->addSql('DROP TABLE metier.qt');
        $this->addSql('DROP TABLE metier.quota_transferable');
        $this->addSql('DROP TABLE statut_alerte');
        $this->addSql('DROP TABLE type_rapport');
        $this->addSql('ALTER TABLE metier.attribution_pv ALTER date_decision TYPE DATE');
        $this->addSql('ALTER TABLE metier.attribution_pv ALTER date_decision DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.attribution_pv ALTER reprise DROP NOT NULL');
        $this->addSql('CREATE SEQUENCE pef_gid_seq');
        $this->addSql('SELECT setval(\'pef_gid_seq\', (SELECT MAX(gid) FROM pef))');
        $this->addSql('ALTER TABLE pef ALTER gid SET DEFAULT nextval(\'pef_gid_seq\')');
        $this->addSql('ALTER TABLE pef ALTER numero_pef DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER zone_ DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER zone_ TYPE VARCHAR(5)');
        $this->addSql('ALTER TABLE pef ALTER aire_pef TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE pef ALTER aire_pef DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER quotas TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE pef ALTER quotas DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER ta DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER ts DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER tas DROP NOT NULL');
        $this->addSql('ALTER TABLE pef ALTER geom DROP NOT NULL');
        $this->addSql('CREATE INDEX perimetre_exploitation_forestiere_2015_geom_idx ON pef (geom)');
        $this->addSql('ALTER TABLE metier.pagebcbp ADD immatcamion VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebcbp ADD soumettre BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebcbp ADD photo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebcbp ADD nb_billes INT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebcbp ADD volume DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebcbp ADD entre_lje BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.lignepagelje ADD code_pagebtgu_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.lignepagelje ADD CONSTRAINT fk_97452d2920a1d299 FOREIGN KEY (code_pagebtgu_id) REFERENCES metier.pagebtgu (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_97452d2920a1d299 ON metier.lignepagelje (code_pagebtgu_id)');
        $this->addSql('ALTER TABLE metier.documentetate2 ALTER code_generation_id DROP NOT NULL');
        $this->addSql('ALTER INDEX metier.idx_df9a63a29dbc4d6c RENAME TO idx_df9a63a2faaaec0a');
        $this->addSql('ALTER TABLE metier.pagepdtdrv ALTER numpage_pdtdrv DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.pagefp ALTER code_generation_id DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.documentbcbgfh ADD code_reprise_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_e1052a9fcba8bd2 ON metier.documentbcbgfh (code_reprise_id)');
        $this->addSql('ALTER TABLE metier.pagebtgu ADD fini BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebtgu ADD entre_lje BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pagebtgu ADD soumettre BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.exportateur ADD agree BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE observateur.ticket DROP CONSTRAINT FK_6012DC1AF297FF5F');
        $this->addSql('ALTER TABLE observateur.ticket DROP CONSTRAINT FK_6012DC1AEE3BAA66');
        $this->addSql('DROP INDEX IDX_6012DC1AF297FF5F');
        $this->addSql('DROP INDEX IDX_6012DC1AEE3BAA66');
        $this->addSql('ALTER TABLE metier.fiche_prospection ALTER date_inventaire TYPE DATE');
        $this->addSql('ALTER TABLE metier.lignepagebtgu ALTER updated_by DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.exploitant ADD agreeboo BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.reprise ADD motif TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.reprise ALTER numero_autorisation DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.reprise ALTER numero_autorisation TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE metier.pageetate2 ALTER volumetotal DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.pageetate2 ALTER created_by DROP NOT NULL');
        $this->addSql('ALTER TABLE metier.inventaire_forestier ALTER date_inventaire TYPE DATE');
        $this->addSql('ALTER TABLE metier.lignepagebcbp ADD exercice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.lignepagebcbp ADD CONSTRAINT fk_606d998b89d40298 FOREIGN KEY (exercice_id) REFERENCES admin.exercice (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_606d998b89d40298 ON metier.lignepagebcbp (exercice_id)');
    }
}
