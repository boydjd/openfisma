/* Helper function for the on-line help feature in OpenFISMA */
var helpPanels = new Array();
function showHelp(event, helpModule) {
    if (helpPanels[helpModule]) {
        helpPanels[helpModule].show();
    } else {
        alert('create panel');
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
