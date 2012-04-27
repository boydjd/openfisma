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
     * A reference to a YUI table which contains comments for the current page
     * 
     * This reference will be set when the page loads by the script which initializes the table
     */
    commentTable : null,

    /**
     * Map LDAP column names onto names of fields in this form
     *
     * ldap name => field name
     */
    ldapColumnMap : {
        'givenname' : 'nameFirst',
        'mail' : 'email',
        'mobile' : 'phoneMobile',
        'samaccountname' : 'username',
        'sn' : 'nameLast',
        'telephonenumber' : 'phoneOffice',
        'title' : 'title',
        'uid' : 'username'
    },

    /**
     * Handle successful comment events by inserting the latest comment into the top of the comment table
     * 
     * @param comment An object containing the comment record values
     * @param yuiPanel A reference to the modal YUI dialog
     */
    commentCallback : function (comment, yuiPanel) {
        var that = this;

        var commentRow = {
            timestamp : comment.createdTs,
            username : comment.username,
            comment : comment.comment
        };

        this.commentTable = Fisma.Registry.get('comments');

        this.commentTable.addRow(commentRow);

        /*
         * Redo the sort. If the user had some other sort applied, then our element might be inserted in
         * the wrong place and the sort would be wrong.
         */
        this.commentTable.sortColumn(this.commentTable.getColumn(0), YAHOO.widget.DataTable.CLASS_DESC);

        // Highlight the added row so the user can see that it worked
        var rowBlinker = new Fisma.Blinker(
            100,
            6,
            function () {
                that.commentTable.highlightRow(0);
            },
            function () {
                that.commentTable.unhighlightRow(0);
            }
        );

        rowBlinker.start();

        // Hide YUI dialog
        yuiPanel.hide();
        yuiPanel.destroy();
    },

    /**
     * Display a dialog which shows user information for the specified user.
     * 
     * @param referenceElement The panel will be displayed near this element
     * @param username The name of the user to get info for
     */
    displayUserInfo : function (referenceElement, username) {

        var panel;

        if (typeof Fisma.User.userInfoPanelList[username] === 'undefined') {

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
        var panel = new YAHOO.widget.Panel(
            YAHOO.util.Dom.generateId(), 
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
            '/user/info/username/' + encodeURI(username),
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

                    var alertMessage = 'Failed to generate password: ' + o.statusText;
                    Fisma.Util.showAlertDialog(alertMessage);
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
                    try {
                        var data = YAHOO.lang.JSON.parse(o.responseText);

                        // Query comes originally from the user. Escape it just to be safe.
                        data.query = encodeURI(data.query);

                        // Make sure each column value is not null in LDAP account, then populate to related elements.
                        if (YAHOO.lang.isValue(data.accounts)) {
                            if (data.accounts.length === 0) {
                                Fisma.Util.message('No account matches your query: '
                                    + encodeURI(data.query) + '.', 'warning', true);
                            } else if (data.accounts.length === 1) {
                                Fisma.User.populateAccountForm(data.accounts[0]);
                            } else {
                                Fisma.User.showMultipleAccounts(data.accounts);
                            }
                        } else {
                            Fisma.Util.message(data.msg, data.type, true);
                        }
                    } catch (e) {
                        if (YAHOO.lang.isValue(e.message)) {
                            Fisma.Util.message('Error: ' + e.message, 'warning', true);
                        } else {
                            Fisma.Util.message('An unknown error occurred.', 'warning', true);
                        }
                    }

                    Fisma.User.checkAccountBusy = false;
                    checkAccountButton.className = "yui-button yui-push-button";
                    spinner.hide();
                },

                failure : function(o) {
                    Fisma.User.checkAccountBusy = false;
                    checkAccountButton.className = "yui-button yui-push-button";
                    spinner.hide();

                    var alertMessage = "Failed to check account password: " + o.statusText;
                    Fisma.Util.showAlertDialog(alertMessage);
                }
            },
            null);
    },

    /**
     * Fill in the account info for one user and display a success message
     * 
     * @param account {Object} A dictionary of LDAP data for an account.
     */
    populateAccountForm : function (account) {
        Fisma.Util.message('Your search matched one user: ' + account.dn, 'info', true);

        var ldapColumn;
        for (ldapColumn in Fisma.User.ldapColumnMap) {
            if (!Fisma.User.ldapColumnMap.hasOwnProperty(ldapColumn)) {
                continue;
            }

            var fieldName = Fisma.User.ldapColumnMap[ldapColumn];
            var fieldValue = account[ldapColumn];
        
            if (YAHOO.lang.isValue(fieldValue)) {
                document.getElementById(fieldName).value = fieldValue;
            }
        }
    },

    /**
     * Display a list of accounts that a user can select from
     * 
     * @param accounts {Object} An array of LDAP account dictionaries.
     */
    showMultipleAccounts : function (accounts) {
        Fisma.Util.message('<p>Multiple accounts match your query. Click a name to select it.</p>', 'info', 'true');
        var msgBar = document.getElementById('msgbar');

        var accountsContainer = document.createElement('p');
        var index;

        for (index in accounts) {
            var account = accounts[index];

            var accountLink = document.createElement('a');
            accountLink.setAttribute('href', '#');
            accountLink.account = account;
            YAHOO.util.Event.on(accountLink, "click", Fisma.User.populateAccountForm, this.account);

            var accountText = account.givenname
                            + ' '
                            + account.sn
                            + ' ['
                            + (YAHOO.lang.isValue(account.samaccountname) ? account.samaccountname : account.uid)
                            + ']';
            accountLink.appendChild(document.createTextNode(accountText));
            accountLink.appendChild(document.createElement('br'));

            accountsContainer.appendChild(accountLink);
        }

        msgBar.appendChild(accountsContainer);
    },

    /**
     * Show the comment panel
     * 
     * @return void
     */
    showCommentPanel : function () {

        // The scope is the button that was clicked, so save it for closures
        var button = this;

        var lockedElement = YAHOO.widget.Button.getButton('locked-button');
        var lockedValue;
        if (!YAHOO.lang.isUndefined(lockedElement)) {
            var menu = lockedElement.getMenu();
            lockedValue = YAHOO.lang.isNull(menu.activeItem) ? menu.srcElement.value : menu.activeItem.value;
        }

        // Only show panel when status is locked
        if (YAHOO.lang.isUndefined(lockedElement) || parseInt(lockedValue, 10) === 0) {
            this.submitForm();
            return;
        }

        // Create a panel
        var content = document.createElement('div');

        var messageContainer = document.createElement('span');
        var warningMessage = document.createTextNode("Please add a comment explaining why you are locking" +
                                                     " this user's account.");
        messageContainer.appendChild(warningMessage);
        content.appendChild(messageContainer);

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
        var buttonContainer = document.createElement('span');
        var submitButton = new YAHOO.widget.Button({type: 'button', label: "Save", container: buttonContainer});
        content.appendChild(buttonContainer);

        Fisma.HtmlPanel.showPanel('Add Comment', content);

        submitButton.on('click', function() {

            // Get commentTextArea value from panel and assign its value to comment element
            var commentElement = YAHOO.util.Dom.get('commentTextArea').value;
            YAHOO.util.Dom.get('comment').value = commentElement;
            button.submitForm();
        });

        return;
    }
};
