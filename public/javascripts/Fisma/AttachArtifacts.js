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
 * @fileoverview Provides client-side behavior for the AttachArtifacts behavior
 * 
 * @todo This is currently set up to only handle one file upload at time. With some refactoring, however, we could
 * support multiple file uploads in parallel.
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */
 
Fisma.AttachArtifacts = {
    
    /**
     * The amount of time to delay in between requesting upload progress
     */
    sampleInterval : 1000,
    
    /**
     * The APC file upload ID which is used to track this upload on the server side
     */
    apcId : null,
    
    /**
     * A reference to the YUI progress bar
     */
    yuiProgressBar : null,
    
    /**
     * Server polling timeout ID
     * 
     * Polling is accomplished using settimout(). The ID which that returns is saved so that the timeout can be
     * canceled when the upload is finished (or fails).
     */
    pollingTimeoutId : null,
    
    /**
     * Reference to the last asynchonrous request dispatched by this object
     * 
     * This can be used to cancel the last pending request before it completes
     */
    lastAsyncRequest : null,
    
    /**
     * A flag that indicates whether polling is enabled or not
     */
    pollingEnabled : false,
    
    /**
     * A configuration object specified by the invoker of showPanel
     * 
     * See technical specification for Attach Artifacts behavior for the structure of this object
     */
    config : null,
    
    /**
     * Reference to the YUI panel which is displayed to handle file uploads
     */
     yuiPanel : null,
        
    /**
     * Show the file upload panel
     * 
     * This is an event handler, so 'this' will not refer to the local object
     * 
     * @param event Required to implement an event handler but not used
     * @param config Contains the callback information for this file upload (See definition of config member)
     */
    showPanel : function (event, config) {
        Fisma.AttachArtifacts.config = config;

        // Create a new panel
        var newPanel = new YAHOO.widget.Panel('panel', {modal : true, close : false});
        newPanel.setHeader('Upload Artifact');
        newPanel.setBody("Loading...");
        newPanel.render(document.body);
        newPanel.center();
        newPanel.show();

        Fisma.AttachArtifacts.yuiPanel = newPanel;

        // Get panel content from artifact controller
        YAHOO.util.Connect.asyncRequest(
            'GET', 
            '/artifact/upload-form',
            {
                success: function(o) {
                    o.argument.setBody(o.responseText);
                    o.argument.center();
                },

                failure: function(o) {
                    o.argument.setBody('The content for this panel could not be loaded.');
                    o.argument.center();
                },
                
                argument: newPanel
            }, 
            null
        );
    },
    
    /**
     * Show the progress bar and kick off the tracking process
     * 
     * This is called in the onSubmit event
     */
    trackUploadProgress : function () {

        // Disable the upload button
        var uploadButton = document.getElementById('uploadButton');
        uploadButton.disabled = true;

        /**
         * If upload progress is enabled on the server, then there will be a hidden element in the page with the ID
         * 'progress_key'. This is an indicator whether or not to enable upload progress on the client side.
         */
        var apcHiddenEl = document.getElementById('progress_key');

        if (apcHiddenEl) {
            this.apcId = apcHiddenEl.value;
            
            // Remove the inderminate progress bar
            var progressBarContainer = document.getElementById('progressBarContainer');

            var progressBarWidth = parseInt(YAHOO.util.Dom.getStyle(progressBarContainer, 'width'));
            var progressBarHeight = parseInt(YAHOO.util.Dom.getStyle(progressBarContainer, 'height'));

            YAHOO.util.Dom.removeClass(progressBarContainer, 'attachArtifactsProgressBar');

            while (progressBarContainer.hasChildNodes()) {
                progressBarContainer.removeChild(progressBarContainer.firstChild);
            }

            // Add YUI bar
            var yuiProgressBar = new YAHOO.widget.ProgressBar();
            
            yuiProgressBar.set('width', progressBarWidth); 
            yuiProgressBar.set('height', progressBarHeight);
            
            yuiProgressBar.set('ariaTextTemplate', 'Upload is {value}% complete');

            yuiProgressBar.set('anim', true);
            var animation = yuiProgressBar.get('anim')
            animation.duration = 2;
            animation.method = YAHOO.util.Easing.easeNone;
            
            yuiProgressBar.render('progressBarContainer');
            
            YAHOO.util.Dom.addClass(progressBarContainer, 'attachArtifactsProgressBar');
            
            // Store progress bar reference inside this object
            this.yuiProgressBar = yuiProgressBar;

            // Kick off the polling loop
            this.pollingEnabled = true;
            
            setTimeout(
                this.getProgress, 
                this.sampleInterval, 
                this
            );
        }

        // Display the progress bar
        document.getElementById('progressBarContainer').style.display = 'block';
        document.getElementById('progressTextContainer').style.display = 'block';

        /**
         * Post the form. This needs to be done aysnchronously, or else the web browser will not 
         * respond to the progress tracking XHRs
         */
        setTimeout(this.postForm, 0, this);
        
        return false;
    },
    
    /**
     * Posts the artifact attachment form asynchronously
     * 
     * The form needs to be posted asynchronously because otherwise the browser will begin ignoring responses to XHR
     * requests -- which would totally defeat the purpose of upload progress tracking.
     * 
     * This is called by settimeout(), which means the execution context is not the object, and the 'this' keyword
     * won't refer to the object either. So the object is passed in as the 'arg' parameter
     * 
     * @param arg The AttachArtifacts object
     */
    postForm : function(arg) {

        var postUrl = "/"
                    + encodeURIComponent(arg.config.server.controller)
                    + "/"
                    + encodeURIComponent(arg.config.server.action)
                    + "/id/"
                    + encodeURIComponent(arg.config.id)
                    + "/format/json";

        YAHOO.util.Connect.setForm('uploadArtifactForm', true);
        YAHOO.util.Connect.asyncRequest(
            'POST', 
            postUrl, 
            {
                upload : arg.handleUploadComplete,
                
                failure : function (o) {
                    alert('Document upload failed.');
                }, 
                
                argument : arg
            }, 
            null
        );
    },
    
    /**
     * Poll the server for file upload progress
     * 
     * This is called by settimeout(), which means the execution context is not the object, and the 'this' keyword
     * won't refer to the object either. So the object is passed in as the 'arg' parameter
     * 
     * @param arg The AttachArtifacts object
     */
    getProgress : function (arg) {

        if (arg.pollingEnabled) {
            arg.lastAsyncRequest = YAHOO.util.Connect.asyncRequest(
                'GET', 
                '/artifact/upload-progress/format/json/id/' + arg.apcId,
                {
                    success : function (asyncResponse) {
                    
                        // Parse server response and update progress bar
                        var response = YAHOO.lang.JSON.parse(asyncResponse.responseText);
                        var percent = Math.round((response.progress.current / response.progress.total) * 100);
                        arg.yuiProgressBar.set('value', percent);
                    
                        // Update progress text
                        var progressTextEl = document.getElementById('progressTextContainer').firstChild;

                        progressTextEl.nodeValue = percent + '%';
                    
                        // Reschedule the timeout to call this method again
                        arg.pollingTimeoutId = setTimeout(arg.getProgress, arg.sampleInterval, arg);
                    }
                }, 
                null
            );
        }
    },
    
    /**
     * Handle a completed file upload
     * 
     * This object's contents are passed as asyncResponse.argument and stored into the local variable "attachArtifacts"
     * 
     * @param asyncResponse Response object from YUI connection
     */
    handleUploadComplete : function (asyncResponse) {

        var attachArtifacts = asyncResponse.argument;

        // Check response status and display error message if necessary
        var responseStatus = YAHOO.lang.JSON.parse(asyncResponse.responseText);
        
        if (!responseStatus.success) {
            alert(responseStatus.message);
        }
        
        // Stop the polling process and cancel the last asynchronous request
        attachArtifacts.pollingEnabled = false;
        clearTimeout(attachArtifacts.pollingTimeoutId);
        YAHOO.util.Connect.abort(attachArtifacts.lastAsyncRequest);
        
        // Update progress to 100%
        if (attachArtifacts.yuiProgressBar) {
            attachArtifacts.yuiProgressBar.get('anim').duration = .5;
            attachArtifacts.yuiProgressBar.set('value', 100);
        }
        document.getElementById('progressTextContainer').firstChild.textValue = 'Verifying file.';
                
        /**
         * Invoke callback. These are stored in the configuration as strings, so we need to find the real object 
         * references using array access notation.
         * 
         * @todo Error handling is bad here. We really need a JS debug mode so that we could help out the developer
         * realize if these callbacks are invalid.
         */
        var callbackObject = Fisma[attachArtifacts.config.callback.object];

        if (typeof callbackObject != "Undefined") {
            
            var callbackMethod = callbackObject[attachArtifacts.config.callback.method];
            
            if (typeof callbackMethod == "function") {
                
                /**
                 * Passing callbackObject to call() will make that the scope for the called method, which gives "this"
                 * its expected meaning.
                 */
                callbackMethod.call(callbackObject, attachArtifacts.yuiPanel);
            }
        }
    }
};
