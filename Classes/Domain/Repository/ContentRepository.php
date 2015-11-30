<?php
namespace Mfc\AmazonAffiliate\Domain\Repository;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class ContentRepository
 * @package Mfc\AmazonAffiliate\Domain\Repository
 */
class ContentRepository
{

    /**
     * @var string
     */
    const TABLE = 'tt_content';

    /**
     * @param string $asin
     * @return array
     */
    public function findUidsByAsin($asin)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid',
            static::TABLE,
            '(tx_amazonaffiliate_amazon_asin = ' . $this->getDatabaseConnection()->fullQuoteStr($asin, static::TABLE)
            . ' OR  pi_flexform LIKE "%' . $this->getDatabaseConnection()->escapeStrForLike($asin, static::TABLE) . '%")'
            . BackendUtility::deleteClause('tt_content')
        );

        if (is_array($rows)) {
            return $rows;
        }

        return [];
    }

    /**
     * @return int
     */
    public function countAll()
    {
        $count = (int)$this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            static::TABLE,
            ''
        );

        return $count;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
