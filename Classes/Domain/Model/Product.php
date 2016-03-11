<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sascha Egerer <info@sascha-egerer.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
namespace Mfc\AmazonAffiliate\Domain\Model;

use Mfc\AmazonAffiliate\Service\AmazonEcsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Product
 * @package Mfc\AmazonAffiliate\Domain\Model
 */
class Product
{

    /**
     * Id of the database entry
     *
     * @var int
     */
    protected $uid;

    /**
     * The Amazon ASIN
     *
     * @var string
     */
    protected $asin;

    /**
     * The name of the amazon product. We use this
     * only for the backend module
     *
     * @var string
     */
    protected $name;

    /**
     * The status of the product
     * If it is false, the product is
     * not (maybe anymore) valid Amazon product
     *
     * @var bool
     */
    protected $status = false;

    /**
     * The status message if the status
     * is false. Could be the error message
     * from Amazon
     *
     * @var string
     */
    protected $statusMessage;

    /**
     * @var array
     */
    protected $fieldlist = ['uid', 'asin', 'name', 'status'];

    /**
     * @var AmazonEcsService
     */
    protected $amazonEcs;

    /**
     * The amazon SOAP object info
     *
     * @var bool|Object
     */
    protected $amazonProduct = null;

    /**
     * If false, use just local data. We need this for the Backend Module
     *
     * @var bool
     */
    protected $useCachedData = false;

    /**
     * indicates if something of the
     * products data has changed
     *
     * @var bool
     */
    protected $_dirty = false;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $database;

    /**
     * @param string $asin The Amazon ASIN
     * @param boolean $useCachedData set true if the local cached data should be used
     */
    public function __construct($asin = '', $useCachedData = false)
    {
        $asin = trim($asin);

        $this->database = $GLOBALS['TYPO3_DB'];

        if ($asin) {
            /**
             * create an instance of the AmazonEcsService
             */
            $this->amazonEcs = GeneralUtility::makeInstance(AmazonEcsService::class);

            $this->setUseCachedData($useCachedData);

            /**
             * check if the Syntax of the given ASIN is valid
             */
            if (!AmazonEcsService::validateAsinSyntax($asin)) {

                /**
                 * set a statusMessage because the product is not valid
                 */
                $this->setStatusMessage("The Syntax of the ASIN '%s' is invalid.");
            } else {
                /**
                 * set the ASIN
                 */
                $this->asin = $asin;

                /**
                 * load the data from amazon
                 */
                $this->loadData();
            }
        }
    }

    /**
     * @param array $product
     * @return Product
     */
    public function setDataWithArray(array $product)
    {
        $this->amazonProduct = $product;

        $product = [
            'asin' => $this->getItemAttribute("ASIN"),
            'name' => $this->getItemAttribute("ItemAttributes.Title"),
            'status' => true,
        ];

        foreach ($this->fieldlist as $field) {
            $this->$field = $product[$field];
        }

        return $this;
    }

    /**
     * write data to database if some has been changed
     */
    public function __destruct()
    {
        //write data to database
        if ($this->_dirty && $this->getUid()) {
            $data = [
                'pid' => 1,
                'asin' => $this->getAsin(),
                'name' => $this->getName(),
                'status' => $this->getStatus(),
            ];
            $this->database->exec_UPDATEquery('tx_amazonaffiliate_products', "uid = '" . intval($this->getUid()) . "'",
                $data);
        }
    }

    /**
     * check if the product is a valid amazon product
     *
     * @return bool
     */
    private function loadData()
    {
        //check if product for given ASIN already exists in our local database
        $product = $this->database->exec_SELECTgetRows('*',
            'tx_amazonaffiliate_products',
            'asin = ' . $this->database->fullQuoteStr($this->getAsin(), 'tx_amazonaffiliate_products'),
            '',
            '',
            1
        );

        if (count($product) == 1) {
            $product = $product[0];
        } else {
            $product = false;
        }
        if ($this->getUseCachedData() && is_array($product)) {
            $this->setStatusMessage("You have requested a cached product which was not found in the local database.");
        } else {
            $this->loadAmazonProduct();

            if ($this->getAmazonProduct() == false || is_array($this->getItemAttribute("Items.Request.Errors"))) {

                $this->setStatusMessage($this->getItemAttribute("Items.Request.Errors.Error.Message"));
                if (!is_array($product)) {
                    $product = [];
                }
                $product['status'] = false;
                $this->_dirty = true;

            } elseif ($this->getAmazonProduct()) {
                if (!is_array($product)) {
                    $product = [
                        'pid' => 1,
                        'asin' => $this->getItemAttribute("ASIN"),
                        'name' => $this->getItemAttribute("ItemAttributes.Title"),
                        'status' => true,
                    ];
                    $this->database->exec_INSERTquery('tx_amazonaffiliate_products', $product);
                } else {
                    $product['status'] = true;
                    $this->_dirty = true;
                }
            }
        }

        if (is_array($product)) {
            foreach ($this->fieldlist as $field) {
                $this->$field = $product[$field];
            }
        }
    }

