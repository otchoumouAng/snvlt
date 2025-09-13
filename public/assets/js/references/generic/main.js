document.addEventListener('DOMContentLoaded', function() {
    // These are set in the Twig template
    if (typeof entityName === 'undefined' || typeof entityTitle === 'undefined') {
        console.error('entityName or entityTitle is not defined. Make sure to set them in your Twig template.');
        return;
    }

    let table;
    let preloadedFormHtml;
    const modalElement = document.getElementById('formModal');
    const modal = new bootstrap.Modal(modalElement);
    const modalBody = document.getElementById('formModalBody');
    const modalTitle = document.getElementById('formModalTitle');

    // --- New elements for Documents Modal ---
    const documentsModalElement = document.getElementById('documentsModal');
    let documentsModal;
    if (documentsModalElement) {
        documentsModal = new bootstrap.Modal(documentsModalElement);
    }
    const addDocumentBtn = document.getElementById('addDocumentBtn');
    const associatedDocsTableBody = document.getElementById('associatedDocumentsTableBody');
    let currentTypeDemandeId = null;

    // --- API object ---
    const api = {
        saveEntity: (data) => fetch(`/admin/reference/${entityName}/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(res => res.json()),

        getAllTypeDocuments: () => fetch('/api/type-documents').then(res => res.json()),

        getAssociatedDocuments: (typeDemandeId) => fetch(`/api/type-demande/${typeDemandeId}/documents`).then(res => res.json()),

        addDocumentToDemande: (typeDemandeId, typeDocumentId) => fetch(`/api/type-demande/${typeDemandeId}/documents`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type_document_id: typeDocumentId })
        }).then(res => res.json()),

        removeDocumentFromDemande: (typeDemandeDetailId) => fetch(`/api/type-demande-detail/${typeDemandeDetailId}`, {
            method: 'DELETE'
        })
    };


    function initializeDataTable() {
        preloadedFormHtml = modalBody.innerHTML;

        table = new DataTable('#genericTable', {
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: `/admin/reference/${entityName}/data`,
                dataSrc: 'data',
                error: (xhr, error, thrown) => {
                    console.error('DataTables AJAX error:', error, thrown);
                    Notification.error('Erreur de chargement des données.');
                }
            },
            columns: [
                { data: 'libelle', title: 'Libellé' },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    searchable: false,
                    width: '100px',
                    render: (data, type, row) => `<button class="btn btn-sm btn-light btn-edit" data-id="${row.id}"><i class="mdi mdi-pencil"></i></button>`
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
            createdRow: (row, data) => {
                row.id = 'row_' + data.id;
            }
        });

        $('#genericTable tbody').on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            showForm(id, 'edit');
        });

        // --- Double-click listener for documents modal ---
        if (entityName === 'categories_activite') {
            $('#genericTable tbody').on('dblclick', 'tr', function() {
                const rowData = table.row(this).data();
                if (rowData) {
                    openDocumentsModal(rowData.id, rowData.libelle);
                }
            });
        }
    }

    function showForm(id, mode) {
        modalBody.innerHTML = preloadedFormHtml;
        const form = modalBody.querySelector('#genericForm');

        if (mode === 'edit' && id) {
            modalTitle.textContent = 'Modifier ' + entityTitle;
            const rowData = table.row('#row_' + id).data();
            if (rowData) {
                form.querySelector('#libelle').value = rowData.libelle;
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'id';
                hiddenInput.value = id;
                form.prepend(hiddenInput);
                form.querySelector('button[type="submit"]').textContent = 'Modifier';
            }
        } else {
            modalTitle.textContent = 'Nouveau ' + entityTitle;
            form.querySelector('button[type="submit"]').textContent = 'Créer';
        }

        form.addEventListener('submit', handleFormSubmit);
        modal.show();
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const data = Object.fromEntries(new FormData(form).entries());

        try {
            const result = await api.saveEntity(data);
            if (result.success) {
                Notification.success(result.message);
                modal.hide();
                table.ajax.reload(null, false);
            } else {
                Notification.error(result.message || "Une erreur s'est produite.");
            }
        } catch (error) {
            Notification.error("Erreur critique lors de l'enregistrement.");
            console.error("Save Error:", error);
        }
    }

    // --- Functions for Documents Modal ---
    async function openDocumentsModal(typeDemandeId, typeDemandeName) {
        currentTypeDemandeId = typeDemandeId;
        document.getElementById('typeDemandeName').textContent = typeDemandeName;

        // Clear previous content
        document.getElementById('typeDocumentSelect').innerHTML = '<option selected>Chargement...</option>';
        associatedDocsTableBody.innerHTML = '<tr><td colspan="2">Chargement...</td></tr>';

        documentsModal.show();

        try {
            const [allDocs, associatedData] = await Promise.all([
                api.getAllTypeDocuments(),
                api.getAssociatedDocuments(typeDemandeId)
            ]);

            populateDocumentSelect(allDocs);
            renderAssociatedDocuments(associatedData.typeDemandeDetails);

        } catch (error) {
            Notification.error("Erreur lors du chargement des données des documents.");
            console.error(error);
            associatedDocsTableBody.innerHTML = '<tr><td colspan="2" class="text-danger">Erreur de chargement.</td></tr>';
        }
    }

    function populateDocumentSelect(documents) {
        const select = document.getElementById('typeDocumentSelect');
        select.innerHTML = '<option value="" selected>Choisir un document...</option>';
        documents.forEach(doc => {
            const option = new Option(doc.designation, doc.id);
            select.add(option);
        });
    }

    function renderAssociatedDocuments(details) {
        associatedDocsTableBody.innerHTML = '';
        if (details.length === 0) {
            associatedDocsTableBody.innerHTML = '<tr><td colspan="2">Aucun document requis pour ce type de demande.</td></tr>';
            return;
        }
        details.forEach(detail => {
            const row = associatedDocsTableBody.insertRow();
            row.innerHTML = `
                <td>${detail.typeDocument.designation}</td>
                <td>
                    <button class="btn btn-sm btn-danger btn-delete-doc" data-detail-id="${detail.id}">
                        <i class="mdi mdi-trash-can"></i>
                    </button>
                </td>
            `;
        });
    }

    async function handleAddDocument() {
        const typeDocumentId = document.getElementById('typeDocumentSelect').value;
        if (!typeDocumentId || !currentTypeDemandeId) {
            Notification.warning("Veuillez sélectionner un document.");
            return;
        }

        try {
            const result = await api.addDocumentToDemande(currentTypeDemandeId, typeDocumentId);
            if (result.error) {
                 Notification.error(result.error);
            } else {
                Notification.success("Document ajouté avec succès.");
                // Refresh the table
                const updatedData = await api.getAssociatedDocuments(currentTypeDemandeId);
                renderAssociatedDocuments(updatedData.typeDemandeDetails);
            }
        } catch (error) {
            Notification.error("Erreur lors de l'ajout du document.");
            console.error(error);
        }
    }

    async function handleDeleteDocument(event) {
        const button = event.target.closest('.btn-delete-doc');
        if (!button) return;

        const detailId = button.dataset.detailId;
        if (!confirm("Êtes-vous sûr de vouloir retirer ce document ?")) {
            return;
        }

        try {
            const response = await api.removeDocumentFromDemande(detailId);
            if (response.ok) {
                Notification.success("Document retiré avec succès.");
                button.closest('tr').remove();
            } else {
                 Notification.error("Erreur lors de la suppression.");
            }
        } catch (error) {
             Notification.error("Erreur critique lors de la suppression.");
             console.error(error);
        }
    }

    // --- Initializers ---
    initializeDataTable();
    document.getElementById('btnAdd').addEventListener('click', () => showForm(null, 'new'));

    if (documentsModalElement) {
        addDocumentBtn.addEventListener('click', handleAddDocument);
        associatedDocsTableBody.addEventListener('click', handleDeleteDocument);
    }
});
