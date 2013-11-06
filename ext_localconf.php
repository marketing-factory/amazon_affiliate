<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

/** @noinspection PhpUndefinedVariableInspection */
t3lib_extMgm::addPItoST43($_EXTKEY, 'piproducts/class.tx_amazonaffiliate_piproducts.php', '_piproducts', 'list_type', 1);

	// Hooks for datamap procesing
	// For processing the order sfe, when changing the pid
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
	'EXT:amazon_affiliate/hooks/class.tx_amazonaffiliate_dmhooks.php:tx_amazonaffiliate_dmhooks';

	// implement the ASIN-evaluation function for TCE forms
$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_amazonaffiliate_asineval'] =
	'EXT:amazon_affiliate/hooks/class.tx_amazonaffiliate_asineval.php';


	// linkhandler hooks
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['amazonaffiliate'] =
	'EXT:amazon_affiliate/hooks/class.tx_amazonaffiliate_linkhandler.php:tx_amazonaffiliate_linkhandler';
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] =
	'EXT:amazon_affiliate/hooks/class.tx_amazonaffiliate_browselinkshook.php:tx_amazonaffiliate_browselinkshook';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] =
	'EXT:amazon_affiliate/hooks/class.tx_amazonaffiliate_browselinkshook.php:tx_amazonaffiliate_browselinkshook';

	// register the task for the scheduler
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_amazonaffiliate_updatestatus'] = array(
	'extension' => $_EXTKEY,
		// task title
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:CheckAsin.name',
		// task description
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:CheckAsin.description',
);

?>