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
 * @fileoverview Client-side behavior related to the Finding module
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id: AttachArtifacts.js 3188 2010-04-08 19:35:38Z mhaase $
 */

Fisma.Chart = {

    /**
     * A generic handler for link events in an XML/SWF chart that will interpolate query parameters into a specified
     * URL and then redirect the user to that URL.
     *
     * This function takes a variable argument list. The first argument is the URL. The URL can contain '%s' tokens
     * which will be interpolated one-by-one with the remaining arguments.
     *
     * Example: handleLink('/param1/%s/param2/%s?q=%s', 'A', 'B', 'C') would redirect the user to the URL
     * /param1/A/param2/B?q=C
     *
     * @param baseUrl A [trusted] URL with a sprintf style '%s' in it that represents the request parameter
     * @param variable arguments
     */
    handleLink : function (baseUrl) {

        // Sanity check: number of argument place holders in URL equals number of arguments to this function
        var placeHolders = baseUrl.match(/%s/);
        
        if (placeHolders.length != arguments.length - 1) {
            throw "Expected " + placeHolders.length + " arguments but found " + (arguments.length - 1);
        }

        // Loop over the variable length arguments (skipping the first argument, which is baseUrl)
        var argumentIndex;

        for (argumentIndex = 1; argumentIndex < arguments.length; argumentIndex++) {
            baseUrl = baseUrl.replace('%s', escape(arguments[argumentIndex]));
        }
        
        location.href = baseUrl;
    }
};
