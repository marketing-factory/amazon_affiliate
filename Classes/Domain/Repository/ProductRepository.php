<?php
namespace Mfc\AmazonAffiliate\Domain\Repository;

use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class ProductRepository
 * @package Mfc\AmazonAffiliate\Domain\Repository
 */
class ProductRepository
{

    /**
     * @var string
     */
    const TABLE = 'tx_amazonaffiliate_products';

    /**
     * @var int
     */
    const ACTIVE = 1;

    /**
     * @var int
     */
    const INACTIVE = 2;

    /**
     * @param int $flag
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findAll($flag = 0, $limit = 0, $offset = 0)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;

        $where = '1=1';
        $where .= (($flag & static::ACTIVE) == static::ACTIVE) ? ' AND status = 1' : '';
        $where .= (($flag & static::INACTIVE) == static::INACTIVE) ? ' AND status = 0' : '';

        $data = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            static::TABLE,
            $where,
            '',
            '',
            $limit > 0  ? $offset . ',' . $limit : ''
        );

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    /**
     * @param string $asin
     * @return array|null
     */
    public function findByAsin($asin)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            static::TABLE,
            'asin = ' . $this->getDatabaseConnection()->fullQuoteStr($asin, static::TABLE)
        );

        if (is_array($row)) {
            return $row;
        }

        return null;
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
