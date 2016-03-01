/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 *  JavaScript for link handler
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function ($, LinkBrowser) {
    'use strict';

    var AmazonProductLinkHandler = {};

    AmazonProductLinkHandler.link = function (event) {
        event.preventDefault();

        var asin = $(this).find('[name="lasin"]').val();
        var hover = $('body').find('[name="lhover"]').is(':checked');

        if (asin === 'amazonaffiliate:') {
            return;
        }

        while (asin.substr(0, 16) === 'amazonaffiliate:') {
            asin = asin.substr(16);
        }

        if (hover) {
            asin += '|1';
        }

        LinkBrowser.finalizeFunction('amazonaffiliate:' + asin);
    }

    $(function () {
        $('#lasinform').on('submit', AmazonProductLinkHandler.link);
    });

    return AmazonProductLinkHandler;
});
