/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * @fileoverview Highlighting utility for HTML DOM
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

/**
 * This class is still experimental. It was written for the search results highlighting and then refactored into its own
 * class, but it still makes a number of assumptions about how highlighting is performed and what the DOM will look
 * like when we try to highlight it. It needs some work to become a general-purpose highlighting utility class.
 */
Fisma.Highlighter = function() {
    return {
        
        /**
         * Highlights delimited text in specified HTML elements
         * 
         * The element to be highlighted must contain matching pairs of delimiters. This method replaces the text in 
         * between each pair of delimiters with highlighted text and then removes the pair of delimiters.
         * 
         * The method is recursive and will replace all instances of delimited text.
         * 
         * @param elements Array of HTML elements
         * @param delimiter Phrases delimted with this string will be replaced with highlighted text
         */
        highlightDelimitedText : function (elements, delimiter) {

            var escapedDelimiter = Fisma.Util.escapeRegexValue(delimiter);

            var regex = new RegExp("^(.*?)" + escapedDelimiter + "(.*?)" + escapedDelimiter + "(.*?)$");

            for (var i in elements) {
                var element = elements[i];

                // Skip empty table cells
                if (!element.firstChild || !element.firstChild.firstChild) {
                    continue;
                }

                var parentNode = element.firstChild;
                
                // Don't try to highlight non-text nodes (text nodeType is 3 -- can't find a named constant for it)
                if (parentNode && parentNode.firstChild && parentNode.firstChild.nodeType != 3) {
                    continue;
                }

                var textNode = parentNode.firstChild;
                var cellText = textNode.nodeValue;

                var matches = this._getDelimitedRegexMatches(cellText, regex);

                this._highlightMatches(parentNode, matches);
            }
        },
        
        /**
         * A helper function that returns a list of text snippets matching a regex
         * 
         * The regex is assumed to be looking for a particular delimiter, in the form:
         *     (some text)delimiter(highlighted text)delimiter(some more text)
         * 
         * This function returns a list of text snippets with an odd length. Every 2nd snippet in this list is one
         * that was matched between delimiters and, therefore, needs to be highlighted. (If there are no snippets to
         * be highlighted, then the list returned will have length==1.)
         *
         * @param text The string to match
         * @param regex The regex to use for the match. It must have 3 parenthetical expressions. (see example above)
         */
        _getDelimitedRegexMatches : function (text, regex) {

            // The list of matching snippets that will be returned
            var matches = [];

            // Used for storing regex matches temporarily
            var highlightMatches = null;

            // Stores the current text that matching is done against
            var currentText = text;

            do {

                highlightMatches = currentText.match(regex);

                // Match 3 subexpressions plus the overall match -> 4 total matches
                if (highlightMatches && highlightMatches.length == 4) {

                    var preMatch = highlightMatches[1];
                    var highlightMatch = highlightMatches[2];
                    var postMatch = highlightMatches[3];
                    
                    matches.push(preMatch);
                    matches.push(highlightMatch);
                    
                    // The rest of the matching text becomes the input for the next loop iteration, in order to 
                    // match multiple times on the same input string.
                    currentText = postMatch;
                } else {

                    // Any remaining text gets pushed onto the matches list
                    matches.push(currentText);

                    // If the input text contains a delimiter that doesn't have a matching delimiter, then nothing will     
                    // ever get matched and we need to break out of the loop or else it will loop indefinitely.
                    break;
                }
                
            } while (highlightMatches);
            
            return matches;
        },
        
        /**
         * Create highlighted span elements based on a list of matching text snippets
         * 
         * @param parentNode The HTML element that is being highlighted
         * @param matches See the description for getDelimitedRegexMatches() for an explanation of the matches array
         */
        _highlightMatches : function (parentNode, matches) {

            if ((matches.length > 1) && (matches.length % 2 == 1)) {

                // Remove current text
                parentNode.removeChild(parentNode.firstChild);

                // Iterate over matches and create new text nodes (for plain text) and new spans (for highlighted
                // text)
                for (var j in matches) {
                    var match = matches[j];

                    var newTextNode = document.createTextNode(match);

                    if (j % 2 === 0) {
                        // This is a plaintext node
                        parentNode.appendChild(newTextNode);
                    } else {
                        // This is a highlighted node
                        var newSpan = document.createElement('span');
                        newSpan.className = 'highlight';
                        newSpan.appendChild(newTextNode);
                        
                        parentNode.appendChild(newSpan);
                    }
                }
            }
        }
    }
};
