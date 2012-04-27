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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * A widget that displays a grid of images that a user can select from.
     *
     * @namespace Fisma
     * @class ImagePicker
     * @constructor
     *
     * @param {String} name The name of the form element associated with the picker.
     * @param {HTMLElement} parent The parent element to render inside of.
     * @param {Object} images A dictionary of images where keys are image IDs and values are URLs to find images at.
     * @param {Integer} selectedImage The ID number of the default image.
     * @param {String} managementUrl Optional. A URL that this image picker can link to for managing the images.
     * @param {String} uploadUrl Optional. A URL that can accept the POST of an image.
     */
    var IP = function(name, parent, images, selectedImage, managementUrl, uploadUrl) {
        this._parent = parent;
        this._images = images;
        this._selectedImageId = selectedImage;
        this._managementUrl = managementUrl;
        this._uploadUrl = uploadUrl;

        // The form element wrapper may put something else in here, so get rid of that first.
        while (parent.firstChild) {
            parent.removeChild(parent.firstChild);
        }

        this._renderImage();
        this._renderHiddenInput(name);
        this._renderDrawer();
    };

    /**
     * Valid states for the drawer.
     *
     * @static
     */
    IP.DRAWER = {OPEN: 0, CLOSED: 1};

    YAHOO.lang.extend(IP, Object, {
        /**
         * The parent container
         */
        _parent: null,

        /**
         * The currently displayed image
         */
        _displayedImage: null,

        /**
         * The identifier of the currently selected image
         */
        _selectedImageId: null,

        /**
         * The DOM element that contains the currently selected image
         */
        _selectedImageEl: null,

        /**
         * A dictionary of image IDs and image URLs
         */
        _images: null,

        /**
         * The drawer is the container that holds the image grid.
         */
        _drawer: null,

        /**
         * Whether the drawer is open, closed, or transitioning between the two
         */
        _drawerState: null,

        /**
         * The selected image is tracked in a hidden input field so that the information can be sent
         * back to the server on a form submission.
         */
        _hiddenInput: null,

        /**
         * A link to a URL where the images in this picker can be managed.
         *
         * If set, the widget will display a URL button for this URL. If not set, the button will not be displayed at all.
         */
        _managementUrl: null,

        /**
         * The index of the current row (where to insert a new icon)
         */
        _currentRow: 0,

        /**
         * The index of the current column (where to insert a new icon)
         */
        _currentColumn: 0,

        /**
         * The width of the image grid (in columns)
         */
        _gridWidth: null,

        /**
         * The table that contains the image grid
         */
        _imageGrid: null,

        /**
         * A reference to the image in the top left corner. This is selected by default if no other image
         * has previously been selected.
         */
        _topLeftImage: null,

        /**
         * A URL that can accept a POST of an image file.
         *
         * This URL should return a normal Fisma_AsyncReponse object with these additional fields:
         *
         * 1. identifier: the identifier of the newly uploaded image
         * 2. imageData: a data URI for the newly uploaded image (after it has possibly been resized and re-encoded)
         * 3. imageUrl: a URL pointing to the newly uploaded image
         *
         * Notice that #2 and #3 are redundant. The data URI is a more efficient implementation (saves a
         * request/response cycle) but is not supported by IE7. So this class sniffs the browser and picks the
         * appropriate option for getting the image data.
         */
        _uploadUrl: null,

        /**
         * Render the default image component of this widget.
         */
        _renderImage: function () {
            // Display the default image
            this._displayedImage = document.createElement("img");
            this._displayedImage.tabIndex = 0;
            if (this._images[this._selectedImageId]) {
                this._displayedImage.src = this._images[this._selectedImageId];
            } else {
                this._displayedImage.src = "/images/blank.png";
            }
            this._displayedImage.style.verticalAlign = "middle";
            this._displayedImage.title = "Press space to edit. Use arrow keys to move selection.";
            this._parent.appendChild(this._displayedImage);

            // Set up the hover text for the image
            var clickMessage = document.createElement("span");
            clickMessage.appendChild(document.createTextNode("Click to edit"));
            clickMessage.style.marginLeft = ".5em";
            YAHOO.util.Dom.setStyle(clickMessage, "opacity", 0);
            this._parent.appendChild(clickMessage);

            var fadeInMessage = new YAHOO.util.Anim(clickMessage, {
                opacity: {
                    to: 1.0
                }
            }, 0.5, YAHOO.util.Easing.easeOut);

            var fadeOutMessage = new YAHOO.util.Anim(clickMessage, {
                opacity: {
                    to: 0.0
                }
            }, 0.5, YAHOO.util.Easing.easeIn);

            // Attach events
            YAHOO.util.Event.addListener(this._displayedImage, "mouseover", function () {fadeInMessage.animate();});
            YAHOO.util.Event.addListener(this._displayedImage, "mouseout", function () {fadeOutMessage.animate();});
            YAHOO.util.Event.addListener(this._displayedImage, "click", this._toggleDrawer, this, true);
            YAHOO.util.Event.addListener(this._displayedImage, "keydown", function (event) {
                if (event.keyCode === YAHOO.util.KeyListener.KEY.SPACE) {
                    this._toggleDrawer();
                }
            }, this, true);
        },

        /**
         * Render the hidden input field.
         *
         * @param String name The name of the input field
         */
        _renderHiddenInput: function (name) {
            this._hiddenInput = document.createElement("input");
            this._hiddenInput.type = "hidden";
            this._hiddenInput.name = name;
            this._hiddenInput.value = this._selectedImageId;
            this._parent.appendChild(this._hiddenInput);
        },

        /**
         * Render the drawer.
         */
        _renderDrawer: function () {
            this._drawer = document.createElement("div");
            this._drawer.className = "imagePickerDrawer";
            this._drawerState = IP.DRAWER.CLOSED;
            YAHOO.util.Dom.setStyle(this._drawer, "opacity", 0);
            YAHOO.util.Dom.setStyle(this._drawer, "display", "none");
            this._parent.appendChild(this._drawer);

            // Draw table grid with images
            this._calculateGridWidth();
            var table = document.createElement("table");
            this._imageGrid = table;
            table.className = "imagePickerGrid";
            this._drawer.appendChild(table);

            var tableRow = table.insertRow(this._currentRow);

            var imageId;
            for (imageId in this._images) {
                var imageUrl = this._images[imageId];

                var tableCell = tableRow.insertCell(this._currentColumn);
                this._renderImageCell(tableCell, imageId, imageUrl);

                if (++this._currentColumn >= this._gridWidth) {
                    this._currentColumn = 0;
                    tableRow = table.insertRow(++this._currentRow);
                }
            }

            this._renderManagementButton();
            this._renderUploadButton();
        },

        /**
         * Add a new image to the image picker.
         *
         * @param {int} id  The image identifier
         * @param {string} url The URL where the image can be gotten from
         */
        _addImage: function(id, url) {
            // If we overflow the right side of the grid, then wrap onto a new line.
            if (this._currentColumn >= this._gridWidth) {
                this._currentColumn = 0;
                this._imageGrid.insertRow(++this._currentRow);
            }

            // Create the new image and select it.
            var newCell = this._imageGrid.rows[this._currentRow].insertCell(this._currentColumn++);
            this._renderImageCell(newCell, id, url);

            this._handleImageSelect(null, newCell.firstChild);
        },

        /**
         * Figure out grid dimensions.
         *
         * We want a grid with width greater than or equal to height, but not *too* wide.
         */
        _calculateGridWidth: function() {
            var numberOfImages = this._getNumberOfImages();

            if (numberOfImages <= 25) {
                this._gridWidth = 5;
            } else {
                this._gridWidth = Math.sqrt(numberOfImages);
            }
        },

        /**
         * Return the number of images used in this image picker.
         *
         * Unfortunately, the only way to do this seems to be iterating over the images (?), there's no
         * PHP equivalent to count().
         */
        _getNumberOfImages: function() {
            var numberOfImages = 0;

            var imageId;
            for (imageId in this._images) {
                if (this._images.hasOwnProperty(imageId)) {
                    numberOfImages++;
                }
            }

            return numberOfImages;
        },

        /**
         * Render a single image in the image picker grid
         *
         * @param {HTMLElement} parent A parent element (probably a <td>).
         * @param {integer} imageId A numeric identifier for the image (this is used to set the hidden form value).
         * @param {string} imageUrl A URL where the image can be fetched from.
         */
        _renderImageCell: function(parent, imageId, imageUrl) {
            var image = document.createElement("img");
            image.src = imageUrl;
            image.setAttribute("imageId", imageId);

            if (imageId === this._selectedImageId) {
                image.className = "imagePickerSelectedImage";
                this._selectedImageEl = image;
            }

            YAHOO.util.Event.addListener(image, "click", this._handleImageSelect, image, this);

            // This will effectively "preload" the image. Otherwise, the brower might wait until
            // the drawer is displayed:
            var i = new Image();
            i.src = imageUrl;

            parent.appendChild(image);

            if (YAHOO.lang.isNull(this._topLeftImage)) {
                this._topLeftImage = image;
            }
        },

        /**
         * Render the [optional] buttons at the bottom of the drawer that deal with image uploads.
         */
        _renderManagementButton: function () {
            if (this._managementUrl) {
                var managementButton = new YAHOO.widget.Button({
                    type: "link",
                    id: YAHOO.util.Dom.generateId(),
                    label: "Manage Images",
                    href: this._managementUrl,
                    container: this._drawer
                });

                managementButton.setStyle("position", "relative");
                managementButton.setStyle("left", "3px");
                managementButton.setStyle("margin-bottom", "5px");
                managementButton.setStyle("font-weight", "normal");
            }
        },

        /**
         * Renders the upload button and sets up the event handlers for selecting and uploading files.
         *
         * This was a nice, tight, funtion before I made it compatible with IE. Now it's a sprawling mess.
         */

        _renderUploadButton: function () {
            if (this._uploadUrl) {
                // Create a hidden form for the file upload
                var fileForm = document.createElement("form");
                fileForm.method = "post";
                fileForm.action = "/icon/create";
                fileForm.enctype = "multipart/form-data";

                var fileElement = document.createElement("input");
                fileElement.type = "file";
                fileElement.name = "imageUpload";
                fileElement.setAttribute("accept", "image/*"); // not supported in IE or safari
                fileForm.appendChild(fileElement);

                var csrfElement = document.createElement("input");
                csrfElement.type = "hidden";
                csrfElement.name = "csrf";
                csrfElement.value = document.getElementsByName("csrf")[0].value;
                fileForm.appendChild(csrfElement);

                var uploadButton = new YAHOO.widget.Button({
                    id: YAHOO.util.Dom.generateId(),
                    label: "Upload A New Image",
                    container: this._drawer
                });

                uploadButton.setStyle("position", "relative");
                uploadButton.setStyle("left", "3px");
                uploadButton.setStyle("margin-bottom", "5px");
                uploadButton.setStyle("font-weight", "normal");

                var ieDialog;
                if (YAHOO.env.ua.ie) {
                    /* IE doesn't let us call the click() method on a file input and then submit it through an iframe.
                     * So in IE we'll put the file form in a dialog instead of hiding it.
                     */
                    var formDiv = document.createElement("div");

                    var header = document.createElement("div");
                    header.className = "hd";
                    header.appendChild(document.createTextNode("Upload an image…"));
                    formDiv.appendChild(header);

                    var body = document.createElement("div");
                    body.className = "bd";
                    body.appendChild(fileForm);
                    formDiv.appendChild(body);

                    ieDialog = new YAHOO.widget.Dialog(formDiv, {
                        fixedCenter: true,
                        draggable: false,
                        modal: true
                    });

                    ieDialog.render(document.body);
                    ieDialog.hide();

                    uploadButton.on("click", function (event) {
                        ieDialog.show();
                    });
                } else {
                    // In good browsers, the button triggers the click event on a hidden file input.
                    YAHOO.util.Dom.setStyle(fileElement, "opacity", 0);
                    document.body.appendChild(fileForm);

                    uploadButton.on("click", function (event) {
                        fileElement.click();
                    });
                }

                // When a file is selected, send that image file to the server by submitting the form.
                YAHOO.util.Event.on(fileElement, "change", function () {
                    uploadButton.set('disabled', true);

                    if (YAHOO.env.ua.ie) {
                        ieDialog.hide();
                        this._showDrawer();
                    }

                    YAHOO.util.Connect.setForm(fileForm, true, true);
                    YAHOO.util.Connect.asyncRequest(
                        'POST',
                        this._uploadUrl,
                        {
                            upload: function (o) {
                                uploadButton.set('disabled', false);

                                var response;
                                try {
                                    response = YAHOO.lang.JSON.parse(o.responseText).response;
                                } catch (e) {
                                    Fisma.Util.showAlertDialog("Uploading failed: could not parse response.");
                                    return;
                                }

                                if (!response.success) {
                                    Fisma.Util.showAlertDialog("Uploading failed: " + response.message);
                                    return;
                                }

                                if (YAHOO.env.ua.ie && YAHOO.env.ua.ie <= 8) {
                                    // IE7 doesn't support data URIs, and IE8 doesn't support data
                                    // URIs over 32k, so it needs to make a 2nd request to get the image
                                    this._addImage(response.identifier, response.imageUrl);
                                } else {
                                    // Good browsers will display the data URI returned with the response
                                    this._addImage(response.identifier, response.imageData);
                                }
                            },
                            scope: this
                        }
                    );
                }, this, true);
            }
        },

        /**
         * Handle the selection of an image in the image picker.
         *
         * @param  {YAHOO.util.Event} event
         * @param {HTMLElement} imageEl The image element that was selected
         * @return {[type]}
         */
        _handleImageSelect: function (event, imageEl) {
            // Update the currently selected image information
            this._hiddenInput.value = imageEl.getAttribute("imageId");
            this._displayedImage.src = imageEl.src;

            // Move the selection UI
            if (this._selectedImageEl) {
                this._selectedImageEl.className = "0";
            }
            this._selectedImageEl = imageEl;
            this._selectedImageEl.className = "imagePickerSelectedImage";
        },

        /**
         * Toggle the drawer's visibility
         */
        _toggleDrawer: function () {
            if (this._drawerState === IP.DRAWER.OPEN) {
                this._hideDrawer();
            } else {
                this._showDrawer();
            }
        },

        /**
         * Show the drawer
         */
        _showDrawer: function () {
            var that = this;

            this._drawerState = IP.DRAWER.OPEN;

            var fadeInDrawer = new YAHOO.util.Anim(this._drawer, {
                opacity: {
                    to: 1.0
                }
            }, 0.5, YAHOO.util.Easing.easeOut);

            this._drawer.style.display = "block";
            fadeInDrawer.animate();

            // Close the drawer when the user clicks anywhere outside of the drawer or presses escape.
            setTimeout(function() {
                // IE7 doesn't like event listeners to be added inside an event handler, so I used setTimeout.
                YAHOO.util.Event.addListener(document.body, "click", that._handleBodyClick, that, true);
                YAHOO.util.Event.addListener(document.body, "keydown", that._handleBodyKeypress, that, true);
            }, 0);
        },

        /**
         * Hide the drawer
         */
        _hideDrawer: function () {
            this._drawerState = IP.DRAWER.CLOSED;

            var fadeOutDrawer = new YAHOO.util.Anim(this._drawer, {
                opacity: {
                    to: 0.0
                }
            }, 0.5, YAHOO.util.Easing.easeIn);

            fadeOutDrawer.onComplete.subscribe(function () {this._drawer.style.display = "none";}, this, true);
            fadeOutDrawer.animate();

            // Remove the event listeners set up in _showDrawer
            YAHOO.util.Event.removeListener(document.body, "click", this._handleBodyClick);
            YAHOO.util.Event.removeListener(document.body, "keydown", this._handleBodyKeypress);
        },

        /**
         * When the user clicks outside the drawer and the drawer is visible, hide the drawer.
         */
        _handleBodyClick: function (event) {
            var imageRegion = YAHOO.util.Dom.getRegion(this._displayedImage);
            var drawerRegion = YAHOO.util.Dom.getRegion(this._drawer);
            var clickPoint = new YAHOO.util.Point(event.pageX, event.pageY);

            // Don't respond to simulated clicks -- i.e. clicks triggered by click().
            if (clickPoint.x === 0 && clickPoint.y === 0) {
                return;
            }

            if (!imageRegion.contains(clickPoint) && !drawerRegion.contains(clickPoint)) {
                this._hideDrawer();
            }
        },

        /**
         * Handle key events while the drawer is visible.
         */
        _handleBodyKeypress: function (event) {
            var preventDefault = true;

            switch (event.keyCode) {
                // These cases intentionally fall through:
                case YAHOO.util.KeyListener.KEY.ESCAPE:
                case YAHOO.util.KeyListener.KEY.ENTER:
                    this._hideDrawer();
                    break;

                // These cases intentionally fall through:
                case YAHOO.util.KeyListener.KEY.UP:
                case YAHOO.util.KeyListener.KEY.DOWN:
                case YAHOO.util.KeyListener.KEY.LEFT:
                case YAHOO.util.KeyListener.KEY.RIGHT:
                    this._moveSelection(event.keyCode);
                    break;

                default:
                    preventDefault = false;
            }

            if (preventDefault) {
                YAHOO.util.Event.preventDefault(event);
            }
        },

        /**
         * Move the image selection up, down, left, or right
         *
         * @param {YAHOO.util.KeyListener.KEY} keyCode The direction to move
         */
        _moveSelection: function (keyCode) {
            var newImageEl;

            // If no image selected, automatically select the first image.
            if (YAHOO.lang.isNull(this._selectedImageEl)) {
                this._handleImageSelect(null, this._topLeftImage);
                return;
            }

            var cellIndex;
            switch (keyCode) {
                case YAHOO.util.KeyListener.KEY.UP:
                    var rowAbove = this._selectedImageEl.parentNode.parentNode.previousSibling;
                    var lastRow = this._selectedImageEl.parentNode.parentNode.parentNode.lastChild;
                    cellIndex = this._selectedImageEl.parentNode.cellIndex;

                    if (YAHOO.lang.isValue(rowAbove)) {
                        newImageEl = rowAbove.cells[cellIndex].firstChild;
                    } else {
                        if (lastRow.cells.length <= cellIndex) {
                            lastRow = lastRow.previousSibling;
                        }

                        newImageEl = lastRow.cells[cellIndex].firstChild;
                    }
                    break;
                case YAHOO.util.KeyListener.KEY.DOWN:
                    var rowBelow = this._selectedImageEl.parentNode.parentNode.nextSibling;
                    var firstRow = this._selectedImageEl.parentNode.parentNode.parentNode.firstChild;
                    cellIndex = this._selectedImageEl.parentNode.cellIndex;

                    if (YAHOO.lang.isValue(rowBelow) && rowBelow.cells.length > cellIndex) {
                        newImageEl = rowBelow.cells[cellIndex].firstChild;
                    } else {
                        newImageEl = firstRow.cells[cellIndex].firstChild;
                    }
                    break;
                case YAHOO.util.KeyListener.KEY.LEFT:
                    var cellToTheLeft = this._selectedImageEl.parentNode.previousSibling;
                    var lastCellInRow = this._selectedImageEl.parentNode.parentNode.lastChild.firstChild;

                    if (YAHOO.lang.isValue(cellToTheLeft)) {
                        newImageEl = cellToTheLeft.firstChild;
                    } else {
                        newImageEl = lastCellInRow;
                    }
                    break;
                case YAHOO.util.KeyListener.KEY.RIGHT:
                    var cellToTheRight = this._selectedImageEl.parentNode.nextSibling;
                    var firstCellInRow = this._selectedImageEl.parentNode.parentNode.firstChild.firstChild;

                    if (YAHOO.lang.isValue(cellToTheRight)) {
                        newImageEl = cellToTheRight.firstChild;
                    } else {
                        newImageEl = firstCellInRow;
                    }
                    break;
                default:
                    throw "Unexpected keycode " + keyCode;
            }

            if (YAHOO.lang.isValue(newImageEl) && newImageEl !== this._selectedImageEl) {
                this._handleImageSelect(null, newImageEl);
            }
        }
    });

    Fisma.ImagePicker = IP;
}());
