<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

/** @noinspection PhpUndefinedVariableInspection */
$TCA['tx_amazonaffiliate_products'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tx_amazonaffiliate_products',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_amazonaffiliate_products.gif',
	),
);


if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModulePath('txamazonaffiliateM1', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');

	t3lib_extMgm::addModule('txamazonaffiliateM1', '', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/amazon_products/', 'Amazon Products');


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_piproducts'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_piproducts'] = 'pi_flexform';

t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_piproducts', 'FILE:EXT:' . $_EXTKEY . '/flexform_ds_piproducts.xml');


t3lib_extMgm::addPlugin(array(
	'LLL:EXT:amazon_affiliate/locallang_db.xml:tt_content.list_type_piproducts',
	$_EXTKEY . '_piproducts',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
), 'list_type');

if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_amazonaffiliate_piproducts_wizicon'] = t3lib_extMgm::extPath($_EXTKEY) . 'piproducts/class.tx_amazonaffiliate_piproducts_wizicon.php';
}

$tempColumns = array(
	'tx_amazonaffiliate_amazon_asin' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tt_content.tx_amazonaffiliate_amazon_asin',
		'config' => array(
			'type' => 'text',
			'cols' => '30',
			'rows' => '3',
			'wizards' => array(
				'_PADDING' => 2,
				'link' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:cms/locallang_ttc.xml:image_link_formlabel',
					'icon' => 'link_popup.gif',
					'script' => 'browse_links.php?mode=wizard',
					'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
				),
			),
			'softref' => 'typolink[linkList]',
		),
	),
);


t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('tt_content', 'tx_amazonaffiliate_amazon_asin', '', 'after:image');

?>