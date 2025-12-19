class NouvelleDemandeApp {
    constructor() {
        this.apiService = window.apiService;
        this.notification = window.notificationSystem;
        this.selectedDemandeId = null;
        this.currentMode = null;
        this.dataTable = null;
        this.modal = null;
        this.modalTemplates = {
            new: null,
            edit: null,
            read: null
        };
    }
    

    init() {
        this.preloadModalTemplates();
        this.initDataTable();
        this.bindEvents();
        this.loadDemandes();
    }

    // Précharger les templates des modaux
    preloadModalTemplates() {
        this.modalTemplates.new = $('#template-form-new').html();
        this.modalTemplates.edit = $('#template-form-edit').html();
        this.modalTemplates.read = $('#template-form-read').html();
    }

    initDataTable() {
        this.dataTable = new DataTable('#demandesTable', {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },


            columns: [
                // 1. La colonne de contrôle responsive (qui manquait)
                {
                    data: null,
                    defaultContent: '',
                    className: 'dtr-control',
                    orderable: false
                },
                // 2. Le reste des colonnes, dans le bon ordre
                { data: 'id', title: 'ID' },

                { 
                    data: 'typeDemande', 
                    title: 'Type de Demande',
                    render: function(data, type, row) {
                        let date = row.dateCreation || ''; 
                        return `${data}<br><small class="text-muted">${date}</small>`;
                    }
                },

                { data: 'societe', title: 'Société' }, // La colonne "Société" ajoutée
                {
                    data: 'statut',
                    title: 'Statut Demande',
                    render: (data) => NouvelleDemandeApp.getStatusBadge(data)
                },
                { data: 'anneeExercice', title: 'Année Exercice' },
                { data: 'numero_pef', title: 'Nº PEF', className: 'none' },
                { data: 'produit', title: 'Produit', className: 'none' },
                { data: 'description', title: 'Description', className: 'none' },
                { data: 'dateCreation', title: 'Date Demande', className: 'none' }
            ],
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 2 },
                { responsivePriority: 4, targets: 3 },
                { responsivePriority: 5, targets: 4 }
            ],
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            select: {
                style: 'single',
                info: false
            }
        });


        this.dataTable.on('select', (e, dt, type, indexes) => {
            if (type === 'row') {
                const data = this.dataTable.row(indexes).data();
                if (data) {
                    this.selectedDemandeId = data.id;

                    // Activer le bouton 'Modifier' SEULEMENT si le statut est 'Créé'
                    $('#editBtn').prop('disabled', data.statut !== 'Créé');

                    $('#trackBtn').prop('disabled', false);

                    this.displayDocumentPanel(data);
                }
            }
        });

        $(document).on('click', '#pay-fees-btn', () => {
            if (this.selectedDemandeId) {
                window.location.href = `/paiement/new?demandeId=${this.selectedDemandeId}`;
            } else {
                this.notification.error("Veuillez d'abord sélectionner une demande.");
            }
        });

        // ÉVÉNEMENT : Désélection
        this.dataTable.on('deselect', (e, dt, type, indexes) => {
            if (type === 'row') {
                this.selectedDemandeId = null;
                $('#editBtn').prop('disabled', true);
                $('#trackBtn').prop('disabled', true);
                $('#submitBtn').prop('disabled', true);
                this.showDetailsPlaceholder();
            }
        });

    // SUPPRESSION du double-clic pour le modal 'read'
    this.dataTable.off('dblclick', 'tbody tr');



    }

    bindEvents() {
        // Les boutons d'ouverture de modal ne changent pas
        $('#addBtn').on('click', () => this.openModal(null, 'new'));
        $('#editBtn').on('click', () => {
            if (this.selectedDemandeId) {
                this.openModal(this.selectedDemandeId, 'edit');
            }
        });

        $('#trackBtn').on('click', () => {
            if (this.selectedDemandeId) {
                this.showTrackingPortal(this.selectedDemandeId);
            }
        });

        // La soumission du formulaire dans le modal ne change pas
        $(document).on('submit', '#demandeForm', (e) => {
            e.preventDefault();
            this.saveDemande(); // La fonction saveDemande est déjà correcte
        });

        $(document).on('change', '#typeDemande', async (e) => {
            const selectedOptionText = $(e.currentTarget).find('option:selected').text();
            const pefContainer = $('#pef-container');
            const produitContainer = $('#produit-container');

            pefContainer.hide();
            produitContainer.hide();

            if (selectedOptionText === "Autorisation annuelle de reprise d'activité dans les PEF") {
                pefContainer.show();
                const pefs = await this.apiService.get('/admin/nouvelle_demande/api/user/pefs');
                const pefSelect = $('#numero_pef');
                pefSelect.empty();
                pefSelect.append('<option value="">Sélectionner un PEF</option>');
                pefs.forEach(pef => {
                    //pefSelect.append(`<option value="${pef.id}">${pef.libelle}</option>`);
                    pefSelect.append(`<option value="${pef.libelle}">${pef.libelle}</option>`);
                });

            } else if (selectedOptionText === "Agrément d'exportateur de produits forestiers ( Ligneux et Non ligneux)") {
                produitContainer.show();
            }
        });

        // Événements pour le panneau de documents
        $('#details-panel').on('click', '.upload', (e) => {
            const docTypeId = $(e.currentTarget).closest('.document-item-new').data('doc-type-id');
            const fileInput = $('#pdf-upload-panel');
            fileInput.attr('data-doc-type-id', docTypeId);
            fileInput.click();
        });

        $('#details-panel').on('click', '#upload-special-excel-btn', (e) => {
            const fileInput = $('#excel-upload-panel');
            fileInput.click();
        });

        $('#details-panel').on('change', '#pdf-upload-panel', (e) => {
            const docTypeId = $(e.currentTarget).attr('data-doc-type-id');
            this.handleFileUpload(e.target.files, docTypeId);
        });

        $('#details-panel').on('click', '.view', (e) => {
            const docPath = $(e.currentTarget).closest('.document-item-new').data('doc-path');
            if (docPath) {
                window.open(docPath, '_blank');
            } else {
                this.notification.error('Chemin du document non trouvé.');
            }
        });

        // Événement pour la suppression d'un document
        $('#details-panel').on('click', '.delete-doc', (e) => {
            e.preventDefault();
            e.stopPropagation(); // Éviter d'autres déclenchements
            const docId = $(e.currentTarget).closest('.document-item-new').data('doc-id');
            if (docId) {
                this.removeDocument(docId);
            } else {
                this.notification.error('Identifiant du document introuvable.');
            }
        });


        // Use document for delegated event since the button is in the header, outside the panel
        $(document).on('click', '#refresh-demande-btn', (e) => {
            if (this.selectedDemandeId) {
                const action = $(e.currentTarget).data('action');
                if (action === 'submit') {
                    this.submitDemande();
                } else if (action === 'refresh') {
                    const rowData = this.dataTable.row({ selected: true }).data();
                    if (rowData) {
                        this.displayDocumentPanel(rowData);
                    }
                }
            }
        });

        // Utiliser la délégation d'événements pour les éléments chargés en AJAX
        $(document).on('click', '#back-to-list', (e) => {
            e.preventDefault();
            this.showMainView();
        });

        /*$(document).on('click', '.stepper-item.completed:not(.rejected)', (e) => {
            const stepId = $(e.currentTarget).data('step-id');
            this.loadStepDetails(this.selectedDemandeId, stepId);
        });

        $(document).on('click', '.stepper-item.rejected', (e) => {
            const item = $(e.currentTarget);
            const details = item.data('details');
            const title = item.find(".stepper-title").text();
            const date = item.find(".stepper-date")?.text() || "—";

            if (details) {
                const detailsHtml = `
                    <div style="margin-top: 15px; padding: 10px; border-radius: 5px; background-color: #f8d7da; border: 1px solid #f5c2c7;">
                        <h5 style="color: #842029; margin-bottom: 5px;">Motif du Rejet :</h5>
                        <p style="margin: 0;">${details}</p>
                    </div>
                `;

                $('#step-details-content').html(
                    `<h4>Étape: ${title}</h4>
                     <p>Date: ${date}</p>
                     ${detailsHtml}`
                );

                $('#step-details-placeholder').hide();
                $('#step-details-content').show();
            }
        });*/

        $(document).on('click', '.stepper-item', (e) => {
            const item = $(e.currentTarget);
            const stepId = item.data('step-id');
            
            // On autorise le clic si l'étape est complétée, rejetée ou suspendue
            if (item.hasClass('completed') || item.hasClass('rejected') || item.hasClass('suspended')) {
                const details = item.data('details');
                const title = item.find(".stepper-title").text();
                const date = item.find(".stepper-date")?.text() || "—";
                
                // Si l'étape est rejetée ou suspendue, on affiche directement les détails depuis l'attribut data-details
                if (item.hasClass('rejected') || item.hasClass('suspended')) {
                    if (details) {
                        const reasonTitle = item.hasClass('rejected') ? 'Motif du Rejet' : 'Motif de la Suspension';
                        const detailsHtml = `
                            <div style="margin-top: 15px; padding: 10px; border-radius: 5px; background-color: #f8d7da; border: 1px solid #f5c2c7;">
                                <h5 style="color: #842029; margin-bottom: 5px;">${reasonTitle} :</h5>
                                <p style="margin: 0;">${details}</p>
                            </div>
                        `;

                        $('#step-details-content').html(
                            `<h4>Étape: ${title}</h4>
                             <p>Date: ${date}</p>
                             ${detailsHtml}`
                        );

                        $('#step-details-placeholder').hide();
                        $('#step-details-content').show();
                    }
                } else {
                    // Pour les étapes complétées (non rejetées), on charge les détails via l'API
                    this.loadStepDetails(this.selectedDemandeId, stepId);
                }
            }
        });

        $(document).on('click', '#view-signed-doc-portal-btn', (e) => {
            this.showTrackingPortal(this.selectedDemandeId);
        });

    }

        // Nouvelle fonction pour afficher le portail de suivi
    async showTrackingPortal(demandeId) {
        // Supposons une nouvelle méthode API qui retourne le HTML du portail
        try {
            // Idéalement, votre API retourne le HTML pré-rempli
            const trackingHtml = await this.apiService.getTrackingView(demandeId);

            // Remplacer le contenu de la page
            // Assurez-vous d'avoir un conteneur global, ex: <div id="page-wrapper">
            $('#page_content').fadeOut(200, function() {
                $(this).html(trackingHtml).fadeIn(200);
            });

        } catch (error) {
            this.notification.error("Impossible de charger la vue de suivi.");
            console.error(error);
        }
    }

    // Nouvelle fonction pour revenir à la vue principale
    showMainView() {
        // Vous devez avoir une fonction qui peut recharger la vue initiale.
        // La solution la plus simple est de recharger la page, mais pour une vraie SPA,
        // vous devriez avoir le template initial et le re-injecter.
        location.reload(); // Solution simple et efficace pour le moment
    }

    // Nouvelle fonction pour charger les détails d'une étape
    async loadStepDetails(demandeId, stepId) {
        // Supposons une nouvelle méthode API qui retourne le HTML des détails
        try {
            const detailsHtml = await this.apiService.getStepDetails(demandeId, stepId);
            $('#step-details-placeholder').hide();
            $('#step-details-content').html(detailsHtml);

            // Ajoute un indicateur visuel sur l'étape cliquée
            $('.stepper-item').removeClass('selected');
            $(`.stepper-item[data-step-id="${stepId}"]`).addClass('selected');

        } catch (error) {
            this.notification.error("Impossible de charger les détails de l'étape.");
            console.error(error);
        }
    }



    async loadDemandes() {
        try {
            this.notification.info('Chargement des demandes...', 2000);
            const demandes = await this.apiService.getDemandes();
            this.dataTable.clear().rows.add(demandes).draw();

            // Réinitialiser la sélection
            this.selectedDemandeId = null;
            $('#editBtn').prop('disabled', true);
            this.showDetailsPlaceholder();

        } catch (error) {
            this.notification.error('Erreur lors du chargement des demandes');
            console.error(error);
        }
    }

    async selectDemande(id) {
        try {
            this.selectedDemandeId = id;
            $('#editBtn').prop('disabled', false);

            const details = await this.apiService.getDemandeDetails(id);
            this.displayDetails(details);
        } catch (error) {
            this.notification.error('Erreur lors du chargement des détails');
            console.error(error);
        }
    }


    showDetailsPlaceholder() {
        const detailsContent = $('#details-content');
        const placeholder = $('#details-placeholder');

        detailsContent.removeClass('visible').html('');
        placeholder.show();
    }

    async openModal(id, mode) {
    try {
        // Utiliser le template préchargé
        const formHtml = this.modalTemplates[mode];
        if (!formHtml) {
            throw new Error(`Template non trouvé pour le mode: ${mode}`);
        }

        $('#modalContainer').html(formHtml);

        // Initialiser le modal Bootstrap
        const modalElement = document.getElementById('demandeModal');
        this.modal = new bootstrap.Modal(modalElement);
        this.modal.show();

        this.currentMode = mode;

        // Si nous avons un ID (mode 'edit'), charger les données
        if (id) {
            // ✅ FIX: Utiliser la sélection de la table pour obtenir les données de manière fiable
            const rowData = this.dataTable.row({ selected: true }).data();
            
            if (rowData) {
                this.setupModalWithData(mode, rowData);
            } else {
                // Ce fallback ne devrait plus être nécessaire, mais on le garde par sécurité
                this.notification.error("Impossible de récupérer les données de la ligne sélectionnée.");
                this.modal.hide();
            }
        } else {
            // Pour le mode 'new', initialiser avec un objet vide
            this.setupModalWithData(mode, {});
        }

    } catch (error) {
        this.notification.error("Erreur lors de l'ouverture du modal");
        console.error(error);
    }
}

