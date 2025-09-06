<?php

namespace App\Service\Paiement;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TresorPayService
{
    private $httpClient;
    //private $apiUrl = 'https://wbservice.tresor.gouv.ci/wbpartenaires/tstrest/GenererAvisrecette';
    private $apiUrl = 'https://wbservice2.tresor.gouv.ci/wbpartenaires/tstrest/GenererAvisrecette';
    private $credentialId = 'l4lhut2b_0cvR4CcF';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    private function normalizeString(string $input): string
    {
        // Convert to uppercase
        $input = strtoupper($input);

        // Remove accents
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::createFromRules(':: NFD; :: [:M] Remove; :: NFC;', \Transliterator::FORWARD);
            if ($transliterator !== null) {
                $input = $transliterator->transliterate($input);
            } else {
                // Fallback if creation fails
                $accents = ['À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ'];
                $no_accents = ['A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','B','Ss','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','d','n','o','o','o','o','o','o','u','u','u','u','y','b','y'];
                $input = str_replace($accents, $no_accents, $input);
            }
        } else {
            // Fallback for environments without intl extension
            $accents = ['À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ'];
            $no_accents = ['A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','B','Ss','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','d','n','o','o','o','o','o','o','u','u','u','u','y','b','y'];
            $input = str_replace($accents, $no_accents, $input);
        }

        return $input;
    }

    public function genererAvisRecette(
        string $identifiant,
        float $montant_total,
        string $client_nom,
        string $client_prenom,
        string $nature_recette,
        ?string $telephone = null
    ): array {
        $payload = [
            'action' => 'GenererAvisrecette',
            'credential_id' => $this->credentialId,
            'identifiant' => $identifiant,
            'montant_total' => $montant_total,
            'client_nom' => $client_nom,
            'client_prenom' => $client_prenom,
            'telephone' => $telephone,
            'nature_recette' => $this->normalizeString($nature_recette),
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'json' => $payload,
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            return [
                'response_code' => -100,
                'response_message' => 'Erreur de communication avec l\'API TrésorPay: ' . $e->getMessage(),
            ];
        }
    }
}
