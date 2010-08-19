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
 * @fileoverview Functions related to the search engine and search UI
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @package   Fisma
 */

Fisma.Search = function() {
    return {
        
        /**
         * True if the test configuration process is currently running
         */
        testConfigurationActive : false,

        /**
         * Test the current system configuration
         */
        
        testConfiguration : function () {
            
            if (Fisma.Search.testConfigurationActive) {
                return;
            }

            Fisma.Search.testConfigurationActive = true;

            var testConfigurationButton = document.getElementById('testConfiguration');
            testConfigurationButton.className = "yui-button yui-push-button yui-button-disabled";

            var spinner = new Fisma.Spinner(testConfigurationButton.parentNode);
            spinner.show();

            var form = document.getElementById('search_config');
            YAHOO.util.Connect.setForm(form);

            YAHOO.util.Connect.asyncRequest(
                'POST', 
                '/config/test-search/format/json', 
                {
                    success : function (o) {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;
                        
                        if (response.success) {
                            message("Search configuration is valid", "notice");
                        } else {
                            message(response.message, "warning");
                        }

                        testConfigurationButton.className = "yui-button yui-push-button";
                        Fisma.Search.testConfigurationActive = false;
                        spinner.hide();
                    },

                    failure : function (o) {
                        message('Error: ' + o.statusText, 'warning');

                        spinner.hide();
                    }
                }
            );            
        }
    }
}();
