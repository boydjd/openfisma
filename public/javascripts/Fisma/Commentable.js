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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id: AttachArtifacts.js 3188 2010-04-08 19:35:38Z mhaase $
 */
 
Fisma.Commentable = {
    /**
     * Reference to the asynchonrous request dispatched by this object
     */
    asyncRequest : null,
    
    /**
     * A configuration object specified by the invoker of showPanel
     * 
     * See technical specification for Commentable behavior for the structure of this object
     */
    config : null,
    
    /**
     * Reference to the YUI panel which is displayed to input the comment
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
         Fisma.Commentable.config = config;

         // Create a new panel
         var newPanel = new YAHOO.widget.Panel('panel', {modal : true, close : true});
         newPanel.setHeader('Add Comment');
         newPanel.setBody("Loading...");
         newPanel.render(document.body);
         newPanel.center();
         newPanel.show();

         // Register listener for the panel close event
         newPanel.hideEvent.subscribe(function () {
             Fisma.Commentable.closePanel.call(Fisma.Commentable);
         });

         Fisma.Commentable.yuiPanel = newPanel;

         // Get panel content from comment controller
         YAHOO.util.Connect.asyncRequest(
             'GET', 
             '/comment/form',
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
             null);
         
         // Prevent form submission
         return false;
     },
     
     /**
      * Posts the artifact attachment form asynchronously
      * 
      * The form needs to be posted asynchronously because otherwise the browser will begin ignoring responses to XHR
      * requests -- which would totally defeat the purpose of upload progress tracking.
      * 
      * @param arg The AttachArtifacts object
      */
     postComment : function() {
         
         var postUrl = "/comment/add/id/" + encodeURIComponent(Fisma.Commentable.config.id) + "/type/" + encodeURIComponent(Fisma.Commentable.config.type) + "/format/json";

         YAHOO.util.Connect.setForm('addCommentForm');
         Fisma.Commentable.asyncRequest = YAHOO.util.Connect.asyncRequest(
             'POST', 
             postUrl, 
             {
                 success : function (asyncResponse) {
                     Fisma.Commentable.commentCallback.call(Fisma.Commentable, asyncResponse);
                 },

                 failure : function (o) {
                     alert('Document upload failed.');
                 }
             }, 
             null);
                  
         // Prevent form submission
         return false;
     },
     
     /**
      * Handle the server response after a comment is added
      * 
      * @param asyncResponse Response object from YUI connection
      */
     commentCallback : function (asyncResponse) {

         var responseStatus;
         
         // Check response status and display error message if necessary
         try {
             var responseObject = YAHOO.lang.JSON.parse(asyncResponse.responseText);
             responseStatus = responseObject.response;
         } catch (e) {
             if (e instanceof SyntaxError) {
                 // Handle a JSON syntax error by constructing a fake response object
                 responseStatus = new Object();
                 responseStatus.success = false;
                 responseStatus.message = "Invalid response from server.";
             } else {
                 throw e;
             }
         }

         if (!responseStatus.success) {
             alert("Error: " + responseStatus.message);

             return;
         }

         /*
          * Invoke callback. These are stored in the configuration as strings, so we need to find the real object 
          * references using array access notation.
          * 
          * @todo Error handling is bad here. We really need a JS debug mode so that we could help out the developer
          * realize if these callbacks are invalid.
          */
         var callbackObject = Fisma[this.config.callback.object];

         if (typeof callbackObject != "Undefined") {

             var callbackMethod = callbackObject[this.config.callback.method];

             if (typeof callbackMethod == "function") {

                 /**
                  * Passing callbackObject to call() will make that the scope for the called method, which gives "this"
                  * its expected meaning.
                  */
                 callbackMethod.call(callbackObject, responseStatus.comment, this.yuiPanel);
             }
         }
     },

     /**
      * Handle a panel close event by canceling the POST request
      */
     closePanel : function () {
         if (this.asyncRequest) {
             YAHOO.util.Connect.abort(this.asyncRequest);
         }
     }
};
