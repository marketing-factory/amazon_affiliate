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
 *  JavaScript for backend module
 */
define(['jquery', 'datatables'], function ($) {

    var Module = {
        dataTable: null,
        identifier: {
            checkbox: {
                invalid: '#invalid',
                active: '#active'
            },
            table: '#table',
        }
    };

    Module.initializeView = function () {
        this.dataTable = $(this.identifier.table).DataTable({
            serverSide: true,
            paging: false,
            lengthChange: false,
            stateSave: true,
            sDom: '<"top"i>rt<"bottom"lp><"clear">',
            ajax: {
                'type': 'POST',
                'url': TYPO3.settings.ajaxUrls['amazon_affiliate::product::list'],
                'data': (function (that) {
                    return function (data) {
                        data.invalid = $(that.identifier.checkbox.invalid).prop('checked') ? 1 : 0;
                        data.active = $(that.identifier.checkbox.active).prop('checked') ? 1 : 0;
                    }
                })(this)
            },

            columnDefs: [{
                targets: 'link',
                createdCell: function (td, cellData) {
                    $(td).html('<a class="btn btn-default" href="' + cellData + '"><span class="icon icon-size-small icon-state-default icon-actions-document-view"><span class="icon-markup"><span class="icon-unify"><i class="fa fa-desktop"></i></span></span></span></a>');
                }
            }],

            'columns': [
                {'name': 'uid', 'data': 'uid'},
                {'name': 'asin', 'data': 'asin'},
                {'name': 'name', 'data': 'name'},
                {'name': 'link', 'data': 'link'},
            ]
        });
    };

    $(document).ready(function () {
        Module.initializeView();

        $(Module.identifier.checkbox.invalid).on('change', (function (that) {
            return function () {
                if (this.checked) {
                    $(that.identifier.checkbox.active).attr('disabled', true);
                } else {
                    $(that.identifier.checkbox.active).removeAttr('disabled');
                }
                that.dataTable.ajax.reload();
            }
        })(Module));

        $(Module.identifier.checkbox.active).on('change', (function (that) {
            return function () {
                if (this.checked) {
                    $(that.identifier.checkbox.invalid).attr('disabled', true);
                } else {
                    $(that.identifier.checkbox.invalid).removeAttr('disabled');
                }

                that.dataTable.ajax.reload();
            }
        })(Module));
    });
});
