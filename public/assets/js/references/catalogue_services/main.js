document.addEventListener('DOMContentLoaded', function() {
    let table;
    let preloadedForms = {};
    const modalElement = document.getElementById('formModal');
    const modal = new bootstrap.Modal(modalElement);
    const modalBody = document.getElementById('formModalBody');
    const modalTitle = document.getElementById('formModalTitle');

    // API Service specific to this CRUD
    const apiService = {
        getCatalogueData: () => fetch('/paiement/catalogue_services/data').then(res => res.json()),
        getCatalogueServiceDetails: (id) => fetch(`/paiement/catalogue_services/${id}/details`).then(res => res.json()),
        saveCatalogueService: (data) => fetch('/paiement/catalogue_services/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(res => res.json())
    };

    function initializeDataTable() {
        preloadedForms = {
            new: document.getElementById('template-form-new').innerHTML,
            edit: document.getElementById('template-form-edit').innerHTML,
        };

        table = new DataTable('#catalogueServicesTable', {
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: '/admin/catalogue_services/data',
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error, thrown);
                    Notification.error('Erreur de chargement des données.');
                }
            },
            columns: [
                { data: 'code_service', title: 'Code' },
                { data: 'designation', title: 'Désignation' },
                { data: 'montant_fcfa', title: 'Montant (FCFA)' },
                { data: 'type_service', title: 'Type de Service' },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-light btn-edit" data-id="${row.id}"><i class="mdi mdi-pencil"></i></button>`;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' }
        });

        $('#catalogueServicesTable tbody').on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            showForm(id, 'edit');
        });
    }

    async function showForm(id, mode) {
        modalTitle.textContent = mode === 'new' ? "Nouveau Service" : "Modifier le Service";

        // Use the 'new' form template for both modes, as we will populate it dynamically
        modalBody.innerHTML = preloadedForms.new;
        const form = modalBody.querySelector('#catalogueServiceForm');

        if (id && mode === 'edit') {
            try {
                const details = await apiService.getCatalogueServiceDetails(id);
                if (details.error) throw new Error(details.error);

                form.querySelector('#id').value = details.id;
                form.querySelector('#code_service').value = details.code_service;
                form.querySelector('#designation').value = details.designation;
                form.querySelector('#montant_fcfa').value = details.montant_fcfa;
                form.querySelector('#note').value = details.note;
                form.querySelector('#type_service_id').value = details.type_service_id;
                form.querySelector('#categorie_activite_id').value = details.categorie_activite_id;
                form.querySelector('#type_demandeur_id').value = details.type_demandeur_id;
                form.querySelector('#type_paiement_id').value = details.type_paiement_id;

                form.querySelector('button[type="submit"]').textContent = 'Modifier';

            } catch (error) {
                Notification.error("Erreur lors du chargement des détails du service.");
                console.error(error);
                return;
            }
        }

        form.addEventListener('submit', handleFormSubmit);
        modal.show();
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const data = Object.fromEntries(new FormData(form).entries());

        try {
            const result = await apiService.saveCatalogueService(data);
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
