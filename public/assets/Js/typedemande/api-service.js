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

    // Méthodes spécifiques pour TypeDemande
    getTypeDemandeForm(id, mode) {
        return this.get(`/admin/type_demandes/form?id=${id || ''}&mode=${mode || ''}`);
    }

    saveTypeDemande(data) {
        // Invalider le cache des données après une modification
        this.cache.clear();
        return this.post('/admin/type_demandes/save', data);
    }


    // Méthodes pour les documents
    async getAllDocuments() {
        try {
            const response = await this.get('/admin/type_documents/data');
            return response.data || [];
        } catch (error) {
            console.error('Erreur lors de la récupération des documents:', error);
            throw error;
        }
    }
    
    async getTypeDemandeDetails(id) {
        return this.get(`/admin/type_demandes/${id}/details`);
    }
    
    async getTypeDemandeDocuments(id) {
        const details = await this.getTypeDemandeDetails(id);
        return details.documents || [];
    }
    
    async saveTypeDemandeDocuments(typeDemandeId, documentIds) {
        return this.post(`/admin/type_demandes/${typeDemandeId}/documents`, { documents: documentIds });
    }
}

// Export singleton instance
window.apiService = new ApiService();