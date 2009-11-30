/*****************************************************************************
 *
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
 ******************************************************************************
 *
 * Helper function for the on-line help feature in OpenFISMA
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: $
 *
 ******************************************************************************
 */

var helpPanels = new Array();
function showHelp(event, helpModule) {
    if (helpPanels[helpModule]) {
        helpPanels[helpModule].show();
    } else {
        // Create new panel
        var newPanel = new YAHOO.widget.Panel('helpPanel', {width:"400px"} );
        newPanel.setHeader("Help");
        newPanel.setBody("Loading...");
        newPanel.render(document.body);
        newPanel.center();
        newPanel.show();
        
        // Load the help content for this module
        YAHOO.util.Connect.asyncRequest('GET', 
                                        '/help/help/module/' + helpModule, 
                                        {
                                            success: function(o) {
                                                // Set the content of the panel to the text of the help module
                                                o.argument.setBody(o.responseText);
                                                // Re-center the panel (because the content has changed)
                                                o.argument.center();
                                            },
                                            failure: function(o) {alert('Failed to load the help module.');},
                                            argument: newPanel
                                        }, 
                                        null);
        
        // Store this panel to be re-used on subsequent calls
        helpPanels[helpModule] = newPanel;
    }
}
