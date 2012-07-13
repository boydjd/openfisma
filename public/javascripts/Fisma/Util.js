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
 * @fileoverview Utility functions
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Util = {
    /**
     * Pop-up the reassogication form
     *
     * @param event The HTML Event
     * @param args The Object literal containing real arguments
     *      String title    The Title of the page
     *      String url      The URL of the reassociateAction
    */
    showReassociatePanel : function (event, args) {
        var panel = Fisma.UrlPanel.showPanel(args.title, args.url, function() {
            // The form contains some scripts that need to be executed
            var scriptNodes = panel.body.getElementsByTagName('script');
            var i;
            for (i = 0; i < scriptNodes.length; i++) {
                try {
                    eval(scriptNodes[i].text);
                } catch (e) {
                    var message = 'Not able to execute one of the scripts embedded in this page: ' + e.message;
                    Fisma.Util.showAlertDialog(message);
                }
            }

            // Set the correct action
            if (panel.body.getElementsByTagName('form').length > 0) {
                panel.body.getElementsByTagName('form')[0].action = args.url;
            }

            // make the submit button a YUI widget
            var inputs = panel.body.getElementsByTagName("input");
            for (i in inputs) {
                if (!YAHOO.lang.isUndefined(inputs[i]) && inputs[i].type === 'submit') {
                    var submitButton = new YAHOO.widget.Button(inputs[i]);
                }
            }

            // Fix the bug where the panel doesn't close if opened the second time in IE
            panel.subscribe("hide", function() {
                Fisma.Registry.get("messageBoxStack").pop();
                setTimeout(function () {
                    panel.destroy();
                    panel = null;
                }, 0);
            }, this, true);

        }, "reassociatePanel");
    },

    /**
     * Using jQuery to trigger a not-so-smart YUI Button
     *
     * @param buttonName The name of the button, which happens (thanks God) to be the id of the span with onClick
     */
    triggerButton : function (buttonName) {
        jQuery('#' + buttonName).click();
    },

    /**
     * Escapes the specified string so that it can be included in a regex without special characters affecting
     * the regex's meaning
     *
     * Special characters are: .*+?|()[]{}\
     *
     * @param rawValue Unescaped input
     */
    escapeRegexValue : function (rawValue) {
        var specials = new RegExp("[.*+?|()\\[\\]{}\\\\]", "g");

        return rawValue.replace(specials, "\\$&");
    },

    /**
     * Convert a string name of an object into a reference to that object.
     *
     * For example, "Fisma.Foo.myFoo" returns a reference to the actual Fisma.Foo.myFoo object, if it exists,
     * or throws an error if does not exist.
     *
     * This is useful for sending references to objects in JSON syntax, since JSON cannot encode a reference directly.
     *
     * @param objectName The string name of the object
     */
    getObjectFromName : function (objectName) {
        var pieces = objectName.split('.');
        var currentObj = window;
        var piece;

        for (piece in pieces) {
            currentObj = currentObj[pieces[piece]];

            if (currentObj === undefined) {
                throw "Specified object does not exist: " + objectName;
            }
        }

        // At this point, the current value of parentObj should be the object itself
        return currentObj;
    },

    /**
     * Positions the specified panel relative to the specified element.
     *
     * The goal is to put the panel near the specified element (typically, just underneath and left-aligned) but with
     * the added constraint of not positioning the panel anywhere that will be clipped by the current viewport.
     *
     * @param panel
     * @param referenceElement
     */
    positionPanelRelativeToElement : function (panel, referenceElement) {

        // This is a constant which indicates how far down the panel should be below the reference element
        var VERTICAL_OFFSET = 5;

        panel.cfg.setProperty(
            "context",
            [
                referenceElement,
                YAHOO.widget.Overlay.TOP_LEFT,
                YAHOO.widget.Overlay.BOTTOM_LEFT,
                null,
                [0, VERTICAL_OFFSET]
            ]);
    },

    /**
     * Generate a timestamp in the format HH:MM:SS, using 24 hour time
     */
    getTimestamp : function () {
        var date = new Date();

        var hours = date.getHours().toString();

        if (hours.length === 1) {
            hours = "0" + hours;
        }

        var minutes = date.getMinutes().toString();

        if (minutes.length === 1) {
            minutes = "0" + minutes;
        }

        var seconds = date.getSeconds().toString();

        if (seconds.length === 1) {
            seconds = "0" + seconds;
        }

        return hours + ":" + minutes + ":" + seconds;
    },

    /**
     * Show confirm window with warning message. config object can have width, text, isLink, url and func
     *
     * If there is a config.url string, clicking "Yes" button will navigate there. If there is a config.func,
     * that function will be called, and the parameters passed to that function must be in an /array/ in config.args.
     * If the event comes from a link, set config.isLink to true so that it won't be directed to the link before
     * "YES" button is clicked.
     *
     * @param event
     * @param config
     */
    showConfirmDialog : function (event, config) {
        var confirmDialog = Fisma.Util.getDialog();

        var buttons = [ { text:"Yes", handler : function () {
                            if (config.url) {
                                document.location = config.url;
                             } else if (config.func) {
                                 var funcObj = config.func;

                                 if (!YAHOO.lang.isFunction(funcObj)) {
                                    funcObj = Fisma.Util.getObjectFromName(config.func);
                                 }

                                 if (YAHOO.lang.isFunction(funcObj)) {
                                     if (config.args) {
                                         funcObj.apply(this, config.args);
                                     } else {
                                         funcObj.call();
                                     }
                                 }
                             }
                             this.destroy();
                            }
                        },
                        { text:"No",  handler : function () {
                            this.destroy();
                            }
                        }
                     ];

        confirmDialog.setHeader("Are you sure?");
        confirmDialog.setBody(config.text);
        confirmDialog.cfg.queueProperty("buttons", buttons);
        if (config.width) {
            confirmDialog.cfg.setProperty("width", config.width);
        }
        confirmDialog.render(document.body);
        confirmDialog.show();
        if (config.isLink) {
            YAHOO.util.Event.preventDefault(event);
        }

        confirmDialog.hideEvent.subscribe(function (e) {
            setTimeout(function () {confirmDialog.destroy();}, 0);
        });
    },

    /**
     * Show alert warning message. The config object can have width and zIndex property
     *
     * Generanlly, it can just pass alert message string if it does not need to override default config
     *
     * @param message string
     * @param config object
     */
    showAlertDialog : function (alertMessage, config) {
        var alertDialog = Fisma.Util.getDialog();

        var handleOk =  function() {
            this.destroy();
        };
        var button = [ { text: "Ok", handler: handleOk } ];

        alertDialog.setHeader("WARNING");
        alertDialog.setBody(alertMessage);
        alertDialog.cfg.queueProperty("buttons", button);

        if (!YAHOO.lang.isUndefined(config) && config.width) {
            alertDialog.cfg.setProperty("width", config.width);
        }
        if (!YAHOO.lang.isUndefined(config) && config.zIndex) {
            alertDialog.cfg.setProperty("zIndex", config.zIndex);
        }

        alertDialog.render(document.body);
        alertDialog.show();

        alertDialog.hideEvent.subscribe(function (e) {
            setTimeout(function () {alertDialog.destroy();}, 0);
        });
    },

    /**
     * Generate a YUI SimpleDialog
     *
     * @param (boolean) closable Whether to show a close button AND allow closing the dialog
     * @return a YUI SimpleDialog
     */
    getDialog : function (closable) {
        closable = (typeof closable === "undefined") ? true : closable;

        var dialog =
            new YAHOO.widget.SimpleDialog("warningDialog",
                { width: "400px",
                  fixedcenter: true,
                  visible: false,
                  close: closable,
                  modal: true,
                  icon: YAHOO.widget.SimpleDialog.ICON_WARN,
                  constraintoviewport: true,
                  draggable: false
                } );

        return dialog;
    },

    /**
     * Use post method to update/delete a subject. The parameters take action url and a subject id.
     *
     * @param event { Event Object } The event from click event
     * @param param1 { mixed } It is an object when called by click event, otherwise, it is a string for action url.
     * @param param2 { number|null } It is a number for subject id or it is null when it is called by click event.
     */
    formPostAction : function (event, param1, param2) {
        var submitForm = document.createElement("FORM");
        document.body.appendChild(submitForm);
        submitForm.method = "POST";

        if (YAHOO.lang.isNull(event) || '' === event) {
            submitForm.action = param1;
        } else {
            submitForm.action = param1.action;
        }

        var subId = document.createElement('input');
        subId.type = 'hidden';
        subId.name = 'id';

        if (YAHOO.lang.isNull(event) || '' === event) {
            subId.value = param2;
        } else {
            subId.value = param1.id;
        }

        submitForm.appendChild(subId);

        var subcsrf = document.createElement('input');
        subcsrf.type = 'hidden';
        subcsrf.name = 'csrf';
        subcsrf.value = $('[name="csrf"]').val();
        submitForm.appendChild(subcsrf);

        submitForm.submit();
     },

    /**
     * I've refactored this slightly by moving most of the logic into MessageBox.js and MessageBoxStack.js, and moving
     * the styles into MessageBox.css. I've kept this global method in place to avoid breaking the API right before a
     * release (which would require diff'ing a lot of lines of code.)
     *
     * @param msg {String} the message to display
     * @param model {String} either "info" or "warning" -- this affects the color scheme used to display the message
     * @param clear {Boolean} If true, new message will replace existing message. If false, new message will be
     *              appended.
     */
     message: function (msg, model, clear) {
        clear = clear || false;

        msg = $P.stripslashes(msg);

        var messageBoxStack = Fisma.Registry.get("messageBoxStack");
        var messageBox = messageBoxStack.peek();

        if (messageBox) {
            if (clear) {
                messageBox.setMessage(msg);
            } else {
                messageBox.addMessage(msg);
            }

            if (model === 'warning') {
                messageBox.setErrorLevel(Fisma.MessageBox.ERROR_LEVEL.WARN);
            } else {
                messageBox.setErrorLevel(Fisma.MessageBox.ERROR_LEVEL.INFO);
            }

            messageBox.show();
        }
    },

    /**
     * To format time on the hidden element by id
     *
     * @param id
     */
    updateTimeField: function (id) {
        var hiddenEl = document.getElementById(id);
        var hourEl = document.getElementById(id + 'Hour');
        var minuteEl = document.getElementById(id + 'Minute');
        var ampmEl = document.getElementById(id + 'Ampm');

        var hour = hourEl.value;
        var minute = minuteEl.value;
        var ampm = ampmEl.value;

        // Make up a date (whose values will be discarded) to use internal time conversion
        var currentTime = new Date();
        var currentMonth = currentTime.getMonth() + 1;
        var currentDay = currentTime.getDate();
        var currentYear = currentTime.getFullYear();

        var date = new Date(
            currentMonth + "/" + currentDay + "/" + currentYear
            + " " + hour + ":" + minute + ":00" + " " + ampm
        );
        hour = $P.str_pad(date.getHours(), 2, '0', 'STR_PAD_LEFT');
        minute = $P.str_pad(date.getMinutes(), 2, '0', 'STR_PAD_LEFT');

        var time = hour + ':' + minute + ':00';
        hiddenEl.value = time;
    },

    /**
     * Convert a hash array into an application/x-www-form-urlencoded string.
     *
     * This only supports scalar values.
     *
     * @param {Object} params
     * @return {string}
     * @static
     */
    convertObjectToPostData: function (params) {
        var postData = '';
        var key;

        for (key in params) {
            var value = params[key];

            postData += encodeURIComponent(key) + "=" + encodeURIComponent(value) + "&";
        }

        postData = postData.substring(0, postData.length - 1);

        return postData;
    },

    /**
     * Create a dialog which what's new content shows on
     *
     * @param {string} params current version number.
     */
    showWhatsNewDialog: function (currentVersion) {
        var dialog = new YAHOO.widget.SimpleDialog("whatsNewDialog",
            {width: "855px",
             fixedcenter: true,
             visible: false,
             close: false,
             modal: true,
             constraintoviewport: true,
             draggable: false
            });

        //Add line spacing to dialog
        var bottomPanel = document.createElement('div');
        bottomPanel.className = 'dialog-button-panel';

        var close = document.createElement('a');
        close.className = 'close-link';
        close.href = '#';
        close.innerHTML = 'Close';
        bottomPanel.appendChild(close);

        var dialogTip = document.createElement('div');
        dialogTip.className = 'dialog-tip';

        var dontShowCheckbox = document.createElement('input');
        dontShowCheckbox.type = 'checkbox';
        dontShowCheckbox.id = 'notShow';
        dontShowCheckbox.name = 'notShow';
        dialogTip.appendChild(dontShowCheckbox);

        var dontShowLabel = document.createElement('label');
        dontShowLabel.type = 'checkbox';
        dontShowLabel.setAttribute('for','notShow');

        var checkboxSpan = document.createElement('span');
        checkboxSpan.className = 'checkbox-label';
        checkboxSpan.innerHTML = "Don't show again";
        dontShowLabel.appendChild(checkboxSpan);
        dialogTip.appendChild(dontShowLabel);

        var dialogSpan = document.createElement('span');
        dialogSpan.innerHTML = " (Access from 'User Preferences' menu)";
        dialogTip.appendChild(dialogSpan);

        bottomPanel.appendChild(dialogTip);

        var iframe;
        // Get rid of iframe boarder with IE
        if (YAHOO.env.ua.ie) {
            iframe = document.createElement('<iframe frameborder="0"></iframe>');
        } else {
            iframe = document.createElement('iframe');
        }

        iframe.height = '420px';
        iframe.width = '830px';
        iframe.style.border = 'none';
        iframe.style.overflow = 'hidden';
        iframe.scrolling = 'no';

        var title = $("title").text().split("-")[0].trim();
        var header = "<font size=3>What’s New in " + title + " " + currentVersion + " </font>";

        dialog.setHeader(header);
        dialog.setBody(iframe);
        dialog.setFooter(bottomPanel);
        dialog.render(document.body);
        dialog.show();

        dontShowCheckbox.focus();
        YAHOO.util.Event.addListener(close, 'click', function() {
            var notShowCheckbox = document.getElementById('notShow');
            if (notShowCheckbox.checked === true) {
                var whatsNewStorage = new Fisma.PersistentStorage('WhatsNew.Checked');
                whatsNewStorage.set('version', currentVersion.substr(0, (currentVersion.length-2)));
                whatsNewStorage.sync();
            }

            dialog.destroy();
        });

        iframe.src = '/whats-new';
    },

    /**
     * Create a file upload form
     *
     * @param {object} config
     *     String .action             url to POST to
     *     String .fileElementName    name of the File input element
     *     int    .maxValue           the max upload file size
     *     String .accept             The accept attribute, not supported in IE or safari. Optional.
     * @return a form element.
     */
    createUploadFileForm : function (config) {

        // Create a hidden form for the file upload
        var fileForm = document.createElement("form");
        fileForm.method = "post";
        fileForm.action = config.action;
        fileForm.enctype = "multipart/form-data";

        var maxElement = document.createElement("input");
        maxElement.type = "hidden";
        maxElement.name = "MAX_FILE_SIZE";
        maxElement.value = config.maxValue;
        fileForm.appendChild(maxElement);

        var fileElement = document.createElement("input");
        fileElement.type = "file";
        fileElement.name = config.fileElementName;
        if (!YAHOO.lang.isUndefined(config.accept)) {
            fileElement.setAttribute("accept", config.accept); // not supported in IE or safari
        }

        fileForm.appendChild(fileElement);
        return fileForm;
    },

     /**
     * Display the description div to the below of element.
     *
     * @param {String} targetId The id of element
     * @param {String} description The description of element
     */
    showDescription: function (targetId, description) {

        // Create a description div for showing description
        var container = document.createElement("div");
        container.className = 'descriptionBox';
        container.id = targetId + '_description';

        var containerTop = document.createElement("div");
        containerTop.className = 'descriptionBoxTop';
        container.appendChild(containerTop);

        var containerCenter = document.createElement("div");
        containerCenter.className = 'descriptionBoxCenter';
        containerCenter.innerHTML = description;
        container.appendChild(containerCenter);

        var containerBottom = document.createElement("div");
        containerBottom.className = 'descriptionBoxBottom';
        container.appendChild(containerBottom);

        var targetEl;
        if (jQuery('#' + targetId)[0]) {
            targetEl = jQuery('#' + targetId);
        } else {
            targetEl = jQuery('#' + targetId + '-button');
        }

        // Clone a tr element from the tr of the current element.
        // Clean the content of cloned tr and insert it to the next sibling of the tr of current element.
        // Append the description div to the second td of the cloned tr.
        var tr = targetEl.closest('tr');
        var cloneTr = tr.clone();
        cloneTr.children().text("").last().html(container);
        tr.after(cloneTr);

        // Remove the description attribute from element
        targetEl.removeAttr('description');
    },

    /**
     * Get Previous/Next record based on the id passed by config and then view the record.
     *
     * @param {Object} event.
     * @param {Object} config
     *     String  .action  define button action type: previous or next.
     *     String  .url     url to view the record.
     *     Integer .id      the current record id.
     */
    getNextPrevious : function (event, config) {
        var storage = new Fisma.Storage('Fisma.PreviousNext');
        var ids = storage.get(config.modelName + 'ids');

        var i;
        for (i = 0; i < ids.length; i++) {
            if (parseInt(ids[i], 10) === parseInt(config.id, 10)) {

                // Next button.
                if (config.action === 'next') {
                    if ((ids.length - 1) === i + 1) {
                        document.location = config.url + ids[i+1] + '/fromSearch/1' + '/last/1';
                    } else {
                        document.location = config.url + ids[i+1] + '/fromSearch/1';
                    }
                    return;
                } else { // Previous button
                    if ( (i-1) === 0) {
                        document.location = config.url + ids[i-1] + '/fromSearch/1' +  '/first/1';
                    } else {
                        document.location = config.url + ids[i-1] + '/fromSearch/1';
                    }
                    return;
                }
            }
        }
    },

    /**
     * Submit the first form on the page
     */
    submitFirstForm : function(event, config) {
        var form = $('form').eq(0);
        var buttons = $('.yui-menu-button', form).get();
        for (var i = 0; i < buttons.length; i++) {
            button = YAHOO.widget.Button.getButton(buttons[i].id);
            if (button.getMenu()) {
                button.createHiddenFields();
            }
        }
        form.submit();
    }
};
