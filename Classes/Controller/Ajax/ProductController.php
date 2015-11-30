<?php
namespace Mfc\AmazonAffiliate\Controller\Ajax;

use Mfc\AmazonAffiliate\Domain\Repository\ProductRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataController
 * @package Mfc\AmazonAffiliate\Controller\Ajax
 */
class ProductController
{

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @return ProductController
     */
    public function __construct()
    {
        $this->productRepository = GeneralUtility::makeInstance(ProductRepository::class);
    }

    /**
     * @return string
     */
    public function listAction()
    {
        $draw = (int) GeneralUtility::_GP('draw');

        $flags = 0;
        if ((bool) GeneralUtility::_GP('active')) {
            $flags |= ProductRepository::ACTIVE;
        }

        if ((bool) GeneralUtility::_GP('invalid')) {
            $flags |= ProductRepository::INACTIVE;
        }

        $rows = $this->productRepository->findAll(
            $flags,
            GeneralUtility::_POST('length'),
            GeneralUtility::_POST('start')
        );

        foreach ($rows as $i => $row) {
            $rows[$i]['link'] = BackendUtility::getModuleUrl(
                'web_amazonaffiliate',
                [
                    'action' => 'showProductAction',
                    'asin' => trim($row['asin'])
                ],
                false,
                true
            );
        }

        header('Content-Type: application/json; charset=utf-8');
        die(json_encode([
            'draw' => $draw,
            'recordsTotal' => $this->productRepository->countAll(),
            'recordsFiltered' => count($rows),
            'data' => $rows
        ]));
    }

}
