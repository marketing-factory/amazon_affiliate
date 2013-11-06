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

/** @var language $language */
$language = $GLOBALS['LANG'];
$language->includeLLFile('EXT:amazon_affiliate/mod1/locallang.xml');
	// This checks permissions and exits if the users has no permission for entry.
/** @var t3lib_beUserAuth $backendUser */
$backendUser = $GLOBALS['BE_USER'];
/** @noinspection PhpUndefinedVariableInspection */
$backendUser->modAccess($MCONF, 1);
	// DEFAULT initialization of a module [END]


/**
 * Module 'Amazon Products' for the 'amazon_affiliate' extension.
 *
 * @author	Sascha Egerer <info@sascha-egerer.de>
 * @package	TYPO3
 * @subpackage	tx_amazonaffiliate
 */
class  tx_amazonaffiliate_module1 extends t3lib_SCbase {
	/**
	 * @var
	 */
	public $pageinfo;

	/**
	 * @var tx_amazonaffiliate_amazonecs
	 */
	public $amazonEcs;

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	public function menuConfig() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		$this->MOD_MENU = Array(
			'function' => Array(
				'1' => $language->getLL('showInactive'),
				'2' => $language->getLL('showActive'),
				'3' => $language->getLL('showAll'),
				'4' => $language->getLL('showInvalidWidgets'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose 'web' as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return void
	 */
	public function main() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $beUser */
		$beUser = $GLOBALS['BE_USER'];

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';

			// JavaScript
		$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL) {
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

		$headerSection = '';
		$this->content .= $this->doc->startPage($language->getLL('title'));
		$this->content .= $this->doc->header($language->getLL('title'));
		$this->content .= $this->doc->spacer(5);
		$this->content .= $this->doc->section('', $this->doc->funcMenu($headerSection, t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
		$this->content .= $this->doc->divider(5);

			// Render content:
		$this->moduleContent();

			// ShortCut
		if ($beUser->mayMakeShortcut()) {
			$this->content .= $this->doc->spacer(20) . $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
		}

		$this->content .= $this->doc->spacer(10);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	public function moduleContent() {
		$this->amazonEcs = t3lib_div::makeInstance('tx_amazonaffiliate_amazonecs');
			/** @var $databaseHandle t3lib_DB */
		$databaseHandle = $GLOBALS['TYPO3_DB'];
			/** @var language $language */
		$language = $GLOBALS['LANG'];

		if (t3lib_div::_GP('showAsin') && preg_match('/^[a-z0-9]{10}$/i', t3lib_div::_GP('showAsin'))) {
			$outputString = '<a href="' . t3lib_div::linkThisScript(array('showAsin' => '')) . '">&lt; zur&uuml;ck</a><br />';

				// find all records with the given ASIN
			$recordUidList = array();

			/** @var $tx_amazonaffiliate_updatestatus tx_amazonaffiliate_updatestatus */
			$tx_amazonaffiliate_updatestatus = t3lib_div::makeInstance('tx_amazonaffiliate_updatestatus');

			$records = $tx_amazonaffiliate_updatestatus->find('amazonaffiliate:' . t3lib_div::_GP('showAsin'));

			if (is_array($records)) {
				foreach ($records as $record) {
					$recordUidList[] = array(
						'tablename' => $record['__database_table'],
						'uid' => $record['uid']
					);
				}
			}

			/**
			 * get all products from the tt_content image records
			 */
			$records = $databaseHandle->exec_SELECTgetRows('uid',
				'tt_content',
					'(tx_amazonaffiliate_amazon_asin = "' . t3lib_div::_GP('showAsin') .
						'" OR pi_flexform LIKE "%' . t3lib_div::_GP('showAsin') . '%")' . t3lib_befunc::deleteClause('tt_content')
			);

			if (is_array($records)) {
				foreach ($records as $record) {
					$recordUidList[] = array(
						'tablename' => 'tt_content',
						'uid' => $record['uid']
					);
				}
			}

			foreach ($recordUidList as $record) {
				$content = '<br />' . $language->sL($GLOBALS['TCA'][$record['tablename']]['ctrl']['title']) . ': UID ' . $record['uid'];
				$content .= ' <a href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
					rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '&edit[' .
					$record['tablename'] . '][' . $record['uid'] . ']=edit" style="font-weight:bold">edit</a>';

				$outputString .= $content;
			}

		} else {
			$validProducts = array();
				// show invalid widgets
			if ($this->MOD_SETTINGS['function'] == 4) {

					// get all widget records
				$asinRecords = $databaseHandle->exec_SELECTgetRows('uid,pi_flexform',
					'tt_content',
					'pi_flexform LIKE "%asinlist%"' . t3lib_befunc::deleteClause('tt_content')
				);

				$invalidWidgets = array();

				foreach ($asinRecords as $asinRecord) {
					$mode = $this->amazonEcs->piObj->pi_getFFvalue(t3lib_div::xml2array($asinRecord['pi_flexform']), 'mode');
					if ($mode == 'ASINList') {
						$widgetIsValid = TRUE;
						$asinArray = t3lib_div::trimExplode(
							LF,
							$this->amazonEcs->piObj->pi_getFFvalue(t3lib_div::xml2array($asinRecord['pi_flexform']), 'asinlist'),
							TRUE
						);

						foreach ($asinArray as $asin) {
							$product = t3lib_div::makeInstance('tx_amazonaffiliate_product', $asin, TRUE);
							if ($product->getStatus() == TRUE) {
								$validProducts[] = $asin;
							} else {
								$widgetIsValid = FALSE;
								break;
							}
						}

						if (count($validProducts) < $this->amazonEcs->getMinimumAsinlistCount()) {
							$widgetIsValid = FALSE;
						}

						if (!$widgetIsValid) {
							$invalidWidgets[] = $asinRecord['uid'];
						}
					}
				}

				$outputString = '<h3>' . $language->getLL('invalidWidgets') . '</h3>';
				if (count($invalidWidgets) > 0) {
					foreach ($invalidWidgets as $recordUid) {
						$content = '<br />' . $language->sL($GLOBALS['TCA']['tt_content']['ctrl']['title']) . ': UID ' . $recordUid;

						$content .= ' <a href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
							rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '&edit[tt_content][' . $recordUid . ']=edit' .
							'" style="font-weight:bold">edit</a>';

						$outputString .= $content;
					}
				} else {
						// 'Es wurden keine ungültigen Widgets gefunden.';
					$outputString = $language->getLL('noInvalidWidgetsFound');
				}
			} else {

				switch ($this->MOD_SETTINGS['function']) {
					case 1:
						$where = 'status = 0';
						break;
					case 2:
						$where = 'status = 1';
						break;
					default:
						$where = '';
						break;
				}

				$asinRecords = $databaseHandle->exec_SELECTgetRows('asin',
					'tx_amazonaffiliate_products',
					$where
				);

				$tRows = array();
				$tRows[] = '
					<tr>
						<td class="c-headLine" width="30"><strong>' . $language->getLL('uid') . '</strong></td>
						<td class="c-headLine" width="80"><strong>' . $language->getLL('asin') . '</strong></td>
						<td class="c-headLine"><strong>' . $language->getLL('name') . '</strong></td>
						<td class="c-headLine">Details</td>
					</tr>';

				foreach ($asinRecords as $asinRecord) {
					/**
					 * @var $product tx_amazonaffiliate_product
					 */
					$product = t3lib_div::makeInstance('tx_amazonaffiliate_product', $asinRecord['asin'], TRUE);

					$linkparams = array('showAsin' => $product->getAsin());

					$tRows[] = '
						<tr class="bgColor4">
							<td>' . htmlspecialchars($product->getUid()) . '</td>
							<td>' . htmlspecialchars($product->getAsin()) . '</td>
							<td>' . htmlspecialchars($product->getName()) . '</td>
							<td><a href="' . t3lib_div::linkThisScript($linkparams) . '">Details</a></td>
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/mod1/index.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/amazon_affiliate/mod1/index.php']);
}

	// Make instance:
/** @var tx_amazonaffiliate_module1 $SOBE */
$SOBE = t3lib_div::makeInstance('tx_amazonaffiliate_module1');
$SOBE->init();

	// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	/** @noinspection PhpIncludeInspection */
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>