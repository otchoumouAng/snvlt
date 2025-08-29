class ValidationNouvelleDemandeApp {
    constructor() {
        this.apiService = window.validationApiService;
        this.notification = window.notificationSystem;
        this.selectedDemandeId = null;

        this.demandeSelector = document.getElementById('demande-selector');
        this.validationDetails = document.getElementById('validation-details');
        this.demandeTitre = document.getElementById('demande-titre');
        this.stepperContainer = document.getElementById('stepper-container');
        this.validerBtn = document.getElementById('valider-etape-btn');
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        this.demandeSelector.addEventListener('change', (e) => {
            this.selectedDemandeId = e.target.value;
            if (this.selectedDemandeId) {
                this.loadValidationDetails(this.selectedDemandeId);
            }
        });

        this.validerBtn.addEventListener('click', () => {
            if (this.selectedDemandeId) {
                this.validerEtapeCourante(this.selectedDemandeId);
            }
        });
    }

    async loadValidationDetails(id) {
        try {
            this.notification.info('Chargement des détails de validation...');
            const data = await this.apiService.getDemandeDetails(id);

            this.demandeTitre.textContent = data.titre;
            this.renderStepper(data.etapes);

            this.validationDetails.classList.remove('d-none');
        } catch (error) {
            this.notification.error(error.message);
            this.validationDetails.classList.add('d-none');
        }
    }

    renderStepper(etapes) {
        let stepperHtml = '<ol class="stepper">';
        etapes.forEach(etape => {
            let stepClass = '';
            if (etape.statut === 'validé') {
                stepClass = 'validated';
            } else if (etape.statut === 'en_cours') {
                stepClass = 'in-progress';
            }

            stepperHtml += `
                <li class="step ${stepClass}">
                    <div class="step-icon">
                        <i class="ph-fill ${etape.statut === 'validé' ? 'ph-check' : 'ph-number-circle-' + (etapes.indexOf(etape) + 1)}"></i>
                    </div>
                    <div class="step-label">${etape.nom}</div>
                </li>
            `;
        });
        stepperHtml += '</ol>';
        this.stepperContainer.innerHTML = stepperHtml;
    }

    async validerEtapeCourante(id) {
        try {
            this.notification.info("Validation de l'étape en cours...");
            const result = await this.apiService.validerEtape(id);
            if (result.success) {
                this.notification.success(result.message);
                // Refresh details
                this.loadValidationDetails(id);
            } else {
                this.notification.error("La validation a échoué.");
            }
        } catch (error) {
            this.notification.error(error.message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const app = new ValidationNouvelleDemandeApp();
    app.init();
});
