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
     * A reference to a YUI table which contains comments for the current page
     *
     * This reference will be set when the page loads by the script which initializes the table
     */
    commentTable : null,

    /**
     * Handle successful comment events by inserting the latest comment into the top of the comment table
     *
     * @param comment An object containing the comment record values
     * @param yuiPanel A reference to the modal YUI dialog
     */
    commentCallback : function (comment, yuiPanel) {
        var that = this;

        var commentRow = {
            'timestamp' : comment.createdTs,
            'username' : comment.username,
            'comment' : comment.comment,
            'delete' :
                '/comment/remove/format/json/type/User/' +
                'commentId/' + comment.id +
                '/id/' + Fisma.Commentable.config.id
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

        // Update the comment count in the tab UI
        var commentCountEl = document.getElementById('commentsCount').firstChild;
        commentCountEl.nodeValue++;

        // Hide YUI dialog
        yuiPanel.hide();
        yuiPanel.destroy();
    },

    /**
     * Display a dialog which shows user information for the specified user.
     *
     * @param referenceElement The panel will be displayed near this element
     * @param userId The ID number of the user to get info for
     */
    displayUserInfo : function (referenceElement, userId) {

        var panel;

        if (typeof Fisma.User.userInfoPanelList[userId] === 'undefined') {

            // Create new panel
            panel = Fisma.User.createUserInfoPanel(referenceElement, userId);

            Fisma.User.userInfoPanelList[userId] = panel;

            panel.show();
        } else {

            // Panel already exists
            panel = Fisma.User.userInfoPanelList[userId];

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
     * @param userId The ID number of the user to get info for
     * @return YAHOO.widget.Panel
     */
    createUserInfoPanel : function (referenceElement, userId) {

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
        panel.setBody("Loading user profile for <em>" + $(referenceElement).text().trim() + "</em>...");
        panel.render(document.body);

        Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);

        // Load panel content using asynchronous request
        YAHOO.util.Connect.asyncRequest(
            'GET',
            '/user/info/id/' + encodeURI(userId),
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

    /**
     * Configure the autocomplete that is used for lookint up an LDAP account
     *
     * @param autocomplete {YAHOO.widget.AutoComplete}
     * @param params {Array} The arguments passed to the autocomplete constructor
     */
    setupLookupAutocomplete : function (autocomplete, params) {
        autocomplete.itemSelectEvent.subscribe(function (ev, args) {
            var data = args[2][1];
            $("#username").val(data.username);
            $("#email").val(data.mail);
            $("#nameFirst").val(data.givenname);
            $("#nameLast").val(data.sn);
            $("#phoneOffice").val(data.telephonenumber);
        });
        autocomplete.sendQuery($("#lookup").val());
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
    },

    /**
     * Fisma.UrlPanel A placeholder for the notification panel object
     */
    notificationPanel: null,

    /**
     * Display the notification panel
     */
    showNotifications: function() {
        Fisma.User.notificationPanel = Fisma.UrlPanel.showPanel(
            'Notifications',
            '/index/notification/format/html',
            function() {

            }
        );
        return false;
    },

    /**
     * Dismiss notifications and update the UI
     */
    dismissNotifications: function() {
        YAHOO.util.Connect.setForm('dismissNotificationForm');
        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/index/dismiss/format/json',
            {
                success: function(o) {
                    Fisma.User.notificationPanel.destroy();
                    $('.notification').remove();
                },

                failure: function(o) {
                }
            },
            null
        );
        return false;
    },

    /**
     * POST to deleteAction
     */
    deleteUser: function(event, args) {
        var id = args.id;
        var link = args.link;
        Fisma.Util.formPostAction(null, link, id);
    },

    /**
     * Populate homeUrl with built-in values if any
     */
    populateHomeUrl: function(selectElement) {
        var input = $('input#homeUrl'),
            inputRow = input.parents('tr'),
            builtins = {
                'system'        : '/',
                'finding'       : '/finding/dashboard',
                'vulnerability' : '/vm/dashboard',
                'incident'      : '/incident-dashboard',
                'inventory'     : '/organization-dashboard'
            };

        if (selectElement) { //selectElement onChange
            var builtin = selectElement.value;

            if (builtin && input.length === 1 && builtins[builtin]) {
                input.val(builtins[builtin]);
                inputRow.hide();
            } else {
                inputRow.show();
                input.focus();
                input.select();
            }
        } else { //init population
            var index,
                currentValue = input.val();
            selectElement = $('#homeSelect');
            for (index in builtins) {
                if (currentValue === builtins[index]) {
                    selectElement.val(index);
                    inputRow.hide();
                    break;
                }
            }
        }


    },

    treeSort: function(selectElement) {
        var selector = $(selectElement).parents('div').eq(0).find('#organizations ul.treelist li');
        selector.tsort('label',{attr:selectElement.value});
        if (selectElement.value !== 'treePos') {
            selector.addClass('flat');
        } else {
            selector.removeClass('flat');
        }
        var storage = new Fisma.PersistentStorage("Fisma.UserAccess");
        storage.set('sortBy', selectElement.value);
        storage.sync();
    },

    treeFilter: function(selectElement) {
        var selector = $(selectElement).parents('div').eq(0).find('#organizations ul.treelist li');
        selector.show();
        if (selectElement.value.indexOf('all') < 0) {
            selector.find('label').not('[type$="' + selectElement.value + '"]').parents('li').hide();
        } else if (selectElement.value === 'allOrg') {
            selector.find('label').not('[type^="org"]').parents('li').hide();
        } else if (selectElement.value === 'allSys') {
            selector.find('label').not('[type^="sys"]').parents('li').hide();
        }
        var storage = new Fisma.PersistentStorage("Fisma.UserAccess");
        storage.set('filterBy', selectElement.value);
        storage.sync();
    },

    preferredTimezoneToggle: function(checkboxElement) {
        if (checkboxElement.checked) {
            $('#timezone').parents('tr').first().hide();
        } else {
            $('#timezone').parents('tr').first().show();
        }
    }
};
