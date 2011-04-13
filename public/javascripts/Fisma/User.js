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
 * @fileoverview Client-side code for various operations on user objects
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id: AttachArtifacts.js 3188 2010-04-08 19:35:38Z mhaase $
 */
 
Fisma.User = {
    
    /**
     * A dictionary of user info panels that have already been created.
     * 
     * We use this to make sure that we don't create multiple panels for the same user object.
     */
    userInfoPanelList : {},
    
    /**
     * A boolean which indicates if a password is currently being generated
     */
    generatePasswordBusy : false,

    /**
     * A boolean which indicates if an account is currently being checked in LDAP
     */
    checkAccountBusy : false,
    
    /**
     * Display a dialog which shows user information for the specified user.
     * 
     * @param referenceElement The panel will be displayed near this element
     * @param username The name of the user to get info for
     */
    displayUserInfo : function (referenceElement, username) {

        var panel;

        if (typeof Fisma.User.userInfoPanelList[username] == 'undefined') {

            // Create new panel
            panel = Fisma.User.createUserInfoPanel(referenceElement, username);

            Fisma.User.userInfoPanelList[username] = panel;
            
            panel.show();            
        } else {

            // Panel already exists
            panel = Fisma.User.userInfoPanelList[username];
            
            // If panel is hidden then display it, or if its already visible, then hide it.
            if (panel.cfg.getProperty("visible")) {
                panel.hide();
            } else {
                panel.bringToTop();
                panel.show();            
            }
        }        
    },
    
    /**
     * Create the user info panel and position it near the referenceElement
     * 
     * @param referenceElement
     * @param username The name of the user to get info for
     * @return YAHOO.widget.Panel
     */
    createUserInfoPanel : function (referenceElement, username) {
        
        var PANEL_WIDTH = 350; // in pixels
        var panelName, panel;
        
        // Create the new panel object
        panelName = username + 'InfoPanel';
        
        panel = new YAHOO.widget.Panel(
            panelName, 
            {
                width: PANEL_WIDTH + 'px', 
                modal : false, 
                close : true,
                constraintoviewport : true
            }
        );

        panel.setHeader('User Profile');
        panel.setBody("Loading user profile for <em>" + username + "</em>...");
        panel.render(document.body);

        Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);
        
        // Load panel content using asynchronous request
        YAHOO.util.Connect.asyncRequest(
            'GET', 
            '/user/info/username/' + escape(username),
            {
                success: function(o) {
                    panel.setBody(o.responseText);
                    Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);
                },

                failure: function(o) {
                    panel.setBody('User information cannot be loaded.');
                    Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);
                }
            }, 
            null);

        return panel;
    },
    
    generatePassword : function () {
        
        if (Fisma.User.generatePasswordBusy) {
            return true;
        }

        Fisma.User.generatePasswordBusy = true;

        var generatePasswordButton = document.getElementById('generate_password');
        generatePasswordButton.className = "yui-button yui-push-button yui-button-disabled";

        var spinner = new Fisma.Spinner(generatePasswordButton.parentNode);
        spinner.show();

        YAHOO.util.Connect.asyncRequest(
            'GET',
            '/user/generate-password/format/html',
            {
                success : function (o) {
                    document.getElementById('password').value = o.responseText;
                    document.getElementById('confirmPassword').value = o.responseText;

                    Fisma.User.generatePasswordBusy = false;
                    generatePasswordButton.className = "yui-button yui-push-button";
                    spinner.hide();
                },

                failure : function (o) {
                    spinner.hide();

                    alert('Failed to generate password: ' + o.statusText);
                }
            },
            null);

        return false;
    },

    checkAccount : function () {

        if (Fisma.User.checkAccountBusy) {
            return;
        }

        Fisma.User.checkAccountBusy = true;

        var account = document.getElementById('username').value;
        var url = "/user/check-account/format/json/account/" + encodeURIComponent(account);

        var checkAccountButton = document.getElementById('checkAccount');
        checkAccountButton.className = "yui-button yui-push-button yui-button-disabled";

        var spinner = new Fisma.Spinner(checkAccountButton.parentNode);
        spinner.show();

        YAHOO.util.Connect.asyncRequest(
            'GET',
            url,
            {
                success : function (o) {
                    var data = YAHOO.lang.JSON.parse(o.responseText);
                    message(data.msg, data.type, true);

                    // Openfisma column's name is corresponding to LDAP account column's name
                    var openfismaColumns = new Array('nameFirst',
                                                     'nameLast',
                                                     'phoneOffice',
                                                     'phoneMobile',
                                                     'email',
                                                     'title');

                    // LDAP account column's name
                    var ldapColumns = new Array('givenname',
                                                'sn',
                                                'telephonenumber',
                                                'mobile',
                                                'mail',
                                                'title');

                    // Make sure each column value is not null in LDAP account, then populate to related elements.
                    if (data.accountInfo !== null) {
                        for (var i in ldapColumns) {
                            if (!ldapColumns.hasOwnProperty(i)) {
                                continue;
                            }

                            var columnValue = data.accountInfo[ldapColumns[i]];

                            if (columnValue !== null) {
                                document.getElementById(openfismaColumns[i]).value = columnValue;
                            } else {
                                document.getElementById(openfismaColumns[i]).value = '';
                            }
                        }
                    }

                    Fisma.User.checkAccountBusy = false;
                    checkAccountButton.className = "yui-button yui-push-button";
                    spinner.hide();
                },

                failure : function(o) {
                    spinner.hide();

                    alert('Failed to check account password: ' + o.statusText);
                }
            },
            null);
    },

    /**
     * Show the comment panel
     * 
     * @return void
     */
    showCommentPanel : function () {
        var lockedElement = YAHOO.util.Dom.get('locked');

        // Only show panel in locked status
        if (lockedElement === null || parseInt(lockedElement.value, 10) === 0) {
            YAHOO.util.Dom.getAncestorByTagName('save-button', 'form').submit();
            return false;
        }

        // Create a panel
        var content = document.createElement('div');
        var p = document.createElement('p');
        var contentTitle = document.createTextNode('Comments (OPTIONAL):');
        p.appendChild(contentTitle);
        content.appendChild(p);

        // Add comment textarea to panel
        var commentTextArea = document.createElement('textarea');
        commentTextArea.id = 'commentTextArea';
        commentTextArea.name = 'commentTextArea';
        commentTextArea.rows = 5;
        commentTextArea.cols = 60;
        content.appendChild(commentTextArea);

        // Add line spacing to panel
        var lineSpacingDiv = document.createElement('div');
        lineSpacingDiv.style.height = '10px';
        content.appendChild(lineSpacingDiv);

        // Add submmit button to panel
        var continueButton = document.createElement('input');
        continueButton.type = 'button';
        continueButton.id = 'continueButton';
        continueButton.value = 'continue';
        content.appendChild(continueButton);

        Fisma.HtmlPanel.showPanel('Add Comment', content.innerHTML);

        YAHOO.util.Dom.get('continueButton').onclick = Fisma.User.submitUserForm;
        return true;
    },

    /*
     * Submit user form after assign comment value to comment element
     */
    submitUserForm : function () {

        // Get commentTextArea value from panel and assign its value to comment element
        var commentElement = YAHOO.util.Dom.get('commentTextArea').value;
        YAHOO.util.Dom.get('comment').value = commentElement;
        var form = YAHOO.util.Dom.getAncestorByTagName('save-button', 'form');
        form.submit();
    }
};
