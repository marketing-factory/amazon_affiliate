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
	 * @param string $linktxt
	 * @param array $conf
	 * @param string $linkHandlerKeyword
	 * @param string $linkHandlerValue
	 * @param array $link_param
	 * @return mixed
	 */
	public function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param) {
		$value = t3lib_div::trimExplode('|', $linkHandlerValue);

		if (
			tx_amazonaffiliate_amazonecs::validateAsinSyntax($value[0])
			&& $GLOBALS['TSFE']->config['config']['tx_amazonaffiliate_piproducts.']['renderProducts']
		) {
			$product = t3lib_div::makeInstance('tx_amazonaffiliate_product', $value[0]);

			if ($product->getStatus()) {
				$link_paramA = t3lib_div::unQuoteFilenames($link_param, TRUE);

				$amazonHover = ($value[1] == '1' ? TRUE : FALSE);

				$generatedLink = tx_amazonaffiliate_amazonecs::getAmazonLink(
					$linktxt,
					$conf,
					$value[0],
					$amazonHover,
					trim($link_paramA[2]),
					trim($link_paramA[1]),
					trim($link_paramA[3]),
					$link_paramA[4]
				);

				if ($generatedLink != '') {
					$linktxt = $generatedLink;
				}
			}
		}

		return $linktxt;
	}
}

?>