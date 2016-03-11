<?php
if(!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$TCA['tx_amazonaffiliate_products'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tx_amazonaffiliate_products',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'tca.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'icon_tx_amazonaffiliate_products.gif',
	),
);


if(TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath('txamazonaffiliateM1', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/');

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txamazonaffiliateM1', '', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/amazon_products/', 'Amazon Products');



$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_piproducts'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_piproducts'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_piproducts', 'FILE:EXT:' . $_EXTKEY . '/flexform_ds_piproducts.xml');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array(
	'LLL:EXT:amazon_affiliate/locallang_db.xml:tt_content.list_type_piproducts',
	$_EXTKEY . '_piproducts',
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif'
), 'list_type');

if(TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_amazonaffiliate_piproducts_wizicon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'piproducts/class.tx_amazonaffiliate_piproducts_wizicon.php';
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
	'tx_amazonaffiliate_image_caption' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tt_content.tx_amazonaffiliate_image_caption',
			'config' => array(
				'type' => 'input',
				'max' => 100,
				'size' => 50,
			),
		),
);



\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content','tx_amazonaffiliate_amazon_asin','','after:image');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content','tx_amazonaffiliate_image_caption','','after:tx_amazonaffiliate_amazon_asin');

?>