    /**
     * load the product data from amazon
     */
    public function loadAmazonProduct()
    {
        if ($this->getAmazonProduct() === null) {
            $this->setAmazonProduct($this->amazonEcs->lookup($this->getAsin()));
        }
    }

    /**
     * returns the product-value of a given attribute
     *
     * @param $attribute
     * @return bool|null|Object|string
     */
    public function getItemAttribute($attribute)
    {
        $charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'ISO-8859-1';

        $amazonProductValue = $this->getAmazonProduct();

        $attributePathArray = explode(".", $attribute);
        $found = false;
        // check if the first node is found
        // if not we go to add "ItemAttributes" and try it there
        foreach ($attributePathArray as $attributePathNode) {
            if (isset($amazonProductValue)
                && !is_null($amazonProductValue)
                && array_key_exists($attributePathNode, $amazonProductValue)
            ) {
                $amazonProductValue = $amazonProductValue[$attributePathNode];
                $found = true;
            } else {
                break;
            }
        }

        /**
         * if value was not found clear the value
         */
        if (is_array($amazonProductValue) || !$found) {
            $amazonProductValue = '';
        }

        if ($charset != "utf-8") {
            $amazonProductValue = iconv("utf-8", $charset, $amazonProductValue);
        }

        return $amazonProductValue;
    }

    /**
     * @param $amazonEcs
     */
    public function setAmazonEcs($amazonEcs)
    {
        $this->amazonEcs = $amazonEcs;
    }

    /**
     * @return AmazonEcsService
     */
    public function getAmazonEcs()
    {
        return $this->amazonEcs;
    }

    /**
     * @param $asin
     */
    public function setAsin($asin)
    {
        $this->_dirty = true;
        $this->asin = trim($asin);
    }

    /**
     * @return string
     */
    public function getAsin()
    {
        return $this->asin;
    }

    /**
     * @param $fieldlist
     */
    public function setFieldlist($fieldlist)
    {
        $this->fieldlist = $fieldlist;
    }

    /**
     * @return array
     */
    public function getFieldlist()
    {
        return $this->fieldlist;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->_dirty = true;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        $this->_dirty = true;
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param $statusMessage
     */
    public function setStatusMessage($statusMessage)
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * @param $amazonProduct
     */
    public function setAmazonProduct($amazonProduct)
    {
        if (!is_null($amazonProduct)) {
            if ($amazonProduct['ItemAttributes']['ListPrice']['FormattedPrice'] == '' || $amazonProduct['ItemAttributes']['ListPrice']['FormattedPrice'] == 'EUR 0,00') {
                $amazonProduct['ItemAttributes']['ListPrice']['FormattedPrice'] = $amazonProduct['Offers']['Offer']['OfferListing']['Price']['FormattedPrice'];
            }
            if (empty($amazonProduct['MediumImage']['URL'])) {
                $fallbackImage = isset($amazonProduct['ImageSets']['ImageSet']['MediumImage']) ? $amazonProduct['ImageSets']['ImageSet'] : current($amazonProduct['ImageSets']['ImageSet']);
                $amazonProduct['MediumImage']['URL']  = $fallbackImage['MediumImage']['URL'];
            }
            $this->amazonProduct = $amazonProduct;
        }
    }

    /**
     * @return bool|null|Object
     */
    public function getAmazonProduct()
    {
        return $this->amazonProduct;
    }

    /**
     * @param $useCachedData
     */
    public function setUseCachedData($useCachedData)
    {
        $this->useCachedData = $useCachedData;
    }

    /**
     * @return bool
     */
    public function getUseCachedData()
    {
        return $this->useCachedData;
    }
}
