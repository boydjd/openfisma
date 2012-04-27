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
 */

/**
 * The constructor for a spinner object
 *
 * A spinner is a simple graphic that is used to indicate a "busy" or "working" state in response to some user action
 * that does not have any other immediate, visual feedback.
 *
 * @var container The spinner will be appended to the end of this container element.
 */
Fisma.Spinner = function (container) {
    this.container = container;

    // Create new image element to act as spinner
    this.spinner = document.createElement('img');
    this.spinner.id = container.id + "_spinnerImg";
    this.spinner.src = "/images/spinners/small.gif";
    this.spinner.style.visibility = "hidden";

    // Append spinner to end of container element
    this.container.appendChild(this.spinner);
};

Fisma.Spinner.prototype.show = function () {
    this.spinner.style.visibility = 'visible';
};

Fisma.Spinner.prototype.hide = function () {
    this.spinner.style.visibility = 'hidden';
};
