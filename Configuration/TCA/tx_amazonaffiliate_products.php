<?php
defined('TYPO3_MODE') or die('Access denied.');

return call_user_func(function ($_EXTKEY) {

    $ll = 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:';

    return [
        'ctrl' => [
            'title' => 'LLL:EXT:amazon_affiliate/locallang_db.xml:tx_amazonaffiliate_products',
            'label' => 'uid',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'default_sortby' => 'ORDER BY crdate',
            'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'icon_tx_amazonaffiliate_products.gif',
        ],
        'interface' => [
            'showRecordFieldList' => 'name,status,asin',
        ],
        'columns' => [
            'name' => [
                'exclude' => 0,
                'label' => $ll . 'tx_amazonaffiliate_products.name',
                'config' => [
                    'type' => 'input',
                    'size' => '30',
                ],
            ],
            'status' => [
                'exclude' => 0,
                'label' => $ll . 'tx_amazonaffiliate_products.status',
                'config' => [
                    'type' => 'none',
                ],
            ],
            'asin' => [
                'exclude' => 0,
                'label' => $ll . 'tx_amazonaffiliate_products.asin',
                'config' => [
                    'type' => 'input',
                    'size' => '30',
                ],
            ],
        ],
        'types' => [
            '0' => ['showitem' => 'name;;;;1-1-1, status, asin'],
        ],
        'palettes' => [
            '1' => ['showitem' => ''],
        ],
    ];

}, 'amazon_affiliate');
