document.addEventListener('DOMContentLoaded', function() {
    const filtersContainer = document.getElementById('filters-container');
    const clientInfoStep = document.getElementById('client-info-step');
    const confirmationStep = document.getElementById('confirmation-step');
    const serviceSummary = document.getElementById('service-summary');
    const btnConfirm = document.getElementById('btn-confirm');
    const btnSubmit = document.getElementById('btn-submit');
    const btnReset = document.getElementById('btn-reset');

    let selectedValues = {
        categorie_activite_id: null,
        type_demande_id: null,
        service_id: null,
        service_details: null
    };

    const api = {
        getServices: (categoryId, typeDemandeId) => {
            let url = `/api/services_by_category?categorie_id=${categoryId}`;
            if (typeDemandeId) {
                url += `&type_demande_id=${typeDemandeId}`;
            }
            return fetch(url).then(res => res.json());
        },
        getTypesDemande: () => fetch('/api/types_demande_options').then(res => res.json()),
        submitTransaction: (payload) => fetch('/api/transactions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json())
    };

    // --- Event Listeners ---

    filtersContainer.addEventListener('change', (e) => {
        if (e.target.tagName !== 'SELECT') return;

        const select = e.target;
        const fieldName = select.name.replace('_id', '');

        resetSubsequentSteps(fieldName);

        selectedValues[fieldName + '_id'] = select.value;

        if (!select.value) return;

        if (fieldName === 'categorie_activite') {
            loadTypesDemande();
        } else if (fieldName === 'type_demande') {
            loadServices(selectedValues.categorie_activite_id, select.value);
        } else if (fieldName === 'service') {
            const selectedOption = select.options[select.selectedIndex];
            selectedValues.service_details = {
                label: selectedOption.text,
                montant: selectedOption.dataset.montant
            };
            showConfirmationStep();
        }
    });

    btnConfirm.addEventListener('click', () => {
        confirmationStep.style.display = 'none';
        clientInfoStep.style.display = 'block';
        btnSubmit.style.display = 'inline-block';
        prefillUserInfo();
    });

    btnReset.addEventListener('click', resetAll);

    btnSubmit.addEventListener('click', handleSubmit);

    // --- Logic Functions ---

    async function loadTypesDemande() {
        try {
            const types = await api.getTypesDemande();
            if (types.length > 0) {
                createSelect('type_demande', '2. Type de Demande', types);
            } else {
                Notification.warning('Aucun type de demande trouvé. Veuillez en créer un.');
            }
        } catch (e) {
            Notification.error('Erreur de chargement des types de demande.');
        }
    }

    async function loadServices(categoryId, typeDemandeId) {
        try {
            const services = await api.getServices(categoryId, typeDemandeId);
            if (services.length > 0) {
                createSelect('service', '3. Catalogue de Service', services);
            } else {
                Notification.warning('Aucun service disponible pour cette sélection.');
            }
        } catch (e) {
            Notification.error('Erreur de chargement des services.');
        }
    }

    function createSelect(name, labelText, options) {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';
        col.id = `col-${name}`;

        const label = document.createElement('label');
        label.className = 'form-label fw-bold';
        label.htmlFor = `${name}_id`;
        label.textContent = labelText;

        const select = document.createElement('select');
        select.id = `${name}_id`;
        select.name = `${name}_id`;
        select.className = 'form-select';

        select.innerHTML = `<option value="">Sélectionner...</option>`;
        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.id;
            option.textContent = opt.label;
            if (opt.montant) {
                option.dataset.montant = opt.montant;
            }
            select.appendChild(option);
        });

        col.appendChild(label);
        col.appendChild(select);
        filtersContainer.appendChild(col);
    }

    function prefillUserInfo() {
        const workflowContainer = document.getElementById('transaction-workflow');
        const nomInput = document.getElementById('client_nom');
        const prenomInput = document.getElementById('client_prenom');
        const telephoneInput = document.getElementById('telephone');

        nomInput.value = workflowContainer.dataset.userNom || '';
        prenomInput.value = workflowContainer.dataset.userPrenom || '';
        telephoneInput.value = workflowContainer.dataset.userTelephone || '';

        nomInput.readOnly = true;
        prenomInput.readOnly = true;
    }

    function showConfirmationStep() {
        if (!selectedValues.service_details || !selectedValues.type_demande_id) return;

        const typeDemandeSelect = document.getElementById('type_demande_id');
        const typeDemandeText = typeDemandeSelect.options[typeDemandeSelect.selectedIndex].text;

        serviceSummary.innerHTML = `
            <p><strong>Catalogue de Service :</strong> ${selectedValues.service_details.label}</p>
            <p><strong>Type de Demande :</strong> ${typeDemandeText}</p>
        `;
        confirmationStep.style.display = 'block';
    }

    async function handleSubmit() {
        const form = document.getElementById('client-info-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const payload = {
            service_id: selectedValues.service_id,
            type_demande_id: selectedValues.type_demande_id,
            client_nom: document.getElementById('client_nom').value,
            client_prenom: document.getElementById('client_prenom').value,
            telephone: document.getElementById('telephone').value
        };

        const spinner = btnSubmit.querySelector('.spinner-border');
        spinner.style.display = 'inline-block';
        btnSubmit.disabled = true;

        try {
            const result = await api.submitTransaction(payload);

            if (result.success) {
                document.getElementById('transaction-workflow').style.display = 'none';
                const resultContainer = document.getElementById('result-container');
                resultContainer.innerHTML = `
                    <div class="alert alert-success">
                        <h4 class="alert-heading">Opération Réussie !</h4>
                        <p>${result.message}</p>
                        <hr>
                        <p class="mb-0">Veuillez utiliser l'identifiant suivant pour effectuer le paiement : <strong>${result.identifiant_transaction}</strong></p>
                    </div>`;
                resultContainer.style.display = 'block';
            } else {
                Notification.error(result.message || 'Une erreur inconnue est survenue.');
            }
        } catch (error) {
            Notification.error('Erreur de communication avec le serveur.');
        } finally {
            spinner.style.display = 'none';
            btnSubmit.disabled = false;
        }
    }

    function resetSubsequentSteps(fieldName) {
        const order = ['categorie_activite', 'type_demande', 'service'];
        const index = order.indexOf(fieldName);

        for(let i = index + 1; i < order.length; i++) {
            const selectContainer = document.getElementById(`col-${order[i]}`);
            if(selectContainer) selectContainer.remove();
            selectedValues[order[i] + '_id'] = null;
        }

        confirmationStep.style.display = 'none';
        clientInfoStep.style.display = 'none';
        btnSubmit.style.display = 'none';
    }

    function resetAll() {
        const firstSelect = filtersContainer.querySelector('select');
        resetSubsequentSteps(firstSelect.name.replace('_id', ''));
        firstSelect.value = '';
        selectedValues = {
            categorie_activite_id: null,
            type_demande_id: null,
            service_id: null,
            service_details: null
        };
    }

});
