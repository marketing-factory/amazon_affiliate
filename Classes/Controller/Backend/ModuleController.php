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
namespace Mfc\AmazonAffiliate\Controller\Backend;

use Mfc\AmazonAffiliate\Domain\Repository\ContentRepository;
use Mfc\AmazonAffiliate\Domain\Repository\ProductRepository;
use Mfc\AmazonAffiliate\Scheduler\Task\UpdateStatusTask;
use Mfc\AmazonAffiliate\Service\AmazonEcsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class ModuleController
 * @package Mfc\AmazonAffiliate\Controller\Backend
 */
class ModuleController extends AbstractModule
{
    /**
     * @var string
     */
    const EXTKEY = 'amazon_affiliate';

    /**
     * @var
     */
    public $pageinfo;

    /**
     * @var AmazonEcsService
     */
    public $amazonEcs;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ContentRepository
     */
    protected $contentRepository;

    /**
     * @return void
     */
    protected function init()
    {
        // Locallang
        $this->getLanguageService()->includeLLFile('EXT:' . static::EXTKEY . '/Resources/Private/Language/Backend/locallang.xml');

        // Module Header
        $this->makeParameterMenu();
    }

    /**
     * @param $templateName
     * @retun void
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    protected function initializeView($templateName)
    {
        $privateResourcesPath = ExtensionManagementUtility::extPath(
            static::EXTKEY,
            implode(
                DIRECTORY_SEPARATOR,
                [
                    'Resources',
                    'Private',
                ]
            )
        );

        // Initialize view
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setLayoutRootPaths([
            10 => $privateResourcesPath . DIRECTORY_SEPARATOR . 'Layouts',
        ]);
        $this->view->setTemplateRootPaths([
            10 => $privateResourcesPath . DIRECTORY_SEPARATOR . 'Templates',
        ]);
        $this->view->setPartialRootPaths([
            10 => $privateResourcesPath . DIRECTORY_SEPARATOR . 'Partials',
        ]);
        $this->view->setTemplate($templateName);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequest $request, Response $response)
    {
        $this->init();
        $this->initializeView('Backend/Index');


        $this->moduleTemplate->setContent($this->view->render());
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/AmazonAffiliate/Backend/List');

        $response->getBody()->write($this->moduleTemplate->renderContent());

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function listInvalidWidgetsAction(ServerRequest $request, Response $response)
    {
        $this->init();
        $this->initializeView('Backend/ListInvalidWidgets');

        $this->amazonEcs = GeneralUtility::makeInstance(AmazonEcsService::class);

        $asinRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, pi_flexform',
            'tt_content',
            'pi_flexform LIKE "%asinlist%"' .
            'AND pi_flexform LIKE "%<value index=\"vDEF\">ASINList</value>%"' .
            BackendUtility::deleteClause('tt_content')
        );

        $invalidWidgets = $validProducts = [];

        foreach ($asinRecords as $asinRecord) {
            $mode = $this->amazonEcs->piObj->pi_getFFvalue(
                GeneralUtility::xml2array($asinRecord['pi_flexform']),
                'mode'
            );

            if ($mode !== 'ASINList') {
                continue;
            }

            $widgetIsValid = true;
            $asinArray = GeneralUtility::trimExplode(
                LF,
                $this->amazonEcs->piObj->pi_getFFvalue(
                    GeneralUtility::xml2array($asinRecord['pi_flexform']),
                    'asinlist'
                ),
                true
            );

            foreach ($asinArray as $asin) {
                $product = GeneralUtility::makeInstance(
                    'tx_amazonaffiliate_product',
                    $asin,
                    true
                );
                if ($product->getStatus() == true) {
                    $validProducts[] = $asin;
                } else {
                    $widgetIsValid = false;
                    break;
                }
            }

            if (count($validProducts) < $this->amazonEcs->getMinimumAsinlistCount()) {
                $widgetIsValid = false;
            }

            if (!$widgetIsValid) {
                $url = BackendUtility::getModuleUrl(
                    'record_edit',
                    [
                        'edit[tt_content][' . $asinRecord['uid'] . ']' => 'edit',
                        'returnUrl' => BackendUtility::getModuleUrl(
                            'web_amazonaffiliate',
                            [
                                'action' => $this->getCurrentAction()
                            ]
                        ),
                    ]
                );

                $invalidWidgets[] = [
                    'uid' => $asinRecord['uid'],
                    'edit_url' => $url,
                ];
            }
        }

        $this->view->assign('title', $this->getLanguageService()->getLL('invalidWidgets'));
        $this->view->assign('noInvalidWidgetsFoundMessage', $this->getLanguageService()->getLL('noInvalidWidgetsFound'));
        $this->view->assign('label', $this->getLanguageService()->sL($GLOBALS['TCA']['tt_content']['ctrl']['title']));
        $this->view->assign('invalidWidgets', $invalidWidgets);

        $this->moduleTemplate->setContent($this->view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return Response
     */
    public function showProductAction(ServerRequest $request, Response $response)
    {
        $this->init();
        $this->initializeView('Backend/ShowProduct');

        $this->contentRepository = GeneralUtility::makeInstance(ContentRepository::class);

        /** @var UpdateStatusTask $schedulerTask  */
        $schedulerTask = GeneralUtility::makeInstance(UpdateStatusTask::class);


        $rows = [];
        $asin = GeneralUtility::_GET('asin');

        $records = $schedulerTask->find('amazonaffiliate:' . $asin);
        if (is_array($records)) {
            foreach ($records as $record) {
                $rows[] = [
                    'tablename' => $record['__database_table'],
                    'uid' => $record['uid'],
                ];
            }
        }

        $records = $this->contentRepository->findUidsByAsin($asin);
        if (is_array($records)) {
            foreach ($records as $record) {
                $rows[] = [
                    'tablename' => 'tt_content',
                    'uid' => $record['uid'],
                ];
            }
        }

        foreach ($rows as $key => $row) {
            $rows[$key]['edit_url'] = BackendUtility::getModuleUrl(
                'record_edit',
                [
                    'edit[' . $row['tablename'] . '][' . $row['uid'] . ']' => 'edit',
                    'returnUrl' => BackendUtility::getModuleUrl(
                        'web_amazonaffiliate',
                        [
                            'action' => $this->getCurrentAction(),
                            'asin' => GeneralUtility::_GET('asin')
                        ]
                    ),
                ]
            );
        }

        $this->view->assign('asin', $asin);
        $this->view->assign('rows', $rows);

        $this->moduleTemplate->setContent($this->view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());

        return $response;
    }

    /**
     * @return void
     *
     * @throws \InvalidArgumentException In case of invalid menuItems
     */
    protected function makeParameterMenu()
    {
        $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $menu = $menuRegistry->makeMenu()->setIdentifier('ActionMenu');

        $items = [];
        $items[] = $menu
            ->makeMenuItem()
            ->setHref(BackendUtility::getModuleUrl('web_amazonaffiliate', ['action' => 'index']))
            ->setTitle($this->getLanguageService()->getLL('showAll'));

        $items[] = $menu
            ->makeMenuItem()
            ->setHref(BackendUtility::getModuleUrl('web_amazonaffiliate', ['action' => 'listInvalidWidgetsAction']))
            ->setTitle($this->getLanguageService()->getLL('showInvalidWidgets'))
            ->setActive($this->getCurrentAction() === 'listInvalidWidgetsAction');

        foreach ($items as $item) {
            $menu->addMenuItem($item);
        }

        $menuRegistry->addMenu($menu);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return string
     */
    protected function getCurrentAction()
    {
        return GeneralUtility::_GP('action') ?: 'index';
    }
}
