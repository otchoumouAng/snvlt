class ValidationDemandeApp {
    constructor() {
        this.apiService = window.apiService;
        this.notification = window.notificationSystem;
        this.selectedDemandeId = null;
        this.dataTable = null;
        this.dataTableTraitees = null;
    }

    init() {
        this.initDataTable();
        this.initDataTableTraitees();
        this.bindEvents();
        this.loadDemandes();
    }

    initDataTable() {
    this.dataTable = new DataTable('#demandesTable', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        },
        columns: [
            {
                data: null,
                defaultContent: '',
                className: 'dtr-control',
                orderable: false
            },
            { data: 'id', title: 'ID' },
            { data: 'typeDemande', title: 'Type de Demande' },
            { data: 'societe', title: 'Société' },
            {
                data: 'statut',
                title: 'Statut',
                render: (data, type, row) => {
                    return this.getStatusBadge(data);
                }
            },
            { data: 'description', title: 'Étape Actuelle', className: 'none' },
            { data: 'dateCreation', title: 'Date Demande', className: 'none' }
        ],
        responsive: true,
        columnDefs: [
            {
                responsivePriority: 1,
                targets: 1
            },
            {
                responsivePriority: 2,
                targets: 3
            }
        ],
        select: {
            style: 'single',
            info: false
        },
        // ✅ CORRECTION : Tri par la colonne 'dateCreation' (index 6)
        order: [[6, 'desc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50]
    });
}

getStatusBadge(status) {
    switch (status) {
        case 'Signé':
            return '<span class="status-badge status-approved"><i class="ph-fill ph-check-circle"></i><span>Signée & Disponible</span></span>';
        case 'En cours':
            return '<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i><span>En cours</span></span>';
        case 'Soumis':
            return '<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i><span>Soumis</span></span>';
        case 'refuser':
        case 'Rejetée':
            return '<span class="status-badge status-rejected"><i class="ph-fill ph-x-circle"></i><span>Rejetée</span></span>';
        default:
            return `<span class="status-badge">${status}</span>`;
    }
}
    bindEvents() {
        this.dataTable.on('select', (e, dt, type, indexes) => {
            if (type === 'row') {
                const data = this.dataTable.row(indexes).data();
                if (data) {
                    this.selectedDemandeId = data.id;
                    this.displayDetails(data.id, data.etape_id);
                }
            }
        });

        // ❌ SUPPRESSION des écouteurs pour les boutons 'Actualiser'
        
        this.dataTableTraitees.on('select', (e, dt, type, indexes) => {
            if (type === 'row') {
                const data = this.dataTableTraitees.row(indexes).data();
                if (data) {
                    this.selectedDemandeId = data.id;
                    this.displayDetails(data.id, null, true);
                }
            }
        });

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', (event) => {
            const tabId = event.target.id;
            const titleElement = $('.pro-card-title span');
            if (tabId === 'traitees-tab') {
                this.loadDemandesTraitees();
                titleElement.text("Demandes d'Autorisations Traitées");
            } else if (tabId === 'en-cours-tab') {
                titleElement.text("Demandes d'Autorisations à Valider");
            }
        });

        this.dataTable.on('deselect', (e, dt, type, indexes) => {
            if (type === 'row') {
                this.selectedDemandeId = null;
                this.showDetailsPlaceholder();
            }
        });

        $(document).on('click', '.refuse-doc-btn', (e) => {
            const docId = $(e.currentTarget).data('doc-id');
            if (confirm('Êtes-vous sûr de vouloir refuser ce document ?')) {
                this.refusedDocuments[docId] = 'Refusé';
                $(e.currentTarget).closest('.list-group-item').addClass('refused');
                this.notification.info('Document marqué comme refusé.');
                if (Object.keys(this.refusedDocuments).length > 0) {
                    $('#justification-form').slideDown();
                }
            }
        });

        $(document).on('click', '#validate-demande-btn', (e) => {
            const demandeId = $(e.currentTarget).data('demande-id');
            const etapeId = $(e.currentTarget).data('etape-id');
            const justification = $('#justification-comment').val();
            const newStatus = $('#demande-status').val();

            if (Object.keys(this.refusedDocuments).length > 0 && !justification) {
                this.notification.warning('La justification est obligatoire si vous refusez des documents.');
                return;
            }
            this.validateDemande(demandeId, etapeId, newStatus, this.refusedDocuments, justification);
        });

        $(document).on('click', '.view-rejected-docs', (e) => {
            e.preventDefault();
            const demandeId = $(e.currentTarget).data('id');
            this.showRejectedDocumentsModal(demandeId);
        });
    }

    async showRejectedDocumentsModal(demandeId) {
        try {
            const rejectedDocs = await this.apiService.get(`/admin/validation_demande_autorisation/${demandeId}/rejected-documents`);
            const tableBody = $('#rejectedDocsTableBody');
            tableBody.empty();

            if (rejectedDocs && rejectedDocs.length > 0) {
                rejectedDocs.forEach(doc => {
                    const viewButton = `<a href="${doc.path}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="ph ph-eye"></i> Visualiser</a>`;
                    const row = `
                        <tr>
                            <td>${doc.type_document || 'N/A'}</td>
                            <td>${doc.nom_original || 'N/A'}</td>
                            <td>${viewButton}</td>
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

    async loadDemandes() {
        try {
            const demandes = await this.apiService.getDemandesForValidation();
            this.dataTable.clear().rows.add(demandes).draw();
            this.selectedDemandeId = null;
            this.showDetailsPlaceholder();
        } catch (error) {
            this.notification.error('Erreur lors du chargement des demandes en cours');
            console.error(error);
        }
    }

    async loadDemandesTraitees() {
        try {
            const demandes = await this.apiService.get('/admin/validation_demande_autorisation/liste-traitees');
            this.dataTableTraitees.clear().rows.add(demandes).draw();
        } catch (error) {
            this.notification.error('Erreur lors du chargement des demandes traitées');
            console.error(error);
        }
    }

    initDataTableTraitees() {
        this.dataTableTraitees = new DataTable('#demandesTraiteesTable', {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },
            columns: [
                { data: null, defaultContent: '', className: 'dtr-control', orderable: false },
                { data: 'id', title: 'ID' },
                { data: 'titre', title: 'Type' },
                { data: 'societe', title: 'Société' },
                {
                    data: 'statut',
                    title: 'Statut Demande',
                    render: (data, type, row) => this.getStatusBadge(data)
                },
                {
                    data: 'documentSignePath',
                    title: 'Action',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        if (data) {
                            return `<a href="/uploads/documents/${data}" target="_blank" class="btn btn-sm btn-outline-primary" title="Visualiser le document"><i class="ph ph-eye"></i></a>`;
                        }
                        return '';
                    }
                },
                { data: 'pef', title: 'Nº PEF', className: 'none' },
                { data: 'produit', title: 'Produit', className: 'none' },
                { data: 'typeDemande', title: 'Nature', className: 'none' },
                { data: 'dateTraitement', title: 'Date Traitement', className: 'none' }
            ],
            responsive: true,
            select: {
                style: 'single',
                info: false
            },
            order: [[9, 'desc']],
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50]
        });
    }

    getDocumentStatusBadge(status) {
        switch (status) {
            case 'Chargé':
                return '<span class="doc-badge doc-status-loaded">Chargé</span>';
            case 'En attente':
                return '<span class="doc-badge doc-status-pending">En attente</span>';
            case 'Refusé':
                return '<span class="doc-badge doc-status-rejected">Refusé</span>';
            default:
                return `<span class="doc-badge doc-status-default">${status}</span>`;
        }
    }

    async displayDetails(demandeId, etapeId, isTraitee = false) {
        try {
            const details = await this.apiService.getDemandeDetailsForValidation(demandeId);
            const detailsContent = $('#details-content');
            const placeholder = $('#details-placeholder');
            placeholder.hide();

            let documentsHtml = '';
            this.refusedDocuments = {};
            let hasRejectedDocuments = false;

            if (details.documents && details.documents.length > 0) {
                documentsHtml = details.documents.map(doc => {
                    if (doc.statut === 'Rejeté') {
                        hasRejectedDocuments = true;
                    }
                    let actionButtons = '';
                    if (doc.path) {
                        actionButtons = `<a href="${doc.path}" target="_blank" class="btn btn-sm btn-outline-primary view-doc-btn"><i class="ph ph-eye"></i></a>`;
                    }
                    if (!isTraitee && doc.statut === 'Chargé') {
                        actionButtons += ` <button class="btn btn-sm btn-outline-danger refuse-doc-btn" data-doc-id="${doc.document_id}"><i class="ph ph-x"></i></button>`;
                    }
                    return `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-truncate" style="max-width: 70%;">${doc.nom}</span>
                            <div class="d-flex align-items-center">
                                ${this.getDocumentStatusBadge(doc.statut)}
                                ${actionButtons}
                            </div>
                        </li>`;
                }).join('');
            } else {
                documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document pour cette demande</li>';
            }

            let etapesHtml = '<li class="list-group-item text-muted text-center">Aucun circuit de validation trouvé</li>';
            if (details.etapes_validation && details.etapes_validation.length > 0) {
                etapesHtml = details.etapes_validation.map(etape => `
                    <li class="list-group-item d-flex justify-content-between align-items-center ${etape.id === etapeId ? 'active' : ''}">
                        <span>${etape.ordre}. ${etape.nom}</span>
                        <span class="badge bg-info">${etape.statut}</span>
                    </li>`).join('');
            }

            let rejectedLinkHtml = '';
            if (hasRejectedDocuments) {
                rejectedLinkHtml = `
                    <div class="d-grid gap-2 mb-3">
                        <button class="btn btn-sm btn-outline-danger view-rejected-docs" data-id="${demandeId}">
                            <i class="ph-fill ph-warning-circle"></i> Voir les documents rejetés
                        </button>
                    </div>`;
            }

            let validationSectionHtml = '';
            if (!isTraitee) {
                validationSectionHtml = `
                    ${rejectedLinkHtml}
                    <div class="mb-3">
                        <label for="demande-status" class="form-label">Changer le statut de la demande</label>
                        <select id="demande-status" class="form-select">
                            <option value="En cours">--Changer--</option>
                            <option value="Soumis">Soumis</option>
                            <option value="En cours">En cours de traitement</option>
                            <option value="Signé">Demande signée et disponible</option>
                        </select>
                    </div>
                    <div id="file-upload-container" class="mb-3" style="display: none;">
                        <label for="signed-document" class="form-label">Charger le document signé</label>
                        <input type="file" id="signed-document" class="form-control">
                    </div>
                    <div id="justification-form" class="mt-3" style="display: none;">
                        <h6 class="text-muted small fw-bold text-uppercase mb-2">Justification du refus</h6>
                        <textarea id="justification-comment" class="form-control" rows="3" placeholder="Veuillez fournir une justification..."></textarea>
                    </div>
                    <div id="validation-actions" class="mt-3">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" id="validate-demande-btn" data-demande-id="${demandeId}" data-etape-id="${etapeId}">
                                <i class="ph ph-check-circle"></i> Valider
                            </button>
                        </div>
                    </div>`;
            }

            const contentHtml = `
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">${details.titre}</h5>
                    <p class="text-muted mb-2">Société: ${details.societe}</p>
                    ${this.getStatusBadge(details.statut)}
                </div>
                <h6 class="text-muted small fw-bold text-uppercase mb-2">Documents Fournis</h6>
                <ul class="list-group list-group-flush document-list mb-4">${documentsHtml}</ul>
                <h6 class="text-muted small fw-bold text-uppercase mt-4 mb-2">Circuit de Validation</h6>
                <ul class="list-group list-group-flush mb-4">${etapesHtml}</ul>
                ${validationSectionHtml}`;

            detailsContent.html(contentHtml).addClass('visible');

            if (!isTraitee) {
                $('#demande-status').on('change', function() {
                    if ($(this).val() === 'Signé') {
                        $('#file-upload-container').slideDown();
                    } else {
                        $('#file-upload-container').slideUp();
                    }
                });
            }
        } catch (error) {
            this.notification.error("Erreur lors de l'affichage des détails.");
            console.error(error);
            this.showDetailsPlaceholder();
        }
    }

    showDetailsPlaceholder() {
        const detailsContent = $('#details-content');
        const placeholder = $('#details-placeholder');
        detailsContent.removeClass('visible').html('');
        placeholder.show();
    }

    async validateDemande(demandeId, etapeId, newStatus, refusedDocuments, justification) {
        const formData = new FormData();
        formData.append('etapeId', etapeId);
        formData.append('newStatus', newStatus);
        formData.append('justification', justification);
        formData.append('refusedDocuments', JSON.stringify(refusedDocuments));

        if (newStatus === 'Signé') {
            const fileInput = document.getElementById('signed-document');
            if (fileInput.files.length > 0) {
                formData.append('signedDocument', fileInput.files[0]);
            } else {
                this.notification.warning('Veuillez sélectionner un document signé.');
                return;
            }
        }

        try {
            await this.apiService.validateDemande(demandeId, formData);
            
            this.notification.success('Demande validée avec succès.');
            
            this.loadDemandes();
            this.loadDemandesTraitees();

        } catch (error) {
            this.notification.error(error.message || "Erreur lors de la validation de la demande.");
            console.error(error);
        }
    }
}