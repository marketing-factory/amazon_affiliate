<?php
defined('TYPO3_MODE') or die();

call_user_func(function ($_EXTKEY) {

    $ll = 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:';

    $columns = [
        'tx_amazonaffiliate_amazon_asin' => [
            'exclude' => 0,
            'label' => $ll . 'tt_content.tx_amazonaffiliate_amazon_asin',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
                'softref' => 'typolink[linkList]',
            ],
        ],
        'tx_amazonaffiliate_image_caption' => [
            'exclude' => 0,
            'label' => $ll . 'tt_content.tx_amazonaffiliate_image_caption',
            'config' => [
                'type' => 'input',
                'max' => 100,
                'size' => 50,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'tt_content',
        $columns,
        true
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_amazonaffiliate_amazon_asin',
        '',
        'after:image'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_amazonaffiliate_image_caption',
        '',
        'after:tx_amazonaffiliate_amazon_asin'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_piproducts'] =
        'layout,select_key,pages';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_piproducts'] =
        'pi_flexform';

}, 'amazon_affiliate');
