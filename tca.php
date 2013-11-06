<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TCA']['tx_amazonaffiliate_products'] = array (
	'ctrl' => $GLOBALS['TCA']['tx_amazonaffiliate_products']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'name,status,asin'
	),
	'feInterface' => $GLOBALS['TCA']['tx_amazonaffiliate_products']['feInterface'],
	'columns' => array (
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tx_amazonaffiliate_products.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'status' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tx_amazonaffiliate_products.status',
			'config' => array (
				'type' => 'none',
			)
		),
		'asin' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tx_amazonaffiliate_products.asin',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'name;;;;1-1-1, status, asin')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

?>