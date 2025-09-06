# Documentation de l'API

Cette documentation décrit les points d'accès (endpoints) de l'API pour la gestion des paiements et des services associés.

## Table des Matières
1.  [Authentification](#authentification)
2.  [Endpoints de Paiement](#endpoints-de-paiement)
    -   [Initier une Transaction](#initier-une-transaction)
    -   [Webhook de Confirmation de Paiement](#webhook-de-confirmation-de-paiement)
3.  [Endpoints de Données de Référence](#endpoints-de-données-de-référence)
    -   [Lister les Catégories d'Activité](#lister-les-catégories-dactivité)
    -   [Lister les Services par Type de Paiement et Catégorie](#lister-les-services-par-type-de-paiement-et-catégorie)
4.  [Endpoints d'Administration (Catalogue des Services)](#endpoints-dadministration-catalogue-des-services)
    -   [Lister les Services (DataTable)](#lister-les-services-datatable)
    -   [Obtenir les Détails d'un Service](#obtenir-les-détails-dun-service)
    -   [Enregistrer un Service](#enregistrer-un-service)

---

## 1. Authentification

L'accès aux endpoints de l'API peut nécessiter un token d'authentification (par exemple, un token JWT Bearer) dans l'en-tête `Authorization` pour les routes protégées. Les endpoints publics comme le webhook ne nécessitent pas d'authentification.

---

## 2. Endpoints de Paiement

### Initier une Transaction

-   **Méthode**: `POST`
-   **URL**: `/api/paiement/transactions`
-   **Description**: Crée une nouvelle transaction de paiement et génère un avis de recette auprès du service TresorPay.
-   **Corps de la Requête** (JSON):
    ```json
    {
        "service_id": 1,
        "type_paiement_id": 1,
        "client_nom": "N'DIA",
        "client_prenom": "ABDOUL AZIZ",
        "telephone": "2250708674075"
    }
    ```
-   **Paramètres**:
    -   `service_id` (integer, requis): L'ID du service du catalogue.
    -   `type_paiement_id` (integer, requis): L'ID du type de paiement (1 pour "Nouvelle Demande", 2 pour "Renouvellement").
    -   `client_nom` (string, requis): Le nom du client.
    -   `client_prenom` (string, requis): Le(s) prénom(s) du client.
    -   `telephone` (string, optionnel): Le numéro de téléphone du client.

-   **Réponse en cas de Succès** (200 OK):
    ```json
    {
        "success": true,
        "message": "Avis de recette généré avec succès.",
        "identifiant_transaction": "FORET-2025-1757160737477",
        "transaction_id": 26,
        "tresorpay_response": {
            "response_code": 1,
            "response_message": "Traitement effectue avec succes."
        }
    }
    ```

-   **Réponse en cas d'Erreur** (400 Bad Request):
    ```json
    {
        "success": false,
        "message": "Données manquantes: service_id, client_nom, client_prenom et type_paiement_id sont requis"
    }
    ```

### Webhook de Confirmation de Paiement

-   **Méthode**: `POST`
-   **URL**: `/api/webhooks/tresorpay/confirmation`
-   **Description**: Endpoint de callback pour que TresorPay notifie l'application qu'un paiement a été effectué.
-   **Corps de la Requête** (JSON, envoyé par TresorPay):
    ```json
    {
        "numero_avis": "FORET-2025-1757160737477",
        "montant_paiement": 150000,
        "reference": "TRESORPAY_REF_1A2B3CC",
        "date_paiement": "2025-09-05T14:00:00+00:00",
        "payment_phone": "+2250707070707"
    }
    ```
-   **Réponse en cas de Succès** (200 OK):
    ```json
    {
        "success": true,
        "message": "Webhook traité avec succès"
    }
    ```
-   **Réponse en cas d'Erreur** (404 Not Found):
    ```json
    {
        "success": false,
        "message": "Transaction non trouvée"
    }
    ```

---

## 3. Endpoints de Données de Référence

### Lister les Catégories d'Activité

-   **Méthode**: `GET`
-   **URL**: `/api/categories_activite`
-   **Description**: Récupère la liste de toutes les catégories d'activité pour peupler les listes déroulantes.
-   **Réponse en cas de Succès** (200 OK):
    ```json
    [
        {
            "id": 1,
            "label": "Industriel"
        },
        {
            "id": 2,
            "label": "Exportateur"
        }
    ]
    ```

### Lister les Services par Type de Paiement et Catégorie

-   **Méthode**: `GET`
-   **URL**: `/api/services_by_type_and_category`
-   **Description**: Récupère les services filtrés par type de paiement et catégorie d'activité.
-   **Paramètres de Requête**:
    -   `type_paiement_id` (integer, requis): L'ID du type de paiement.
    -   `categorie_id` (integer, requis): L'ID de la catégorie d'activité.
-   **Exemple d'URL**: `/api/services_by_type_and_category?type_paiement_id=1&categorie_id=5`
-   **Réponse en cas de Succès** (200 OK):
    ```json
    [
        {
            "id": 7,
            "label": "Demande de reprise lorem ipsum",
            "montant": "400000"
        }
    ]
    ```

---

## 4. Endpoints d'Administration (Catalogue des Services)

Ces routes sont préfixées par `/paiement/catalogue_services`.

### Lister les Services (DataTable)

-   **Méthode**: `GET`
-   **URL**: `/paiement/catalogue_services/data`
-   **Description**: Fournit les données formatées pour le plugin DataTable sur la page d'administration du catalogue.

### Obtenir les Détails d'un Service

-   **Méthode**: `GET`
-   **URL**: `/paiement/catalogue_services/{id}/details`
-   **Description**: Récupère les détails complets d'un service pour peupler le formulaire de modification.

### Enregistrer un Service

-   **Méthode**: `POST`
-   **URL**: `/paiement/catalogue_services/save`
-   **Description**: Crée ou met à jour un service dans le catalogue.
-   **Corps de la Requête** (JSON):
    ```json
    {
        "id": "7",
        "code_service": "3",
        "montant_fcfa": "400000",
        "designation": "Demande de reprise lorem ipsum",
        "type_service_id": "18",
        "categorie_activite_id": "5",
        "type_demandeur_id": "3",
        "type_paiement_id": "2",
        "note": ""
    }
    ```
