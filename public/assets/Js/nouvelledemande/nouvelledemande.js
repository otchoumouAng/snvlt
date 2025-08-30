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
                { data: 'typeDocument' },
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
                $('#editBtn').prop('disabled', false);
                // On charge les documents dans le panneau de droite
                this.displayDocumentPanel(data); 
            }
        }
    });

    // ÉVÉNEMENT : Désélection
    this.dataTable.on('deselect', (e, dt, type, indexes) => {
        if (type === 'row') {
            this.selectedDemandeId = null;
            $('#editBtn').prop('disabled', true);
            this.showDetailsPlaceholder(); // Retour à l'état initial
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

        // La soumission du formulaire dans le modal ne change pas
        $(document).on('submit', '#demandeForm', (e) => {
            e.preventDefault();
            this.saveDemande(); // La fonction saveDemande est déjà correcte
        });

        // Événements pour le panneau de documents
        // Notez le sélecteur d'événement délégué pour les éléments créés dynamiquement
        $('#details-panel').on('click', '#addDocumentBtnPanel', () => {
            $('#pdf-upload-panel').click();
        });
        
        $('#details-panel').on('change', '#pdf-upload-panel', (e) => {
            this.handleFileUpload(e.target.files);
        });

        $('#details-panel').on('click', '.remove-doc-btn', (e) => {
            const documentId = $(e.currentTarget).closest('.list-group-item').data('doc-id');
            this.removeDocument(documentId);
        });
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

    displayDetails(details) {
    const detailsContent = $('#details-content');
    const placeholder = $('#details-placeholder');
    
    placeholder.hide();
    detailsContent.removeClass('visible');
    
    // On construit la liste des documents, chacun avec son bouton de suppression
    let documentsHtml = '';
    if (details.documents && details.documents.length > 0) {
        details.documents.forEach((doc) => {
            documentsHtml += `
                <li class="list-group-item d-flex justify-content-between align-items-center" data-doc-id="${doc.id}">
                    <a href="${doc.url || '#'}" target="_blank" class="text-decoration-none text-dark text-truncate" style="max-width: 70%;">${doc.nom}</a>
                    <div class="d-flex align-items-center gap-2">
                        ${NouvelDemandeApp.getDocumentStatusBadge(doc.statut)}
                        <button class="btn btn-sm btn-light text-danger remove-doc-btn" title="Retirer le document">
                            <i class="ph-fill ph-x"></i>
                        </button>
                    </div>
                </li>`;
        });
    } else {
        documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document pour cette demande</li>';
    }

    // On construit le HTML complet du panneau
    const contentHtml = `
        <div class="mb-3">
            <h5 class="fw-bold mb-1">${details.titre}</h5>
            <p class="text-muted mb-2">${details.description || 'Aucune description'}</p>
            ${NouvelleDemandeApp.getStatusBadge(details.statut)}
            <div class="mt-2 small text-muted">Type: ${details.typeDocument}</div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="text-muted small fw-bold text-uppercase mb-0">Documents Requis</h6>
            <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" id="addDocumentBtnPanel">
                <i class="ph-fill ph-plus"></i> Ajouter
            </button>
            <input type="file" id="pdf-upload-panel" accept=".pdf" style="display: none;" multiple />
        </div>
        
        <ul class="list-group list-group-flush document-list">${documentsHtml}</ul>
    `;

    detailsContent.html(contentHtml);
    setTimeout(() => detailsContent.addClass('visible'), 10);
    
    // On attache les événements aux nouveaux boutons
    $('#addDocumentBtnPanel').on('click', () => {
        $('#pdf-upload-panel').click();
    });
    
    $('#pdf-upload-panel').on('change', (e) => {
        this.handleFileUpload(e.target.files);
    });
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
            this.setupModal(mode, null);
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
    form.find('#titre').val(data.titre);
    form.find('#description').val(data.description);
    form.find('#typeDocument').val(data.typeDocumentId);
    
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
    this.showLoader(); // Affiche le spinner
    $('#details-title-text').html(`Documents pour : <span class="fw-normal">${demandeData.titre}</span>`);
    
    try {
        const details = await this.apiService.getDemandeDetails(demandeData.id);
        const contentHtml = this.buildDocumentsHtml(details); // On sépare la logique de construction HTML

        $('#details-loader').hide();
        $('#details-content').html(contentHtml).fadeIn(300);

    } catch (error) {
        this.notification.error("Erreur lors du chargement des documents.");
        this.showDetailsPlaceholder(); // En cas d'erreur, on revient au placeholder
    }
}

// NOUVELLE FONCTION pour construire le HTML du panneau
// Fichier : nouvelledemande.js

buildDocumentsHtml(details) {
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

// Fonctions de gestion des états du panneau
showLoader() {
    $('#details-placeholder').hide();
    $('#details-content').hide();
    $('#details-loader').show();
}

showDetailsPlaceholder() {
    $('#details-loader').hide();
    $('#details-content').hide();
    $('#details-placeholder').show();
    $('#details-title-text').text('Documents');
}



    

    async saveDemande() {
        try {
            const formData = {
                id: $('#demandeId').val() || null,
                titre: $('#titre').val(),
                description: $('#description').val(),
                typeDocumentId: $('#typeDocument').val()
            };
            
            // Validation
            if (!formData.titre || !formData.typeDocumentId) {
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

    async handleFileUpload(files) {
        if (!this.selectedDemandeId && !$('#demandeId').val()) {
            this.notification.warning('Veuillez d\'abord enregistrer la demande');
            return;
        }
        
        const demandeId = this.selectedDemandeId || $('#demandeId').val();
        
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
            
            try {
                await this.apiService.addDocument(demandeId, formData);
                this.notification.success('Document ajouté avec succès');
                
                // Reload the data
                if (this.currentMode) {
                    this.loadDemandeData(demandeId);
                } else {
                    this.selectDemande(demandeId);
                }
            } catch (error) {
                this.notification.error('Erreur lors de l\'ajout du document');
                console.error(error);
            }
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
            case 'approved': 
            case 'approuvée':
                return '<span class="status-badge status-approved"><i class="ph-fill ph-check-circle"></i> Approuvée</span>';
            case 'pending': 
            case 'en_attente':
                return '<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i> En attente</span>';
            case 'rejected': 
            case 'rejetée':
                return '<span class="status-badge status-rejected"><i class="ph-fill ph-x-circle"></i> Rejetée</span>';
            default: 
                return '<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i> ' + status + '</span>';
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

// Initialisation de l'application lorsque le document est prêt
$(document).ready(function() {
    // Vérifier que les dépendances sont chargées
    if (typeof window.apiService === 'undefined') {
        console.error('ApiService non chargé');
        return;
    }
    
    if (typeof window.notificationSystem === 'undefined') {
        console.error('NotificationSystem non chargé');
        return;
    }
    
    // Initialiser l'application
    const nouvelleDemandeApp = new NouvelleDemandeApp();
    nouvelleDemandeApp.init();
    
    // Exposer l'instance globalement pour le débogage
    window.nouvelleDemandeApp = nouvelleDemandeApp;
});