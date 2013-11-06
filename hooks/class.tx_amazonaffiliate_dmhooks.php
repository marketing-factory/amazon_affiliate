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

class tx_amazonaffiliate_dmhooks extends tslib_pibase {

	/**
	* This hook is processed BEFORE a datamap is processed (save, update etc.)
	* We use this to check if a the amazon ASIN's are valid
	*
	* @param array $incomingFieldArray: the array of fields that where changed in BE (passed by reference)
	* @param string $table: the table the data will be stored in
	* @param integer $id: The uid of the dataset we're working on
	* @param object $pObj: The instance of the BE Form
	* @return void
	*
	* @author Sascha Egerer <info@sascha-egerer.de>
	*/
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$pObj) {
		$checkedAsins = array();

		/** @var language $language */
		$language = $GLOBALS['LANG'];

		foreach ($incomingFieldArray as $fieldName => $field) {

			if ($fieldName == 'pi_flexform') {
				$asinlist = $this->pi_getFFValue($incomingFieldArray['pi_flexform'], 'asinlist');
				$asinArray = t3lib_div::trimExplode(LF, $asinlist, TRUE);

					// check if the minimum amount of products for the ASINList is given
				if ($this->pi_getFFValue($incomingFieldArray['pi_flexform'], 'mode') == 'ASINList') {
					$amazonEcs = t3lib_div::makeInstance('tx_amazonaffiliate_amazonecs');

					if (count($asinArray) < $amazonEcs->getMinimumAsinlistCount()) {
						$pObj->log($table, $id, 5, 0, 1, 'You have to enter at least  %s products to the ASINList', 1, array($amazonEcs->getMinimumAsinlistCount()));

							// do not close the document even if the 'Save & Close' action is called
							// @TODO is there another way to not close the document?
						unset($_POST['_saveandclosedok_x']);
						$incomingFieldArray = array();
					}
				}
			} elseif ($table == 'tt_content' && $fieldName == 'tx_amazonaffiliate_amazon_asin') {
				$tempAsinArray = t3lib_div::trimExplode(LF, $field, TRUE);
					// remove typolink stuff
				$asinArray = array();
				foreach ($tempAsinArray as $asin) {
						// get only the first 10 characters of the string which should be the ASIN
					$asinArray[] = substr($asin, 0, 10);
				}
			} else {
				preg_match_all('/amazonaffiliate\:([a-z0-9]{10})/i', $field, $matches);
				$asinArray = $matches[1];
			}

			if (is_array($asinArray) && count($asinArray) > 0) {
				foreach ($asinArray as $asin) {
					if (!in_array($asin, $checkedAsins)) {
						$checkedAsins[] = $asin;

							// create a product instance wich checks if the product is valid
						$amazonProduct = t3lib_div::makeInstance('tx_amazonaffiliate_product', $asin);

							// if the status is false, the product is invalid wich means the Syntax of the entered
							// ASIN'n is not valid or it is not a valid Amazon product
						if ($amazonProduct->getStatus() == FALSE) {

							$fieldLabel = $language->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label']);
							if ($fieldLabel) {
								$fieldLabel = ' (Field \'' . rtrim($fieldLabel, ':') . '\')';
							}
							$pObj->log($table, $id, 5, 0, 1, 'The ASIN \'' . $asin . '\'' . $fieldLabel . ' is invalid. ' . $amazonProduct->getStatusMessage(), 1, array($asin));

								// do not close the document even if the 'Save & Close' action is called
								// @TODO is there another way to not close the document?
							unset($_POST['_saveandclosedok_x']);
							$incomingFieldArray = array();
						}
					}
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/hooks/class.tx_amazonaffiliate_dmhooks.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/hooks/class.tx_amazonaffiliate_dmhooks.php']);
}

?>