<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sascha Egerer <info@sascha-egerer.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * Plugin 'Amazon Widget' for the 'amazon_affiliate' extension.
 *
 * @author    Sascha Egerer <info@sascha-egerer.de>
 * @package    TYPO3
 * @subpackage    tx_amazonaffiliate
 */
class tx_amazonaffiliate_piproducts extends AbstractPlugin
{
    // Same as class name
    public $prefixId = 'tx_amazonaffiliate_piproducts';
    // Path to this script relative to the extension dir.
    public $scriptRelPath = 'piproducts/class.tx_amazonaffiliate_piproducts.php';
    // The extension key.
    public $extKey = 'amazon_affiliate';
    public $pi_checkCHash = true;
    public $limit = 10;
    public $extConf = array();

    public $mode = 'products'; //extension mode. could be 'products' or 'widget'
    /**
     * list of ASIN's to display
     *
     * @var array
     */
    public $asinArray = array();
    public $templateCode = '';

    /**
     * @var tx_amazonaffiliate_amazonecs $amazonEcs
     */
    public $amazonEcs;

    /**
     * The main method of the PlugIn
     *
     * @param    string $content : The PlugIn content
     * @param    array $conf : The PlugIn configuration
     * @return    string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();

        $this->pi_initPIflexForm(); // load flexform values

        if (!$this->conf['templateFile']) {
            $msg = "Config not found. Is the static template added?";
            GeneralUtility::sysLog($msg, $this->extKey, 4);
            return "Error! Template not found!";
        }

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['amazon_affiliate']);
        $this->limit = $this->extConf['displayLimit'] ? $this->extConf['displayLimit'] : $this->limit;
        $this->limit = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'limit') ?
            $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'limit') :
            $this->limit;

        $this->mode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'mode');
        $this->asinArray = GeneralUtility::trimExplode(
            LF,
            $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'asinlist'),
            true
        );

        try {
            $this->amazonEcs = GeneralUtility::makeInstance('tx_amazonaffiliate_amazonecs');
        } catch (Exception $e) {
            GeneralUtility::sysLog(
                "Amazon Lookup Error! " . $e->getMessage(),
                'amazon_affiliate',
                2
            );
            return "";
        }

        $this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);

        $content = "";
        if (in_array(
            $this->mode,
            array("products", "ASINList", "SearchAndAdd", "Bestsellers", "NewReleases", "MostWishedFor", "MostGifted")
        )) {
            $content = $this->renderProducts();
        } elseif (in_array($this->mode, array("books"))) {
            $content = $this->renderBooks();
        }

        return $this->pi_wrapInBaseClass($content);
    }


    /**
     * @return string
     */
    protected function renderProducts()
    {
        if (in_array($this->mode, array("products", "ASINList"))) {
            $data = $this->fetchDataByAsin();
        } else {
            $data = $this->fetchDataBySearch();
        }

        return $this->renderProductLists('products', $data);
    }

    protected function renderBooks()
    {
        $data = $this->fetchDataByAsin();

        return $this->renderProductLists('books', $data);
    }

    /**
     * @return array
     */
    protected function fetchDataByAsin()
    {
        $results = array();

        if (!empty($this->asinArray)) {
            $this->amazonEcs->preloadProducts($this->asinArray);

            foreach ($this->asinArray as $asin) {
                $results[] = GeneralUtility::makeInstance('tx_amazonaffiliate_product', $asin);
            }
        }

        return $results;
    }

