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
    }

    bindEvents() {
        this.dataTable.on('select', (e, dt, type, indexes) => {
            if (type === 'row') {
                const data = this.dataTable.row(indexes).data();
                if (data) {
                    this.selectedDemandeId = data.id;
                    this.displayDetails(data.id);
                }
            }
        });

        this.dataTable.on('deselect', (e, dt, type, indexes) => {
            if (type === 'row') {
                this.selectedDemandeId = null;
                this.showDetailsPlaceholder();
            }
        });

        $(document).on('click', '#apply-validation-btn', () => this.applyValidation());
        $(document).on('click', '#cancel-validation-btn', () => this.displayDetails(this.selectedDemandeId));
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

    async displayDetails(demandeId) {
        try {
            // We'll need a new API endpoint for this
            const details = await this.apiService.getDemandeDetailsForValidation(demandeId);
            const detailsContent = $('#details-content');
            const placeholder = $('#details-placeholder');

            placeholder.hide();
            detailsContent.removeClass('visible');

            let documentsHtml = '';
            if (details.documents && details.documents.length > 0) {
                details.documents.forEach((doc) => {
                    documentsHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="${doc.url || '#'}" target="_blank" class="text-decoration-none text-dark text-truncate" style="max-width: 80%;">${doc.nom}</a>
                            <a href="${doc.url || '#'}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="ph ph-download-simple"></i></a>
                        </li>`;
                });
            } else {
                documentsHtml = '<li class="list-group-item text-muted text-center">Aucun document pour cette demande</li>';
            }

            const contentHtml = `
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">${details.titre}</h5>
                    <p class="text-muted mb-2">${details.description || 'Aucune description'}</p>
                    ${this.getStatusBadge(details.statut)}
                </div>

                <h6 class="text-muted small fw-bold text-uppercase mb-2">Documents</h6>
                <ul class="list-group list-group-flush document-list mb-4">${documentsHtml}</ul>

                <h6 class="text-muted small fw-bold text-uppercase mb-2">Action de validation</h6>
                <div class="mb-3">
                    <label for="validation-note" class="form-label">Note</label>
                    <textarea class="form-control" id="validation-note" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Signature</label>
                    <div id="signature-pad" class="border rounded" style="height: 150px;"></div>
                     <button id="clear-signature" class="btn btn-sm btn-link">Effacer</button>
                </div>
                <div class="mb-3">
                    <label for="validation-status" class="form-label">Changer le statut</label>
                    <select class="form-select" id="validation-status">
                        <option value="en_observation">En observation</option>
                        <option value="valider">Valider</option>
                        <option value="refuser">Refuser</option>
                    </select>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-secondary" id="cancel-validation-btn">Annuler</button>
                    <button class="btn btn-primary" id="apply-validation-btn">Appliquer</button>
                </div>
            `;

            detailsContent.html(contentHtml);
            this.initSignaturePad();
            setTimeout(() => detailsContent.addClass('visible'), 10);
        } catch (error) {
            this.notification.error('Erreur lors de l\'affichage des détails.');
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

    initSignaturePad() {
        const signaturePadContainer = document.getElementById('signature-pad');
        if (signaturePadContainer) {
            // Ensure the container is empty before initializing
            signaturePadContainer.innerHTML = '';

            // Pass the container element to LemonadeJS
            this.signaturePad = Signature(signaturePadContainer, {
                width: signaturePadContainer.offsetWidth,
                height: 150,
                instructions: 'Signez ici',
            });

            $('#clear-signature').on('click', () => {
                this.signaturePad.setValue([]);
            });
        }
    }

    async applyValidation() {
        const note = $('#validation-note').val();
        const statut = $('#validation-status').val();
        const signature = this.signaturePad.getImage();

        try {
            await this.apiService.applyValidation(this.selectedDemandeId, { note, statut, signature });
            this.notification.success('Validation appliquée avec succès.');
            this.loadDemandes();
        } catch (error) {
            this.notification.error('Erreur lors de l\'application de la validation.');
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
