document.addEventListener('DOMContentLoaded', function () {
    const workflowContainer = document.getElementById('transaction-workflow');
    if (!workflowContainer) return;

    // --- SÉLECTEURS DOM ---
    const dom = {
        stepper: document.querySelector('.stepper'),
        steps: document.querySelectorAll('.step'),
        progressBar: document.querySelector('.stepper .progress-bar'),
        stepContents: document.querySelectorAll('.step-content'),
        buttons: {
            next: document.getElementById('btn-next'),
            prev: document.getElementById('btn-prev'),
            reset: document.getElementById('btn-reset'),
            submit: document.getElementById('btn-submit'),
        },
        // Étape 1 : Conteneurs et champs
        typeDemandeSelect: document.getElementById('type_demande_id'),
        pefContainer: document.getElementById('pef-select-container'),
        typePaiementContainer: document.getElementById('type-paiement-select-container'),
        summaryCard: document.getElementById('summary-card'),
        summaryContent: document.getElementById('summary-content'),
        // Étape 2 : Formulaire de confirmation
        clientInfoForm: document.getElementById('client-info-form'),
        clientNomInput: document.getElementById('client_nom'),
        clientPrenomInput: document.getElementById('client_prenom'),
        telephoneInput: document.getElementById('telephone'),
        // Résultat final
        resultContainer: document.getElementById('result-container'),
    };

    // --- ÉTAT DE L'APPLICATION ---
    let state = {
        currentStep: 1,
        typeDemandeId: null,
        typeDemandeLabel: null,
        isReprisePef: false,
        pefId: null,
        pefLabel: null,
        typePaiementId: null,
        service: null, // Stocke les détails du service récupérés via API
    };

    // --- API & DATA FETCHING ---
    const api = {
        fetchData: (url) => fetch(url).then(res => {
            if (!res.ok) throw new Error(`Erreur réseau: ${res.statusText}`);
            return res.json();
        }),
        getTypeDemandes: () => api.fetchData(workflowContainer.dataset.typeDemandesUrl),
        getTypePaiements: () => api.fetchData(workflowContainer.dataset.typePaiementsUrl),
        getUserPefs: () => api.fetchData(workflowContainer.dataset.userPefsUrl),
        getServiceDetails: (params) => api.fetchData(`${workflowContainer.dataset.serviceDetailsUrl}?${params}`),
        submitTransaction: (payload) => fetch(workflowContainer.dataset.submitUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json()),
    };

    // --- FONCTIONS DE RENDU (UI) ---

    /** Met à jour l'affichage global (stepper, contenu, boutons) */
    function updateUI() {
        // Mettre à jour la barre de progression
        const progress = ((state.currentStep - 1) / (dom.steps.length - 1)) * 100;
        dom.progressBar.style.width = `${progress}%`;

        // Mettre à jour les icônes du stepper
        dom.steps.forEach(step => {
            const stepNum = parseInt(step.dataset.step, 10);
            step.classList.remove('active', 'completed');
            if (stepNum < state.currentStep) step.classList.add('completed');
            else if (stepNum === state.currentStep) step.classList.add('active');
        });

        // Afficher le contenu de l'étape active
        dom.stepContents.forEach(content => {
            content.style.display = parseInt(content.dataset.step) === state.currentStep ? 'block' : 'none';
        });

        // Gérer la visibilité des boutons
        dom.buttons.prev.style.display = state.currentStep > 1 ? 'inline-block' : 'none';
        dom.buttons.next.style.display = state.currentStep === 1 ? 'inline-block' : 'none';
        dom.buttons.submit.style.display = state.currentStep === 2 ? 'inline-block' : 'none';
        
        // Pré-remplir le formulaire de confirmation
        if (state.currentStep === 2) {
            dom.clientNomInput.value = workflowContainer.dataset.userNom || '';
            dom.clientPrenomInput.value = workflowContainer.dataset.userPrenom || '';
            dom.telephoneInput.value = workflowContainer.dataset.userTelephone || '';
        }
    }

    /** Crée et injecte un champ <select> avec TomSelect dans un conteneur */
    function renderSelect(id, label, options, container, placeholder = 'Sélectionnez une option...') {
        container.innerHTML = `
            <label for="${id}" class="form-label fw-bold">${label}</label>
            <select id="${id}" name="${id}"></select>
        `;
        container.style.display = 'block';
        const selectEl = document.getElementById(id);

        new TomSelect(selectEl, {
            options: options,
            valueField: 'id',
            labelField: 'libelle',
            searchField: ['libelle'],
            placeholder: placeholder,
            create: false,
            sortField: { field: "libelle", direction: "asc" }
        });

        return selectEl; // On retourne l'élément <select> original pour la compatibilité
    }
    
    /** Affiche le résumé du service ou une erreur */
    function renderSummary() {
        dom.summaryCard.style.display = 'block';
        if (state.service && state.service.montant_fcfa !== undefined) {
            let html = `
                <h5 class="mb-3">Résumé de votre demande</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        Service demandé: <span class="fw-bold">${state.typeDemandeLabel}</span>
                    </li>
            `;
            if (state.isReprisePef && state.pefLabel) {
                 html += `<li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                    PEF concerné: <span class="fw-bold">${state.pefLabel}</span>
                </li>`;
            }
            html += `
                </ul>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="h6 mb-0">Montant total à payer:</span>
                    <span class="summary-amount">${new Intl.NumberFormat('fr-FR').format(state.service.montant_fcfa)} FCFA</span>
                </div>
            `;
            dom.summaryContent.innerHTML = html;
            dom.buttons.next.disabled = false;
        } else {
            dom.summaryContent.innerHTML = `<div class="alert alert-warning mb-0">Aucun service correspondant n'a été trouvé pour votre sélection.</div>`;
            dom.buttons.next.disabled = true;
        }
    }

    // --- LOGIQUE MÉTIER ---

    /** Vérifie si les conditions sont remplies pour chercher le détail du service */
    function checkAndFetchServiceDetails() {
        const isReady = state.typeDemandeId && state.typePaiementId && (!state.isReprisePef || (state.isReprisePef && state.pefId));
        
        if (isReady) {
            dom.summaryCard.style.display = 'block';
            dom.summaryContent.innerHTML = '<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2"></span> Recherche du service...</div>';

            const params = new URLSearchParams({
                type_demande_id: state.typeDemandeId,
                type_paiement_id: state.typePaiementId,
            });
            if (state.pefId) {
                params.append('pef_id', state.pefId);
            }

            api.getServiceDetails(params.toString())
                .then(service => {
                    state.service = service;
                    renderSummary();
                })
                .catch(error => {
                    console.error("Erreur lors de la récupération des détails du service:", error);
                    dom.summaryContent.innerHTML = '<div class="alert alert-danger mb-0">Erreur de communication avec le serveur.</div>';
                    dom.buttons.next.disabled = true;
                });
        } else {
             dom.summaryCard.style.display = 'none';
             dom.buttons.next.disabled = true;
        }
    }

    /** Gère la sélection du Type de Demande */
    async function handleTypeDemandeChange(e) {
        const selectedId = e.target.value;
        const selectedOption = e.target.options[e.target.selectedIndex];

        // Réinitialisation partielle
        state.typeDemandeId = selectedId;
        state.isReprisePef = false;
        state.pefId = null;
        state.pefLabel = null;
        state.typePaiementId = null;
        state.service = null;
        dom.pefContainer.style.display = 'none';
        dom.typePaiementContainer.style.display = 'none';
        dom.pefContainer.innerHTML = '';
        dom.typePaiementContainer.innerHTML = '';
        checkAndFetchServiceDetails();

        if (!selectedId) return;

        state.typeDemandeLabel = selectedOption.text;
        // La vérification se fait sur le libellé, à adapter si un ID ou flag est disponible
        if (state.typeDemandeLabel.toLowerCase().includes("reprise d'activité pef")) {
            state.isReprisePef = true;
            dom.pefContainer.innerHTML = '<span class="text-muted">Chargement des PEF...</span>';
            dom.pefContainer.style.display = 'block';
            try {
                const pefs = await api.getUserPefs();
                if (pefs.length > 0) {
                    const pefSelect = renderSelect('pef_id', 'Sélectionnez votre PEF', pefs, dom.pefContainer);
                    pefSelect.addEventListener('change', handlePefChange);
                } else {
                    dom.pefContainer.innerHTML = '<div class="alert alert-warning">Aucun PEF n\'est associé à votre compte.</div>';
                }
            } catch (error) {
                console.error("Erreur chargement PEF:", error);
                dom.pefContainer.innerHTML = '<div class="alert alert-danger">Impossible de charger vos PEF.</div>';
            }
        }
        
        // Charger la nature du paiement dans tous les cas
        try {
            const paiements = await api.getTypePaiements();
            const dataArray = Array.isArray(paiements) ? paiements : paiements.data;
            const options = dataArray ? dataArray.map(p => ({id: p.id, libelle: p.libelle})) : [];

            const paiementSelect = renderSelect('type_paiement_id', 'Nature du paiement', options, dom.typePaiementContainer);
            paiementSelect.addEventListener('change', handleTypePaiementChange);
        } catch(error) {
            console.error("Erreur chargement TypePaiement:", error);
            dom.typePaiementContainer.innerHTML = '<div class="alert alert-danger">Impossible de charger les natures de paiement.</div>';
        }
    }

    /** Gère la sélection du PEF */
    function handlePefChange(e) {
        state.pefId = e.target.value;
        state.pefLabel = e.target.value ? e.target.options[e.target.selectedIndex].text : null;
        checkAndFetchServiceDetails();
    }
    
    /** Gère la sélection de la Nature du Paiement */
    function handleTypePaiementChange(e) {
        state.typePaiementId = e.target.value;
        checkAndFetchServiceDetails();
    }

    /** Soumet la transaction finale */
    async function handleSubmit() {
        if (!dom.clientInfoForm.checkValidity()) {
            dom.clientInfoForm.reportValidity();
            return;
        }

        const payload = {
            service_id: state.service.id, // Assurez-vous que l'API service renvoie un 'id'
            pef_id: state.pefId,
            client_nom: dom.clientNomInput.value,
            client_prenom: dom.clientPrenomInput.value,
            telephone: dom.telephoneInput.value,
        };

        dom.buttons.submit.disabled = true;
        dom.buttons.submit.querySelector('.spinner-border').style.display = 'inline-block';

        try {
            const result = await api.submitTransaction(payload);
            if (result.success) {
                workflowContainer.style.display = 'none';
                dom.resultContainer.style.display = 'block';
                dom.resultContainer.innerHTML = `
                    <div class="alert alert-success text-center">
                        <h4 class="alert-heading">Opération Réussie !</h4>
                        <p>${result.message || 'Votre demande a été enregistrée.'}</p>
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
                    </div>`;
            } else {
                Notification.error(result.message || 'Une erreur est survenue lors de la soumission.');
            }
        } catch (error) {
            console.error('Submission failed', error);
            Notification.error('Erreur de communication avec le serveur.');
        } finally {
            dom.buttons.submit.disabled = false;
            dom.buttons.submit.querySelector('.spinner-border').style.display = 'none';
        }
    }


    // --- GESTIONNAIRES D'ÉVÉNEMENTS (Navigation) ---
    function handleNext() {
        if (state.currentStep === 1 && state.service) {
            state.currentStep++;
            updateUI();
        } else {
            alert("Veuillez finaliser votre sélection pour continuer.");
        }
    }

    function handlePrev() {
        if (state.currentStep > 1) {
            state.currentStep--;
            updateUI();
        }
    }
    
    function resetWorkflow() {
        state = { currentStep: 1, typeDemandeId: null, typeDemandeLabel: null, isReprisePef: false, pefId: null, pefLabel: null, typePaiementId: null, service: null };
        
        if (dom.typeDemandeSelect.tomselect) {
            dom.typeDemandeSelect.tomselect.clear();
        } else {
            dom.typeDemandeSelect.value = '';
        }
        
        dom.pefContainer.innerHTML = '';
        dom.typePaiementContainer.innerHTML = '';
        dom.pefContainer.style.display = 'none';
        dom.typePaiementContainer.style.display = 'none';
        
        dom.summaryCard.style.display = 'none';
        dom.summaryContent.innerHTML = '';
        
        dom.buttons.next.disabled = true;
        updateUI();
    }

    // --- INITIALISATION ---
    async function initialize() {
        try {
            const typeDemandes = await api.getTypeDemandes();
            // CORRECTION: Gère les deux formats de réponse API: un tableau direct ou un objet { data: [...] }
            const dataArray = Array.isArray(typeDemandes) ? typeDemandes : typeDemandes.data;
            const options = dataArray ? dataArray.map(td => ({id: td.id, libelle: td.libelle})) : [];

            if (!options.length) {
                // Si aucune option n'est trouvée, il y a un problème avec les données.
                throw new Error("La liste des services est vide ou dans un format incorrect.");
            }

            dom.typeDemandeSelect.innerHTML = ''; // Vide le select avant d'utiliser TomSelect
            new TomSelect(dom.typeDemandeSelect, {
                options: options,
                valueField: 'id',
                labelField: 'libelle',
                searchField: 'libelle',
                create: false,
                placeholder: 'Sélectionnez un service...',
                sortField: { field: "libelle", direction: "asc" }
            });

            dom.typeDemandeSelect.addEventListener('change', handleTypeDemandeChange);
            dom.buttons.next.addEventListener('click', handleNext);
            dom.buttons.prev.addEventListener('click', handlePrev);
            dom.buttons.reset.addEventListener('click', resetWorkflow);
            dom.buttons.submit.addEventListener('click', handleSubmit);

            updateUI();
        } catch (error) {
            console.error("Impossible d'initialiser le module de paiement:", error);
            workflowContainer.innerHTML = `<div class="alert alert-danger">Le module de paiement n'a pas pu être chargé. Cause: ${error.message}. Veuillez rafraîchir la page.</div>`;
        }
    }

    initialize();
});

