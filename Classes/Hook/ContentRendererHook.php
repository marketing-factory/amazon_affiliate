<?php
namespace Mfc\AmazonAffiliate\Hook;

use Mfc\AmazonAffiliate\Domain\Model\Product;
use Mfc\AmazonAffiliate\Service\AmazonEcsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\CssStyledContent\Controller\CssStyledContentController;

/**
 * Class ContentRenderer
 * @package Mfc\AmazonAffiliate\Hooks
 */
class ContentRendererHook extends CssStyledContentController
{

    /**
     * A modified version of the tx_cssstyledcontent_pi1->render_textpic function
     *
     * @param $content
     * @param $conf
     * @return mixed|string
     */
    public function render_textpic($content, $conf)
    {
        $amazonImgList = trim($this->cObj->stdWrap($conf['amazonImgList'], $conf['amazonImgList.']));

        if ($amazonImgList == '') {
            $content = parent::render_textpic($content, $conf);
        } elseif ($GLOBALS['TSFE']->config['config']['tx_amazonaffiliate_piproducts.']['renderProducts']) {

            /**
             * ###################################################################
             * THIS IS A COPY OF tx_cssstyledcontent_pi1->render_textpic
             * EACH CHANGE IS COMMENTED WITH A "#amazon_affilate Change - start#" (or end) COMMENT
             * ###################################################################
             */

            unset($GLOBALS['TSFE']->lastImageInfo);

            // #amazon_affilate Change - start#
            $amazonEcs = GeneralUtility::makeInstance(AmazonEcsService::class);
            // create an array form the amazonImgList
            $amazonProducts = [];
            $amazonProductsTempArray = GeneralUtility::trimExplode(LF, $amazonImgList);
            foreach ($amazonProductsTempArray as $amazonProduct) {
                if (GeneralUtility::isFirstPartOfStr($amazonProduct, "amazonaffiliate:")) {
                    $amazonProduct = substr($amazonProduct, strlen("amazonaffiliate:"));
                }
                $amazonProduct = GeneralUtility::trimExplode("|", $amazonProduct);
                if (AmazonEcsService::validateAsinSyntax($amazonProduct[0])) {
                    $amazonProducts[] = [
                        'asin' => $amazonProduct[0],
                        'hover' => $amazonProduct[1],
                    ];
                }
            }
            unset($amazonProduct);

            $renderMethod = $this->cObj->stdWrap($conf['renderMethod'], $conf['renderMethod.']);

            //manipulate caption for amazon image
            unset($conf['caption.']['1.']['1.']['data']);
            $conf['caption.']['1.']['1.']['field'] = 'tx_amazonaffiliate_image_caption';

            // Render using the default IMGTEXT code (table-based)
            if (!$renderMethod || $renderMethod == 'table') {
                return $this->cObj->IMGTEXT($conf);
            }

            // Specific configuration for the chosen rendering method
            if (is_array($conf['rendering.'][$renderMethod . '.'])) {
                $conf = array_replace_recursive($conf, $conf['rendering.'][$renderMethod . '.']);
            }

            // Image or Text with Image?
            if (is_array($conf['text.'])) {
                $content = $this->cObj->stdWrap($this->cObj->cObjGet($conf['text.'], 'text.'), $conf['text.']);
            }

            if (count($amazonProducts) < 1) {
                if (is_array($conf['stdWrap.'])) {
                    if (is_array($conf['stdWrap.'])) {
                        return $this->cObj->stdWrap($content, $conf['stdWrap.']);
                    }

                    return $content;
                }
            } else {
                $amazonEcs->addHoverJavascript();
            }

            $imgStart = intval($this->cObj->stdWrap($conf['imgStart'], $conf['imgStart.']));
            $imgCount = count($amazonProducts) - $imgStart;

            // Does we need to render a "global caption" (below the whole image block)?
            $renderGlobalCaption = !$conf['captionSplit'] && !$conf['imageTextSplit'] && is_array($conf['caption.']);
            if ($imgCount == 1) {
                // If we just have one image, the caption relates to the image, so it is not "global"
                $renderGlobalCaption = false;
            }

            // Use the calculated information (amount of images, if global caption is
            // wanted) to choose a different rendering method for the images-block
            $GLOBALS['TSFE']->register['imageCount'] = $imgCount;
            $GLOBALS['TSFE']->register['renderGlobalCaption'] = $renderGlobalCaption;
            $fallbackRenderMethod = $this->cObj->cObjGetSingle($conf['fallbackRendering'], $conf['fallbackRendering.']);
            if ($fallbackRenderMethod && is_array($conf['rendering.'][$fallbackRenderMethod . '.'])) {
                $conf = array_replace_recursive($conf, $conf['rendering.'][$fallbackRenderMethod . '.']);
            }

            // Global caption
            $globalCaption = '';
            if ($renderGlobalCaption) {
                $globalCaption = $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'),
                    $conf['caption.']);
            }

            // Positioning
            $position = $this->cObj->stdWrap($conf['textPos'], $conf['textPos.']);

            // 0,1,2 = center,right,left
            $imagePosition = $position & 7;
            // 0,8,16,24 (above,below,intext,intext-wrap)
            $contentPosition = $position & 24;

            $textMargin = intval($this->cObj->stdWrap($conf['textMargin'], $conf['textMargin.']));
            if (!$conf['textMargin_outOfText'] && $contentPosition < 16) {
                $textMargin = 0;
            }

            $colspacing = intval($this->cObj->stdWrap($conf['colSpace'], $conf['colSpace.']));

            $border = intval($this->cObj->stdWrap($conf['border'], $conf['border.'])) ? 1 : 0;
            $borderThickness = intval($this->cObj->stdWrap($conf['borderThick'], $conf['borderThick.']));
            $borderThickness = $borderThickness ? $borderThickness : 1;
            $borderSpace = (($conf['borderSpace'] && $border) ? intval($conf['borderSpace']) : 0);

            // Generate cols
            $cols = intval($this->cObj->stdWrap($conf['cols'], $conf['cols.']));
            $colCount = ($cols > 1) ? $cols : 1;
            if ($colCount > $imgCount) {
                $colCount = $imgCount;
            }
            $rowCount = ceil($imgCount / $colCount);

            // Generate rows
            $rows = intval($this->cObj->stdWrap($conf['rows'], $conf['rows.']));
            if ($rows > 1) {
                $rowCount = $rows;
                if ($rowCount > $imgCount) {
                    $rowCount = $imgCount;
                }
                $colCount = ($rowCount > 1) ?
                    ceil($imgCount / $rowCount) :
                    $imgCount;
            }

            // Max Width
            $maxW = intval($this->cObj->stdWrap($conf['maxW'], $conf['maxW.']));

            if ($contentPosition >= 16) { // in Text
                $maxWInText = intval($this->cObj->stdWrap($conf['maxWInText'], $conf['maxWInText.']));
                if (!$maxWInText) {
                    // If maxWInText is not set, it's calculated to the 50% of the max
                    $maxW = round($maxW / 100 * 50);
                } else {
                    $maxW = $maxWInText;
                }
            }

            // max usuable width for images (without spacers and borders)
            $netW = $maxW - $colspacing * ($colCount - 1) - $colCount * $border * ($borderThickness + $borderSpace) * 2;

            // Specify the maximum width for each column
            $columnWidths = $this->getImgColumnWidths($conf, $colCount, $netW);
            // EqualHeight
            $equalHeight = intval($this->cObj->stdWrap($conf['equalH'], $conf['equalH.']));
            if ($equalHeight) {
                $relations_cols = [];
                $imgWidths = []; // contains the individual width of all images after scaling to $equalHeight
                for ($a = 0; $a < $imgCount; $a++) {
                    $imgKey = $a + $imgStart;
                    //$imgInfo = $gifCreator->getImageDimensions($imgPath . $imgs[$imgKey]);
                    $amazonProduct = GeneralUtility::makeInstance(Product::class, $amazonProducts[$imgKey]['asin']);
                    $imgInfo = [
                        0 => $amazonProduct->getItemAttribute("LargeImage.Width._"),
                        1 => $amazonProduct->getItemAttribute("LargeImage.Height._"),
                    ];
                    $rel = $imgInfo[1] / $equalHeight; // relationship between the original height and the wished height
                    if ($rel) { // if relations is zero, then the addition of this value is omitted as the image is not expected to display because of some error.
                        $imgWidths[$a] = $imgInfo[0] / $rel;
                        $relations_cols[floor($a / $colCount)] += $imgWidths[$a]; // counts the total width of the row with the new height taken into consideration.
                    }
                }
            }

            $imageRowsFinalWidths = []; // contains the width of every image row
            $imgsTag = []; // array index of $imgsTag will be the same as in $imgs, but $imgsTag only contains the images that are actually shown
            $origImages = [];
            $rowIdx = 0;
            $sumOfAllCols = 0;

            for ($a = 0; $a < $imgCount; $a++) {
                $imgKey = $a + $imgStart;

                $GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgKey; // register IMG_NUM is kept for backwards compatibility
                $GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgKey;

                if ($equalHeight) {

                    if ($a % $colCount == 0) {
                        // a new row startsS
                        $accumWidth = 0; // reset accumulated net width
                        $accumDesiredWidth = 0; // reset accumulated desired width
                        $rowTotalMaxW = $relations_cols[$rowIdx];
                        if ($rowTotalMaxW > $netW) {
                            $scale = $rowTotalMaxW / $netW;
                        } else {
                            $scale = 1;
                        }
                        $desiredHeight = $equalHeight / $scale;
                        $rowIdx++;
                    }

                    $availableWidth = $netW - $accumWidth; // this much width is available for the remaining images in this row (int)
                    $desiredWidth = $imgWidths[$a] / $scale; // theoretical width of resized image. (float)
                    $accumDesiredWidth += $desiredWidth; // add this width. $accumDesiredWidth becomes the desired horizontal position
                    $suggestedWidth = round($accumDesiredWidth - $accumWidth);
                    $finalImgWidth = (int)min($availableWidth,
                        $suggestedWidth); // finalImgWidth may not exceed $availableWidth
                    $accumWidth += $finalImgWidth;
                    $imgConf['file.']['width'] = $finalImgWidth;
                    $imgConf['file.']['height'] = round($desiredHeight);

                    // other stuff will be calculated accordingly:
                    unset($imgConf['file.']['maxW']);
                    unset($imgConf['file.']['maxH']);
                    unset($imgConf['file.']['minW']);
                    unset($imgConf['file.']['minH']);
                    unset($imgConf['file.']['width.']);
                    unset($imgConf['file.']['maxW.']);
                    unset($imgConf['file.']['maxH.']);
                    unset($imgConf['file.']['minW.']);
                    unset($imgConf['file.']['minH.']);
                } else {
                    $imgConf['file.']['maxW'] = $columnWidths[($a % $colCount)];
                }


                $titleInLink = $this->cObj->stdWrap($imgConf['titleInLink'], $imgConf['titleInLink.']);
                if ($titleInLink) {
                    // Title in A-tag instead of IMG-tag
                    $titleText = trim($this->cObj->stdWrap($imgConf['titleText'], $imgConf['titleText.']));
                    if ($titleText) {
                        // This will be used by the IMAGE call later:
                        $GLOBALS['TSFE']->ATagParams .= ' title="' . $titleText . '"';
                    }
                }
                if ($cols == 1) {
                    if ($this->cObj->data['imagewidth'] > 0) {
                        $imageRowsFinalWidths[floor($a % $colCount)] = $this->cObj->data['imagewidth'];
                    } elseif (isset($imgConf['file.']['width'])) {
                        $imageRowsFinalWidths[floor($a % $colCount)] = $imgConf['file.']['width'];
                    } else {
                        $amazonProduct = GeneralUtility::makeInstance(Product::class, $amazonProducts[$a]['asin']);
                        $minWith = ($netW > $amazonProduct->getItemAttribute("LargeImage.Width._")) ? $amazonProduct->getItemAttribute("LargeImage.Width._") : $netW;
                        $imageRowsFinalWidths[floor($a % $colCount)] = ($minWith / $colCount) - 15;
                    }
                } else {
                    if ($this->cObj->data['imagewidth'] > 0) {
                        $imageRowsFinalWidths[floor($a % $colCount)] += $this->cObj->data['imagewidth'];
                    } elseif (isset($imgConf['file.']['width'])) {
                        $imageRowsFinalWidths[floor($a % $colCount)] += $imgConf['file.']['width'];
                    } else {
                        $amazonProduct = GeneralUtility::makeInstance(Product::class, $amazonProducts[$a]['asin']);
                        $minWith = ($netW > $amazonProduct->getItemAttribute("LargeImage.Width._")) ? $amazonProduct->getItemAttribute("LargeImage.Width._") : $netW;
                        $imageRowsFinalWidths[floor($a % $colCount)] += ($minWith / $colCount) - 15;
                    }
                }

                if ($cols == 1) {

                    $sumOfAllCols = ($sumOfAllCols > $imageRowsFinalWidths[floor($a % $colCount)]) ? $sumOfAllCols : ($imageRowsFinalWidths[floor($a % $colCount)] + 10);
                } else {
                    $sumOfAllCols += $imageRowsFinalWidths[floor($a % $colCount)] + 10;
                }


                // add the amazon image tags
                $imgsTag[$imgKey] = $amazonEcs->getAmazonImageOnlyCode($amazonProducts[$a]['asin'],
                    $imageRowsFinalWidths[floor($a % $colCount)], $imgConf['file.']['height'],
                    $amazonProducts[$a]['hover']);
                // #amazon_affilate Change - end#

            }
            // How much space will the image-block occupy?
            $imageBlockWidth = $sumOfAllCols + $colspacing * ($colCount - 1) + $colCount * $border * ($borderSpace + $borderThickness) * 2;
            $GLOBALS['TSFE']->register['rowwidth'] = $imageBlockWidth;
            $GLOBALS['TSFE']->register['rowWidthPlusTextMargin'] = $imageBlockWidth + $textMargin;

            // noRows is in fact just one ROW, with the amount of columns specified, where the images are placed in.
            // noCols is just one COLUMN, each images placed side by side on each row
            $noRows = $this->cObj->stdWrap($conf['noRows'], $conf['noRows.']);
            $noCols = $this->cObj->stdWrap($conf['noCols'], $conf['noCols.']);
            if ($noRows) {
                $noCols = 0;
            } // noRows overrides noCols. They cannot exist at the same time.

            if ($noRows) {
                $rowCount = 1;
            }
            if ($noCols) {
                $colCount = 1;
                $columnWidths = [];
            }

            // Edit icons:
            if (!is_array($conf['editIcons.'])) {
                $conf['editIcons.'] = [];
            }
            $editIconsHTML = $conf['editIcons'] && $GLOBALS['TSFE']->beUserLogin ?
                $this->cObj->editIcons('', $conf['editIcons'], $conf['editIcons.']) :
                '';

            // If noRows, we need multiple imagecolumn wraps
            $imageWrapCols = 1;
            if ($noRows) {
                $imageWrapCols = $colCount;
            }

            // User wants to separate the rows, but only do that if we do have rows
            $separateRows = $this->cObj->stdWrap($conf['separateRows'], $conf['separateRows.']);
            if ($noRows) {
                $separateRows = 0;
            }
            if ($rowCount == 1) {
                $separateRows = 0;
            }

            // Render the images
            $images = '';
            for ($c = 0; $c < $imageWrapCols; $c++) {
                $tmpColspacing = $colspacing;
                if (($c == $imageWrapCols - 1 && $imagePosition == 2) || ($c == 0 && ($imagePosition == 1 || $imagePosition == 0))) {
                    // Do not add spacing after column if we are first column (left) or last column (center/right)
                    $tmpColspacing = 0;
                }

                $firstColClass = '';
                if ($c == 0 && ($imagePosition == 1 || $imagePosition == 0)) {
                    $firstColClass = 'csc-textpic-firstcol';
                } else {
                    $firstColClass = '';
                }

                $thisImages = '';
                $allRows = '';
                $maxImageSpace = 0;
                for ($i = $c; $i < count($imgsTag); $i = $i + $imageWrapCols) {
                    $imgKey = $i + $imgStart;
                    $colPos = $i % $colCount;
                    if ($separateRows && $colPos == 0) {
                        $thisRow = '';
                    }

                    // Render one image
                    if ($origImages[$imgKey][0] == 0) {
                        $imageSpace = $imageRowsFinalWidths[floor($i % $colCount)] + $border * ($borderSpace + $borderThickness) * 2;
                    } else {
                        $imageSpace = $origImages[$imgKey][0] + $border * ($borderSpace + $borderThickness) * 2;
                    }

                    $GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgKey;
                    $GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgKey;
                    $GLOBALS['TSFE']->register['ORIG_FILENAME'] = $origImages[$imgKey]['origFile'];
                    $GLOBALS['TSFE']->register['imagewidth'] = $origImages[$imgKey][0];
                    $GLOBALS['TSFE']->register['imagespace'] = $imageSpace;
                    $GLOBALS['TSFE']->register['imageheight'] = $origImages[$imgKey][1];
                    if ($imageSpace > $maxImageSpace) {
                        $maxImageSpace = $imageSpace;
                    }
                    $thisImage = '';
                    $thisImage .= $this->cObj->stdWrap($imgsTag[$imgKey], $conf['imgTagStdWrap.']);

                    if (!$renderGlobalCaption) {
                        $thisImage .= $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'),
                            $conf['caption.']);
                    }

                    if ($editIconsHTML) {
                        $thisImage .= $this->cObj->stdWrap($editIconsHTML, $conf['editIconsStdWrap.']);
                    }
                    $thisImage = $this->cObj->stdWrap($thisImage, $conf['oneImageStdWrap.']);

                    $singleImageClass = '';
                    $singleImageClass .= ($firstColClass ? ' ' . $firstColClass : '');

                    $thisImage = str_replace('###CLASSES###', $singleImageClass, $thisImage);
                    if ($colCount > 1) {
                        $firstColClass = '';
                    }
                    unset($singleImageClass);

                    if ($separateRows) {
                        $thisRow .= $thisImage;
                    } else {
                        $allRows .= $thisImage;
                    }
                    $GLOBALS['TSFE']->register['columnwidth'] = $maxImageSpace + $tmpColspacing;


                    // Close this row at the end (colCount), or the last row at the final end
                    if ($separateRows && ($i + 1 == count($imgsTag))) {
                        // Close the very last row with either normal configuration or lastRow stdWrap
                        $allRows .= $this->cObj->stdWrap($thisRow, (is_array($conf['imageLastRowStdWrap.']) ?
                            $conf['imageLastRowStdWrap.'] :
                            $conf['imageRowStdWrap.']));
                    } elseif ($separateRows && $colPos == $colCount - 1) {
                        $allRows .= $this->cObj->stdWrap($thisRow, $conf['imageRowStdWrap.']);
                    }
                }
                if ($separateRows) {
                    $thisImages .= $allRows;
                } else {
                    $thisImages .= $this->cObj->stdWrap($allRows, $conf['noRowsStdWrap.']);
                }
                if ($noRows) {
                    // Only needed to make columns, rather than rows:
                    $images .= $this->cObj->stdWrap($thisImages, $conf['imageColumnStdWrap.']);
                } else {
                    $images .= $thisImages;
                }
            }

