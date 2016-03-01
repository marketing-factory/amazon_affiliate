<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sascha Egerer <info@sascha-egerer.de>
 *  (c) 2015 Alexander Schnitzler <aschnitzler@marketing-factory.de>
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
namespace Mfc\AmazonAffiliate\Service;

use Mfc\AmazonAffiliate\Domain\Model\Product;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * Class AmazonEcsService
 * @package Mfc\AmazonAffiliate\Service
 */
class AmazonEcsService extends \AmazonECS implements SingletonInterface
{

    /**
     * The amazon config values
     *
     * @var array
     */
    protected $extConfArr;

    /**
     * A storage where products are stored after
     * they are requested. We use this to prevent multiple
     * product checks
     *
     * @var array
     */
    private $productCache = [];

    /**
     * @var AbstractPlugin
     */
    public $piObj;

    /**
     * Possible Responsegroups: BrowseNodeInfo,MostGifted,NewReleases,MostWishedFor,TopSellers
     */
    public $validResponsegroups = ["BrowseNodeInfo", "MostGifted", "NewReleases", "MostWishedFor", "TopSellers"];

    /**
     * is the amazon hover JavaScript already added?
     *
     * @var bool
     */
    private $hoverJavaScriptAdded = false;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->piObj = GeneralUtility::makeInstance(AbstractPlugin::class);

