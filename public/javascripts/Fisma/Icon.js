/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Icon = {
    /**
     * Popup a panel for uploading image files
     *
     * @return {Boolean} False to interrupt consequent operations
     */
    showPanel : function() {
        Fisma.UrlPanel.showPanel(
            'Upload Image',
            '/icon/upload-form',
            function(panel) {
                jQuery("input[type=file]").attr("accept", "image/*");

                var addAnotherFileButton = new YAHOO.widget.Button("add-another-file-button");

                // YUI strips away the classes, replace them
                YAHOO.util.Dom.addClass("add-another-file-button", "ie7-only");
                YAHOO.util.Dom.addClass("add-another-file-button", "ie8-only");
                YAHOO.util.Dom.addClass("add-another-file-button", "ie9-only");

                // Add the appropriate event listener to the button
                YAHOO.util.Event.addListener("add-another-file-button", "click", Fisma.Icon.addUploadImage);

                // Make the submit button a YUI widget
                var inputs = panel.body.getElementsByTagName("input");
                var i, submitButton;
                for (i in inputs) {
                    if (inputs[i].type === 'submit') {
                        submitButton = new YAHOO.widget.Button(inputs[i]);
                    }
                }

                var uploadIconTooltipTxt = "Please upload a square image file larger than 32 x 32 pixels. ";
                uploadIconTooltipTxt += "You don't have to worry about the dimensional size of the image as the ";
                uploadIconTooltipTxt += "system will automatically resize and scale the image down to 32 x 32 ";
                uploadIconTooltipTxt += "pixels, but you do have to worry about the shape. As rectangular images ";
                uploadIconTooltipTxt += "will be distorted, please make sure that you are uploading a square image ";
                uploadIconTooltipTxt += "file. Formats accepted are JPEG, GIF, SVG, BMP, and PNG.";
                submitButton.set('title', uploadIconTooltipTxt);

                // Register listener for the panel close event
                panel.hideEvent.subscribe(function () {
                    setTimeout(function () {
                        panel.destroy();
                        uploadIconTooltipObj.destroy();
                    }, 0);
                });
            }
        );
        return false;
    },

    /**
     * Handle onclick event of the button on the image upload form to attach one more files
     */
    addUploadImage : function() {
        var file = jQuery("input[type=file]").last();
        var anotherFile = file.clone(true);
        anotherFile.insertAfter(file);

        YAHOO.util.Event.preventDefault(event);

        return false;
    }
};
