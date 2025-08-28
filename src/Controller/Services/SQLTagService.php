<?php 

namespace App\Controller\Services;

use Symfony\Component\HttpFoundation\Request;

class SQLTagService
{
    /**
     * Configuration des tags SQL.
     * - 'baseTable' : Table principale.
     * - 'filters' : Filtres à appliquer, organisés par table.
     * - 'join' : Jointure à appliquer (optionnel).
     * - 'aggregates' : Agrégations à appliquer (optionnel).
     */
    protected static $SQLTag = [
        'total_exploitation' => [
            'baseTable'  => 'admin.exercice',
            'filters'    => [
                'admin.exercice' => ['annee' => 'exercice'], 
            ],
            'join' => ['admin.exercice.id', 'metier.lignepagebrh.exercice_id'],
            'aggregates' => [
                'sum' => ['lignepagebrh.cubage_lignepagebrh'], 
            ],
            /*'groupBy' => ['exercice.id', 'exercice.annee'],*/
        ],
        
        'volume_transformation' => [
            'baseTable'  => 'transformation.billon',
            'filters'    => [],
            'aggregates' => [
                'count' => ['billon.volume'], 
            ],
        ],
        'nb_autorisations' => [
            'baseTable'  => 'transformation.billon',
            'filters'    => [],
            'aggregates' => [
                'count' => ['billon.dm'], 
            ],
        ],
        'bille' => [
            'baseTable'  => 'metier.lignepagebrh',
            'filters'    => [
                'metier.lignepagebrh' => [],
            ],
            'aggregates' => [
                'count' => ['billon.dm'],
            ],
        ],
    ];

    /**
     * Récupère les données du tag et remplace les paramètres dynamiques.
     */
    public static function getTagData(string $tag, Request $request)
    {
        if (!isset(self::$SQLTag[$tag])) {
            return null;
        }

        $tagData = self::$SQLTag[$tag];
        $dynamicParams = ['exercice', 'exercice_id']; // Liste des paramètres dynamiques

        // Traitement des filtres dynamiques
        if (isset($tagData['filters'])) {
            foreach ($tagData['filters'] as $schemaTable => &$conditions) {
                foreach ($conditions as $column => &$value) {
                    if (in_array($value, $dynamicParams, true)) {
                        if (!$request->query->has($value)) {
                            throw new \InvalidArgumentException("Le paramètre '$value' est requis pour ce tag.");
                        }
                        $value = $request->query->get($value);
                    }
                }
            }
        }

        return $tagData;
    }
}