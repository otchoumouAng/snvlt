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
        
        responsive: true, // Votre ligne est correcte

        // --- AJOUTER CETTE SECTION ---
        columnDefs: [
            {
                // Applique la classe pour l'icône de contrôle à la première colonne
                className: 'dtr-control',
                orderable: false,
                targets: 0
            },
            { 
                // Priorité 1 (la plus haute) pour la colonne de contrôle
                responsivePriority: 1, 
                targets: 0 
            },
            { 
                // Priorité 2 pour le statut, qui restera visible plus longtemps
                responsivePriority: 2, 
                targets: -1 // Cible la dernière colonne
            }
        ],
        // --- FIN DE L'AJOUT ---

        select: {
            style: 'single',
            info: false
        },
        order: [[4, 'desc']], // L'ordre se base sur la colonne "Date Demande"
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50]
    });
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

    async displayDetails(demandeId, etapeId) {
        try {
            const details = await this.apiService.getDemandeDetailsForValidation(demandeId);
            const detailsContent = $('#details-content');
            const placeholder = $('#details-placeholder');

            placeholder.hide();

            let documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document pour cette demande</li>';
            if (details.documents && details.documents.length > 0) {
                documentsHtml = details.documents.map(doc => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="${doc.path || '#'}" target="_blank" class="text-decoration-none text-dark text-truncate" style="max-width: 80%;">${doc.nom}</a>
                        <span class="badge bg-secondary">${doc.statut}</span>
                        <a href="${doc.path || '#'}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="ph ph-eye"></i></a>
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

                <h6 class="text-muted small fw-bold text-uppercase mb-2">Documents Fournis</h6>
                <ul class="list-group list-group-flush document-list mb-4">${documentsHtml}</ul>

                <h6 class="text-muted small fw-bold text-uppercase mb-2">Circuit de Validation</h6>
                <ul class="list-group list-group-flush document-list mb-4">${etapesHtml}</ul>

                <div id="validation-actions">
                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Action</h6>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-danger" id="reject-step-btn" data-etape-id="${etapeId}"><i class="ph ph-x-circle"></i> Rejeter</button>
                        <button class="btn btn-success" id="approve-step-btn" data-etape-id="${etapeId}"><i class="ph ph-check-circle"></i> Approuver</button>
                    </div>
                </div>

                <div id="rejection-form" class="mt-3" style="display: none;">
                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Motif du Rejet</h6>
                    <textarea id="rejection-comment" class="form-control" rows="3" placeholder="Veuillez fournir un commentaire..."></textarea>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button class="btn btn-secondary btn-sm" id="cancel-rejection-btn">Annuler</button>
                        <button class="btn btn-danger btn-sm" id="confirm-rejection-btn" data-etape-id="${etapeId}">Confirmer le Rejet</button>
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

    async approveStep(etapeId) {
        try {
            await this.apiService.post(`/admin/validation_demande_autorisation/etape/${etapeId}/validate`, { decision: 'approve' });
            this.notification.success('Étape approuvée avec succès.');
            this.loadDemandes();
        } catch (error) {
            this.notification.error("Erreur lors de l'approbation de l'étape.");
            console.error(error);
        }
    }

    async rejectStep(etapeId, comment) {
        if (!comment) {
            this.notification.warning('Le commentaire de rejet est obligatoire.');
            return;
        }

        try {
            await this.apiService.post(`/admin/validation_demande_autorisation/etape/${etapeId}/validate`, {
                decision: 'reject',
                comment: comment
            });
            this.notification.success('Étape rejetée avec succès.');
            this.loadDemandes();
        } catch (error) {
            this.notification.error(error.message || "Erreur lors du rejet de l'étape.");
            console.error(error);
        }
    }

    getStatusBadge(status) {
        switch (status) {
            case 'approved':
            case 'valider':
            case 'approuvée':
                return '<span class="status-badge status-approved"><i class="ph-fill ph-check-circle"></i> Approuvée</span>';
            case 'pending':
            case 'en_attente':
            case 'en_observation':
                return '<span class="status-badge status-pending"><i class="ph-fill ph-hourglass"></i> En attente</span>';
            case 'rejected':
            case 'refuser':
            case 'rejetée':
                return '<span class="status-badge status-rejected"><i class="ph-fill ph-x-circle"></i> Rejetée</span>';
            default:
                return `<span class="status-badge">${status}</span>`;
        }
    }
}