setupModalWithData(mode, data) {
    const modal = $('#demandeModal');
    const title = modal.find('#modal-title');
    const icon = modal.find('#modal-icon');
    const saveBtn = modal.find('#saveBtn');
    const deleteBtn = modal.find('#deleteBtn');
    const form = modal.find('#demandeForm');
    // On cible la section des documents
    const documentsSection = modal.find('#documents-section');

    // Remplir le formulaire avec les données
    form.find('#demandeId').val(data.id);
    form.find('#typePaiement').val(data.typePaiementId); // Updated
    form.find('#description').val(data.description);
    form.find('#typeDemande').val(data.typeDemandeId).trigger('change'); // Corrected from typeDocument
    form.find('#anneeExercice').val(data.anneeExercice);

    // Set mode-specific configurations
    switch(mode) {
        case 'new':
            title.text('Nouvelle Demande');
            icon.attr('class', 'ph-fill ph-file-plus');
            saveBtn.show().text('Initier');
            deleteBtn.hide();
            documentsSection.hide(); // La section est déjà cachée pour 'new'
            form.find('input, select, textarea').prop('disabled', false);
            // Set the anneeExercice for new demands
            const currentYear = new Date().getFullYear();
            form.find('#anneeExercice').val(currentYear + 1);
            break;

        case 'edit':
            title.text('Modifier la Demande');
            icon.attr('class', 'ph-fill ph-pencil-simple');
            saveBtn.show().text('Modifier');
            deleteBtn.show();
            documentsSection.hide(); // CHANGEMENT : On cache la section
            form.find('input, select, textarea').prop('disabled', false);

            // SUPPRIMÉ : On ne charge plus les documents dans la modale
            // this.loadDocuments(data.id);
            break;

        case 'read':
            title.text('Détails de la Demande');
            icon.attr('class', 'ph-fill ph-eye');
            saveBtn.hide();
            deleteBtn.hide();
            documentsSection.hide(); // CHANGEMENT : On cache la section
            form.find('input, select, textarea').prop('disabled', true);

            // SUPPRIMÉ : On ne charge plus les documents dans la modale
            // this.loadDocuments(data.id);
            break;
    }
}

