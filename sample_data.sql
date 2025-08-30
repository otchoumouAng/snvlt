-- Sample data for metier.etape_validation
-- Please execute these commands after running the database migration.

-- For Demande ID 1 (En cours)
INSERT INTO metier.etape_validation (id, demande_id, nom, statut, ordre, date_traitement, details) VALUES
(1, 1, 'Soumission de la demande', 'completed', 1, '2024-08-20 10:00:00', 'La demande a été reçue et enregistrée par le système.'),
(2, 1, 'Vérification des pièces', 'active', 2, NULL, 'Les documents sont en cours de vérification par un agent.'),
(3, 1, 'Validation finale', 'pending', 3, NULL, NULL);

-- For Demande ID 2 (Soumis)
INSERT INTO metier.etape_validation (id, demande_id, nom, statut, ordre, date_traitement, details) VALUES
(4, 2, 'Soumission de la demande', 'active', 1, NULL, 'La demande vient d''être soumise.'),
(5, 2, 'Analyse de la demande', 'pending', 2, NULL, NULL),
(6, 2, 'Approbation', 'pending', 3, NULL, NULL);

-- For Demande ID 3 (Validé)
INSERT INTO metier.etape_validation (id, demande_id, nom, statut, ordre, date_traitement, details) VALUES
(7, 3, 'Soumission', 'completed', 1, '2024-08-21 11:00:00', 'Demande soumise avec succès.'),
(8, 3, 'Vérification', 'completed', 2, '2024-08-22 14:30:00', 'Toutes les pièces ont été vérifiées et sont conformes.'),
(9, 3, 'Validation', 'completed', 3, '2024-08-23 09:00:00', 'La demande a été validée par l''autorité compétente.');

-- For Demande ID 4 (En cours)
INSERT INTO metier.etape_validation (id, demande_id, nom, statut, ordre, date_traitement, details) VALUES
(10, 4, 'Dépôt du dossier', 'completed', 1, '2024-08-25 16:00:00', 'Dossier déposé au guichet.'),
(11, 4, 'Examen de recevabilité', 'completed', 2, '2024-08-26 11:00:00', 'Le dossier est jugé recevable.'),
(12, 4, 'Analyse technique', 'active', 3, NULL, 'L''analyse technique est en cours.'),
(13, 4, 'Décision finale', 'pending', 4, NULL, NULL);
