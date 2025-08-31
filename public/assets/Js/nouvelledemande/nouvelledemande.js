class NouvelleDemandeApp {
    constructor() {
        this.apiService = window.apiService;
        this.notification = window.notificationSystem;
        this.selectedDemandeId = null;
        this.currentMode = null;
        this.dataTable = null;
        this.modal = null;
    }

    init() {
        this.initDataTable();
        this.bindEvents();
        this.loadDemandes();
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
                    render: (data, type, row) => NouvelleDemandeApp.getStatusBadge(data)
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
                    $('#trackBtn').prop('disabled', false);
                    this.displayDetailsPanel(data);
                }
            }
        });

        this.dataTable.on('deselect', (e, dt, type, indexes) => {
            if (type === 'row') {
                this.selectedDemandeId = null;
                $('#editBtn').prop('disabled', true);
                $('#trackBtn').prop('disabled', true);
                this.showDetailsPlaceholder();
            }
        });
    }

    bindEvents() {
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

        $(document).on('submit', '#demandeForm', (e) => {
            e.preventDefault();
            this.saveDemande();
        });

        // Event delegation for document panel
        $('#details-panel')
            .on('click', '.upload-doc-btn', function() {
                $(this).siblings('.pdf-upload-panel').click();
            })
            .on('change', '.pdf-upload-panel', (e) => {
                const typeDocId = $(e.currentTarget).closest('.document-item').data('type-doc-id');
                this.handleFileUpload(e.target.files, typeDocId);
            })
            .on('click', '.remove-doc-btn', (e) => {
                const documentId = $(e.currentTarget).closest('.document-item').data('doc-id');
                this.removeDocument(documentId);
            });

        $(document).on('click', '#back-to-list', (e) => {
            e.preventDefault();
            this.showMainView();
        });

        $(document).on('click', '.stepper-item.completed', (e) => {
            const stepId = $(e.currentTarget).data('step-id');
            this.loadStepDetails(this.selectedDemandeId, stepId);
        });
    }

    async showTrackingPortal(demandeId) {
        try {
            const trackingHtml = await this.apiService.getTrackingView(demandeId);
            $('#page_content').fadeOut(200, function() {
                $(this).html(trackingHtml).fadeIn(200);
            });
        } catch (error) {
            this.notification.error("Impossible de charger la vue de suivi.");
            console.error(error);
        }
    }

    showMainView() {
        location.reload();
    }

    async loadStepDetails(demandeId, stepId) {
        try {
            const detailsHtml = await this.apiService.getStepDetails(demandeId, stepId);
            $('#step-details-placeholder').hide();
            $('#step-details-content').html(detailsHtml);
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
            this.selectedDemandeId = null;
            $('#editBtn').prop('disabled', true);
            $('#trackBtn').prop('disabled', true);
            this.showDetailsPlaceholder();
        } catch (error) {
            this.notification.error('Erreur lors du chargement des demandes');
            console.error(error);
        }
    }

    async displayDetailsPanel(demandeData) {
        this.showLoader();
        $('#details-title-text').html(`Détails pour : <span class="fw-normal">${demandeData.titre}</span>`);

        try {
            const details = await this.apiService.getDemandeDetails(demandeData.id);
            let documentsListHtml = '';
            if (details.documents && details.documents.length > 0) {
                documentsListHtml = details.documents.map(doc => {
                    if (doc.statut === 'fourni') {
                        return `
                            <li class="document-item" data-doc-id="${doc.document_id}" data-type-doc-id="${doc.type_document_id}">
                                <i class="ph-fill ph-file-pdf icon"></i>
                                <div class="info">
                                    <div class="name">${doc.nom}</div>
                                    <div class="meta">Fichier: ${doc.nom_fichier || 'N/A'}</div>
                                </div>
                                ${NouvelleDemandeApp.getDocumentStatusBadge(doc.statut)}
                                <button class="btn btn-sm btn-danger remove-doc-btn"><i class="ph ph-trash"></i></button>
                            </li>
                        `;
                    } else { // manquant
                        return `
                             <li class="document-item" data-type-doc-id="${doc.type_document_id}">
                                <i class="ph-light ph-file icon"></i>
                                <div class="info">
                                    <div class="name">${doc.nom}</div>
                                    <div class="meta">Requis</div>
                                </div>
                                ${NouvelleDemandeApp.getDocumentStatusBadge(doc.statut)}
                                <button class="btn btn-sm btn-primary upload-doc-btn"><i class="ph ph-upload-simple"></i></button>
                                <input type="file" class="pdf-upload-panel" accept=".pdf" style="display: none;" />
                            </li>
                        `;
                    }
                }).join('');
            } else {
                documentsListHtml = `
                    <div class="text-center p-4">
                        <i class="ph-light ph-file-magnifying-glass" style="font-size: 2.5rem; color: #ced4da;"></i>
                        <p class="text-muted mt-2 small">Aucun document requis pour ce type de demande.</p>
                    </div>
                `;
            }

            const contentHtml = `
                <div class="mb-3">
                    <p class="text-muted mb-2">${details.description || 'Aucune description.'}</p>
                    ${NouvelleDemandeApp.getStatusBadge(details.statut)}
                    <div class="mt-2 small text-muted">Type: ${details.typeDemande}</div>
                </div>
                <hr>
                <h6 class="text-muted small fw-bold text-uppercase mb-3">Documents Requis</h6>
                <ul class="document-list">${documentsListHtml}</ul>
            `;

            $('#details-placeholder').hide();
            $('#details-content').html(contentHtml).show().css('opacity', 0).animate({ opacity: 1 }, 300);
        } catch (error) {
            console.error("Erreur lors du chargement des détails:", error);
            this.notification.error("Erreur lors du chargement des détails.");
            this.showDetailsPlaceholder();
        } finally {
            $('#details-loader').hide();
        }
    }

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
                typeDemandeId: $('#typeDemande').val()
            };
            
            if (!formData.titre || !formData.typeDemandeId) {
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

    async handleFileUpload(files, typeDocId) {
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
            formData.append('type_document_id', typeDocId);
            
            try {
                await this.apiService.addDocument(demandeId, formData);
                this.notification.success('Document ajouté avec succès');
                const selectedData = this.dataTable.row({ selected: true }).data();
                if (selectedData) {
                    this.displayDetailsPanel(selectedData);
                }
            } catch (error) {
                this.notification.error('Erreur lors de l\'ajout du document');
                console.error(error);
            }
        }
    }

    async removeDocument(documentId) {
        if (!this.selectedDemandeId) {
            this.notification.error('Aucune demande sélectionnée');
            return;
        }
        
        if (!confirm('Êtes-vous sûr de vouloir retirer ce document ?')) {
            return;
        }
        
        const demandeId = this.selectedDemandeId;
        
        try {
            await this.apiService.removeDocument(demandeId, documentId);
            this.notification.success('Document retiré avec succès');
            const selectedData = this.dataTable.row({ selected: true }).data();
            if (selectedData) {
                this.displayDetailsPanel(selectedData);
            }
        } catch (error) {
            this.notification.error('Erreur lors du retrait du document');
            console.error(error);
        }
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
                return `<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i> ${status}</span>`;
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
                return `<span class="badge bg-secondary">${status}</span>`;
        }
    }
}

$(document).ready(function() {
    if (typeof window.apiService === 'undefined') {
        console.error('ApiService non chargé');
        return;
    }
    if (typeof window.notificationSystem === 'undefined') {
        console.error('NotificationSystem non chargé');
        return;
    }
    const nouvelleDemandeApp = new NouvelleDemandeApp();
    nouvelleDemandeApp.init();
    window.nouvelleDemandeApp = nouvelleDemandeApp;
});