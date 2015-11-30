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
namespace Mfc\AmazonAffiliate\Hook;

use Mfc\AmazonAffiliate\Domain\Model\Product;
use Mfc\AmazonAffiliate\Service\AmazonEcsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LinkHandler
 * @package Mfc\AmazonAffiliate\Hooks
 */
class LinkHandlerHook
{

    /**
     * Linkhandler wich generates the amazon links
     *
     * @param string $linktxt
     * @param array $conf
     * @param string $linkHandlerKeyword
     * @param string $linkHandlerValue
     * @param string $link_param
     * @param object &$pObj
     * @return string
     */
    public function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj)
    {
        $value = GeneralUtility::trimExplode("|", $linkHandlerValue);

        if (AmazonEcsService::validateAsinSyntax($value[0])
            && $GLOBALS['TSFE']->config['config']['tx_amazonaffiliate_piproducts.']['renderProducts']
        ) {
            $product = GeneralUtility::makeInstance(Product::class, $value[0]);

            if ($product->getStatus()) {
                $link_paramA = GeneralUtility::unQuoteFilenames($link_param, true);

                $amazonHover = ($value[1] == '1' ? true : false);

                $generatedLink = AmazonEcsService::getAmazonLink(
                    $linktxt,
                    $conf,
                    $value[0],
                    $amazonHover,
                    trim($link_paramA[2]),
                    trim($link_paramA[1]),
                    trim($link_paramA[3]),
                    $link_paramA[4]
                );

                if ($generatedLink != '') {
                    $linktxt = $generatedLink;
                }
            }
        }

        return $linktxt;
    }

}
