### Api de Confirmation de Paiement

## *********************************************************** ##
        Bearer Token:aeea2310-f193-41bf-bab6-02fb2752f082
        Content-Type:application/json
## *********************************************************** ##



-   **Méthode**: `POST`
-   **Base Route**: `https://boislegal.ci/`
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