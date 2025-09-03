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

    function initializeDataTable() {
        // We only have one form template now
        preloadedFormHtml = modalBody.innerHTML;

        table = new DataTable('#genericTable', {
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: `/admin/reference/${entityName}/data`,
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
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
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-light btn-edit" data-id="${row.id}"><i class="mdi mdi-pencil"></i></button>`;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
            createdRow: function(row, data) {
                row.id = 'row_' + data.id;
            }
        });

        $('#genericTable tbody').on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            showForm(id, 'edit');
        });
    }

    function showForm(id, mode) {
        modalBody.innerHTML = preloadedFormHtml;
        const form = modalBody.querySelector('#genericForm');

        if (mode === 'edit' && id) {
            modalTitle.textContent = 'Modifier ' + entityTitle;
            const rowData = table.row('#row_' + id).data();
            if (rowData) {
                form.querySelector('#libelle').value = rowData.libelle;
                // Add hidden ID field
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
            const response = await fetch(`/admin/reference/${entityName}/save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

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

    initializeDataTable();

    document.getElementById('btnAdd').addEventListener('click', () => showForm(null, 'new'));
});
