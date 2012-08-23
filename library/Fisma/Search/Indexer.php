<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Responsible for fetch data from the relational database that is suitable for indexing, then working with the backend
 * to get those documents into the search index.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Indexer
{
    /**
     * Reference to the search engine used by this indexer.
     *
     * @var Fisma_Search_Engine
     */
    private $_searchEngine;

    /**
     * Constructor
     *
     * @param Fisma_Search_Engine $searchEngine
     */
    public function __construct(Fisma_Search_Engine $searchEngine)
    {
        $this->_searchEngine = $searchEngine;
    }

    /**
     * Returns a query that fetches all searchable fields for a particular table -- including those fields defined
     * on a related model -- for all indexable records.
     *
     * @param string $modelName
     * @param array $relationAliases Passed by reference. On return it will contain a map of relation names
     *                               and query table aliases.
     * @return Doctrine_Query
     */
    public function getRecordFetchQuery($modelName, &$relationAliases = null)
    {
        $allRecordsQuery = Doctrine_Query::create()
                           ->from("$modelName a")
                           ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        // Add relations (if any) to the query
        $table = Doctrine::getTable($modelName);
        $searchableFields = $table->getSearchableFields();

        $currentAlias = 'a';
        $relationAliases = array($modelName => $currentAlias);

        foreach ($searchableFields as $fieldName => $fieldDefinition) {
            if (isset($fieldDefinition['join'])) {
                $relation = $fieldDefinition['join']['relation'];
                $relationParts = explode('.', $relation);
                $subrelation = '';
                $previousAlias = 'a';
                foreach ($relationParts as $part) {
                    $subrelation .= ($subrelation !== '' ? '.' : '') . $part;
                    if (!isset($relationAliases[$subrelation])) {
                        // Create a new relation alias
                        $currentAlias = chr(ord($currentAlias) + 1);

                        $allRecordsQuery->leftJoin("$previousAlias.$part $currentAlias");
                        $allRecordsQuery->addSelect("$currentAlias.id");

                        $relationAliases[$subrelation] = $currentAlias;
                    }
                    $previousAlias = $relationAliases[$subrelation];
                }

                $relationAlias = $relationAliases[$relation];

                $name = $fieldDefinition['join']['field'];

                $allRecordsQuery->addSelect("$relationAlias.$name");
            } else {
                $allRecordsQuery->addSelect("a.$fieldName");
            }
        }

        // Make sure soft deleted records are included, too
        if ($table->hasColumn('deleted_at')) {
            $allRecordsQuery->addSelect('a.deleted_at')
                            ->andWhere('(a.deleted_at = a.deleted_at OR a.deleted_at IS NULL)');
        }

        // Implementers can tweak the selection query to filter out undesired records
        if ($table instanceof Fisma_Search_CustomIndexBuilder_Interface) {
            $allRecordsQuery = $table->getSearchIndexQuery($allRecordsQuery, $relationAliases);
        }

        return $allRecordsQuery;
    }

    /**
     * Indexes the records and fields represented by a doctrine query.
     *
     * @param Doctrine_Query $query
     * @param string $modelName
     * @param int $chunkSize The number of records to index in each batch
     * @param callback $progressCallback This callback will be invoked with the number of indexed documents as a
     *        parameter.
     */
    public function indexRecordsFromQuery(Doctrine_Query $query,
                                          $modelName,
                                          $chunkSize = 1,
                                          $progressCallback = null)
    {
        $currentRecord = 0;
        $totalRecords = $query->count();

        while ($currentRecord < $totalRecords) {
            $query->limit($chunkSize)
                  ->offset($currentRecord);

            $recordSet = $query->execute();

            $this->_searchEngine->indexCollection($modelName, $recordSet);

            $currentRecord += count($recordSet);

            if ($progressCallback) {
                call_user_func($progressCallback, $currentRecord);
            }
        }

    }
}
