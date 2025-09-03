document.addEventListener('DOMContentLoaded', function() {
    const filtersContainer = document.getElementById('filters-container');
    const clientInfoStep = document.getElementById('client-info-step');
    const serviceSummary = document.getElementById('service-summary');
    const btnSubmit = document.getElementById('btn-submit');
    const btnReset = document.getElementById('btn-reset');

    let selectedServiceId = null;

    const fieldLabels = {
        'type_service': 'Type de Service',
        'categorie_activite': 'Catégorie d\'Activité',
        'type_demandeur': 'Type de Demandeur',
        'type_demande': 'Type de Demande',
        'regime_fiscal': 'Régime Fiscal',
        'services': 'Service Final'
    };

    filtersContainer.addEventListener('change', async (e) => {
        if (e.target.tagName === 'SELECT') {
            const select = e.target;
            const value = select.value;

            // Remove subsequent filters
            let nextElement = select.parentElement.nextElementSibling;
            while(nextElement) {
                const toRemove = nextElement;
                nextElement = nextElement.nextElementSibling;
                toRemove.remove();
            }

            hideFinalStep();

            if (!value) {
                return;
            }

            const params = new URLSearchParams();
            document.querySelectorAll('#filters-container select').forEach(s => {
                if (s.value) {
                    params.append(s.name, s.value);
                }
            });

            try {
                const response = await fetch(`/api/services/options?${params.toString()}`);
                const data = await response.json();

                if (data.options && data.options.length > 0) {
                    if (data.type === 'services') {
                        displayFinalServices(data.options);
                    } else {
                        createNextSelect(data.type, data.options);
                    }
                } else {
                     serviceSummary.innerHTML = '<div class="alert alert-warning">Aucun service ne correspond aux critères sélectionnés.</div>';
                     serviceSummary.parentElement.style.display = 'block';
                }

            } catch (error) {
                Notification.error('Erreur lors de la récupération des options.');
                console.error(error);
            }
        }
    });

    function createNextSelect(fieldName, options) {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';

        const label = document.createElement('label');
        label.className = 'form-label fw-bold';
        label.setAttribute('for', fieldName + '_id');
        label.textContent = fieldLabels[fieldName] || fieldName;

        const select = document.createElement('select');
        select.id = fieldName + '_id';
        select.name = fieldName + '_id';
        select.className = 'form-select';
        select.dataset.next = getNextFieldName(fieldName);

        select.innerHTML = '<option value="">Sélectionner...</option>';
        options.forEach(opt => {
            select.innerHTML += `<option value="${opt.id}">${opt.label}</option>`;
        });

        col.appendChild(label);
        col.appendChild(select);
        filtersContainer.appendChild(col);
    }

    function displayFinalServices(services) {
        const fieldName = 'service';
        const col = document.createElement('div');
        col.className = 'col-md-12 mb-3';

        const label = document.createElement('label');
        label.className = 'form-label fw-bold';
        label.setAttribute('for', fieldName + '_id');
        label.textContent = 'Choisissez le service final';

        const select = document.createElement('select');
        select.id = fieldName + '_id';
        select.name = fieldName + '_id';
        select.className = 'form-select';

        select.innerHTML = '<option value="">Sélectionner un service...</option>';
        services.forEach(service => {
            select.innerHTML += `<option value="${service.id}" data-montant="${service.montant_fcfa}">${service.label} - ${Number(service.montant_fcfa).toLocaleString('fr-FR')} FCFA</option>`;
        });

        select.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            if(e.target.value) {
                selectedServiceId = e.target.value;
                const serviceName = selectedOption.text;
                showFinalStep(serviceName);
            } else {
                hideFinalStep();
            }
        });

        col.appendChild(label);
        col.appendChild(select);
        filtersContainer.appendChild(col);
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

    function getNextFieldName(currentField) {
        const order = ['type_service', 'categorie_activite', 'type_demandeur', 'type_demande', 'regime_fiscal'];
        const currentIndex = order.indexOf(currentField);
        return order[currentIndex + 1] || 'services';
    }

    btnReset.addEventListener('click', () => {
        // Remove all but the first filter
        while (filtersContainer.children.length > 1) {
            filtersContainer.removeChild(filtersContainer.lastChild);
        }
        filtersContainer.querySelector('select').value = '';
        hideFinalStep();
    });

    btnSubmit.addEventListener('click', async () => {
        const form = document.getElementById('client-info-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const clientNom = document.getElementById('client_nom').value;
        const clientPrenom = document.getElementById('client_prenom').value;
        const telephone = document.getElementById('telephone').value;

        if (!selectedServiceId) {
            Notification.error('Aucun service final n\'a été sélectionné.');
            return;
        }

        const payload = {
            service_id: selectedServiceId,
            client_nom: clientNom,
            client_prenom: clientPrenom,
            telephone: telephone
        };

        // Show spinner and disable button
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

            if (result.success) {
                document.getElementById('transaction-workflow').style.display = 'none';
                const resultContainer = document.getElementById('result-container');
                resultContainer.innerHTML = `
                    <div class="alert alert-success">
                        <h4 class="alert-heading">Opération Réussie !</h4>
                        <p>${result.message}</p>
                        <hr>
                        <p class="mb-0">Veuillez utiliser l'identifiant suivant pour effectuer le paiement : <strong>${result.identifiant_transaction}</strong></p>
                    </div>
                `;
                resultContainer.style.display = 'block';
            } else {
                Notification.error(result.message || 'Une erreur inconnue est survenue.');
            }

        } catch (error) {
            Notification.error('Erreur de communication avec le serveur.');
            console.error('Submission error:', error);
        } finally {
            // Hide spinner and re-enable button
            spinner.style.display = 'none';
            btnSubmit.disabled = false;
        }
    });
});
