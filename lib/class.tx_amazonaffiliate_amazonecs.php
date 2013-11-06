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
class tx_amazonaffiliate_amazonecs extends AmazonECS implements t3lib_Singleton {
	/**
	 * The amazon config values
	 * @var array $extConfArr
	 */
	protected $extConfArr;

	/**
	 * A storage where products are stored after
	 * they are requested. We use this to prevent multiple
	 * aray $productCache product checks
	 */
	private $productCache;

	/**
	 * @var tslib_pibase
	 */
	public $piObj;

	/**
	 * Possible Responsegroups: BrowseNodeInfo,MostGifted,NewReleases,MostWishedFor,TopSellers
	 */
	public $validResponsegroups = array('BrowseNodeInfo', 'MostGifted', 'NewReleases', 'MostWishedFor', 'TopSellers');

	/**
	 * is the amazon hover JavaScript already added?
	 *
	 * @var boolean
	 */
	private $hoverJavaScriptAdded = FALSE;


	/**
	 * Constructor of tx_amazonaffiliate_amazonecs
	 */
	public function __construct() {
		$this->piObj = t3lib_div::makeInstance('tslib_pibase');

			// get the extension config
		$this->extConfArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['amazon_affiliate']);

		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.'];

			// check if the required Amazon access_key, secret_key and associate_tag is configured
		if ($this->extConfArr['access_key'] != '' && $this->extConfArr['secret_key'] != '' && $this->extConfArr['associate_tag'] != '') {
			parent::__construct($this->extConfArr['access_key'], $this->extConfArr['secret_key'], $this->extConfArr['country'], $this->extConfArr['associate_tag']);
		} else {
			throw new Exception('The Amazon Options are not Configured or the configuration is incomplete! Please Check your Extension Configuration!');
		}

		$this->productCache = array();