        // get the extension config
        $this->extConfArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['amazon_affiliate']);

        $this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.'];

        // check if the required Amazon access_key, secret_key and associate_tag is configured
        if ($this->extConfArr['access_key'] != '' && $this->extConfArr['secret_key'] != '' && $this->extConfArr['associate_tag'] != '') {

            parent::__construct($this->extConfArr['access_key'], $this->extConfArr['secret_key'],
                $this->extConfArr['country'], $this->extConfArr['associate_tag']);

        } else {
            throw new \Exception('The Amazon Options are not Configured or the configuration is incomplete! Please Check your Extension Configuration!');
        }

        $this->responseGroup("Large");
        $this->returnType(self::RETURN_TYPE_ARRAY);
    }

    /**
     * returns the associate_tag
     * @return string
     */
    public function getAssociateTag()
    {
        return $this->extConfArr['associate_tag'];
    }

    /**
     * returns the country
     *
     * @param bool $strToUpper
     * @return string
     */
    public function getCountry($strToUpper = true)
    {
        if ($strToUpper) {
            return strtoupper($this->extConfArr['country']);
        }
        return $this->extConfArr['country'];
    }


    /**
     * returns the associate_tag
     * @return string
     */
    public function getMinimumAsinlistCount()
    {
        return $this->extConfArr['minimumAsinlistCount'];
    }

    /**
     * @return mixed
     */
    public function getProductImageSize()
    {
        return $this->conf['productListing.']['imageSize'];
    }

    /**
     * Simple check if the given ASIN is 10 Chars long and Alphanum
     * @static
     * @param string $asin the ASIN
     * @return boolean
     */
    static function validateAsinSyntax($asin)
    {
        $asin = trim($asin);

        // Check if ASIN is alphanumeric and 10 chars long
        return ctype_alnum($asin) && strlen($asin) == 10;

    }

    /**
     * @param $linktxt
     * @param $conf
     * @param $asin
     * @param $hover
     * @param $class
     * @param $target
     * @param $title
     * @param $additionalParams
     * @return string
     */
    public static function getAmazonLink($linktxt, $conf, $asin, $hover, $class, $target, $title, $additionalParams)
    {
        $result = '';
        $pObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        /** @var AmazonEcsService $amazonEcs */
        $amazonEcs = GeneralUtility::makeInstance(__CLASS__);
        if ($target == '-' || $target == '') {
            $target = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['target'];
        }
        $urlTemplate = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['url'];

        $markers = [];
        $markers['ASIN'] = $asin;
        $markers['ASSOCIATE_TAG'] = $amazonEcs->getAssociateTag();

        $url = $pObj->substituteMarkerArray($urlTemplate, $markers, "###|###");

        $link_param = implode(" ", [$url, $target, $class, $title, $additionalParams]);

        if ($link_param != '') {
            $conf['parameter'] = $link_param;

            if ($hover) {
                $wrapTemplate = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['urlHoverStdWrap'];
                $wrap = $pObj->substituteMarkerArray($wrapTemplate, $markers, "###|###");

                $conf['wrap'] = $wrap;

                /*
                 * Temporarily disabled as the currently loaded js file
                 * is completely empty and there is no other quick
                 * solution yet.
                 */
//                $amazonEcs->addHoverJavascript();
            } else {
                $wrapTemplate = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['urlStdWrap'];
                $wrap = $pObj->substituteMarkerArray($wrapTemplate, $markers, "###|###");

                $conf['wrap'] = $wrap;
                $conf['ATagParams'] = "name=\"noHover\"";
            }

            unset($conf['parameter.']);

            $result = $pObj->typoLink($linktxt, $conf);
        }
        return $result;
    }

    /**
     * add amazon hover javascript
     */
    public function addHoverJavascript()
    {
        if ($this->hoverJavaScriptAdded == false) {
            $code = str_replace("###ASSOCIATE_TAG###", $this->getAssociateTag(),
                $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['amazonJS.']['hover']);

            $GLOBALS['TSFE']->additionalFooterData['amazon_affiliate'] .= $code;
            $this->hoverJavaScriptAdded = true;
        }
    }

    /**
     *  returns the Amazon Image tag by a given asin
     *
     * @param $asin
     * @param int $maxWidth
     * @param int $maxHeight
     * @param bool $hover
     * @param bool $useTagTemplate
     * @return mixed|string
     */
    public function getAmazonImageOnlyCode(
        $asin,
        $maxWidth = 0,
        $maxHeight = 0,
        $hover = false,
        $useTagTemplate = false
    ) {

        if ($useTagTemplate) {
            $noHoverAttribute = '';
            if (!$hover) {
                $noHoverAttribute = "name=\"" . $asin . "\"";
            }

            $makerNames = [
                '###ASIN###',
                '###ASSOCIATE_TAG###',
                '###IMAGE_SIZE###',
                '###COUNTRY###',
                '###NO_HOVER###',
            ];
            $markerValues = [
                $asin,
                $this->getAssociateTag(),
                $maxHeight,
                $this->getCountry(),
                $noHoverAttribute,
            ];

            return str_replace($makerNames, $markerValues,
                $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['productListing.']['imageCode']);
        } else {

            $amazonProduct = GeneralUtility::makeInstance(Product::class, $asin);

            $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);

            $imageSizeName = $this->getImageSizeName($maxWidth, $maxHeight);

            $imageUrl = $amazonProduct->getItemAttribute($imageSizeName . ".URL");

            $imageInfo = [
                0 => $amazonProduct->getItemAttribute($imageSizeName . ".Width._"),
                1 => $amazonProduct->getItemAttribute($imageSizeName . ".Height._"),
            ];

            $imageScale = $gifCreator->getImageScale($imageInfo, $imageInfo[0], $imageInfo[1],
                ["maxW" => $maxWidth, "maxH" => $maxHeight]);

            $imageTag = '<img src="' . $imageUrl . '" width="' . $imageScale[0] . '" height="' . $imageScale[1] . '" />';
            $linkedImageTag = self::getAmazonLink($imageTag, [], $asin, $hover, '', '', '', '');

            return $linkedImageTag;
        }
    }

    /**
     * @param $width
     * @param $height
     * @return string
     */
    public function getImageSizeName($width, $height)
    {
        $sizeName = "LargeImage";

        if (empty($width) && empty($height)) {
            //large image because no size is given
        } else {
            // get the maximum length a image side can have
            $maxLength = max([$width, $height]);

            if ($maxLength <= 75) {
                $sizeName = "SmallImage";
            } elseif ($maxLength <= 160) {
                $sizeName = "MediumImage";
            }
        }

        return $sizeName;
    }

    /**
     * Load a product from amazon or load it from the cache if it already exists
     *
     * @param string $asin The ASIN
     * @return mixed
     * @throws \Exception
     */
    public function lookup($asin)
    {
        try {
            //throw exception if multiple asins are given
            if (count(explode(",", $asin)) != 1) {
                throw new \Exception("Empty and multiple ASIN's are not supported. Please use the preloadProducts Method to load Multiple products",
                    1322135802);
            }

            // build a hash of the request params
            $params = md5(serialize($this->buildRequestParams('ItemLookup', [])));

            // add the product to the cache if it does not exist
            if (!array_key_exists($asin, $this->productCache) || !array_key_exists($params,
                    $this->productCache[$asin])
            ) {
                $amazon_product = parent::lookup($asin);
                $this->productCache[$asin][$params] = $amazon_product['Items']['Item'];
            }

            // return the product form cache
            return $this->productCache[$asin][$params];
        } catch (\Exception $e) {
            GeneralUtility::sysLog("Amazon Lookup Error! " . $e->getMessage(),
                'amazon_affiliate', 2);

            return false;
        }
    }

    /**
     * preload multiple products to the cache. This saves performance
     * because you can load multiple products with one request
     *
     * @param Array|string $asin_list List of ASIN's
     */
    public function preloadProducts($asin_list)
    {

        if (!is_array($asin_list)) {
            $asin_list = GeneralUtility::trimExplode(",", $asin_list, true);
        }

        // build a hash of the request params
        $params = md5(serialize($this->buildRequestParams('ItemLookup', [])));

        $request_asin_list = [];
        foreach ($asin_list as $asin) {
            if (count($this->productCache) == 0 || (!array_key_exists($asin,
                        $this->productCache) && isset($this->productCache[$asin]) && !array_key_exists($params,
                        $this->productCache[$asin]))
            ) {
                $request_asin_list[] = $asin;
            }
        }

        try {
            if (count($request_asin_list) > 0) {

                $amazon_products = parent::lookup(implode(",", $request_asin_list));

                // check if we got multiple products
                if ($amazon_products['Items']['Item']['ASIN']) {
                    // we got only one product
                    $this->productCache[$amazon_products['Items']['Item']['ASIN']][$params] = $amazon_products['Items']['Item'];

                    //add the rest of the asins to the cacheArray because they are invalid but we've also done the request
                    foreach ($request_asin_list as $asin) {
                        if (!array_key_exists($asin, $this->productCache) || (array_key_exists($asin,
                                    $this->productCache) && !array_key_exists($params, $this->productCache[$asin]))
                        ) {
                            $this->productCache[$asin][$params] = false;
                        }
                    }
                } else {
                    if (isset($amazon_products['Items']['Item'])) {
                        foreach ($amazon_products['Items']['Item'] as $item) {
                            $this->productCache[$item['ASIN']][$params] = $item;
                        }
                    }
                }

            }
        } catch (\Exception $e) {
            GeneralUtility::sysLog("Amazon Lookup Error! " . $e->getMessage(),
                'amazon_affiliate', 2);

        }

    }

    /**
     * get browse nodes
     *
     * @param $responseGroup
     * @param $nodeId
     * @return array
     */
    public function getBrowseNodes($responseGroup, $nodeId)
    {
        $browseNodes = [];

        try {
            $browseNodes = $this->responseGroup($responseGroup)->browseNodeLookup($nodeId);
        } catch (\Exception $e) {
        }

        return $browseNodes;
    }

    /**
     * used by the TCA to get the items for the BrowseNode selection
     *
     * @param Array $config The field config
     * @return Array
     */
    public function getBrowseNodesSelectItems($config)
    {

        $responseGroup = $this->piObj->pi_getFFvalue(GeneralUtility::xml2array($config['row']['pi_flexform']),
            'mode');
        $browseNode = $this->piObj->pi_getFFvalue(GeneralUtility::xml2array($config['row']['pi_flexform']),
            'browsenode');

        $browseNodes = $this->getBrowseNodes("BrowseNodeInfo", $browseNode);

        $charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'ISO-8859-1';

        try {
            if (is_array($browseNodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'])) {
                ksort($browseNodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']);
                foreach ($browseNodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'] as $browseNodeItem) {

                    if ($charset != "utf-8") {
                        $name = iconv("utf-8", $charset, $browseNodeItem['Name']);
                    } else {
                        $name = $browseNodeItem['Name'];
                    }

                    $config["items"][] = [0 => $name, 1 => $browseNodeItem['BrowseNodeId']];
                }
            }
        } catch (\Exception $e) {

        }

        return $config;
    }

}
