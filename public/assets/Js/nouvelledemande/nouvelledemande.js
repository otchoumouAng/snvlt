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
            select: {
                style: 'single',
                info: false
            },
            responsive: true,
            order: [[4, 'desc']],
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50]
        });

        // Événement de sélection
        this.dataTable.on('click', 'tbody tr', (e) => {
            const row = this.dataTable.row(e.currentTarget);
            const data = row.data();
            if (data) {
                // Mettre en surbrillance la ligne sélectionnée
                row.select();
                
                // Utiliser les données existantes au lieu de faire un nouvel appel API
                this.selectedDemandeId = data.id;
                $('#editBtn').prop('disabled', false);
                this.displayDetails(data);
            }
        });

        // Événement de double-clic
        this.dataTable.on('dblclick', 'tbody tr', (e) => {
            const row = this.dataTable.row(e.currentTarget);
            const data = row.data();
            if (data) {
                this.openModal(data.id, 'read');
            }
        });
    }

    bindEvents() {
        // Add button
       $('#addBtn').on('click', () => {
            this.openModal(null, 'new');
        });

        // Edit button
        $('#editBtn').on('click', () => {
            if (this.selectedDemandeId) {
                this.openModal(this.selectedDemandeId, 'edit');
            }
        });


        // Modal events (delegated)
        $(document).on('click', '#addDocumentBtn', (e) => {
            $('#pdf-upload').click();
        });

        $(document).on('change', '#pdf-upload', (e) => {
            this.handleFileUpload(e.target.files);
        });

        $(document).on('click', '.remove-doc-btn', (e) => {
            const documentId = $(e.currentTarget).closest('.list-group-item').data('doc-id');
            this.removeDocument(documentId);
        });

        $(document).on('submit', '#demandeForm', (e) => {
            e.preventDefault();
            this.saveDemande();
        });

        $(document).on('click', '#deleteBtn', (e) => {
            this.deleteDemande();
        });

        // Fermer le modal quand il est caché
        $(document).on('hidden.bs.modal', '#demandeModal', () => {
            this.cleanupModal();
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
        const detailsPlaceholder = $('#details-placeholder');
        const documentsContent = $('#documents-content');
        const documentsPlaceholder = $('#documents-placeholder');

        // Hide placeholders and content
        detailsPlaceholder.hide();
        documentsPlaceholder.hide();
        detailsContent.removeClass('visible');
        documentsContent.removeClass('visible');

        // Populate main details
        const detailsHtml = `
            <h5 class="fw-bold">${details.titre}</h5>
            <p class="text-muted mb-2">${details.description || 'Aucune description'}</p>
            ${NouvelleDemandeApp.getStatusBadge(details.statut)}
            <div class="mt-2 small text-muted">Type: ${details.typeDocument}</div>
        `;
        detailsContent.html(detailsHtml);

        // Populate documents
        const hasDocuments = details.documents && details.documents.length > 0;
        let documentsHtml = '';
        if (hasDocuments) {
            details.documents.forEach((doc) => {
                documentsHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-doc-id="${doc.id}">
                        <span class="text-truncate" style="max-width: 70%;">${doc.nom}</span>
                        <div class="d-flex align-items-center gap-2">
                            ${NouvelleDemandeApp.getDocumentStatusBadge(doc.statut)}
                            <button class="btn btn-sm btn-light text-danger remove-doc-btn" title="Retirer le document">
                                <i class="ph-fill ph-x"></i>
                            </button>
                        </div>
                    </li>`;
            });
        } else {
            documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document ajouté</li>';
        }

        const documentsPanelHtml = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small fw-bold text-uppercase mb-0">Documents Requis</h6>
                 <button type="button" class="btn btn-sm btn-outline-primary" id="addDocumentBtnPanel">
                    <i class="ph-fill ph-plus me-1"></i> Ajouter
                </button>
                <input type="file" id="pdf-upload-panel" accept=".pdf" style="display: none;" multiple />
            </div>
            <ul class="list-group list-group-flush document-list">${documentsHtml}</ul>
        `;
        documentsContent.html(documentsPanelHtml);

        // Show content with transition
        setTimeout(() => {
            detailsContent.addClass('visible');
            documentsContent.addClass('visible');
        }, 10);

        // Bind panel events
        $('#addDocumentBtnPanel').on('click', () => {
            $('#pdf-upload-panel').click();
        });

        $('#pdf-upload-panel').on('change', (e) => {
            this.handleFileUpload(e.target.files);
        });
    }

    showDetailsPlaceholder() {
        const detailsContent = $('#details-content');
        const detailsPlaceholder = $('#details-placeholder');
        const documentsContent = $('#documents-content');
        const documentsPlaceholder = $('#documents-placeholder');
        
        detailsContent.removeClass('visible').html('');
        documentsContent.removeClass('visible').html('');
        detailsPlaceholder.show();
        documentsPlaceholder.show();
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
            documentsSection.hide();
            form.find('input, select, textarea').prop('disabled', false);
            break;
            
        case 'edit':
            title.text('Modifier la Demande');
            icon.attr('class', 'ph-fill ph-pencil-simple');
            saveBtn.show().text('Modifier');
            deleteBtn.show();
            documentsSection.show();
            form.find('input, select, textarea').prop('disabled', false);
            
            // Charger les documents depuis l'API
            this.loadDocuments(data.id);
            break;
            
        case 'read':
            title.text('Détails de la Demande');
            icon.attr('class', 'ph-fill ph-eye');
            saveBtn.hide();
            deleteBtn.hide();
            documentsSection.show();
            form.find('input, select, textarea').prop('disabled', true);
            
            // Charger les documents depuis l'API
            this.loadDocuments(data.id);
            break;
    }
}

async loadDocuments(demandeId) {
    try {
        const details = await this.apiService.getDemandeDetails(demandeId);
        let documentsHtml = '';
        
        if (details.documents && details.documents.length > 0) {
            details.documents.forEach((doc) => {
                documentsHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-doc-id="${doc.id}">
                        <span class="text-truncate" style="max-width: 70%;">${doc.nom}</span>
                        <div class="d-flex align-items-center gap-2">
                            ${NouvelleDemandeApp.getDocumentStatusBadge(doc.statut)}
                            ${this.currentMode !== 'read' ? 
                                `<button class="btn btn-sm btn-light text-danger remove-doc-btn" title="Retirer le document">
                                    <i class="ph-fill ph-x"></i>
                                </button>` : ''
                            }
                        </div>
                    </li>`;
            });
        } else {
            documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document ajouté</li>';
        }
        
        $('#documents-list').html(documentsHtml);
    } catch (error) {
        console.error('Erreur lors du chargement des documents:', error);
    }
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