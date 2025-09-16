document.addEventListener('DOMContentLoaded', function () {
    const workflowContainer = document.getElementById('transaction-workflow');
    if (!workflowContainer) return;

    // --- DOM Elements ---
    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const btnNext = document.getElementById('btn-next');
    const btnPrev = document.getElementById('btn-prev');
    const btnReset = document.getElementById('btn-reset');
    const btnSubmit = document.getElementById('btn-submit');

    // Step 1
    const step1Container = document.getElementById('step-1-container');
    const typeDemandeSelect = document.getElementById('type_demande_id');

    // Step 2
    const step2Container = document.getElementById('step-2-container');

    // Step 3
    const summaryContainer = document.getElementById('summary-container');

    // Step 4
    const clientInfoForm = document.getElementById('client-info-form');
    const clientNomInput = document.getElementById('client_nom');
    const clientPrenomInput = document.getElementById('client_prenom');
    const telephoneInput = document.getElementById('telephone');

    // Result
    const resultContainer = document.getElementById('result-container');


    // --- State ---
    let state = {
        currentStep: 1,
        typeDemandeId: null,
        typeDemandeLabel: null,
        isReprise: false,
        pefId: null,
        typePaiementId: null,
        typeDemandeurId: null,
        service: null, // To store the fetched service details
    };

    // --- API ---
    const api = {
        getTypeDemandes: () => fetch(workflowContainer.dataset.typeDemandesUrl).then(res => res.json()),
        getUserPefs: () => fetch('/api/user/pefs').then(res => res.json()),
        getTypePaiements: () => fetch('/api/type-paiements').then(res => res.json()),
        getServiceDetails: (params) => fetch(`/api/service-details?${params}`).then(res => res.json()),
        submitTransaction: (payload) => fetch('/api/transactions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json())
    };


    // --- Functions ---

    function createSelect(id, label, options, container, placeholder = 'Sélectionnez...') {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-3';

        const labelEl = document.createElement('label');
        labelEl.htmlFor = id;
        labelEl.className = 'form-label fw-bold';
        labelEl.textContent = label;

        const selectEl = document.createElement('select');
        selectEl.id = id;
        selectEl.name = id;
        selectEl.className = 'form-select';
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;

        options.forEach(opt => {
            selectEl.appendChild(new Option(opt.label, opt.id));
        });

        col.appendChild(labelEl);
        col.appendChild(selectEl);
        container.appendChild(col);
        return selectEl;
    }

    async function buildStep2() {
        step2Container.innerHTML = ''; // Clear previous fields
        summaryContainer.innerHTML = '<p>Veuillez compléter les étapes précédentes pour voir le résumé.</p>'; // Reset summary
        state.pefId = null;
        state.typePaiementId = null;

        try {
            if (state.isReprise) {
                const pefs = await api.getUserPefs();
                if (pefs.length > 0) {
                    createSelect('pef_id', 'Sélectionnez votre PEF', pefs, step2Container);
                } else {
                     step2Container.innerHTML = '<div class="col-12"><div class="alert alert-warning">Vous n\'avez aucun PEF associé à votre compte.</div></div>';
                }
            }

            const typePaiements = await api.getTypePaiements();
            createSelect('type_paiement_id', 'Nature du paiement', typePaiements, step2Container);

        } catch (error) {
            console.error("Failed to build step 2:", error);
            // Notification.error("Erreur lors de la construction de l'étape suivante.");
        }
    }

    async function buildStep3() {
        if (!state.typeDemandeId || !state.typePaiementId || (state.isReprise && !state.pefId)) {
            summaryContainer.innerHTML = '<p>Veuillez compléter les sélections pour voir le résumé.</p>';
            return;
        }

        summaryContainer.innerHTML = '<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Chargement du résumé...</div>';

        try {
            // TODO: Add type_demandeur_id logic
            const params = new URLSearchParams({
                type_demande_id: state.typeDemandeId,
                type_paiement_id: state.typePaiementId,
            });
            if (state.pefId) {
                params.append('pef_id', state.pefId);
            }

            const service = await api.getServiceDetails(params.toString());
            state.service = service;

            if (service && service.montant_fcfa) {
                let html = `
                    <h5 class="mb-3">Résumé</h5>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Service
                            <span>${state.typeDemandeLabel}</span>
                        </li>
                `;
                if(state.isReprise && state.pefId) {
                     const pefSelect = document.getElementById('pef_id');
                     const pefLabel = pefSelect.options[pefSelect.selectedIndex].text;
                     html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PEF Concerné
                            <span>${pefLabel}</span>
                        </li>`;
                }
                 html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Montant à payer
                            <strong class="text-primary fs-5">${service.montant_fcfa} FCFA</strong>
                        </li>
                    </ul>
                `;
                summaryContainer.innerHTML = html;
            } else {
                summaryContainer.innerHTML = '<div class="alert alert-danger">Aucun service correspondant n\'a été trouvé pour votre sélection.</div>';
            }

        } catch (error) {
            console.error("Failed to fetch service details:", error);
            summaryContainer.innerHTML = '<div class="alert alert-danger">Erreur de communication avec le serveur.</div>';
        }
    }


    function updateUI() {
        // Update stepper
        steps.forEach(step => {
            const stepNum = parseInt(step.dataset.step, 10);
            step.classList.remove('active', 'completed');
            if (stepNum < state.currentStep) {
                step.classList.add('completed');
            } else if (stepNum === state.currentStep) {
                step.classList.add('active');
            }
        });

        // Update step content
        stepContents.forEach(content => {
            const stepNum = parseInt(content.dataset.step, 10);
            content.style.display = stepNum === state.currentStep ? 'block' : 'none';
        });

        // Update buttons
        btnPrev.style.display = state.currentStep > 1 ? 'inline-block' : 'none';
        btnNext.style.display = state.currentStep < 4 ? 'inline-block' : 'none';
        btnSubmit.style.display = state.currentStep === 4 ? 'inline-block' : 'none';

        // Prefill user info in last step
        if (state.currentStep === 4) {
            clientNomInput.value = workflowContainer.dataset.userNom || '';
            clientPrenomInput.value = workflowContainer.dataset.userPrenom || '';
            telephoneInput.value = workflowContainer.dataset.userTelephone || '';
        }
    }

    async function initializeStep1() {
        try {
            const typeDemandes = await api.getTypeDemandes();
            typeDemandeSelect.innerHTML = '<option value=""></option>'; // For placeholder
            typeDemandes.forEach(td => {
                const option = new Option(td.label, td.id);
                // We'll need a way to identify the "Reprise" service
                if (td.is_reprise) { // Assuming a property 'is_reprise'
                    option.dataset.isReprise = "true";
                }
                typeDemandeSelect.appendChild(option);
            });

            // Assuming TomSelect is available
            new TomSelect(typeDemandeSelect, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });

        } catch (error) {
            console.error("Failed to load TypeDemandes:", error);
            // Notification.error("Impossible de charger les services. Veuillez rafraîchir la page.");
        }
    }


    function handleNext() {
        // Validation logic for current step can be added here
        if (state.currentStep === 1 && !state.typeDemandeId) {
            // Notification.error("Veuillez sélectionner un service.");
            alert("Veuillez sélectionner un service.");
            return;
        }

        state.currentStep++;
        updateUI();
    }

    function handlePrev() {
        if (state.currentStep > 1) {
            state.currentStep--;
            updateUI();
        }
    }

    function reset() {
        // Full reset logic here
        state = {
            currentStep: 1,
            typeDemandeId: null,
            typeDemandeLabel: null,
            isReprise: false,
            pefId: null,
            typePaiementId: null,
            typeDemandeurId: null,
            service: null,
        };
        // Reset TomSelect
        if (typeDemandeSelect.tomselect) {
            typeDemandeSelect.tomselect.clear();
        }
        // Clear dynamically added fields
        step2Container.innerHTML = '';
        summaryContainer.innerHTML = '<p>Veuillez compléter les étapes précédentes pour voir le résumé.</p>';
        updateUI();
    }


    // --- Event Listeners ---
    btnNext.addEventListener('click', handleNext);
    btnPrev.addEventListener('click', handlePrev);
    btnReset.addEventListener('click', reset);
    btnSubmit.addEventListener('click', handleSubmit);

    typeDemandeSelect.addEventListener('change', (e) => {
        state.typeDemandeId = e.target.value;
        const selectedOption = e.target.options[e.target.selectedIndex];

        if (e.target.value && selectedOption) {
            state.typeDemandeLabel = selectedOption.text;
            state.isReprise = selectedOption.dataset.isReprise === "true";
            buildStep2();
        } else {
            state.typeDemandeLabel = null;
            state.isReprise = false;
            step2Container.innerHTML = ''; // Clear next step if selection is cleared
        }
        summaryContainer.innerHTML = '<p>Veuillez compléter les étapes précédentes pour voir le résumé.</p>';
    });

    step2Container.addEventListener('change', (e) => {
        if (e.target.id === 'pef_id') {
            state.pefId = e.target.value;
        }
        if (e.target.id === 'type_paiement_id') {
            state.typePaiementId = e.target.value;
        }
        buildStep3();
    });


    async function handleSubmit() {
        if (!clientInfoForm.checkValidity()) {
            clientInfoForm.reportValidity();
            return;
        }

        const payload = {
            service_id: state.service.id,
            pef_id: state.pefId,
            client_nom: clientNomInput.value,
            client_prenom: clientPrenomInput.value,
            telephone: telephoneInput.value,
        };

        btnSubmit.disabled = true;
        btnSubmit.querySelector('.spinner-border').style.display = 'inline-block';

        try {
            const result = await api.submitTransaction(payload);
            if (result.success) {
                workflowContainer.style.display = 'none';
                resultContainer.style.display = 'block';
                resultContainer.innerHTML = `
                    <div class="alert alert-success text-center">
                        <h4 class="alert-heading">Opération Réussie !</h4>
                        <p>${result.message}</p>
                        <p class="mb-2">Utilisez l'identifiant <strong>${result.identifiant_transaction}</strong> pour le paiement.</p>
                        <hr>
                        <div class="mt-3">
                             <a href="/paiement/transaction/${result.transaction_id}/notice" target="_blank" class="btn btn-primary">
                                <i class="mdi mdi-file-pdf-box"></i> Télécharger l'Avis de Recette
                            </a>
                            <a href="${workflowContainer.dataset.suiviUrl}" class="btn btn-info">
                                <i class="mdi mdi-track-fast"></i> Suivre mes paiements
                            </a>
                        </div>
                    </div>
                `;
            } else {
                // Notification.error(result.message || 'Une erreur est survenue.');
                 alert(result.message || 'Une erreur est survenue.');
            }
        } catch (error) {
            console.error('Submission failed', error);
            // Notification.error('Erreur de communication avec le serveur.');
             alert('Erreur de communication avec le serveur.');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.querySelector('.spinner-border').style.display = 'none';
        }
    }


    // --- Initialization ---
    initializeStep1();
    updateUI();
});
