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
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */




/**
 * Class that adds the wizard icon.
 *
 * @author	Sascha Egerer <info@sascha-egerer.de>
 * @package	TYPO3
 * @subpackage	tx_amazonaffiliate
 */
class tx_amazonaffiliate_piproducts_wizicon {

	/**
	 * Processing the wizard items array
	 *
	 * @param	array $wizardItems: The wizard items
	 * @return	array $wizardItemsModified array with wizard items
	 */
	function proc($wizardItems)	{
		global $LANG;

		$LL = $this->includeLocalLang();

		$wizardItems['plugins_tx_amazonaffiliate_piproducts'] = array(
			'icon'=>\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('amazon_affiliate').'piproducts/ce_wiz.gif',
			'title'=>$LANG->getLLL('piproducts_title',$LL),
			'description'=>$LANG->getLLL('piproducts_plus_wiz_description',$LL),
			'params'=>'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=amazon_affiliate_piproducts'
		);

		return $wizardItems;
	}

	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	string The array with language labels
	 */
	function includeLocalLang()	{
		$llFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('amazon_affiliate') . 'locallang.xml';
		/** @var \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser $llXmlParser */
		$llXmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangXmlParser');
		$localLang = $llXmlParser->getParsedData($llFile, $GLOBALS['LANG']->lang);

		return $localLang;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/piproducts/class.tx_amazonaffiliate_piproducts_wizicon.php'])	{
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/piproducts/class.tx_amazonaffiliate_piproducts_wizicon.php']);
}

?>