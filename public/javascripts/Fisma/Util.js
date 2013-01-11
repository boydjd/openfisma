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
            $('input[type=submit]', panel.body).each(function() {
                $(this).replaceWith(
                    $('<button/>').text($(this).val()).button()
                );
            });

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

        var buttons = [
            {
                'text': "Yes",
                'handler': function () {
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
            {
                'text': "No",
                'handler': function () {
                    this.destroy();
                }
            }
        ];

        confirmDialog.setHeader("Are you sure?");
        confirmDialog.setBody(config.text);
        if (config.width) {
            confirmDialog.cfg.setProperty("width", config.width);
        }
        confirmDialog.render(document.body);
        for (var i in buttons) {
            var buttonDef = buttons[i];
            $('<button/>')
                .text(buttonDef.text)
                .button()
                .data('fn', buttonDef.handler)
                .click(function() {
                    $(this).data('fn').apply(confirmDialog);
                })
                .appendTo(confirmDialog.body)
                .css('margin-right', '4px')
            ;
        }
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
            if (!YAHOO.lang.isUndefined(config) && config.callback) {
                config.callback();
            }
        };
        var button = [{'text': "Ok", 'handler': handleOk } ];

        alertDialog.setHeader("WARNING");
        alertDialog.setBody(alertMessage);

        if (!YAHOO.lang.isUndefined(config) && config.width) {
            alertDialog.cfg.setProperty("width", config.width);
        }
        if (!YAHOO.lang.isUndefined(config) && config.zIndex) {
            alertDialog.cfg.setProperty("zIndex", config.zIndex);
        }

        alertDialog.render(document.body);
        for (var i in buttons) {
            var buttonDef = buttons[i];
            $('<button/>')
                .text(buttonDef.text)
                .button()
                .data('fn', buttonDef.handler)
                .click(function() {
                    $(this).data('fn').apply(alertDialog);
                })
                .appendTo(confirmDialog.body)
                .css('margin-right', '4px')
            ;
        }
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

        var returnUrl = document.createElement('input');
        returnUrl.type = 'hidden';
        returnUrl.name = 'returnUrl';
        returnUrl.value = window.location;
        submitForm.appendChild(returnUrl);

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
    updateTimeField: function (e) {
        var date    = $(this).timeEntry('getTime'),
            hour    = $P.str_pad(date.getHours(), 2, '0', 'STR_PAD_LEFT'),
            minute  = $P.str_pad(date.getMinutes(), 2, '0', 'STR_PAD_LEFT'),
            time    = hour + ':' + minute + ':00',
            $target = $('#' + $(this).attr('target'));
        $target.val(time);
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
        var form = $('form').not(function() { return $(this).parents('div#toolbarForm').length > 0; }).eq(0);
        var buttons = $('.yui-menu-button', form).get();
        var i, button;

        for (i = 0; i < buttons.length; i++) {
            button = YAHOO.widget.Button.getButton(buttons[i].id);
            if (button.getMenu()) {
                button.createHiddenFields();
            }
        }
        form.submit();
    },

    goBack : function() {
        history.go(-1);
    },

    /**
     * Get IE Version number
     *
     * @return int The version number. -1 if not IE
     */
    getIEVersion : function()
    // Returns the version of Internet Explorer or a -1
    // (indicating the use of another browser).
    {
        var rv = -1; // Return value assumes failure.
        if (navigator.appName === 'Microsoft Internet Explorer') {
            var ua = navigator.userAgent;
            var re  = new RegExp("MSIE ([0-9]{1,}[\\.0-9]{0,})");
            if (re.exec(ua) !== null) {
                rv = parseFloat( RegExp.$1 );
            }
        }
        return rv;
    },

    showInputDialog: function(title, query, callbacks, defaultValue) {
        var Dom = YAHOO.util.Dom,
            Event = YAHOO.util.Event,
            Panel = YAHOO.widget.Panel,
            contentDiv = document.createElement("div"),
            errorDiv = document.createElement("div"),
            form = document.createElement('form'),
            textField = $('<input type="text"/>').get(0),
            button = $('<button/>').text('OK').get(0),
            table = $('<table class="fisma_crud"><tbody><tr><td>' + query + ': </td><td></td><td></td></tr></tbody></table>');
        table.appendTo(form);
        $("td", table).get(1).appendChild(textField);
        $("td", table).get(2).appendChild(button);
        contentDiv.appendChild(errorDiv);
        contentDiv.appendChild(form);

        // Make Go button YUI widget
        $(button).button();

        // Prepare the panel
        var panel = new Panel(Dom.generateId(), {modal: true});
        panel.setHeader(title);
        panel.setBody(contentDiv);
        panel.render(document.body);
        $(textField).focus();
        panel.center();

        // Add event listener
        Event.addListener(form, "submit", callbacks['continue'], {panel: panel, errorDiv: errorDiv, textField: textField});
        panel.subscribe("hide", callbacks.cancel);

        // Fill in default value if set
        if (defaultValue !== undefined) {
            textField.value = defaultValue;
        }
        // Show the panel
        panel.show();
        textField.focus();
    },

    setDefaultTimezone: function(geoip) {
        var timezone = geoip.timezone;
    },

    getTimezone: function() {
        var jstz=function(){var b=function(a){a=-a.getTimezoneOffset();return null!==a?a:0},c=function(){return b(new Date(2010,0,1,0,0,0,0))},f=function(){return b(new Date(2010,5,1,0,0,0,0))},e=function(){var a=c(),d=f(),b=c()-f();return new jstz.TimeZone(jstz.olson.timezones[0>b?a+",1":0<b?d+",1,s":a+",0"])};return{determine_timezone:function(){"undefined"!==typeof console&&console.log("jstz.determine_timezone() is deprecated and will be removed in an upcoming version. Please use jstz.determine() instead.");return e()},determine:e,date_is_dst:function(a){var d=5<a.getMonth()?f():c(),a=b(a);return 0!==d-a}}}();jstz.TimeZone=function(b){var c=null,c=b;"undefined"!==typeof jstz.olson.ambiguity_list[c]&&function(){for(var b=jstz.olson.ambiguity_list[c],e=b.length,a=0,d=b[0];a<e;a+=1)if(d=b[a],jstz.date_is_dst(jstz.olson.dst_start_dates[d])){c=d;break}}();return{name:function(){return c}}};jstz.olson={};jstz.olson.timezones={"-720,0":"Etc/GMT+12","-660,0":"Pacific/Pago_Pago","-600,1":"America/Adak","-600,0":"Pacific/Honolulu","-570,0":"Pacific/Marquesas","-540,0":"Pacific/Gambier","-540,1":"America/Anchorage","-480,1":"America/Los_Angeles","-480,0":"Pacific/Pitcairn","-420,0":"America/Phoenix","-420,1":"America/Denver","-360,0":"America/Guatemala","-360,1":"America/Chicago","-360,1,s":"Pacific/Easter","-300,0":"America/Bogota","-300,1":"America/New_York","-270,0":"America/Caracas","-240,1":"America/Halifax","-240,0":"America/Santo_Domingo","-240,1,s":"America/Asuncion","-210,1":"America/St_Johns","-180,1":"America/Godthab","-180,0":"America/Argentina/Buenos_Aires","-180,1,s":"America/Montevideo","-120,0":"America/Noronha","-120,1":"Etc/GMT+2","-60,1":"Atlantic/Azores","-60,0":"Atlantic/Cape_Verde","0,0":"Etc/UTC","0,1":"Europe/London","60,1":"Europe/Berlin","60,0":"Africa/Lagos","60,1,s":"Africa/Windhoek","120,1":"Asia/Beirut","120,0":"Africa/Johannesburg","180,1":"Europe/Moscow","180,0":"Asia/Baghdad","210,1":"Asia/Tehran","240,0":"Asia/Dubai","240,1":"Asia/Yerevan","270,0":"Asia/Kabul","300,1":"Asia/Yekaterinburg","300,0":"Asia/Karachi","330,0":"Asia/Kolkata","345,0":"Asia/Kathmandu","360,0":"Asia/Dhaka","360,1":"Asia/Omsk","390,0":"Asia/Rangoon","420,1":"Asia/Krasnoyarsk","420,0":"Asia/Jakarta","480,0":"Asia/Shanghai","480,1":"Asia/Irkutsk","525,0":"Australia/Eucla","525,1,s":"Australia/Eucla","540,1":"Asia/Yakutsk","540,0":"Asia/Tokyo","570,0":"Australia/Darwin","570,1,s":"Australia/Adelaide","600,0":"Australia/Brisbane","600,1":"Asia/Vladivostok","600,1,s":"Australia/Sydney","630,1,s":"Australia/Lord_Howe","660,1":"Asia/Kamchatka","660,0":"Pacific/Noumea","690,0":"Pacific/Norfolk","720,1,s":"Pacific/Auckland","720,0":"Pacific/Tarawa","765,1,s":"Pacific/Chatham","780,0":"Pacific/Tongatapu","780,1,s":"Pacific/Apia","840,0":"Pacific/Kiritimati"};jstz.olson.dst_start_dates={"America/Denver":new Date(2011,2,13,3,0,0,0),"America/Mazatlan":new Date(2011,3,3,3,0,0,0),"America/Chicago":new Date(2011,2,13,3,0,0,0),"America/Mexico_City":new Date(2011,3,3,3,0,0,0),"Atlantic/Stanley":new Date(2011,8,4,7,0,0,0),"America/Asuncion":new Date(2011,9,2,3,0,0,0),"America/Santiago":new Date(2011,9,9,3,0,0,0),"America/Campo_Grande":new Date(2011,9,16,5,0,0,0),"America/Montevideo":new Date(2011,9,2,3,0,0,0),"America/Sao_Paulo":new Date(2011,9,16,5,0,0,0),"America/Los_Angeles":new Date(2011,2,13,8,0,0,0),"America/Santa_Isabel":new Date(2011,3,5,8,0,0,0),"America/Havana":new Date(2011,2,13,2,0,0,0),"America/New_York":new Date(2011,2,13,7,0,0,0),"Asia/Gaza":new Date(2011,2,26,23,0,0,0),"Asia/Beirut":new Date(2011,2,27,1,0,0,0),"Europe/Minsk":new Date(2011,2,27,2,0,0,0),"Europe/Helsinki":new Date(2011,2,27,4,0,0,0),"Europe/Istanbul":new Date(2011,2,28,5,0,0,0),"Asia/Damascus":new Date(2011,3,1,2,0,0,0),"Asia/Jerusalem":new Date(2011,3,1,6,0,0,0),"Africa/Cairo":new Date(2010,3,30,4,0,0,0),"Asia/Yerevan":new Date(2011,2,27,4,0,0,0),"Asia/Baku":new Date(2011,2,27,8,0,0,0),"Pacific/Auckland":new Date(2011,8,26,7,0,0,0),"Pacific/Fiji":new Date(2010,11,29,23,0,0,0),"America/Halifax":new Date(2011,2,13,6,0,0,0),"America/Goose_Bay":new Date(2011,2,13,2,1,0,0),"America/Miquelon":new Date(2011,2,13,5,0,0,0),"America/Godthab":new Date(2011,2,27,1,0,0,0)};jstz.olson.ambiguity_list={"America/Denver":["America/Denver","America/Mazatlan"],"America/Chicago":["America/Chicago","America/Mexico_City"],"America/Asuncion":["Atlantic/Stanley","America/Asuncion","America/Santiago","America/Campo_Grande"],"America/Montevideo":["America/Montevideo","America/Sao_Paulo"],"Asia/Beirut":"Asia/Gaza Asia/Beirut Europe/Minsk Europe/Helsinki Europe/Istanbul Asia/Damascus Asia/Jerusalem Africa/Cairo".split(" "),"Asia/Yerevan":["Asia/Yerevan","Asia/Baku"],"Pacific/Auckland":["Pacific/Auckland","Pacific/Fiji"],"America/Los_Angeles":["America/Los_Angeles","America/Santa_Isabel"],"America/New_York":["America/Havana","America/New_York"],"America/Halifax":["America/Goose_Bay","America/Halifax"],"America/Godthab":["America/Miquelon","America/Godthab"]};
        return jstz.determine().name();
    }
};

/*
 * Add method to YUI's Event class.  This is referenced by DataTable, but seems to be missing
 */
YAHOO.util.Event.detachListener = function ( el, type ) {
    var i;
    if (!YAHOO.lang.isArray(el)) {
        el = [el];
    }
    for (i in el) {
        YAHOO.util.Event.purgeElement(el[i], false, type);
    }
};
