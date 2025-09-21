class ValidationDemandeApp {
    constructor() {
        this.apiService = window.apiService;
        this.notification = window.notificationSystem;
        this.selectedDemandeId = null;
        this.dataTable = null;
    }

    init() {
        this.initDataTable();
        this.bindEvents();
        this.loadDemandes();
    }

   /* initDataTable() {
        this.dataTable = new DataTable('#demandesTable', {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },
            columns: [
                { data: 'etape_id', title: 'ID Etape' },
                { data: 'titre', title: 'Titre de la Demande' },
                { data: 'description', title: 'Étape Actuelle' },
                { data: 'societe', title: 'Société' },
                { data: 'dateCreation', title: 'Date Demande' },
                {
                    data: 'statut',
                    title: 'Statut Demande',
                    render: (data, type, row) => {
                        return this.getStatusBadge(data);
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
    }*/

    initDataTable() {
    this.dataTable = new DataTable('#demandesTable', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        },
        columns: [
            {
                // Colonne pour l'icône de contrôle responsive (laissée vide)
                data: null,
                defaultContent: '',
                className: 'dtr-control',
                orderable: false
            },
            { data: 'titre', title: 'Type de Demande' },
            { data: 'societe', title: 'Société' },
            {
                data: 'statut',
                title: 'Statut Demande',
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
                targets: 1  // Priorité maximale sur la colonne Type de Demande
            },
            { 
                responsivePriority: 2, 
                targets: 3 // La colonne Statut est la 2e plus importante
            }
        ],
        select: {
            style: 'single',
            info: false
        },
        order: [[5, 'desc']], // Tri par date de demande décroissante (colonne 5)
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
            return `<span class="status-badge">${status}</span>`;
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

        this.dataTable.on('deselect', (e, dt, type, indexes) => {
            if (type === 'row') {
                this.selectedDemandeId = null;
                this.showDetailsPlaceholder();
            }
        });

        $(document).on('click', '#approve-step-btn', (e) => {
            const etapeId = $(e.currentTarget).data('etape-id');
            this.approveStep(etapeId);
        });

        $(document).on('click', '#reject-step-btn', () => {
            $('#validation-actions').hide();
            $('#rejection-form').slideDown();
        });

        $(document).on('click', '#cancel-rejection-btn', () => {
            $('#rejection-form').slideUp(() => {
                $('#validation-actions').show();
                $('#rejection-comment').val('');
            });
        });

        $(document).on('click', '#confirm-rejection-btn', (e) => {
            const etapeId = $(e.currentTarget).data('etape-id');
            const comment = $('#rejection-comment').val();
            this.rejectStep(etapeId, comment);
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
            const justification = $('#justification-comment').val();
            const newStatus = $('#demande-status').val();

            if (Object.keys(this.refusedDocuments).length > 0 && !justification) {
                this.notification.warning('La justification est obligatoire si vous refusez des documents.');
                return;
            }

            this.validateDemande(demandeId, newStatus, this.refusedDocuments, justification);
        });
    }

    async loadDemandes() {
        try {
            this.notification.info('Chargement des demandes...', 2000);
            // We'll need a new API endpoint for this
            const demandes = await this.apiService.getDemandesForValidation();
            this.dataTable.clear().rows.add(demandes).draw();
            this.selectedDemandeId = null;
            this.showDetailsPlaceholder();
        } catch (error) {
            this.notification.error('Erreur lors du chargement des demandes');
            console.error(error);
        }
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



    /*async displayDetails(demandeId, etapeId) {
        try {
            const details = await this.apiService.getDemandeDetailsForValidation(demandeId);
            console.log(details)
            const detailsContent = $('#details-content');
            const placeholder = $('#details-placeholder');

            placeholder.hide();

            let documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document pour cette demande</li>';
            this.refusedDocuments = {}; // Reset refused documents
            if (details.documents && details.documents.length > 0) {
                documentsHtml = details.documents.map(doc => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="${doc.path || '#'}" target="_blank" class="text-decoration-none text-dark text-truncate" style="max-width: 80%;">${doc.nom}</a>
                        <div>
                            <span class="badge bg-secondary me-2">${doc.statut}</span>
                            <a href="${doc.path || '#'}" target="_blank" class="btn btn-sm btn-outline-primary view-doc-btn"><i class="ph ph-eye"></i></a>
                            <button class="btn btn-sm btn-outline-danger refuse-doc-btn" data-doc-id="${doc.document_id}"><i class="ph ph-x"></i></button>
                        </div>
                    </li>
                `).join('');
            }

            let etapesHtml = '<li class="list-group-item text-muted text-center">Aucun circuit de validation trouvé</li>';
            if (details.etapes_validation && details.etapes_validation.length > 0) {
                etapesHtml = details.etapes_validation.map(etape => `
                    <li class="list-group-item d-flex justify-content-between align-items-center ${etape.id === etapeId ? 'active' : ''}">
                        <span>${etape.ordre}. ${etape.nom}</span>
                        <span class="badge bg-info">${etape.statut}</span>
                    </li>
                `).join('');
            }

            const contentHtml = `
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">${details.titre}</h5>
                    <p class="text-muted mb-2">Société: ${details.societe}</p>
                    ${this.getStatusBadge(details.statut)}
                </div>

                <div class="mb-3">
                    <label for="demande-status" class="form-label">Changer le statut de la demande</label>
                    <select id="demande-status" class="form-select">
                        <option value="En cours">En cours de traitement</option>
                        <option value="Signé">Demande signée et disponible</option>
                    </select>
                </div>

                <h6 class="text-muted small fw-bold text-uppercase mb-2">Documents Fournis</h6>
                <ul class="list-group list-group-flush document-list mb-4">${documentsHtml}</ul>

                <h6 class="text-muted small fw-bold text-uppercase mt-4 mb-2">Circuit de Validation</h6>
                <ul class="list-group list-group-flush mb-4">${etapesHtml}</ul>

                <div id="justification-form" class="mt-3" style="display: none;">
                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Justification du refus</h6>
                    <textarea id="justification-comment" class="form-control" rows="3" placeholder="Veuillez fournir une justification..."></textarea>
                </div>

                <div id="validation-actions" class="mt-3">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary" id="validate-demande-btn" data-demande-id="${demandeId}"><i class="ph ph-check-circle"></i> Valider</button>
                    </div>
                </div>
            `;

            detailsContent.html(contentHtml).addClass('visible');
        } catch (error) {
            this.notification.error("Erreur lors de l'affichage des détails.");
            console.error(error);
            this.showDetailsPlaceholder();
        }
    }*/

    async displayDetails(demandeId, etapeId) {
    try {
        const details = await this.apiService.getDemandeDetailsForValidation(demandeId);
        const detailsContent = $('#details-content');
        const placeholder = $('#details-placeholder');

        placeholder.hide();

        // 1. Logique pour les documents
        let documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document pour cette demande</li>';
        this.refusedDocuments = {}; // Réinitialise les documents refusés à chaque affichage
        if (details.documents && details.documents.length > 0) {
            documentsHtml = details.documents.map(doc => {
                // Affiche les boutons uniquement si le statut est "Chargé"
                const actionButtons = doc.statut === 'Chargé' ? `
                    <a href="${doc.path || '#'}" target="_blank" class="btn btn-sm btn-outline-primary view-doc-btn"><i class="ph ph-eye"></i></a>
                    <button class="btn btn-sm btn-outline-danger refuse-doc-btn" data-doc-id="${doc.document_id}"><i class="ph ph-x"></i></button>
                ` : '';

                return `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate" style="max-width: 70%;">${doc.nom}</span>
                        <div class="d-flex align-items-center">
                            ${this.getDocumentStatusBadge(doc.statut)}
                            ${actionButtons}
                        </div>
                    </li>
                `;
            }).join('');
        }

        // 2. Logique pour le circuit de validation
        let etapesHtml = '<li class="list-group-item text-muted text-center">Aucun circuit de validation trouvé</li>';
        if (details.etapes_validation && details.etapes_validation.length > 0) {
            etapesHtml = details.etapes_validation.map(etape => `
                <li class="list-group-item d-flex justify-content-between align-items-center ${etape.id === etapeId ? 'active' : ''}">
                    <span>${etape.ordre}. ${etape.nom}</span>
                    <span class="badge bg-info">${etape.statut}</span>
                </li>
            `).join('');
        }

        // 3. Construction du HTML final pour le panneau de détails
        const contentHtml = `
            <div class="mb-3">
                <h5 class="fw-bold mb-1">${details.titre}</h5>
                <p class="text-muted mb-2">Société: ${details.societe}</p>
                ${this.getStatusBadge(details.statut)}
            </div>

            <div class="mb-3">
                <label for="demande-status" class="form-label">Changer le statut de la demande</label>
                <select id="demande-status" class="form-select">
                    <option value="Soumis">Soumis</option>
                    <option value="En cours">En cours de traitement</option>
                    <option value="Signé">Demande signée et disponible</option>
                </select>
            </div>

            <h6 class="text-muted small fw-bold text-uppercase mb-2">Documents Fournis</h6>
            <ul class="list-group list-group-flush document-list mb-4">${documentsHtml}</ul>

            <h6 class="text-muted small fw-bold text-uppercase mt-4 mb-2">Circuit de Validation</h6>
            <ul class="list-group list-group-flush mb-4">${etapesHtml}</ul>

            <div id="justification-form" class="mt-3" style="display: none;">
                <h6 class="text-muted small fw-bold text-uppercase mb-2">Justification du refus</h6>
                <textarea id="justification-comment" class="form-control" rows="3" placeholder="Veuillez fournir une justification..."></textarea>
            </div>

            <div id="validation-actions" class="mt-3">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary" id="validate-demande-btn" data-demande-id="${demandeId}"><i class="ph ph-check-circle"></i> Valider</button>
                </div>
            </div>
        `;

        detailsContent.html(contentHtml).addClass('visible');
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

    async validateDemande(demandeId, newStatus, refusedDocuments, justification) {
        const data = {
            newStatus: newStatus,
            refusedDocuments: refusedDocuments,
            justification: justification
        };

        try {
            // I will create this new endpoint in the controller
            await this.apiService.post(`/admin/validation_demande_autorisation/${demandeId}/validate_demande`, data);
            this.notification.success('Demande validée avec succès.');
            this.loadDemandes();
        } catch (error) {
            this.notification.error(error.message || "Erreur lors de la validation de la demande.");
            console.error(error);
        }
    }

    
}
