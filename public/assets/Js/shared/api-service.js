class ApiService {
    constructor() {
        this.baseUrl = window.location.origin;
        this.cache = new Map();
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const cacheKey = `${options.method || 'GET'}:${url}`;
        
        // Vérifier le cache pour les requêtes GET
        if ((!options.method || options.method.toUpperCase() === 'GET') && this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        const config = {
            headers: {
                ...options.headers,
            },
            ...options,
        };

        if (config.method) {
            config.method = config.method.toUpperCase();
        } else {
            config.method = 'GET';
        }

        if (config.body && typeof config.body === 'object') {
            const contentTypeHeader = Object.keys(config.headers).find(k => k.toLowerCase() === 'content-type');
            const contentType = contentTypeHeader ? config.headers[contentTypeHeader].toLowerCase() : '';

            if (contentType.includes('application/x-www-form-urlencoded')) {
                const params = new URLSearchParams();
                for (const key in config.body) {
                    params.append(key, config.body[key]);
                }
                config.body = params.toString();
            } else if (contentType.includes('multipart/form-data')) {
                delete config.headers[contentTypeHeader];
            } else {
                config.headers['Content-Type'] = 'application/json';
                config.body = JSON.stringify(config.body);
            }
        }

        try {
            const response = await fetch(url, config);

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('text/html')) {
                const text = await response.text();
                if (!response.ok) throw new Error(text || `Erreur API (${response.status})`);
                
                // Mettre en cache les réponses HTML
                if (config.method === 'GET') {
                    this.cache.set(cacheKey, text);
                }
                
                return text;
            }

            const data = await response.json().catch(() => null);

            if (!response.ok) {
                const msg = data && data.message ? data.message : `Erreur API (${response.status})`;
                throw new Error(msg);
            }
            
            // Mettre en cache les réponses JSON
            if (config.method === 'GET') {
                this.cache.set(cacheKey, data);
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    get(endpoint, options = {}) {
        return this.request(endpoint, { method: 'GET', ...options });
    }

    post(endpoint, data, options = {}) {
        const headers = { ...(options.headers || {}) };

        if (options.json === false) {
            headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        if (options.multipart === true) {
            if (headers['Content-Type']) delete headers['Content-Type'];
        }

        return this.request(endpoint, {
            method: 'POST',
            body: data,
            headers,
            ...options,
        });
    }

    // Méthodes spécifiques pour Nouvelle Demande
    getDemandes() {
        return this.get('/admin/nouvelle_demande/liste');
    }

    getDemandeDetails(id) {
        return this.get(`/admin/nouvelle_demande/details/${id}`);
    }

    getDemandeForm(id, mode) {
        return this.get(`/admin/nouvelle_demande/form?id=${id || ''}&mode=${mode || ''}`);
    }

    saveDemande(data) {
        // Invalider le cache des données après une modification
        this.cache.clear();
        return this.post('/admin/nouvelle_demande/save', data);
    }

    addDocument(demandeId, formData) {
        this.cache.clear();
        return this.post(`/admin/nouvelle_demande/${demandeId}/add_document`, formData, { multipart: true });
    }

    removeDocument(demandeId, documentId) {
        this.cache.clear();
        return this.post(`/admin/nouvelle_demande/${demandeId}/remove_document`, { document_id: documentId });
    }

    getTrackingView(demandeId) {
        // Cette route doit être créée côté backend. Elle retourne du HTML.
        return this.get(`/admin/nouvelle_demande/suivi/${demandeId}`);
    }

    getStepDetails(demandeId, stepId) {
        // Cette route retourne le HTML des détails pour une étape spécifique
        return this.get(`/admin/nouvelle_demande/suivi/${demandeId}/etape/${stepId}`);
    }

    // Méthodes spécifiques pour Validation Demande
    getDemandesForValidation() {
        return this.get('/admin/validation_demande_autorisation/liste');
    }

    getDemandeDetailsForValidation(id) {
        return this.get(`/admin/validation_demande_autorisation/details/${id}`);
    }

    applyValidation(demandeId, data) {
        this.cache.clear();
        // This should be a new endpoint
        return this.post(`/admin/validation_demande_autorisation/${demandeId}/validate`, data);
    }
}

// Export singleton instance
window.apiService = new ApiService();
