/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
 *
 * @fileoverview Used to show or hide a waiting spinner animation following the element.
 *
 * @author    Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

Fisma.WaitingSpinner = function() {
    return {
        animation : "/images/spinners/small.gif",
        container : null,
        element   : null,
        spinner   : null,
        
        init: function(el, container) {
            if (Fisma.WaitingSpinner.spinner != null ) {
                return;
            }
            
            Fisma.WaitingSpinner.element = el;
            Fisma.WaitingSpinner.container = container;
            
            // Create a div container to hold the animation.
            var img = document.createElement('img');
            img.id = 'Spinner_' + el.id;
            img.src = Fisma.WaitingSpinner.animation;
            img.className = 'spinner';
            img.style.display = 'none';
            
            Fisma.WaitingSpinner.container.appendChild(img);
            Fisma.WaitingSpinner.spinner = img;
        },
        show: function() {
            if (Fisma.WaitingSpinner.spinner.style.display == 'none') {
                Fisma.WaitingSpinner.spinner.style.display = 'inline';
            }
            Fisma.WaitingSpinner.element.disabled = true;
            Fisma.WaitingSpinner.container.disabled = true;
        },
        hide: function() {
            if (Fisma.WaitingSpinner.spinner.style.display == 'inline') {
                Fisma.WaitingSpinner.spinner.style.display = 'none';
            }
            Fisma.WaitingSpinner.element.disabled = false;
            Fisma.WaitingSpinner.container.disabled = false;
        },
        destroy: function() {
            Fisma.WaitingSpinner.hide();
            Fisma.WaitingSpinner.container.removeChild(Fisma.WaitingSpinner.spinner);
            Fisma.WaitingSpinner.spinner = null;
        },
        isWorking: function() {
            if (Fisma.WaitingSpinner.spinner != null && Fisma.WaitingSpinner.spinner.style.display != 'none') {
                return ture;
            }
            return false;
        }
    };
}();
