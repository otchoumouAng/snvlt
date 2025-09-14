document.addEventListener('DOMContentLoaded', function() {
    const documentsModalElement = document.getElementById('documentsModal');
    if (!documentsModalElement) return;

    const documentsModal = new bootstrap.Modal(documentsModalElement);
    const typeDemandeNameElement = document.getElementById('typeDemandeName');
    const documentSelect = document.getElementById('documentSelect');
    const addDocumentBtn = document.getElementById('addDocumentBtn');
    const documentsTableBody = document.querySelector('#documentsTable tbody');
    let currentTypeDemandeId = null;

    // Called from main.js on dblclick
    window.openDocumentsModal = function(typeDemandeId, typeDemandeName) {
        currentTypeDemandeId = typeDemandeId;
        typeDemandeNameElement.textContent = typeDemandeName;
        loadAllDocuments();
        loadAssociatedDocuments();
        documentsModal.show();
    };

    async function loadAllDocuments() {
        try {
            const response = await fetch('/admin/reference/types-demande/documents');
            const documents = await response.json();
            documentSelect.innerHTML = '<option selected disabled>Choisir un document...</option>';
            documents.forEach(doc => {
                const option = new Option(doc.libelle, doc.id);
                documentSelect.add(option);
            });
        } catch (error) {
            console.error('Error loading documents:', error);
            Notification.error('Erreur de chargement de la liste des documents.');
        }
    }

    async function loadAssociatedDocuments() {
        if (!currentTypeDemandeId) return;
        try {
            const response = await fetch(`/admin/reference/types-demande/${currentTypeDemandeId}/details`);
            const details = await response.json();
            documentsTableBody.innerHTML = '';
            if (details.length === 0) {
                documentsTableBody.innerHTML = '<tr><td colspan="2" class="text-center">Aucun document requis pour le moment.</td></tr>';
            } else {
                details.forEach(detail => {
                    const row = documentsTableBody.insertRow();
                    row.innerHTML = `
                        <td>${detail.document}</td>
                        <td>
                            <button class="btn btn-sm btn-danger btn-remove-detail" data-id="${detail.id}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </td>
                    `;
                });
            }
        } catch (error) {
            console.error('Error loading associated documents:', error);
            Notification.error('Erreur de chargement des documents associés.');
        }
    }

    addDocumentBtn.addEventListener('click', async function() {
        const typeDocumentId = documentSelect.value;
        if (!typeDocumentId || !currentTypeDemandeId) {
            Notification.error('Veuillez sélectionner un document.');
            return;
        }

        try {
            const response = await fetch('/admin/reference/types-demande/add-detail', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type_demande_id: currentTypeDemandeId,
                    type_document_id: typeDocumentId
                })
            });
            const result = await response.json();

            if (result.success) {
                Notification.success(result.message);
                loadAssociatedDocuments();
            } else {
                Notification.error(result.message || 'Une erreur s\'est produite.');
            }
        } catch (error) {
            console.error('Error adding document:', error);
            Notification.error('Erreur critique lors de l\'ajout du document.');
        }
    });

    documentsTableBody.addEventListener('click', async function(event) {
        if (event.target.closest('.btn-remove-detail')) {
            const button = event.target.closest('.btn-remove-detail');
            const detailId = button.dataset.id;

            if (confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) {
                try {
                    const response = await fetch(`/admin/reference/types-demande/remove-detail/${detailId}`, {
                        method: 'DELETE'
                    });
                    const result = await response.json();

                    if (result.success) {
                        Notification.success(result.message);
                        loadAssociatedDocuments();
                    } else {
                        Notification.error(result.message || 'Une erreur s\'est produite.');
                    }
                } catch (error) {
                    console.error('Error removing document:', error);
                    Notification.error('Erreur critique lors de la suppression du document.');
                }
            }
        }
    });
});
