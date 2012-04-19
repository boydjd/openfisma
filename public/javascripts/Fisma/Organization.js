/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Provides organization related functionality
     *
     * @namespace Fisma
     * @class Organization
     */
    var Organization = {
        /**
         * Organization type filter callback function
         * It set the default organization type, store the selected organization type and refresh window with url 
         *
         * @method Organization.typeHandle
         * @param event {String} The name of the event
         * @param config {Array} An array of YAHOO.util.Event
         */
        typeHandle : function (event, config) {
            // Set the selected organization type
            var organizationTypeFilter = YAHOO.util.Dom.get('orgTypeFilter');
            var selectedType = organizationTypeFilter.options[organizationTypeFilter.selectedIndex];

            // Store the selected organizationTypeId to storage table
            var orgTypeStorage = new Fisma.PersistentStorage(config.namespace);
            orgTypeStorage.set('orgType', selectedType.value); 
            orgTypeStorage.sync();

            Fisma.Storage.onReady(function() {
                // Construct the url and refresh the result after a user changes organization type
                if (!YAHOO.lang.isUndefined(config) && config.url) {
                    var url = config.url + '?orgTypeId=' + encodeURIComponent(selectedType.value);
                    window.location.href = url;
                }
            });
        }
    };

    Fisma.Organization = Organization;
}());
