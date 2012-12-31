/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 *
 * Helper functions for rendering criteria
 *
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

FSC = {
    legendHandler: function(inputElement) {
        var $input = $(inputElement),
            $criterion = $(inputElement).parents('fieldset.criterion').first(),
            $header = $criterion.find('legend.header'),
            $content = $criterion.find('div.content'),
            checked = $input.is('input:checked');
        if (checked) {
            $header
                .addClass('ui-accordion-header-active')
                .removeClass('ui-corner-all')
                .addClass('ui-corner-top');
            $content
                .addClass('ui-accordion-content-active');
        } else {
            $header
                .removeClass('ui-accordion-header-active')
                .addClass('ui-corner-all')
                .removeClass('ui-corner-top');
            $content
                .removeClass('ui-accordion-content-active');
        }
    }
};
Fisma.Search.Criterion = FSC;