    /**
     * Fetch data by search
     *
     * @return array
     */
    protected function fetchDataBySearch()
    {
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'subbrowsenode')) {
            $browseNode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'subbrowsenode');
        } else {
            $browseNode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'browsenode');
        }
        $category = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'searchindex');


        $this->amazonEcs->category(($category != '') ? $category : 'All');

        $results = array();
        $data = array();

        if ($this->mode == 'SearchAndAdd') {
            try {
                $data = $this->amazonEcs->search(
                    $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'searchterm'),
                    ($browseNode > 0) ? $browseNode : null
                );
            } catch (Exception $e) {
                GeneralUtility::sysLog(
                    "Amazon SearchAndAdd Error! " . $e->getMessage(),
                    'amazon_affiliate',
                    2
                );
            }
            $i = 0;
            if (is_array($data['Items']) && is_array($data['Items']['Item'])) {
                // solve problem if amazon delivers a single item
                if (isset($data['Items']['Item']['ASIN'])) {
                    $tempData = $data['Items']['Item'];
                    $data['Items']['Item'] = array();
                    $data['Items']['Item'][] = $tempData;
                }
                foreach ($data['Items']['Item'] as $item) {
                    if ($i < $this->limit) {
                        $i++;
                    } else {
                        break;
                    }

                    if (!isset($item['ItemAttributes']) ||
                        !isset($item['ItemAttributes']['ListPrice']) ||
                        !isset($item['ItemAttributes']['FormattedPrice']) ||
                        $item['ItemAttributes']['ListPrice']['FormattedPrice'] == ''
                    ) {
                        $item['ItemAttributes']['ListPrice']['FormattedPrice'] =
                            $item['Offers']['Offer']['OfferListing']['Price']['FormattedPrice'];
                    }
                    if (!isset($item['MediumImage']) ||
                        !isset($item['MediumImage']['URL']) ||
                        $item['MediumImage']['URL'] == ''
                    ) {
                        $item['MediumImage']['URL'] = $item['ImageSets']['ImageSet']['MediumImage']['URL'];
                    }

                    if (is_array($item) &&
                        $item['MediumImage']['URL'] != '' &&
                        $item['ItemAttributes']['ListPrice']['FormattedPrice'] != ''
                    ) {
                        $results[] =
                            GeneralUtility::makeInstance('tx_amazonaffiliate_product')->setDataWithArray($item);
                    }
                }
            }
        } elseif ($this->mode == 'Bestsellers') {
            $this->amazonEcs->responseGroup('TopSellers');
            $data = $this->amazonEcs->browseNodeLookup($browseNode);
            $results = $this->getTopSellersData($data['BrowseNodes']['BrowseNode']['TopSellers']['TopSeller']);
        }

        return $results;
    }

    /**
     * Get topseller Data
     *
     * @param array $topSellers Topseller
     *
     * @return array
     */
    protected function getTopSellersData(array $topSellers)
    {

        $this->asinArray = array();
        $i = 0;
        foreach ($topSellers as $topSeller) {
            if ($i < $this->limit) {
                $i++;
            } else {
                break;
            }
            $this->asinArray[] = $topSeller['ASIN'];
        }

        $this->amazonEcs->responseGroup('Large');
        return $this->fetchDataByAsin();
    }


    /**
     * Render list
     *
     * @param string $mode Mode
     * @param array $products Array of products
     *
     * @return string
     */
    protected function renderProductLists($mode, array $products)
    {
        $template = $this->cObj->getSubpart($this->templateCode, '###' . strtoupper($mode) . '###');
        $itemTemplate = $this->cObj->getSubpart($template, "###PRODUCT_ITEMS###");

        $result = '';
        if (!empty($products)) {
            /** @var tx_amazonaffiliate_product $product */
            foreach ($products as $product) {
                if ($product->getStatus() == true) {
                    $marker = $this->renderProductMarkers($product, $mode);
                    $result .= $this->cObj->substituteMarkerArray($itemTemplate, $marker, "###|###");
                }
            }
        }

        return $this->cObj->substituteSubpart($template, '###PRODUCT_ITEMS###', $result);
    }


    /**
     * Fills the markers which are defined in TypoScript
     *
     * @param tx_amazonaffiliate_product $product Product
     * @param string $mode Mode
     *
     * @return array
     */
    public function renderProductMarkers(tx_amazonaffiliate_product $product, $mode)
    {
        $marker = array();

        $GLOBALS['TSFE']->register['lastProductLink'] = $product->getItemAttribute('DetailPageURL');

        foreach ($this->conf['productListing.'][$mode . '.']['fields.'] as $key => $field) {
            if (!is_array($field)) {
                $fieldOptions = $this->conf['productListing.'][$mode . '.']['fields.'][$key . "."];

                if ($field == "AMAZON_ATTR" && $fieldOptions['attrName'] != '') {
                    // if it is a amazon product field
                    if (is_string($product->getItemAttribute($fieldOptions['attrName']))) {
                        $fieldValue = utf8_encode($product->getItemAttribute($fieldOptions['attrName']));
                    } else {
                        $fieldValue = "";
                    }
                    $marker["PRODUCT_" . strtoupper($key)] = $this->cObj->stdWrap($fieldValue, $fieldOptions);
                } else {
                    // simple stdWrap
                    $marker["PRODUCT_" . strtoupper($key)] = $this->cObj->stdWrap($field, $fieldOptions);
                }
            }

        }

        return $marker;
    }
}
