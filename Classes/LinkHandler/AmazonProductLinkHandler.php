<?php
namespace Mfc\AmazonAffiliate\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;

/**
 * Class AmazonProductLinkHandler
 * @package Mfc\AmazonAffiliate\LinkHandler
 */
class AmazonProductLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface
{

    /**
     * @var bool
     */
    protected $updateSupported = false;

    /**
     * @var array
     */
    protected $linkParts = [];

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (!$linkParts['url']) {
            return false;
        }

        if (strpos($linkParts['url'], 'amazonaffiliate:') !== false) {
            $this->linkParts = $linkParts;
            return true;
        }

        return false;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        if (isset($this->linkParts['url'])) {
            return $this->linkParts['url'];
        }

        return '';
    }

    /**
     * Render the link handler
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule(
            'TYPO3/CMS/AmazonAffiliate/Recordlist/AmazonProductLinkHandler'
        );

        /** @var StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:amazon_affiliate/Resources/Private/Templates/Recordlist/AmazonProductLinkHandler.html'
        ));

        $matches = [];
        if (preg_match('#^amazonaffiliate:(?P<asin>\w{1,10})(?P<hover>\|1)?#', $this->linkParts['url'], $matches) !== false) {
            if (isset($matches['asin'])) {
                $standaloneView->assign('asin', $matches['asin']);
            }

            if (isset($matches['hover'])) {
                $standaloneView->assign('hover', true);
            }
        }

        return $standaloneView->render();
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        return [];
    }

}
