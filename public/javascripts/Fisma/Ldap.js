/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @fileoverview Provides client-side behavior for the Incident Reporting module
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Ldap = {

    /**
     * A boolean flag which indicates if a validation request is current working
     */
    validateLdapBusy : false,

    /**
     * Uses an XHR to validate the user's current LDAP settings
     */    
    validateLdapConfiguration : function () {

        if (Fisma.Ldap.validateLdapBusy) {
            return;
        }
        
        Fisma.Ldap.validateLdapBusy = true;
        
        // Bit of a hack. Grab the id of the current ldap Config out of the URL
        var location = document.location;
        var pieces = document.location.pathname.split('/');
        var ldapConfigId = null;

        for (pieceIndex in pieces) {
            var piece = pieces[pieceIndex];

            if ('id' == piece) {
                ldapConfigId = pieces[parseInt(pieceIndex, 10) + 1];

                break;
            }
        }
        // End hack. @todo Modify controller so that it passes the id of the configuration to validate.
        
        var validateButton = document.getElementById('validateLdap');
        validateButton.className = "yui-button yui-push-button yui-button-disabled";

        var spinner = new Fisma.Spinner(validateButton.parentNode);
        spinner.show();

        var form = document.getElementById('ldapUpdate');
        YAHOO.util.Connect.setForm(form);
        YAHOO.util.Connect.asyncRequest(
            'POST', 
            '/config/validate-ldap/format/json/id/' + ldapConfigId, 
            {
                success : function (o) {
                    var response = YAHOO.lang.JSON.parse(o.responseText);
                    
                    message(response.msg, response.type, true);

                    validateButton.className = "yui-button yui-push-button";
                    Fisma.Ldap.validateLdapBusy = false;
                    spinner.hide();
                },

                failure : function (o) {
                    message('Validation failed: ' + o.statusText, 'warning', true);

                    spinner.hide();
                }
            }
        );
    }  
};
