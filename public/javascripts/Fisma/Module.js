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
 * @fileoverview Client-side handlers for asynchronously modifying the state of OpenFISMA modules
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Module = {
    handleSwitchButtonStateChange : function (switchButton) {
        
        switchButton.setBusy(true);
        
        var enabled = switchButton.state ? 'true' : 'false';
        
        var requestUrl = '/config/set-module/id/' + switchButton.payload.id + '/enabled/' + enabled + '/format/json/';
        
        YAHOO.util.Connect.asyncRequest(
            'GET', 
            requestUrl,
            {
                success : Fisma.Module.handleAsyncResponse,
                failure : Fisma.Module.handleAsyncResponse,
                argument : switchButton
            }, 
            null);
    },
    
    /**
     * Handle an asynchronous response to a module enable/disable
     */
    handleAsyncResponse : function (response) {

        try {
            var responseStatus = YAHOO.lang.JSON.parse(response.responseText).response;
        } catch (e) {
            if (e instanceof SyntaxError) {
                // Handle a JSON syntax error by constructing a fake response object
                responseStatus = new Object();
                responseStatus.success = false;
                responseStatus.message = "Invalid response from server.";
            } else {
                throw e;
            }
        }
        
        if (!responseStatus.success) {
            alert('Error: Not able to change module status. Reason: ' + responseStatus.message);
        }
        
        // Disable switch button spinner
        var switchButton = response.argument;        
        switchButton.setBusy(false);
    }
};
