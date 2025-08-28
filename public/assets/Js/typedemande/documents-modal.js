class DocumentsModal {
    constructor() {
        this.typeDemandeId = null;
        this.typeDemandeName = null;
        this.modal = null;
    }

    /**
     * Ouvre le modal d'édition des documents
     * @param {number} typeDemandeId - ID du type de demande
     * @param {string} typeDemandeName - Nom du type de demande
     */
    open(typeDemandeId, typeDemandeName) {
        this.typeDemandeId = typeDemandeId;
        this.typeDemandeName = typeDemandeName;

        // Charger le template
        const template = document.getElementById('template-documents').innerHTML;
        document.getElementById('documentsModalBody').innerHTML = template;
        
        // Mettre à jour le titre
        document.getElementById('typeDemandeName').textContent = typeDemandeName;

        // Initialiser le modal
        this.modal = new bootstrap.Modal(document.getElementById('documentsModal'));
        this.modal.show();

        // Initialiser les composants
        this.initSelect2();
        this.loadAvailableDocuments();
        this.loadAssociatedDocuments();

        // Ajouter les écouteurs d'événements
        this.addEventListeners();
    }

    // ... le reste des méthodes ...

    /**
     * Initialise Select2 pour la combobox de sélection des documents
     */
    /**
 * Initialise Select2 pour la combobox de sélection des documents
 */
initSelect2() {
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 non chargé, tentative de rechargement');
        this.loadSelect2Fallback();
        return;
    }
    
    $('#documentSelect').select2({
        placeholder: "Sélectionner un document",
        allowClear: true,
        width: 'resolve'
    });
}

/**
 * Charge Select2 en fallback si non chargé
 */
loadSelect2Fallback() {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
    script.onload = () => {
        console.log('Select2 chargé avec fallback');
        this.initSelect2();
    };
    document.head.appendChild(script);
}

    /**
     * Charge la liste des documents disponibles
     */
    loadAvailableDocuments() {
        apiService.get('/admin/documents/available')
            .then(data => {
                const select = document.getElementById('documentSelect');
                select.innerHTML = '<option value="">Sélectionner un document</option>';
                
                data.forEach(document => {
                    const option = document.createElement('option');
                    option.value = document.id;
                    option.textContent = document.designation;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des documents:', error);
                Notification.error('Erreur lors du chargement des documents disponibles');
            });
    }

    /**
     * Charge la liste des documents associés au type de demande
     */
    loadAssociatedDocuments() {
        apiService.get(`/admin/type_demande/${this.typeDemandeId}/documents`)
            .then(documents => {
                const tbody = document.getElementById('documentsList');
                tbody.innerHTML = '';
                
                if (documents.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center">Aucun document associé</td></tr>';
                    return;
                }
                
                documents.forEach(doc => {
                    const row = document.createElement('tr');
                    row.className = 'document-row';
                    row.innerHTML = `
                        <td>${doc.designation}</td>
                        <td class="document-actions">
                            <button class="btn btn-sm btn-danger remove-document" data-document-id="${doc.id}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                // Ajouter les écouteurs d'événements pour les boutons de suppression
                document.querySelectorAll('.remove-document').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const documentId = e.currentTarget.getAttribute('data-document-id');
                        this.removeDocument(documentId);
                    });
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des documents associés:', error);
                Notification.error('Erreur lors du chargement des documents associés');
            });
    }

    /**
     * Ajoute un document au type de demande
     */
    addDocument() {
        const documentSelect = document.getElementById('documentSelect');
        const documentId = documentSelect.value;
        
        if (!documentId) {
            Notification.warning('Veuillez sélectionner un document');
            return;
        }
        
        apiService.post(`/admin/type_demande/${this.typeDemandeId}/add_document`, { document_id: documentId })
            .then(result => {
                if (result.success) {
                    Notification.success('Document ajouté avec succès');
                    documentSelect.value = '';
                    $('#documentSelect').trigger('change');
                    this.loadAssociatedDocuments();
                } else {
                    Notification.error(result.message || 'Erreur lors de l\'ajout du document');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'ajout du document:', error);
                Notification.error('Erreur lors de l\'ajout du document');
            });
    }

    /**
     * Supprime un document du type de demande
     * @param {number} documentId - ID du document à supprimer
     */
    removeDocument(documentId) {
        Swal.fire({
            title: 'Êtes-vous sûr?',
            text: "Voulez-vous vraiment retirer ce document?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, retirer!',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                apiService.post(`/admin/type_demande/${this.typeDemandeId}/remove_document`, { document_id: documentId })
                    .then(result => {
                        if (result.success) {
                            Notification.success('Document retiré avec succès');
                            this.loadAssociatedDocuments();
                        } else {
                            Notification.error(result.message || 'Erreur lors du retrait du document');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du retrait du document:', error);
                        Notification.error('Erreur lors du retrait du document');
                    });
            }
        });
    }

    /**
     * Ajoute les écouteurs d'événements
     */
    addEventListeners() {
        document.getElementById('btnAddDocument').addEventListener('click', () => {
            this.addDocument();
        });
    }
}

// Initialiser et exporter l'instance globale
window.documentsModal = new DocumentsModal();