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
                { data: 'id' },
                { data: 'titre' },
                { data: 'typeDemande' },
                { data: 'societe' },
                { data: 'dateCreation' },
                { 
                    data: 'statut',
                    render: function(data, type, row) {
                        return NouvelleDemandeApp.getStatusBadge(data);
                    }
                }
            ],
    
            dom: "<'dataTables_top'l f> t <'dataTables_bottom' i p>",
            
            select: {
                style: 'single',
                info: false
            },
            responsive: true,
            order: [[4, 'desc']],
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50]
        });

        
        this.dataTable.on('select', (e, dt, type, indexes) => {
            if (type === 'row') {
                const data = this.dataTable.row(indexes).data();
                if (data) {
                    this.selectedDemandeId = data.id;
                    // This logic will be moved to the new button handler
                    // For now, just enable the buttons
                    $('#editBtn').prop('disabled', false);
                    $('#trackBtn').prop('disabled', false);

                    this.displayDocumentPanel(data);
                }
            }
        });

        $(document).on('click', '#pay-fees-btn', () => {
            window.location.href = '/paiement/new';
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


        // Event for double-click remains the same
        /*this.dataTable.on('dblclick', 'tbody tr', (e) => {
            const row = this.dataTable.row(e.currentTarget);
            const data = row.data();
            if (data) {
                this.openModal(data.id, 'read');
            }
        });*/

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

        // Événements pour le panneau de documents
        $('#details-panel').on('click', '.upload', (e) => {
            const docTypeId = $(e.currentTarget).closest('.document-item-new').data('doc-type-id');
            const fileInput = $('#pdf-upload-panel');
            fileInput.attr('data-doc-type-id', docTypeId);
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

        $(document).on('click', '.stepper-item.completed', (e) => {
            const stepId = $(e.currentTarget).data('step-id');
            this.loadStepDetails(this.selectedDemandeId, stepId);
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
        
        // Si nous avons un ID, charger les données
        if (id) {
            // Utiliser les données du DataTable si disponibles
            const row = this.dataTable.row(`#${id}`);
            const rowData = row.data();
            
            if (rowData) {
                this.setupModalWithData(mode, rowData);
            } else {
                // Fallback: charger depuis l'API
                await this.loadDemandeData(id);
            }
        } else {
            //this.setupModal(mode, null);
            this.setupModalWithData(mode, {});
        }
        
    } catch (error) {
        this.notification.error('Erreur lors de l\'ouverture du modal');
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
    form.find('#typeDemande').val(data.typeDemandeId); // Corrected from typeDocument
    
    // Set mode-specific configurations
    switch(mode) {
        case 'new':
            title.text('Nouvelle Demande');
            icon.attr('class', 'ph-fill ph-file-plus');
            saveBtn.show().text('Créer');
            deleteBtn.hide();
            documentsSection.hide(); // La section est déjà cachée pour 'new'
            form.find('input, select, textarea').prop('disabled', false);
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

    const status = demandeData.statut;
    let buttonHtml = '';

    if (status === 'créé' || status === 'rejeté') {
        buttonHtml = `<button class="btn btn-sm btn-success" id="refresh-demande-btn" data-action="submit">
                        <i class="ph-fill ph-paper-plane-tilt"></i> Soumettre
                      </button>`;
    } else if (status === 'en cours') {
        buttonHtml = `<button class="btn btn-sm btn-outline-primary" id="refresh-demande-btn" data-action="refresh">
                        <i class="ph-fill ph-arrows-clockwise"></i> Actualiser
                      </button>`;
    } else if (status === 'accepté') {
        buttonHtml = `<button class="btn btn-sm btn-primary" id="pay-fees-btn" data-action="pay">
                        <i class="ph-fill ph-credit-card"></i> Payer les frais
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
        const fullHtml = contentHtml + '<input type="file" id="pdf-upload-panel" accept=".pdf" style="display: none;" />';

        // Replace spinner with content
        $('#details-content').html(fullHtml);

    } catch (error) {
        this.notification.error("Erreur lors du chargement des documents.");
        this.showDetailsPlaceholder(); // En cas d'erreur, on revient au placeholder
    }
}

// NOUVELLE FONCTION pour construire le HTML du panneau
// Fichier : nouvelledemande.js

/*buildDocumentsHtml(details) {
    let documentsListHtml = '';
    if (details.documents && details.documents.length > 0) {
        documentsListHtml = details.documents.map(doc => `
            <li class="document-item" data-doc-id="${doc.id}">
                <i class="ph-fill ph-file-pdf icon"></i>
                <div class="info">
                    <div class="name">${doc.nom}</div>
                    <div class="meta">PDF Document</div>
                </div>
                ${NouvelleDemandeApp.getDocumentStatusBadge(doc.statut)}
                <div class="actions ms-3">
                    <a href="${doc.url || '#'}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Télécharger">
                        <i class="ph-fill ph-download-simple"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger remove-doc-btn" title="Retirer">
                        <i class="ph-fill ph-trash-simple"></i>
                    </button>
                </div>
            </li>
        `).join('');
    } else {
        return `
            <div class="text-center p-5 mt-3">
                <i class="ph-light ph-file-magnifying-glass" style="font-size: 3rem; color: #ced4da;"></i>
                <h6 class="mt-3">Aucun Document</h6>
                <p class="text-muted small">Cette demande n'a pas encore de document attaché.</p>
            </div>
        `;
    }

    return `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-muted small fw-bold text-uppercase mb-0">Fichiers Attachés</h6>
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" id="addDocumentBtnPanel">
                <i class="ph-fill ph-plus-circle"></i> Ajouter
            </button>
            <input type="file" id="pdf-upload-panel" accept=".pdf" style="display: none;" multiple />
        </div>
        <ul class="document-list">${documentsListHtml}</ul>
    `;
}
*/

// Fichier : nouvelledemande.js

buildDocumentsHtml(details) {
    // Si la demande n'a pas de documents requis, on affiche un message.
    if (!details.documents || details.documents.length === 0) {
        return `<div class="text-center p-5 mt-3">
                    <i class="ph-light ph-files" style="font-size: 3rem; color: #ced4da;"></i>
                    <h6 class="mt-3">Aucun document requis</h6>
                    <p class="text-muted small">Cette typologie de demande ne nécessite pas de document.</p>
                </div>`;
    }

    // On construit la liste des documents à fournir
    const documentsListHtml = details.documents.map(doc => {
        let statutHtml = '';
        let actionsHtml = '';

        // Définir le badge de STATUT
        switch (doc.statut) {
            case 'soumis':
                statutHtml = '<span class="status-badge-sm status-fourni">Soumis</span>';
                break;
            case 'accepté':
                statutHtml = '<span class="status-badge-sm status-accepte">Accepté</span>';
                break;
            case 'rejeté':
                statutHtml = '<span class="status-badge-sm status-rejete">Rejeté</span>';
                break;
            case 'Non soumis':
                statutHtml = '<span class="status-badge-sm status-non-fourni">Non soumis</span>';
                break;
            default:
                statutHtml = '<span class="status-badge-sm status-non-fourni">Non soumis</span>';
        }

        // Définir les BOUTONS D'ACTION
        if (doc.statut === 'soumis' || doc.statut === 'accepté') {
            actionsHtml = `<button class="action-btn view" title="Visualiser le document">
                               <i class="ph-fill ph-eye"></i>
                           </button>`;
        } else { // "Non soumis" ou "rejeté"
            actionsHtml = `<button class="action-btn upload" title="Charger le document">
                               <i class="ph-fill ph-upload-simple"></i>
                           </button>`;
        }

        // Assembler le HTML final pour cet item
        return `
            <li class="document-item-new" data-doc-type-id="${doc.type_document_id}" data-doc-id="${doc.document_id}" data-doc-path="${doc.path || ''}">
                <i class="ph-fill ph-file-text doc-icon"></i>
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



    

    async saveDemande() {
        try {
            const formData = {
                id: $('#demandeId').val() || null,
                typePaiementId: $('#typePaiement').val(),
                description: $('#description').val(),
                typeDemandeId: $('#typeDemande').val()
            };
            
            // Validation
            if (!formData.typePaiementId || !formData.typeDemandeId) {
                this.notification.warning('Veuillez remplir tous les champs obligatoires');
                return;
            }
            
            const result = await this.apiService.saveDemande(formData);
            
            if (result.success) {
                this.notification.success('Demande enregistrée avec succès');
                $('#demandeModal').modal('hide');
                this.loadDemandes();
            } else {
                this.notification.error('Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            this.notification.error('Erreur lors de l\'enregistrement: ' + error.message);
            console.error(error);
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
            if (file.type !== 'application/pdf') {
                this.notification.warning('Seuls les fichiers PDF sont acceptés');
                continue;
            }

            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                this.notification.warning('Le fichier ne doit pas dépasser 10 Mo');
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
            if (this.currentMode) {
                this.loadDemandeData(demandeId);
            } else {
                this.selectDemande(demandeId);
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

