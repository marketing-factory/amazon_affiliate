<?php

/***************************************************************
 *  Copyright notice
 *  (c) 2011 Sascha Egerer <info@sascha-egerer.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class tx_amazonaffiliate_updatestatus extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @var tx_amazonaffiliate_amazonecs
     */
    public $amazonEcs;

    /**
     * @return bool
     */
    public function execute()
    {
        $this->amazonEcs = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_amazonecs');

        $this->deleteNotUsedProducts();

        $asinRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'asin',
            'tx_amazonaffiliate_products',
            ''
        );

        $asinList = array();

        // get a list of all asin numbers
        foreach ($asinRecords as $asin) {
            $asinList[] = $asin['asin'];
        }

        //preload all products from amazon so we to do only one request
        $this->amazonEcs->preloadProducts($asinList);

        foreach ($asinList as $asin) {

            /**
             * just load the data from amazon. if the product is invalid the status
             * is updated automatically
             *
             * @var tx_amazonaffiliate_product $product
             */
            $product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_product', $asin);
        }

        return true;
    }

    /**
     * deletes all records from the tx_amazonaffiliate_products Table if
     * the ASIN is not used anymore
     */
    public function deleteNotUsedProducts()
    {
        $asinListArray = array();

        /**
         * get all products from the tt_content image records
         */
        $asinRecords = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid,tx_amazonaffiliate_amazon_asin',
            'tt_content',
            'tx_amazonaffiliate_amazon_asin != ""' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content')
        );

        while ($asinRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($asinRecords)) {

            $asinArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
                LF,
                $asinRecord['tx_amazonaffiliate_amazon_asin'],
                true
            );

            foreach ($asinArray as $asin) {
                $asinListArray[] = $asin;
            }
        }

        /**
         * get all products from the flexform widget records
         */
        $asinRecords = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid,pi_flexform',
            'tt_content',
            'pi_flexform LIKE "%asinlist%"' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content')
        );

        while ($asinRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($asinRecords)) {
            $mode = $this->amazonEcs->piObj->pi_getFFvalue(
                \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($asinRecord['pi_flexform']),
                'mode'
            );

            if ($mode == 'ASINList' || $mode == 'products') {
                $asinArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
                    LF,
                    $this->amazonEcs->piObj->pi_getFFvalue(
                        \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($asinRecord['pi_flexform']),
                        'asinlist'
                    ),
                    true
                );
                foreach ($asinArray as $asin) {
                    $asinListArray[] = $asin;
                }
            }
        }

        $searchResults = $this->find("amazonaffiliate");

        foreach ($searchResults as $record) {
            foreach ($record as $field) {
                preg_match_all('/amazonaffiliate\:([a-z0-9]{10})/i', $field, $matches);
                if (is_array($matches[1]) && count($matches[1]) > 0) {
                    $asinListArray = array_merge($asinListArray, $matches[1]);
                }
            }
        }

        $asinListArray = array_unique($asinListArray);

        foreach ($asinListArray as $key => $asin) {
            $asinListArray[$key] = "'" . $asin . "'";
        }

        //delete all unused records
        $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            'tx_amazonaffiliate_products',
            'asin NOT IN (' . implode(",", $asinListArray) . ')'
        );
    }


    /**
     * Find records from database based on the given $searchQuery.
     *
     * @param string $searchQuery
     *
     * @return array Result list of database search.
     */
    public function find($searchQuery)
    {
        $recordArray = array();

        foreach ($GLOBALS['TCA'] as $tableName => $value) {
            if (preg_match('@tt_|tx_|pages@', $tableName)) {
                $records = $this->findByTable($searchQuery, $tableName);
                if (is_array($records) && count($records) > 0) {
                    $recordArray = array_merge($recordArray, $records);
                }
            }
        }

        return $recordArray;
    }

    /**
     * Find records by given table name.
     *
     * @param $queryString
     * @param string $tableName Database table name
     *
     * @return array Records found in the database matching the searchQuery
     */
    protected function findByTable($queryString, $tableName)
    {
        $fieldsToSearchWithin = $this->extractSearchableFieldsFromTable($tableName);

        $getRecordArray = array();
        if (count($fieldsToSearchWithin) > 0) {
            $where = $this->makeQuerySearchByTable($queryString, $tableName, $fieldsToSearchWithin);
            $getRecordArray = $this->getRecordArray(
                $tableName,
                $where,
                $fieldsToSearchWithin
            );
        }

        return $getRecordArray;
    }


    /**
     * Get all fields from given table where we can search for.
     *
     * @param string $tableName
     *
     * @return array
     */
    protected function extractSearchableFieldsFromTable($tableName)
    {
        $fieldListArray = array();

        // Traverse configured columns and add them to field array, if available for user.
        foreach ((array) $GLOBALS['TCA'][$tableName]['columns'] as $fieldName => $fieldValue) {
            if (in_array($fieldValue['config']['type'], array('text', 'input'))
                && (!preg_match(
                    '/date|time|int/',
                    $fieldValue['config']['eval']
                ))
            ) {
                $fieldListArray[] = $fieldName;
            }
        }

        return $fieldListArray;
    }

    /**
     * Build the MySql where clause by table.
     *
     * @param string $tableName Record table name
     * @param array $fieldsToSearchWithin User right based visible fields where we can search within.
     *
     * @return string
     */
    protected function makeQuerySearchByTable($queryString, $tableName, array $fieldsToSearchWithin)
    {
        // free text search
        $queryLikeStatement = ' LIKE \'%' . $GLOBALS['TYPO3_DB']->quoteStr($queryString, $tableName) . '%\'';
        $integerFieldsToSearchWithin = array();
        $queryEqualStatement = '';

        if (is_numeric($GLOBALS['TYPO3_DB']->quoteStr($queryString, $tableName))) {
            $queryEqualStatement = ' = \'' . $GLOBALS['TYPO3_DB']->quoteStr($queryString, $tableName) . '\'';
        }
        $uidPos = array_search('uid', $fieldsToSearchWithin);
        if ($uidPos) {
            $integerFieldsToSearchWithin[] = 'uid';
            unset($fieldsToSearchWithin[$uidPos]);
        }
        $pidPos = array_search('pid', $fieldsToSearchWithin);
        if ($pidPos) {
            $integerFieldsToSearchWithin[] = 'pid';
            unset($fieldsToSearchWithin[$pidPos]);
        }

        $queryPart = ' (';
        if (count($integerFieldsToSearchWithin) && $queryEqualStatement !== '') {
            $queryPart .= implode($queryEqualStatement . ' OR ', $integerFieldsToSearchWithin) . $queryEqualStatement
                . ' OR ';
        }
        $queryPart .= implode($queryLikeStatement . ' OR ', $fieldsToSearchWithin) . $queryLikeStatement . ')';
        $queryPart .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName);
        $queryPart .= \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause($tableName);

        return $queryPart;
    }

    /**
     * Process the Database operation to get the search result.
     *
     * @param string $tableName Database table name
     * @param string $where
     * @param array $fieldsToSearchWithin
     *
     * @return array
     */
    protected function getRecordArray($tableName, $where, $fieldsToSearchWithin = array())
    {
        $collect = array();

        $select = "*";
        if (is_array($fieldsToSearchWithin) && count($fieldsToSearchWithin) > 0) {
            $select = implode(',', $fieldsToSearchWithin);
        }

        $queryParts = array(
            'SELECT' => 'uid, pid, ' . $select,
            'FROM' => $tableName,
            'WHERE' => $where
        );
        $result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
        if ($result) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                $row['__database_table'] = $tableName;
                $collect[] = $row;
            }
        }

        return $collect;
    }
}