		$this->responseGroup('Large');
		$this->returnType(self::RETURN_TYPE_ARRAY);
	}

	/**
	 * returns the associate_tag
	 * @return string
	 */
	public function getAssociateTag() {
		return $this->extConfArr['associate_tag'];
	}

	/**
	 * returns the country
	 * @param bool $strToUpper
	 * @return string
	 */
	public function getCountry($strToUpper = TRUE) {
		if ($strToUpper) {
			return strToUpper($this->extConfArr['country']);
		}
		return $this->extConfArr['country'];
	}


	/**
	 * returns the associate_tag
	 * @return string
	 */
	public function getMinimumAsinlistCount() {
		return $this->extConfArr['minimumAsinlistCount'];
	}

	public function getProductImageSize() {
		return $this->conf['productListing.']['imageSize'];
	}

	/**
	 * Simple check if the given ASIN is 10 Chars long and Alphanum
	 * @static
	 * @param string $asin
	 * @return bool
	 */
	public static function validateAsinSyntax($asin) {
		$asin = trim($asin);

			// Check if ASIN is alphanumeric and 10 chars long
		return ctype_alnum($asin) && strlen($asin) == 10;
	}


	/**
	 * @param string $linktxt
	 * @param array $conf
	 * @param string $asin
	 * @param bool $hover
	 * @param string $class
	 * @param string $target
	 * @param string $title
	 * @param array $additionalParams
	 * @return mixed
	 */
	public static function getAmazonLink($linktxt, $conf, $asin, $hover, $class, $target, $title, $additionalParams) {
		$result = '';
		$pObj = t3lib_div::makeInstance('tslib_cObj');
		$amazonEcs = t3lib_div::makeInstance('tx_amazonaffiliate_amazonecs');
		if ($target == '-' || $target == '') {
			$target = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['target'];
		}
		$urlTemplate = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['url'];

		$markers = array();
		$markers['ASIN'] = $asin;
		$markers['ASSOCIATE_TAG'] = $amazonEcs->getAssociateTag();

		$url = $pObj->substituteMarkerArray($urlTemplate, $markers, '###|###');

		$link_param = implode(' ', array($url, $target, $class, $title, $additionalParams));

		if ($link_param != '') {
			$conf['parameter'] = $link_param;

			if ($hover) {
				$wrapTemplate = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['urlHoverStdWrap'];
				$wrap = $pObj->substituteMarkerArray($wrapTemplate, $markers, '###|###');

				$conf['wrap'] = $wrap;

				$amazonEcs = t3lib_div::makeInstance('tx_amazonaffiliate_amazonecs');
				$amazonEcs->addHoverJavascript();
			} else {
				$wrapTemplate = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['linkhandler.']['urlStdWrap'];
				$wrap = $pObj->substituteMarkerArray($wrapTemplate, $markers, '###|###');

				$conf['wrap'] = $wrap;
				$conf['ATagParams'] = 'name=\'noHover\'';
			}

			unset($conf['parameter.']);

			$result = $pObj->typoLink($linktxt, $conf);
		}
		return $result;
	}

	/**
	* add amazon hover javascript
	*/
	public function addHoverJavascript() {
		if ($this->hoverJavaScriptAdded == FALSE) {
			$code = str_replace('###ASSOCIATE_TAG###', $this->getAssociateTag(), $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['amazonJS.']['hover']);

			$GLOBALS['TSFE']->additionalFooterData['amazon_affiliate'] .= $code;
			$this->hoverJavaScriptAdded = TRUE;
		}
	}

	/**
	*  returns the Amazon Image tag by a given asin
	*
	* @param string $asin
	* @param integer $maxWidth
	* @param integer $maxHeight
	* @param boolean $hover
	* @param boolean $useTagTemplate
	* @return mixed|string
	*/
	public function getAmazonImageOnlyCode($asin, $maxWidth = 0, $maxHeight = 0, $hover = FALSE, $useTagTemplate = FALSE) {

		if ($useTagTemplate) {
			$noHoverAttribute = '';
			if (!$hover) {
				$noHoverAttribute = 'name=\'' . $asin . '\'';
			}

			$makerNames = array(
				'###ASIN###',
				'###ASSOCIATE_TAG###',
				'###IMAGE_SIZE###',
				'###COUNTRY###',
				'###NO_HOVER###',
			);
			$markerValues = array(
				$asin,
				$this->getAssociateTag(),
				$maxHeight,
				$this->getCountry(),
				$noHoverAttribute
			);

			return str_replace($makerNames, $markerValues, $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_amazonaffiliate_piproducts.']['productListing.']['imageCode']);
		} else {

			$amazonProduct = t3lib_div::makeInstance('tx_amazonaffiliate_product', $asin);

			$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');

			$imageSizeName = $this->getImageSizeName($maxWidth, $maxHeight);

			$imageUrl = $amazonProduct->getItemAttribute($imageSizeName . '.URL');

			$imageInfo = array(
				0 => $amazonProduct->getItemAttribute($imageSizeName . '.Width._'),
				1 => $amazonProduct->getItemAttribute($imageSizeName . '.Height._')
			);

			$imageScale = $gifCreator->getImageScale($imageInfo, $imageInfo[0], $imageInfo[1], array('maxW' => $maxWidth, 'maxH' => $maxHeight));

			$imageTag =  '<img src="' . $imageUrl . '" width="' . $imageScale[0] . '" height="' . $imageScale[1] . '" />';
			$linkedImageTag = self::getAmazonLink($imageTag, array(), $asin, $hover, '', '', '', '');

			return $linkedImageTag;
		}
	}

	/**
	 * @param integer $width
	 * @param integer $height
	 * @return string
	 */
	public function getImageSizeName($width, $height) {
		$sizeName = 'LargeImage';

		if (!(empty($width) && empty($height))) {
				// get the maximum length a image side can have
			$maxLength = max(array($width, $height));

			if ($maxLength <= 75) {
				$sizeName = 'SmallImage';
			} elseif ($maxLength <= 160) {
				$sizeName = 'MediumImage';
			}
		}

		return $sizeName;
	}

	/**
	* Load a product from amazon or load it from the cache if it already exists
	*
	* @param string $asin The ASIN
	* @return mixed
	* @throws Exception
	*/
	public function lookup($asin) {
		try {
				// throw exception if multiple asins are given
			if (count(explode(',', $asin)) != 1) {
				throw new Exception('Empty and multiple ASIN\'s are not supported. Please use the preloadProducts Method to load Multiple products', 1322135802);
			}

				// build a hash of the request params
			$params = md5(serialize($this->buildRequestParams('ItemLookup', array())));

				// add the product to the cache if it does not exist
			if (!array_key_exists($asin, $this->productCache) || !array_key_exists($params, $this->productCache[$asin])) {
				$amazon_product = parent::lookup($asin);
				$this->productCache[$asin][$params] = $amazon_product['Items']['Item'];
			}

				// return the product form cache
			return $this->productCache[$asin][$params];
		} catch (Exception $e) {
			t3lib_div::sysLog('Amazon Lookup Error! ' . $e->getMessage(), 'amazon_affiliate', 2);

			return FALSE;
		}
	}

	/**
	* preload multiple products to the cache. This saves performance
	* because you can load multiple products with one request
	*
	* @param array|string $asinList List of ASIN's
	* @return void
	*/
	public function preloadProducts($asinList) {
		if (!is_array($asinList)) {
			$asinList = t3lib_div::trimExplode(',', $asinList, TRUE);
		}

			// build a hash of the request params
		$params = md5(serialize($this->buildRequestParams('ItemLookup', array())));

		$request_asin_list = array();
		foreach ($asinList as $asin) {
			if (count($this->productCache) == 0 || (!array_key_exists($asin, $this->productCache) && !array_key_exists($params, $this->productCache[$asin]))) {
				$request_asin_list[] = $asin;
			}
		}

		try {
			if (count($request_asin_list) > 0) {
				$amazon_products = parent::lookup(implode(',', $request_asin_list));

					// check if we got multiple products
				if ($amazon_products['Items']['Item']['ASIN']) {
						// we got only one product
					$this->productCache[$amazon_products['Items']['Item']['ASIN']][$params] = $amazon_products['Items']['Item'];

						// add the rest of the asins to the cacheArray because they are invalid but we've also done the request
					foreach ($request_asin_list as $asin) {
						if (!array_key_exists($asin, $this->productCache) || (array_key_exists($asin, $this->productCache) && !array_key_exists($params, $this->productCache[$asin]))) {
							$this->productCache[$asin][$params] = FALSE;
						}
					}
				} else {
					foreach ($amazon_products['Items']['Item'] as $item) {
						$this->productCache[$item['ASIN']][$params] = $item;
					}
				}
			}
		} catch (Exception $e) {
			t3lib_div::sysLog('Amazon Lookup Error! ' . $e->getMessage(), 'amazon_affiliate', 2);
		}
	}

	/**
	* @param string $responseGroup
	* @param integer $nodeId
	* @return array|mixed
	*/
	public function getBrowseNodes($responseGroup, $nodeId) {
		$browseNodes = array();

		try {
			$browseNodes = $this->responseGroup($responseGroup)->browseNodeLookup($nodeId);
		} catch (Exception $e) {
		}

		return $browseNodes;
	}

	/**
	* used by the TCA to get the items for the BrowseNode selection
	*
	* @param Array $config The field config
	* @return Array
	*/
	public function getBrowseNodesSelectItems($config) {
		$browseNode = $this->piObj->pi_getFFvalue(t3lib_div::xml2array($config['row']['pi_flexform']), 'browsenode');
		$browseNodes = $this->getBrowseNodes('BrowseNodeInfo', $browseNode);

		$charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'ISO-8859-1';

		try {
			if (is_array($browseNodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'])) {
				ksort($browseNodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']);
				foreach ($browseNodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'] as $browseNodeItem) {

					if ($charset != 'utf-8') {
						$name = iconv('utf-8', $charset, $browseNodeItem['Name']);
					} else {
						$name = $browseNodeItem['Name'];
					}

					$config['items'][] = array(0 => $name, 1 => $browseNodeItem['BrowseNodeId']);
				}
			}
		} catch (Exception $e) {
		}

		return $config;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/lib/class.tx_amazonaffiliate_amazonecs.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/lib/class.tx_amazonaffiliate_amazonecs.php']);
}

?>