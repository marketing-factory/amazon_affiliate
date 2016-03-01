<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'piproducts/class.tx_amazonaffiliate_piproducts.php',
    '_piproducts',
    'list_type',
    1
);

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$_EXTKEY . '::product::list'] = [
    'callbackMethod' => \Mfc\AmazonAffiliate\Controller\Ajax\ProductController::class . '->listAction',
    'csrfTokenCheck' => true
];

// Hooks for datamap procesing
// For processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['amazon_affiliate']
    = \Mfc\AmazonAffiliate\Hook\DataHandlerHook::class;

// implement the ASIN-evaluation function for TCE forms
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['amazon_affiliate']
    = \Mfc\AmazonAffiliate\Hook\AsinEvaluatorHook::class;


// linkhandler hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['amazonaffiliate']
    = \Mfc\AmazonAffiliate\Hook\LinkHandlerHook::class;

// register the task for the scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Mfc\AmazonAffiliate\Scheduler\Task\UpdateStatusTask::class] = [
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:CheckAsin.name', // task title
    'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:CheckAsin.description', // task description
];

call_user_func(function () use ($_EXTKEY) {
    $autoload = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
        $_EXTKEY,
        'Resources/PHP/autoload.php'
    );

    if (file_exists($autoload)) {
        require_once $autoload;
    }
});
