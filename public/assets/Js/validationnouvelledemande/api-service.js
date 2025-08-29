class ValidationApiService {
    constructor() {
        this.baseUrl = '/admin/validation/nouvelle-demande';
    }

    async getDemandeDetails(id) {
        const response = await fetch(`${this.baseUrl}/${id}`);
        if (!response.ok) {
            throw new Error('Failed to fetch demande details');
        }
        return response.json();
    }

    async validerEtape(id) {
        const response = await fetch(`${this.baseUrl}/${id}/valider-etape`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // In a real app, you'd need CSRF tokens etc.
            }
        });
        if (!response.ok) {
            throw new Error('Failed to validate step');
        }
        return response.json();
    }
}

window.validationApiService = new ValidationApiService();
