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

    preloadModalTemplates() {
        this.modalTemplates.new = $('#template-form-new').html();
        this.modalTemplates.edit = $('#template-form-edit').html();
        this.modalTemplates.read = $('#template-form-read').html();
    }

    initDataTable() {
        this.dataTable = new DataTable('#demandesTable', {
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' },
            columns: [
                { data: null, defaultContent: '', className: 'dtr-control', orderable: false },
                { data: 'id', title: 'ID' },
                { data: 'typeDemande', title: 'Type de Demande' },
                { data: 'societe', title: 'Société' },
                { data: 'statut', title: 'Statut', render: (data) => NouvelleDemandeApp.getStatusBadge(data) },
                {
                    data: null,
                    title: 'Infos',
                    orderable: false,
                    render: function(data, type, row) {
                        if (row.hasRejectedDocuments) {
                            return `<a href="#" class="view-rejected-docs" data-id="${row.id}" style="color: #6c757d; text-decoration: underline; font-size: 0.85rem;">rejet</a>`;
                        }
                        return '';
                    }
                },
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
                { responsivePriority: 4, targets: 4 },
                { responsivePriority: 5, targets: 5 }
            ],
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            select: { style: 'single', info: false }
        });

        this.dataTable.on('select', (e, dt, type, indexes) => {
            if (type === 'row') {
                const data = this.dataTable.row(indexes).data();
                if (data) {
                    this.selectedDemandeId = data.id;
                    $('#editBtn').prop('disabled', false);
                    $('#trackBtn').prop('disabled', false);
                    this.displayDocumentPanel(data);
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
            if (this.selectedDemandeId) this.openModal(this.selectedDemandeId, 'edit');
        });
        $('#trackBtn').on('click', () => {
            if (this.selectedDemandeId) this.showTrackingPortal(this.selectedDemandeId);
        });

        $(document).on('submit', '#demandeForm', (e) => {
            e.preventDefault();
            this.saveDemande();
        });

        $('#demandesTable tbody').on('click', '.view-rejected-docs', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const demandeId = $(e.currentTarget).data('id');
            this.showRejectedDocumentsModal(demandeId);
        });

        $(document).on('change', '#typeDemande', async (e) => {
            const selectedOptionText = $(e.currentTarget).find('option:selected').text();
            $('#pef-container').toggle(selectedOptionText.includes("reprise d'activité"));
            $('#produit-container').toggle(selectedOptionText.includes("Agrément d'exportateur"));
        });

        $('#details-panel').on('click', '.upload', (e) => {
            const docTypeId = $(e.currentTarget).closest('.document-item-new').data('doc-type-id');
            $('#pdf-upload-panel').attr('data-doc-type-id', docTypeId).click();
        });

        $('#details-panel').on('change', '#pdf-upload-panel', (e) => {
            const docTypeId = $(e.currentTarget).data('doc-type-id');
            this.handleFileUpload(e.target.files, docTypeId);
        });

        $('#details-panel').on('click', '.view', (e) => {
            const docPath = $(e.currentTarget).closest('.document-item-new').data('doc-path');
            if (docPath) window.open(docPath, '_blank');
            else this.notification.error('Chemin du document non trouvé.');
        });

        $(document).on('click', '#refresh-demande-btn', (e) => {
            if (!this.selectedDemandeId) return;
            const action = $(e.currentTarget).data('action');
            if (action === 'submit') this.submitDemande();
            else if (action === 'refresh') {
                const rowData = this.dataTable.row({ selected: true }).data();
                if (rowData) this.displayDocumentPanel(rowData);
            }
        });

        $(document).on('click', '#back-to-list', (e) => {
            e.preventDefault();
            this.showMainView();
        });

        $(document).on('click', '.stepper-item.completed', (e) => {
            const stepId = $(e.currentTarget).data('step-id');
            this.loadStepDetails(this.selectedDemandeId, stepId);
        });

        $(document).on('click', '#view-signed-doc-portal-btn', (e) => {
            this.showTrackingPortal(this.selectedDemandeId);
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
            this.showDetailsPlaceholder();
        } catch (error) {
            this.notification.error('Erreur lors du chargement des demandes');
            console.error(error);
        }
    }

    showDetailsPlaceholder() {
        $('#details-content').hide();
        $('#details-placeholder').show();
        $('#details-title-text').text('Documents');
    }

    async openModal(id, mode) {
        try {
            const formHtml = this.modalTemplates[mode];
            if (!formHtml) throw new Error(`Template non trouvé pour le mode: ${mode}`);

            $('#modalContainer').html(formHtml);
            const modalElement = document.getElementById('demandeModal');
            this.modal = new bootstrap.Modal(modalElement);
            this.modal.show();

            this.currentMode = mode;
            
            if (id) {
                const rowData = this.dataTable.row(`#${id}`).data();
                if (rowData) this.setupModalWithData(mode, rowData);
                else await this.loadDemandeData(id);
            } else {
                this.setupModalWithData(mode, {});
            }
        } catch (error) {
            this.notification.error('Erreur lors de l\'ouverture du modal');
            console.error(error);
        }
    }

    setupModalWithData(mode, data) {
        const modal = $('#demandeModal');
        const form = modal.find('#demandeForm');
        form.find('#demandeId').val(data.id);
        form.find('#typePaiement').val(data.typePaiementId);
        form.find('#description').val(data.description);
        form.find('#typeDemande').val(data.typeDemandeId).trigger('change');

        const config = {
            new: { title: 'Nouvelle Demande', icon: 'ph-file-plus', saveText: 'Créer', showDelete: false, disableForm: false },
            edit: { title: 'Modifier la Demande', icon: 'ph-pencil-simple', saveText: 'Modifier', showDelete: true, disableForm: false },
            read: { title: 'Détails de la Demande', icon: 'ph-eye', saveText: '', showDelete: false, disableForm: true }
        };

        modal.find('#modal-title').text(config[mode].title);
        modal.find('#modal-icon').attr('class', `ph-fill ${config[mode].icon}`);
        modal.find('#saveBtn').toggle(!!config[mode].saveText).text(config[mode].saveText);
        modal.find('#deleteBtn').toggle(config[mode].showDelete);
        modal.find('#documents-section').hide();
        form.find('input, select, textarea').prop('disabled', config[mode].disableForm);
    }

    async displayDocumentPanel(demandeData) {
        this.showLoader();
        const status = demandeData.statut;
        let buttonHtml = '';

        if (status === 'Créé' || status === 'rejeté' || status === 'Rejeté') {
            buttonHtml = `<button class="btn btn-sm btn-success" id="refresh-demande-btn" data-action="submit"><i class="ph-fill ph-paper-plane-tilt"></i> Soumettre</button>`;
        } else if (status === 'En cours' || status === 'Soumis') {
            buttonHtml = `<button class="btn btn-sm btn-outline-primary" id="refresh-demande-btn" data-action="refresh"><i class="ph-fill ph-arrows-clockwise"></i> Actualiser</button>`;
        } else if (status === 'Signé') {
            buttonHtml = `<button class="btn btn-sm btn-success" id="view-signed-doc-portal-btn"><i class="ph-fill ph-file-arrow-down"></i> Document signé</button>`;
        }

        $('#details-title-text').html(`<div class="d-flex justify-content-between align-items-center"><span>Documents pour : <span class="fw-normal">${demandeData.titre}</span></span>${buttonHtml}</div>`);
        $('#details-content').html('<div class="loader"></div>').show();
        $('#details-placeholder').hide();

        try {
            const details = await this.apiService.getDemandeDetails(demandeData.id);
            const contentHtml = this.buildDocumentsHtml(details);
            const fullHtml = contentHtml + '<input type="file" id="pdf-upload-panel" accept=".pdf,.xls,.xlsx" style="display: none;" />';
            $('#details-content').html(fullHtml);
        } catch (error) {
            this.notification.error("Erreur lors du chargement des documents.");
            this.showDetailsPlaceholder();
        }
    }

    buildDocumentsHtml(details) {
        if (!details.documents || details.documents.length === 0) {
            return `<div class="text-center p-5"><i class="ph-light ph-folder-simple-dashed" style="font-size: 3rem; color: #ced4da;"></i><h6 class="mt-3">Aucun document requis</h6><p class="text-muted small">Ce type de demande ne nécessite aucun document à fournir.</p></div>`;
        }

        details.documents.sort((a, b) => (a.fichierSpecial ? -1 : 1));

        const documentsListHtml = details.documents.map(doc => {
            const iconHtml = doc.fichierSpecial ? '<i class="ph-fill ph-file-xls doc-icon" style="color: #1D6F42;"></i>' : '<i class="ph-fill ph-file-text doc-icon"></i>';
            const statutHtml = this.constructor.getDocumentStatusBadge(doc.statut);
            const actionsHtml = (doc.statut === 'Chargé' || doc.statut === 'Accepté')
                ? `<button class="action-btn view" title="Visualiser"><i class="ph-fill ph-eye"></i></button>`
                : `<button class="action-btn upload" title="Charger"><i class="ph-fill ph-upload-simple"></i></button>`;

            return `
                <li class="document-item-new" data-doc-type-id="${doc.type_document_id}" data-doc-id="${doc.document_id || ''}" data-doc-path="${doc.path || ''}">
                    ${iconHtml}
                    <div class="doc-info">${doc.nom}</div>
                    <div class="doc-status">${statutHtml}</div>
                    <div class="doc-actions">${actionsHtml}</div>
                </li>`;
        }).join('');
        return `<ul class="document-requirements-list">${documentsListHtml}</ul>`;
    }

    showLoader() {
        $('#details-placeholder').hide();
        $('#details-content').html('<div class="loader"></div>').show();
    }

    async saveDemande() {
        try {
            const formData = {
                id: $('#demandeId').val() || null,
                typePaiementId: $('#typePaiement').val(),
                description: $('#description').val(),
                typeDemandeId: $('#typeDemande').val(),
                numero_pef: $('#numero_pef').val(),
                produit: $('#produit').val()
            };
            
            if (!formData.typePaiementId || !formData.typeDemandeId) {
                this.notification.warning('Veuillez remplir tous les champs obligatoires');
                return;
            }
            
            const result = await this.apiService.saveDemande(formData);
            if (result.success) {
                this.notification.success('Demande enregistrée avec succès');
                this.modal.hide();
                this.loadDemandes();
            } else {
                this.notification.error('Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            this.notification.error('Erreur: ' + error.message);
        }
    }

    async handleFileUpload(files, docTypeId) {
        if (!this.selectedDemandeId) return this.notification.warning('Veuillez sélectionner une demande');
        const demandeId = this.selectedDemandeId;

        for (const file of files) {
            const formData = new FormData();
            formData.append('document', file);
            formData.append('type_document_id', docTypeId);

            try {
                await this.apiService.addDocument(demandeId, formData);
                this.notification.success(`'${file.name}' ajouté.`);
                const rowData = this.dataTable.row({ selected: true }).data();
                if (rowData) this.displayDocumentPanel(rowData);
            } catch (error) {
                this.notification.error(`Erreur d'ajout pour '${file.name}'.`);
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
            this.notification.error('Erreur: ' + error.message);
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
        const statusMap = {
            'Créé': { icon: 'ph-file-plus', class: 'status-pending' },
            'En cours': { icon: 'ph-hourglass', class: 'status-pending' },
            'Soumis': { icon: 'ph-paper-plane-tilt', class: 'status-pending' },
            'Rejeté': { icon: 'ph-x-circle', class: 'status-rejected' },
            'Signé': { icon: 'ph-check-circle', class: 'status-approved' },
            'rejeté': { icon: 'ph-x-circle', class: 'status-rejected' },
        };
        const config = statusMap[status] || { icon: 'ph-question', class: 'status-pending' };
        return `<span class="status-badge ${config.class}"><i class="ph-fill ${config.icon}"></i> ${status}</span>`;
    }

    static getDocumentStatusBadge(status) {
        const statusMap = {
            'Chargé': { class: 'status-fourni' },
            'Accepté': { class: 'status-accepte' },
            'Rejeté': { class: 'status-rejete' },
            'Non chargé': { class: 'status-non-fourni' },
        };
        const config = statusMap[status] || { class: 'status-encours' };
        return `<span class="status-badge-sm ${config.class}">${status}</span>`;
    }

    async showRejectedDocumentsModal(demandeId) {
        try {
            const rejectedDocs = await this.apiService.get(`/admin/nouvelle_demande/${demandeId}/rejected-documents`);
            const tableBody = $('#rejectedDocsTableBody');
            tableBody.empty();

            if (rejectedDocs && rejectedDocs.length > 0) {
                rejectedDocs.forEach(doc => {
                    const row = `
                        <tr>
                            <td>${doc.type_document || 'N/A'}</td>
                            <td>${doc.nom_original || 'N/A'}</td>
                            <td>${doc.date_ajout || 'N/A'}</td>
                        </tr>`;
                    tableBody.append(row);
                });
            } else {
                tableBody.append('<tr><td colspan="3" class="text-center">Aucun document rejeté pour cette demande.</td></tr>');
            }

            const modal = new bootstrap.Modal(document.getElementById('rejectedDocsModal'));
            modal.show();
        } catch (error) {
            this.notification.error('Erreur lors du chargement des documents rejetés.');
            console.error(error);
        }
    }
}