// NOUVELLE FONCTION CENTRALE pour le panneau
async displayDocumentPanel(demandeData) {
    this.showLoader();

    const details = await this.apiService.getDemandeDetails(demandeData.id);
    const status = demandeData.statut;
    let buttonHtml = '';

    if (status === 'Créé' || status === 'rejeté') {
        buttonHtml = `<button class="btn btn-sm btn-success" id="refresh-demande-btn" data-action="submit">
                        <i class="ph-fill ph-paper-plane-tilt"></i> Soumettre
                      </button>`;
    } else if (status === 'En cours'|| status === 'Soumis') {
        buttonHtml = `<button class="btn btn-sm btn-outline-primary" id="refresh-demande-btn" data-action="refresh">
                        <i class="ph-fill ph-arrows-clockwise"></i> Actualiser
                      </button>`;
    } else if (status === 'Signé') {
        buttonHtml = `<button class="btn btn-sm btn-success" id="view-signed-doc-portal-btn">
                        <i class="ph-fill ph-file-arrow-down"></i> Document signé et disponible
                      </button>`;
    } else {
        buttonHtml = ``; // No button for other statuses
    }

    const titleHtml = `
        <div class="d-flex justify-content-between align-items-center">
            <span>Documents pour : <span class="fw-normal">${demandeData.titre}</span></span>
            ${buttonHtml}
        </div>`;
    $('#details-title-text').html(titleHtml);

    // Show spinner
    $('#details-content').html('<div class="loader"></div>').show();
    $('#details-placeholder').hide();

    try {
        const details = await this.apiService.getDemandeDetails(demandeData.id);
        const contentHtml = this.buildDocumentsHtml(details); // On sépare la logique de construction HTML

        // Add the hidden file input
        const fullHtml = contentHtml +
        '<input type="file" id="pdf-upload-panel" accept=".pdf,.xls,.xlsx" style="display: none;" />';

        // Replace spinner with content
        $('#details-content').html(fullHtml);

    } catch (error) {
        this.notification.error("Erreur lors du chargement des documents.");
        this.showDetailsPlaceholder(); // En cas d'erreur, on revient au placeholder
    }
}



