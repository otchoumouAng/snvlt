<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\DBAL\Connection;
use App\Controller\Services\SQLTagService;

class DynamicQueryController extends AbstractController
{
    #[Route('/snvlt/admin/query/{tag}', name: 'app_dynamic_query')]
    
    public function getDataDynamickly(string $tag, Request $request, Connection $connection): JsonResponse
    {
        // Récupération de la configuration traitée pour le tag donné
        try {
            $tagData = SQLTagService::getTagData($tag, $request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        if (!$tagData) {
            return new JsonResponse(['error' => 'Tag invalide'], 404);
        }

        // Extraction du schéma et du nom de la table de base
        $baseSchemaTable = $tagData['baseTable'];
        $baseTableParts = explode('.', $baseSchemaTable);
        $baseTable = end($baseTableParts); // Nom de la table
        $baseSchema = count($baseTableParts) > 1 ? $baseTableParts[0] : null; // Schéma (si présent)

        // Utilisation d'un alias simple pour la table de base
        $baseTableAlias = $baseTable;

        // Construction de la requête avec le QueryBuilder de Doctrine DBAL
        $qb = $connection->createQueryBuilder()
            ->from($baseSchema ? "$baseSchema.$baseTable" : $baseTable, $baseTableAlias);

        // Appliquer les agrégats
        $this->applyAggregates($qb, $tagData);
        if (!isset($tagData['aggregates'])) {
            $qb->addSelect("$baseTableAlias.*"); 
        }

        // Gestion de la jointure
        $this->applyJoins($qb, $tagData, $baseTableAlias);

        // Application dynamique des filtres pour chaque table
        $this->applyFilters($qb, $tagData['filters'], $baseTableAlias);

        // Application du GROUP BY
        if (isset($tagData['groupBy'])) {
            foreach ($tagData['groupBy'] as $column) {
                $qb->addGroupBy($column);
            }
        }

        //echo $qb->getSQL();
        // Exécution de la requête et récupération des résultats
        $results = $qb->executeQuery()->fetchAllAssociative();

        return new JsonResponse($results);
    }
 

/**
 * Applique les jointures définies dans la configuration.
*/

private function applyJoins($qb, array $tagData, string $baseTableAlias): void
{
    if (isset($tagData['join'])) {
        if (!is_array($tagData['join']) || count($tagData['join']) !== 2) {
            throw new \InvalidArgumentException('Format de jointure invalide pour le tag.');
        }

        [$leftColumn, $rightColumn] = $tagData['join']; 

        [$joinSchemaTable1,$joinTable1, $joinColumn1] = explode('.', $leftColumn);
        [$joinSchemaTable2,$joinTable2, $joinColumn2] = explode('.', $rightColumn);

        // Extraction du schéma et du nom de la table
        $joinParts = explode('.', $joinSchemaTable2);


        // Utilisation d'un alias simple pour la table jointe
        $alias = $joinTable2; // Alias = nom de la table
        $joinCondition = "$joinTable1.$joinColumn1 = $joinTable2.$joinColumn2";

        // Ajout de la jointure avec schéma si nécessaire
        $qb->join($baseTableAlias, "$joinSchemaTable2.$joinTable2", $alias, $joinCondition);
        
    }
}


    private function applyFilters($qb, array $filters, string $baseTableAlias): void
{
    foreach ($filters as $schemaTable => $conditions) {
        // Extraction du schéma et du nom de la table
        $tableParts = explode('.', $schemaTable);
        $table = end($tableParts); // Nom de la table
        $schema = count($tableParts) > 1 ? $tableParts[0] : null; // Schéma (si présent)

        // Utilisation d'un alias simple pour la table
        $alias = $table;

        foreach ($conditions as $column => $value) {
            $paramName = $alias . '_' . $column;
            $qb->andWhere("$alias.$column = :$paramName")
               ->setParameter($paramName, $value);
        }
    }
}

private function applyAggregates($qb, array $tagData): void
{
    if (isset($tagData['aggregates'])) {
        foreach ($tagData['aggregates'] as $function => $columns) {
            foreach ($columns as $column) {
                // Extraction du schéma et du nom de la table
                $columnParts = explode('.', $column);
                $table = $columnParts[0]; // Nom de la table
                $columnName = $columnParts[1]; // Nom de la colonne

                // Remplace les points par des underscores dans l'alias
                $alias = str_replace('.', '_', "{$table}_{$columnName}");
                $qb->addSelect("$function($table.$columnName) AS $alias");
            }
        }
    }
}

}