            // Add the global caption, if not split
            if ($globalCaption) {
                $images .= $globalCaption;
            }

            // CSS-classes
            $captionClass = '';
            $classCaptionAlign = [
                'center' => 'csc-textpic-caption-c',
                'right' => 'csc-textpic-caption-r',
                'left' => 'csc-textpic-caption-l',
            ];
            $captionAlign = $this->cObj->stdWrap($conf['captionAlign'], $conf['captionAlign.']);
            if ($captionAlign) {
                $captionClass = $classCaptionAlign[$captionAlign];
            }
            $borderClass = '';
            if ($border) {
                $borderClass = $conf['borderClass'] ?
                    $conf['borderClass'] :
                    'csc-textpic-border';
            }


            // Multiple classes with all properties, to be styled in CSS
            $class = '';
            $class .= ($borderClass ?
                ' ' . $borderClass :
                '');
            $class .= ($captionClass ?
                ' ' . $captionClass :
                '');
            $class .= ($equalHeight ?
                ' csc-textpic-equalheight' :
                '');
            $addClasses = $this->cObj->stdWrap($conf['addClasses'], $conf['addClasses.']);
            $class .= ($addClasses ?
                ' ' . $addClasses :
                '');

            // Do we need a width in our wrap around images?
            $imgWrapWidth = '';
            if ($position == 0 || $position == 8) {
                // For 'center' we always need a width: without one, the margin:auto trick won't work
                $imgWrapWidth = $imageBlockWidth;
            }
            if ($rowCount > 1) {
                // For multiple rows we also need a width, so that the images will wrap
                $imgWrapWidth = $imageBlockWidth;
            }

            // Wrap around the whole image block
            $GLOBALS['TSFE']->register['totalwidth'] = $imgWrapWidth;
            if ($imgWrapWidth) {
                $images = $this->cObj->stdWrap($images, $conf['imageStdWrap.']);
            } else {
                $images = $this->cObj->stdWrap($images, $conf['imageStdWrapNoWidth.']);
            }

            $output = $this->cObj->cObjGetSingle($conf['layout'], $conf['layout.']);
            $output = str_replace('###TEXT###', $content, $output);
            $output = str_replace('###IMAGES###', $images, $output);
            $output = str_replace('###CLASSES###', $class, $output);

            if ($conf['stdWrap.']) {
                $output = $this->cObj->stdWrap($output, $conf['stdWrap.']);
            }

            $content = $output;
        }

        return $content;
    }

}
