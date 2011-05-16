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
    Fisma.SessionManager = {
        /**
         * Polling delay, in seconds.
         */
        POLL_DELAY: 1,

        /**
         * Name of the cookie used to track timeout.
         */
        COOKIE_NAME: "session_timeout",

        /**
         * Timer object saved on init().
         */
        _timer: null,

        /**
         * Called onDOMReady to start the polling procedure.
         *
         * @method _init
         * @public
         * @static
         */
        init: function() {
            Fisma.SessionManager._timer = YAHOO.lang.later(
                Fisma.SessionManager.POLL_DELAY * 1000,
                null,
                Fisma.SessionManager.poll,
                null,
                true);
        },

        /**
         * Executes at each polling cycle to test if the session is near timeout.
         *
         * @method poll
         * @public
         * @static
         */
        poll: function() {
            var timeout = YAHOO.util.Cookie.get(Fisma.SessionManager.COOKIE_NAME);
            timeout -= Fisma.SessionManager.POLL_DELAY;
            YAHOO.util.Cookie.set(Fisma.SessionManager.COOKIE_NAME, timeout);
        },

        /**
         * Notifies the user of an eminent timeout and prompts to continue session.
         *
         * @method notifyUser
         * @public
         * @static
         */
        notifyUser: function() {
        },

        /**
         * Action taken when the user ops to continue their session, responsible for contacting the server.
         *
         * @method continueSession
         * @public
         * @static
         */
        continueSession: function() {
        },

        /**
         * Called when the users session has been determined to be expired. Redirects the user to the Log In screen.
         *
         * @method logout
         * @public
         * @static
         */
        logout: function() {
        }
    };
    // start the session manager
    YAHOO.util.Event.onDOMReady(Fisma.SessionManager.init);
})();
