/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Provides session timeout awareness with XHR support.
     * @namespace Fisma
     * @class SessionManager
     */
    var Manager = { // shorthand, full assignment at bottom
        /**
         * Polling delay, in seconds.
         */
        POLL_DELAY: 1,

        /**
         * Name of the cookie used to track server timestamp.
         */
        COOKIE_TIMESTAMP: "session_timestamp",

        /**
         * URI of the refresh session action
         */
        REFRESH_SESSION_URI: "/auth/refresh-session/format/json",

        /**
         * Time (in seconds) before session expires.
         */
        _inactivityPeriod: null,

        /**
         * Time (in seconds) before alerting the user of inactivity.
         */
        _inactivityNotice: null,

        /**
         * Timer object saved on init().
         */
        _timer: null,

        /**
         * Timestamp reported by server on the most recent request.
         */
        _serverTimestamp: null,

        /**
         * Timestamp on client that corresponds to the server timestamp.
         * Used to determine how long the client has been idle.
         */
        _localTimestamp: null,

        /**
         * YUI Panel For Idle Notice
         */
        _inactivityPanel: null,

        /**
         * YUI Button for the idle notice panel.
         */
        _inactivityPanelButton: null,

        /**
         * Indicates if there has been a recent session refresh if true.
         */
        _recentSessionRefresh: false,

        /**
         * Called onDOMReady to start the polling procedure.
         *
         * @method _init
         * @public
         * @static
         */
        init: function(inactivityPeriod, inactivityNotice) {
            Manager._inactivityPeriod = Number(inactivityPeriod);
            Manager._inactivityNotice = Number(inactivityNotice);
            Manager._serverTimestamp = YAHOO.util.Cookie.get(Manager.COOKIE_TIMESTAMP);
            Manager._localTimestamp = Manager._getLocalTimestamp();
            Manager._timer = YAHOO.lang.later( Manager.POLL_DELAY * 1000, null, Manager.poll, null, true);
            var Event = YAHOO.util.Event;
            Event.onDOMReady(function() {
                Event.addListener(document.body, "click", Manager.onActivityEvent);
                Event.addListener(document.body, "keypress", Manager.onActivityEvent);
            });
        },

        /**
         * Executes at each polling cycle to test if the session is near timeout.
         *
         * @method poll
         * @public
         * @static
         */
        poll: function() {
            var timestamp = YAHOO.util.Cookie.get(Manager.COOKIE_TIMESTAMP);
            // reset state if server timestamp changes
            if (timestamp !== Manager._serverTimestamp) {
                Manager._serverTimestamp = timestamp;
                Manager._localTimestamp = Manager._getLocalTimestamp();
                return;
            }

            var idle = Manager._getLocalTimestamp() - Manager._localTimestamp;
            // check to see if the session has expired
            if (idle > Manager._inactivityPeriod) {
                Manager.logout();
                return;
            }

            // see if only two minutes remain
            if (idle > Manager._inactivityNotice) {
                Manager.notifyUser();
            }
        },

        /**
         * Notifies the user of an eminent timeout and prompts to continue session.
         *
         * @method notifyUser
         * @public
         * @static
         */
        notifyUser: function() {
            if (YAHOO.lang.isNull(Manager._inactivityPanel)) {
                var content = document.createElement("div");
                content.innerHTML = "Your session will expire soon, please click Continue to continue working.";
                var buttonDiv = document.createElement("div");
                YAHOO.util.Dom.setStyle(buttonDiv, "text-align", "right");
                Manager._inactivityPanelButton = new YAHOO.widget.Button({
                    type: "push",
                    label: "Continue",
                    container: buttonDiv,
                    onclick: {fn: Manager.continueSession}
                });
                content.appendChild(buttonDiv);
                var panel = new YAHOO.widget.Panel(
                    "inactivity-notice",
                    {width: "320px", fixedcenter: true, draggable: false, modal: true, close: false}
                );
                panel.setHeader("Your Session Will Expire");
                panel.setBody(content);
                panel.render(document.body);
                Manager._inactivityPanel = panel;
            }
            Manager._inactivityPanel.show();
        },

        /**
         * Action taken when the user ops to continue their session, responsible for contacting the server.
         *
         * @method continueSession
         * @public
         * @static
         */
        continueSession: function() {
            if (YAHOO.lang.isObject(Manager._inactivityPanelButton)) {
                Manager._inactivityPanelButton.set("disabled", true);
            }
            var callback = function() {
                if (YAHOO.lang.isObject(Manager._inactivityPanel)) {
                    Manager._inactivityPanel.hide();
                }
                if (YAHOO.lang.isObject(Manager._inactivityPanelButton)) {
                    Manager._inactivityPanelButton.set("disabled", false);
                }
            };
            YAHOO.util.Connect.asyncRequest(
                "GET",
                Manager.REFRESH_SESSION_URI,
                {success: callback, failure: callback});
        },

        /**
         * Called when the users session has been determined to be expired. Redirects the user to the Log In screen.
         *
         * @method logout
         * @public
         * @static
         */
        logout: function() {
            document.location.href = "/auth/logout";
        },

        /**
         * Get the current local unix time.
         *
         * @method _getLocalTimestamp
         * @return integer
         * @public
         * @static
         */
        _getLocalTimestamp: function() {
            return Math.round((new Date()).getTime() / 1000);
        },

        /**
         * Callback function for mouse clicks and key press events.
         *
         * @method onActivityEvent
         * @return void
         * @public
         * @static
         */
        onActivityEvent: function() {
            // disable activity listening when the inactivity panel is being displayed to the user
            if (YAHOO.lang.isObject(Manager._inactivityPanel) && Manager._inactivityPanel.getProperty("visible")) {
                return;
            }
            if (Manager._recentSessionRefresh) {
                return;
            }
            Manager._recentSessionRefresh = true;
            Manager.continueSession();
            YAHOO.lang.later(15000, null, function() {Manager._recentSessionRefresh = false;});
        }
    };
    Fisma.SessionManager = Manager;
})();
