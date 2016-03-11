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


$LANG->includeLLFile('EXT:amazon_affiliate/mod1/locallang.xml');
$BE_USER->modAccess($MCONF, 1); // This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('amazon_affiliate') . 'lib/class.tx_amazonaffiliate_product.php');

/**
 * Module 'Amazon Products' for the 'amazon_affiliate' extension.
 *
 * @author	Sascha Egerer <info@sascha-egerer.de>
 * @package	TYPO3
 * @subpackage	tx_amazonaffiliate
 */
class  tx_amazonaffiliate_module1 extends \TYPO3\CMS\Backend\Module\BaseScriptClass {
	var $pageinfo;

	public $amazonEcs;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init() {
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig() {
		global $LANG;
		$this->MOD_MENU = Array(
			'function' => Array(
				'1' => $LANG->getLL('showInactive'),
				'2' => $LANG->getLL('showActive'),
				'3' => $LANG->getLL('showAll'),
				'4' => $LANG->getLL('showInvalidWidgets'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return void [type]        ...
	 */
	function main() {
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		// Draw the header.
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('bigDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';

		// JavaScript
		$this->doc->JScode = '
							<script language="javascript" type="text/javascript">
								script_ended = 0;
								function jumpToUrl(URL)	{
									document.location = URL;
								}
							</script>
						';
		$this->doc->postCode = '
							<script language="javascript" type="text/javascript">
								script_ended = 1;
								if (top.fsMod) top.fsMod.recentIds["web"] = 0;
							</script>
						';

		$this->doc->inDocStylesArray['tx_amazonaffiliate_mod1'] = '
				#typo3-page-stdlist td {padding:2px;}
			';

		$headerSection = "";
		$this->content .= $this->doc->startPage($LANG->getLL('title'));
		$this->content .= $this->doc->header($LANG->getLL('title'));
		$this->content .= $this->doc->spacer(5);
		$this->content .= $this->doc->section('', $this->doc->funcMenu($headerSection, \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
		$this->content .= $this->doc->divider(5);


		// Render content:
		$this->moduleContent();


		// ShortCut
		if($BE_USER->mayMakeShortcut()) {
			$this->content .= $this->doc->spacer(20) . $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
		}

		$this->content .= $this->doc->spacer(10);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent() {

		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {

		$this->amazonEcs = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_amazonecs');


		if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showAsin') && preg_match('/^[a-z0-9]{10}$/i', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showAsin'))) {
			$outputString = '<a href="' . \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array("showAsin" => '')) . '">&lt; zur&uuml;ck</a><br />';

			// find all records with the given ASIN

			$recordUidList = array();

			/** @var $tx_amazonaffiliate_updatestatus tx_amazonaffiliate_updatestatus */
			$tx_amazonaffiliate_updatestatus = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_updatestatus');

			$records = $tx_amazonaffiliate_updatestatus->find("amazonaffiliate:" . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showAsin'));

			if(is_array($records)) {
				foreach($records as $record) {
					$recordUidList[] = array(
						'tablename' => $record['__database_table'],
						'uid' => $record['uid']
					);
				}
			}

			/**
			 * get all products from the tt_content image records
			 */
			$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid',
				'tt_content',
					'(tx_amazonaffiliate_amazon_asin = "' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showAsin') .
						'" OR pi_flexform LIKE "%' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showAsin') . '%")' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content')
			);

			if(is_array($records)) {
				foreach($records as $record) {
					$recordUidList[] = array(
						'tablename' => 'tt_content',
						'uid' => $record['uid']
					);
				}
			}

			foreach($recordUidList as $record) {

				$content = "<br />" . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$record['tablename']]['ctrl']['title']) . ": UID " . $record['uid'];
				//	$record
				$content .= ' <a href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
					rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("REQUEST_URI")) . '&edit[' .
					$record['tablename'] . '][' . $record['uid'] . ']=edit' . '" style="font-weight:bold">edit</a>';

				$outputString .= $content;
			}

		} else {

			// show invalid widgets
			if($this->MOD_SETTINGS['function'] == 4) {

				//get all widget records
				$asinRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pi_flexform',
					'tt_content',
					'pi_flexform LIKE "%asinlist%"' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content')
				);

				$invalidWidgets = array();

				foreach($asinRecords as $asinRecord) {
					$mode = $this->amazonEcs->piObj->pi_getFFvalue(\TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($asinRecord['pi_flexform']), 'mode');
					if($mode == 'ASINList') {
						$widgetIsValid = true;
						$asinArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $this->amazonEcs->piObj->pi_getFFvalue(\TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($asinRecord['pi_flexform']), 'asinlist'), true);

						foreach($asinArray as $asin) {
							$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_product', $asin, true);
							if($product->getStatus() == true) {
								$validProducts[] = $asin;
							} else {
								$widgetIsValid = false;
								break;
							}
						}

						if(count($validProducts) < $this->amazonEcs->getMinimumAsinlistCount()) {
							$widgetIsValid = false;
						}

						if(!$widgetIsValid) {
							$invalidWidgets[] = $asinRecord['uid'];
						}
					}
				}

				$outputString = '<h3>' . $GLOBALS['LANG']->getLL('invalidWidgets') . '</h3>';
				if(count($invalidWidgets) > 0) {

					foreach($invalidWidgets as $recordUid) {

						$content = "<br />" . $GLOBALS['LANG']->sL($GLOBALS['TCA']['tt_content']['ctrl']['title']) . ": UID " . $recordUid;
						//	$record
						$content .= ' <a href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
							rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("REQUEST_URI")) . '&edit[tt_content][' . $recordUid . ']=edit' .
							'" style="font-weight:bold">edit</a>';

						$outputString .= $content;
					}
				} else {
					$outputString = $GLOBALS['LANG']->getLL('noInvalidWidgetsFound');//'Es wurden keine ungültigen Widgets gefunden.';
				}
			} else {

				switch($this->MOD_SETTINGS['function']) {
					case 1:
						$where = "status = 0";
						break;
					case 2:
						$where = "status = 1";
						break;
					default:
						$where = "";
						break;
				}


				$asinRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('asin',
					'tx_amazonaffiliate_products',
					$where
				);


				$tRows = array();
				$tRows[] = '
					<tr>
						<td class="c-headLine" width="30"><strong>' . $GLOBALS['LANG']->getLL('uid') . '</strong></td>
						<td class="c-headLine" width="80"><strong>' . $GLOBALS['LANG']->getLL('asin') . '</strong></td>
						<td class="c-headLine"><strong>' . $GLOBALS['LANG']->getLL('name') . '</strong></td>
						<td class="c-headLine">Details</td>
					</tr>';

				foreach($asinRecords as $asinRecord) {

					/**
					 * @var $product tx_amazonaffiliate_product
					 */
					$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_product', $asinRecord['asin'], true);

					$linkparams = array('showAsin' => $product->getAsin());

					$tRows[] = '
						<tr class="bgColor4">
							<td>' . htmlspecialchars($product->getUid()) . '</td>
							<td>' . htmlspecialchars($product->getAsin()) . '</td>
							<td>' . htmlspecialchars($product->getName()) . '</td>
							<td><a href="' . \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript($linkparams) . '">Details</a></td>
						</tr>';
				}
				// Create overview
				$outputString = '<table border="0" cellpadding="3" cellspacing="2" id="typo3-page-stdlist" width="100%">' . implode('', $tRows) . '</table>';
			}
		}

		// Add output:
		$this->content .= $outputString;
	}

}


if(defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/mod1/index.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/mod1/index.php']);
}


// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_amazonaffiliate_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>