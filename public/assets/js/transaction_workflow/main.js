document.addEventListener('DOMContentLoaded', function() {
    const filtersContainer = document.getElementById('filters-container');
    const clientInfoStep = document.getElementById('client-info-step');
    const serviceSummary = document.getElementById('service-summary');
    const btnSubmit = document.getElementById('btn-submit');
    const btnReset = document.getElementById('btn-reset');

    let selectedServiceId = null;
    let currentFilters = {};

    const fieldLabels = {
        'type_service': 'Type de Service',
        'services': 'Service'
    };

    filtersContainer.addEventListener('change', async (e) => {
        if (e.target.tagName !== 'SELECT') return;

        const select = e.target;
        const fieldName = select.name.replace('_id', '');
        const value = select.value;

        // Mettre à jour les filtres actuels
        currentFilters[fieldName] = value;

        // Supprimer les filtres suivants
        removeNextFilters(select);
        hideFinalStep();

        if (!value) return;

        // Déterminer le prochain champ à charger
        let nextField;
        if (fieldName === 'categorie_activite') {
            nextField = 'type_service';
        } else if (fieldName === 'type_service') {
            nextField = 'services';
        } else {
            return; // Fin du flux
        }

        await fetchAndPopulateNextSelect(nextField);
    });

    async function fetchAndPopulateNextSelect(nextFieldName) {
        const params = new URLSearchParams(currentFilters).toString().replace(/%5B%5D/g, '');

        try {
            const response = await fetch(`/api/services/options?${params}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            if (data.options && data.options.length > 0) {
                createSelect(nextFieldName, data.options);
            } else {
                Notification.warning('Aucune option disponible pour la sélection actuelle.');
            }
        } catch (error) {
            Notification.error('Erreur lors de la récupération des options.');
            console.error(error);
        }
    }

    function createSelect(fieldName, options) {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';
        col.id = `col-${fieldName}`;

        const label = document.createElement('label');
        label.className = 'form-label fw-bold';
        label.htmlFor = `${fieldName}_id`;
        label.textContent = fieldLabels[fieldName];

        const select = document.createElement('select');
        select.id = `${fieldName}_id`;
        select.name = `${fieldName}_id`;
        select.className = 'form-select';

        let placeholder = fieldName === 'services' ? 'Sélectionner un service final...' : 'Sélectionner...';
        select.innerHTML = `<option value="">${placeholder}</option>`;

        options.forEach(opt => {
            let text = opt.label;
            if (fieldName === 'services') {
                text += ` - ${Number(opt.montant_fcfa).toLocaleString('fr-FR')} FCFA`;
            }
            select.innerHTML += `<option value="${opt.id}">${text}</option>`;
        });

        if (fieldName === 'services') {
            select.addEventListener('change', handleFinalServiceSelection);
        }

        col.appendChild(label);
        col.appendChild(select);
        filtersContainer.appendChild(col);
    }

    function handleFinalServiceSelection(e) {
        const select = e.target;
        const selectedOption = select.options[select.selectedIndex];
        if (select.value) {
            selectedServiceId = select.value;
            const serviceName = selectedOption.text;
            showFinalStep(serviceName);
        } else {
            hideFinalStep();
        }
    }

    function removeNextFilters(currentSelect) {
        let nextElement = currentSelect.parentElement.nextElementSibling;
        while (nextElement) {
            let toRemove = nextElement;
            nextElement = nextElement.nextElementSibling;
            toRemove.remove();
        }
    }

    function showFinalStep(serviceName) {
        serviceSummary.innerHTML = `<strong>Service sélectionné :</strong> ${serviceName}`;
        clientInfoStep.style.display = 'block';
        btnSubmit.style.display = 'inline-block';
        btnReset.style.display = 'inline-block';
    }

    function hideFinalStep() {
        clientInfoStep.style.display = 'none';
        btnSubmit.style.display = 'none';
        btnReset.style.display = 'none';
        selectedServiceId = null;
    }

    btnReset.addEventListener('click', () => {
        const firstSelect = filtersContainer.querySelector('select');
        removeNextFilters(firstSelect);
        firstSelect.value = '';
        currentFilters = {};
        hideFinalStep();
    });

    btnSubmit.addEventListener('click', async () => {
        const form = document.getElementById('client-info-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const payload = {
            service_id: selectedServiceId,
            client_nom: document.getElementById('client_nom').value,
            client_prenom: document.getElementById('client_prenom').value,
            telephone: document.getElementById('telephone').value
        };

        const spinner = btnSubmit.querySelector('.spinner-border');
        spinner.style.display = 'inline-block';
        btnSubmit.disabled = true;

        try {
            const response = await fetch('/api/transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok && result.success) {
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
            console.error('Submission error:', error);
        } finally {
            spinner.style.display = 'none';
            btnSubmit.disabled = false;
        }
    });
});
