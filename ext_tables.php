<?php
defined('TYPO3_MODE') or die('Access denied.');

if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'amazonaffiliate',
        '',
        '',
        [
            'name' => 'web_amazonaffiliate',
            'access' => 'user,group',
            'routeTarget' => \Mfc\AmazonAffiliate\Controller\Backend\ModuleController::class . '::processRequest',
            'labels' => [
                'll_ref' => 'LLL:EXT:amazon_affiliate/Resources/Private/Language/Backend/locallang_mod.xml',
                'tabs_images' => [
                    'tab' => 'EXT:amazon_affiliate/Resources/Public/Icon/Backend/Module.svg'
                ],
            ],
        ]
    );

    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_amazonaffiliate_piproducts_wizicon'] =
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
                $_EXTKEY,
                'piproducts/class.tx_amazonaffiliate_piproducts_wizicon.php'
            );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tt_content',
        'EXT:amazon_affiliate/Resources/Private/Language/locallang_csh_ttcontent.xlf'
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript/',
    'Amazon Products'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_piproducts',
    'FILE:EXT:' . $_EXTKEY . '/flexform_ds_piproducts.xml'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:amazon_affiliate/locallang_db.xml:tt_content.list_type_piproducts',
        $_EXTKEY . '_piproducts',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif',
    ],
    'list_type'
);
