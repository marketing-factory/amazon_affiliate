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

class tx_amazonaffiliate_linkhandler {

	/**
	 * Linkhandler wich generates the amazon links
	 *
	 * @param $linktxt
	 * @param $conf
	 * @param $linkHandlerKeyword
	 * @param $linkHandlerValue
	 * @param $link_param
	 * @param $pObj
	 * @return mixed
	 */
	function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj) {

		$value = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode("|", $linkHandlerValue);

		if(tx_amazonaffiliate_amazonecs::validateAsinSyntax($value[0]) && $GLOBALS['TSFE']->config['config']['tx_amazonaffiliate_piproducts.']['renderProducts']) {

			$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_product', $value[0]);

			if($product->getStatus()) {

				$link_paramA = \TYPO3\CMS\Core\Utility\GeneralUtility::unQuoteFilenames($link_param, false);

				$amazonHover = ($value[1] == '1' ? true : false);

				$generatedLink = tx_amazonaffiliate_amazonecs::getAmazonLink($linktxt,$conf,$value[0], $amazonHover, trim($link_paramA[2]), trim($link_paramA[1]), trim($link_paramA[3]), $link_paramA[4]);

				if($generatedLink != '') {
					$linktxt = $generatedLink;
				}
			}
		}

		return $linktxt;
	}

}

?>