buildDocumentsHtml(details) {
    // Si la demande n'a pas de documents requis, on affiche un message.
    if (!details.documents || details.documents.length === 0) {
        return `<div class="text-center p-5"><i class="ph-light ph-folder-simple-dashed" style="font-size: 3rem; color: #ced4da;"></i><h6 class="mt-3">Aucun document requis</h6><p class="text-muted small">Ce type de demande ne nécessite aucun document à fournir.</p></div>`;
    }

    // Trier les documents pour que le fichier spécial soit toujours en premier
    details.documents.sort((a, b) => {
        if (a.fichierSpecial && !b.fichierSpecial) return -1;
        if (!a.fichierSpecial && b.fichierSpecial) return 1;
        return 0;
    });

    // On construit la liste des documents à fournir
    let documentsListHtml = details.documents.map(doc => {
        let statutHtml = '';
        let actionsHtml = '';
        let iconHtml = doc.fichierSpecial
            ? '<i class="ph-fill ph-file-xls doc-icon" style="color: #1D6F42;"></i>'
            : '<i class="ph-fill ph-file-text doc-icon"></i>';

        // Définir le badge de STATUT
        switch (doc.statut) {
            case 'Chargé':
                statutHtml = '<span class="status-badge-sm status-fourni">Chargé</span>';
                break;
            case 'Accepté':
                statutHtml = '<span class="status-badge-sm status-accepte">Accepté</span>';
                break;
            case 'Rejeté':
                statutHtml = '<span class="status-badge-sm status-rejete">Rejeté</span>';
                break;
            case 'Non chargé':
            default:
                statutHtml = '<span class="status-badge-sm status-non-fourni">Non chargé</span>';
        }

        // Définir les BOUTONS D'ACTION
        if (doc.statut === 'Chargé' || doc.statut === 'Accepté') {
            actionsHtml = `<button class="action-btn view" title="Visualiser le document">
                               <i class="ph-fill ph-eye"></i>
                           </button>`;

            // AJOUT: Bouton de suppression si la demande est modifiable (Créé ou Rejeté)
            if (doc.statut === 'Chargé' && (details.statut === 'Créé' || details.statut === 'Rejeté')) {
                actionsHtml += `<button class="action-btn delete-doc text-danger" title="Retirer le document">
                                    <i class="ph-fill ph-trash"></i>
                                </button>`;
            }

        } else { // "Non chargé" ou "rejeté"
            actionsHtml = `<button class="action-btn upload" title="Charger le document">
                               <i class="ph-fill ph-upload-simple"></i>
                           </button>`;
        }

        // Interdiction de charger un document dans une demande SIGNÉ
        if (details.statut === 'Signé' && doc.statut === 'Non chargé' || details.statut === 'Signé' && doc.statut === 'Rejeté' ) {
            actionsHtml = `<span class="action-btn"><i class="ph ph-lock-key"></i></span>`;
        }

        // Assembler le HTML final pour cet item
        return `
            <li class="document-item-new" data-doc-type-id="${doc.type_document_id}" data-doc-id="${doc.document_id || ''}" data-doc-path="${doc.path || ''}">
                ${iconHtml}
                <div class="doc-info">${doc.nom}</div>
                <div class="doc-status">${statutHtml}</div>
                <div class="doc-actions">${actionsHtml}</div>
            </li>
        `;
    }).join('');

    // On retourne la liste complète
    return `<ul class="document-requirements-list">${documentsListHtml}</ul>`;
}

