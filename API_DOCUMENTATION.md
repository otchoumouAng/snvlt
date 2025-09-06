# Documentation de l'API

Cette documentation décrit les points d'accès (endpoints) de l'API pour la gestion des paiements et des services associés.



## 4. Endpoints d'Administration & points d'accès

# Master datas
   * TYPES PAIEMENT: http://localhost:8001/admin/reference/types_paiement/index
   * CATEGORIES ACTIVITE: http://localhost:8001/admin/reference/categories_activite/index
   * TYPES SERVICE: http://localhost:8001/admin/reference/types_service/index
   * CATALOGUE SERVICES: http://localhost:8001/paiement/catalogue_services/index
   * CREATION D'AVIS DE RECETTE: http://localhost:8001/paiement/new

### Lister les Services (DataTable)

-   **Méthode**: `GET`
-   **URL**: `/paiement/catalogue_services/data`
-   **Description**: Fournit les données formatées pour le plugin DataTable sur la page d'administration du catalogue.

### Obtenir les Détails d'un Service

-   **Méthode**: `GET`
-   **URL**: `/paiement/catalogue_services/{id}/details`
-   **Description**: Récupère les détails complets d'un service pour peupler le formulaire de modification.



## *********************************************************** ##

    -> Cette partie de la doc est à transmettre à Tresor pay -<
    -Token à leur transmettre pour les requêtes: 
    
        Bearer Token:aeea2310-f193-41bf-bab6-02fb2752f082
        Content-Type:application/json

## *********************************************************** ##

### Api de Confirmation de Paiement

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
        "message": "Transaction traitée avec succès"
    }
    ```
-   **Réponse en cas d'Erreur** (404 Not Found):
    ```json
    {
        "success": false,
        "message": "Transaction non trouvée"
    }
    ```