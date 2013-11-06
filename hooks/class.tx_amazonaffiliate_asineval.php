<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2011 Sascha Egerer <info@sascha-egerer.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class tx_amazonaffiliate_asineval {

	/**
	 * Validate a single or a list of ASIN's
	 *
	 * @param mixed $value : The value that has to be checked.
	 * @return mixed The new value of the field
	 */
	public function evaluateFieldValue($value) {
		$value = trim($value);

		if ($value != '') {
			$asinArray = t3lib_div::trimExplode(LF, $value, TRUE);
			foreach ($asinArray as $asin) {
					// create a product instance wich checks if the product is valid
				t3lib_div::makeInstance('tx_amazonaffiliate_product', $asin);
			}
		}

		return $value;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/hooks/class.tx_amazonaffiliate_dmhooks.php']) {
		/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/hooks/class.tx_amazonaffiliate_dmhooks.php']);
}

?>