// Fonctions de gestion des états du panneau
showLoader() {
    $('#details-placeholder').hide();
    $('#details-content').hide();
}

showDetailsPlaceholder() {
    $('#details-content').hide();
    $('#details-placeholder').show();
    $('#details-title-text').text('Documents');
}


    // ✅ MODIFIÉ POUR EMPÊCHER LA DOUBLE SOUMISSION
    async saveDemande() {
        const saveBtn = $('#saveBtn');
        const originalBtnHtml = saveBtn.html();

        try {
            saveBtn.prop('disabled', true);
            saveBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Initiation...');

            const formData = {
                id: $('#demandeId').val() || null,
                typePaiementId: $('#typePaiement').val(),
                description: $('#description').val(),
                typeDemandeId: $('#typeDemande').val(),
                numero_pef: $('#numero_pef').val(),
                produit: $('#produit').val(),
                anneeExercice: $('#anneeExercice').val()
            };
            
            // Validation
            if (!formData.typePaiementId || !formData.typeDemandeId) {
                this.notification.warning('Veuillez remplir tous les champs obligatoires');
                return; // Stop execution
            }
            
            const result = await this.apiService.saveDemande(formData);

            if (result.success) {
                this.notification.success('Demande enregistrée avec succès');
                $('#demandeModal').modal('hide');
                this.loadDemandes();
            } else {
                this.notification.error(result.error || 'Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            this.notification.error('Erreur lors de l\'enregistrement: ' + error.message);
            console.error(error);
        } finally {
            saveBtn.prop('disabled', false);
            saveBtn.html(originalBtnHtml);
        }
    }

    async deleteDemande() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette demande ? Cette action est irréversible.')) {
            return;
        }

        try {
            const demandeId = $('#demandeId').val();
            // Implémentez la suppression côté serveur et appez l'API ici
            this.notification.info('Fonctionnalité de suppression à implémenter');

            // Pour l'instant, on ferme juste le modal
            $('#demandeModal').modal('hide');

        } catch (error) {
            this.notification.error('Erreur lors de la suppression');
            console.error(error);
        }
    }

    async handleFileUpload(files, docTypeId) {
        if (!this.selectedDemandeId) {
            this.notification.warning('Veuillez sélectionner une demande');
            return;
        }

        const demandeId = this.selectedDemandeId;

        for (const file of files) {
            const allowedTypes = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!allowedTypes.includes(file.type)) {
                this.notification.warning('Seuls les fichiers PDF et Excel sont acceptés');
                continue;
            }

            if (file.size > 15 * 1024 * 1024) { // 15MB limit
                this.notification.warning('Le fichier ne doit pas dépasser 15 Mo');
                continue;
            }

            const formData = new FormData();
            formData.append('document', file);
            formData.append('type_document_id', docTypeId);

            try {
                await this.apiService.addDocument(demandeId, formData);
                this.notification.success('Document ajouté avec succès');

                // Reload the document panel
                const rowData = this.dataTable.row({ selected: true }).data();
                if (rowData) {
                    this.displayDocumentPanel(rowData);
                }

            } catch (error) {
                this.notification.error('Erreur lors de l\'ajout du document');
                console.error(error);
            }
        }
    }

    async submitDemande() {
        try {
            const result = await this.apiService.submitDemande(this.selectedDemandeId);
            if (result.success) {
                this.notification.success(result.message || 'Demande soumise avec succès.');
                this.loadDemandes();
                window.open(`/admin/nouvelle_demande/${this.selectedDemandeId}/etat_depot`, '_blank');
            } else {
                this.notification.error(result.error || 'Une erreur est survenue.');
            }
        } catch (error) {
            this.notification.error('Erreur lors de la soumission: ' + error.message);
            console.error(error);
        }
    }

    async removeDocument(documentId) {
        if (!this.selectedDemandeId && !$('#demandeId').val()) {
            this.notification.error('Aucune demande sélectionnée');
            return;
        }

        if (!confirm('Êtes-vous sûr de vouloir retirer ce document ?')) {
            return;
        }

        const demandeId = this.selectedDemandeId || $('#demandeId').val();

        try {
            await this.apiService.removeDocument(demandeId, documentId);
            this.notification.success('Document retiré avec succès');

            // Reload the data
            const rowData = this.dataTable.row({ selected: true }).data();
            if (rowData) {
                this.displayDocumentPanel(rowData);
            }
        } catch (error) {
            this.notification.error('Erreur lors du retrait du document');
            console.error(error);
        }
    }

    cleanupModal() {
        if (this.modal) {
            this.modal.dispose();
            this.modal = null;
        }
        $('#modalContainer').empty();
        this.currentMode = null;
    }

    static getStatusBadge(status) {
        switch (status) {
            case 'créé':
                return '<span class="status-badge status-pending"><i class="ph-fill ph-file-plus"></i> Créé</span>';
            case 'en cours':
                return '<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i> En cours</span>';
            case 'rejeté':
                return '<span class="status-badge status-rejected"><i class="ph-fill ph-x-circle"></i> Rejeté</span>';
            case 'accepté':
                return '<span class="status-badge status-approved"><i class="ph-fill ph-check-circle"></i> Accepté</span>';
            default:
                return '<span class="status-badge status-pending"><i class="ph-fill ph-question"></i> ' + status + '</span>';
        }
    }

    static getDocumentStatusBadge(status) {
        switch (status) {
            case 'provided':
            case 'fourni':
                return '<span class="badge bg-success-subtle text-success-emphasis"><i class="ph-fill ph-check-circle me-1"></i>Fourni</span>';
            case 'missing':
            case 'manquant':
                return '<span class="badge bg-danger-subtle text-danger-emphasis"><i class="ph-fill ph-x-circle me-1"></i>Manquant</span>';
            case 'validating':
            case 'en_validation':
                return '<span class="badge bg-warning-subtle text-warning-emphasis"><i class="ph-fill ph-hourglass me-1"></i>En validation</span>';
            default:
                return '<span class="badge bg-secondary">' + status + '</span>';
        }
    }
}