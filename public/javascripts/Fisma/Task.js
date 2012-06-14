/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @fileoverview Provides client-side behavior for the tasks behavior
 * 
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */
 
Fisma.Task = {
    /**
     * Reference to the asynchonrous request dispatched by this object
     */
    asyncRequest : null,

    /**
     * A configuration object specified by the invoker of showPanel
     * 
     * See technical specification for Task behavior for the structure of this object
     */
    config : null,

    /**
     * Reference to the createTaskPanel which is displayed to input some task fields
     */
    createTaskPanel : null,

    /**
     * A configuration object specified by the invoker of showPanel
     * 
     * See technical specification for Commentable behavior for the structure of this object
     */
    commentConfig : null,

    /**
     * Reference to the YUI panel which is displayed to input the comment
     */
    yuiPanel : null,

    /**
     * Show the file task panel
     * 
     * @param event Required to implement an event handler but not used
     * @param config Contains the callback information for this file upload (See definition of config member)
     */
    showPanel : function (event, config) {
        Fisma.Task.config = config;

        var panelConfig = {width : "50em", modal : true, close : true};
        var url = "/task/form/id/"
            + encodeURIComponent(Fisma.Task.config.id)
            + "/type/"
            + encodeURIComponent(Fisma.Task.config.type);

        Fisma.Task.createTaskPanel = Fisma.UrlPanel.showPanel(
            'Add Task',
            url,
            Fisma.Task.populateTaskForm,
            null,
            panelConfig
        );

        Fisma.Task.createTaskPanel.subscribe("hide", Fisma.Task.removeMessageBox);

        Fisma.Task.createTaskPanel.hideEvent.subscribe(function () {
            setTimeout(function () {
                Fisma.Task.createTaskPanel.destroy();
                Fisma.Task.createTaskPanel = null;
            }, 0);
        });

        return false;
    },

    /**
     * Remove the task modal dialog's custom message box
     * 
     * @param event {YAHOO.util.Event} The YUI event subscriber signature.
     */
    removeMessageBox: function (event) {
        Fisma.Registry.get("messageBoxStack").pop();

        if (YAHOO.env.ua.ie === 7) {
            this.yuiPanel.moveTo(5000,0);
        }
    },

    /**
     * Populate the task create form with some default values
     */
    populateTaskForm : function () {
        // this method is called in the wrong scope :(
        Fisma.Task.createTaskMessageBox();

        // The form contains some scripts that need to be executed
        var scriptNodes = Fisma.Task.createTaskPanel.body.getElementsByTagName('script');

        var i;
        for (i = 0; i < scriptNodes.length; i++) {
            try {
                eval(scriptNodes[i].text);
            } catch (e) {
                var message = 'Not able to execute one of the scripts embedded in this page: ' + e.message;
                Fisma.Util.showAlertDialog(message);
            } 
        }

        // The tool tips will display underneath the modal dialog mask, so we'll move them up to a higher layer.
        var tooltips = YAHOO.util.Dom.getElementsByClassName('yui-tt', 'div');

        var index;
        for (index in tooltips) {
            var tooltip = tooltips[index];

            // The yui panel is usually 4, so anything higher is good.
            tooltip.style.zIndex = 5;
        }
    },

    /**
     * Create Task modal dialog's custom message box
     */
    createTaskMessageBox: function () {
        var messageBarContainer = document.getElementById("taskMessageBar");

        if (YAHOO.lang.isNull(messageBarContainer)) {
            throw "No message bar container found.";
        }

        var taskMessageBox = new Fisma.MessageBox(messageBarContainer);
        Fisma.Registry.get("messageBoxStack").push(taskMessageBox);
    },

    /**
     * Submit an XHR to create a POC object
     */
    createTask : function () {
        // The scope is the button that was clicked, so save it for closures
        var button = this;
        var form = Fisma.Task.createTaskPanel.body.getElementsByTagName('form')[0];

        // Disable the submit button
        button.set("disabled", true);

        var postData = Fisma.Util.convertObjectToPostData({
            objectId: Fisma.Task.config.id,
            type: Fisma.Task.config.type
        });

        YAHOO.util.Connect.setForm(form);
        YAHOO.util.Connect.asyncRequest('POST', '/task/add/format/json', {
            success : function(asyncResponse) {
                var result;

                try {
                    result = YAHOO.lang.JSON.parse(asyncResponse.responseText).response;
                } catch (e) {
                    result = {success : false, message : e};
                }

                if (result.success) {
                    Fisma.Task.taskCallback.call(Fisma.Task, result);

                    Fisma.Task.createTaskPanel.hide();

                    Fisma.Util.message('A task has been created.', 'info', true);
                } else {
                    Fisma.Util.message(result.message, 'warning', true);
                    button.set("disabled", false);
                }
            },
            failure : function(o) {
                var alertMessage = 'Failed to create a new task ' + o.statusText;
                Fisma.Task.createTaskPanel.setBody(alertMessage);
            }
        }, postData);
    },

    /**
     * Handle the server response after a comment is added
     *
     * @param asyncResponse Response object from YUI connection
     */
    taskCallback : function (task) {
        var taskRow = {
            description : task.description,
            assignee : task.assignee,
            expectedCost : task.expectedCost,
            ecd : task.ecd,
            status : task.status,
            comment : null,
            action : null,
            findingStatus : null
        };

        var taskTable = Fisma.Registry.get("taskDataTable");
        taskTable.addRow(taskRow);
        taskTable.sortColumn(taskTable.getColumn('ecd'), YAHOO.widget.DataTable.CLASS_ASC);

        // Highlight the added row so the user can see that it worked
        var rowBlinker = new Fisma.Blinker(
            100,
            6,
            function () {
                taskTable.highlightRow(0);
            },
            function () {
                taskTable.unhighlightRow(0);
            }
        );

        rowBlinker.start();
    },

    /**
     * Show the comment panel for a task
     *
     * @param event Required to implement an event handler but not used
     * @param config Contains the callback information for this comment
     */
    showCommentPanel : function (event, config) {
        Fisma.Task.commentConfig = config;

        // Create a new panel
        var newPanel = new YAHOO.widget.Panel(YAHOO.util.Dom.generateId(), {modal : true, close : true});
        newPanel.setHeader('Add Comment');
        newPanel.setBody("Loading...");
        newPanel.render(document.body);
        newPanel.center();
        newPanel.show();

        // Register listener for the panel close event
        newPanel.hideEvent.subscribe(function () {
            Fisma.Task.closePanel.call(Fisma.Task);
        });

        Fisma.Task.yuiPanel = newPanel;

        // Get panel content from comment controller
        YAHOO.util.Connect.asyncRequest(
            'GET', 
            '/comment/form',
            {
                success: function(o) {
                    o.argument.setBody(o.responseText);
                    var button = new YAHOO.widget.Button(
                        YAHOO.util.Selector.query("input[type=submit]", o.argument.body, true)
                    );

                    o.argument.center();

                    var form = YAHOO.util.Selector.query("form", o.argument.body, true);
                    form.setAttribute('onsubmit', 'return Fisma.Task.postComment();');
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
     * Posts the comment form asynchronously
     */
    postComment : function() {
        var postData = Fisma.Util.convertObjectToPostData({
            taskId: Fisma.Task.commentConfig.id,
            objectId: Fisma.Task.commentConfig.objectId,
            type: Fisma.Task.commentConfig.type
        });

        YAHOO.util.Connect.setForm('addCommentForm');
        Fisma.Task.asyncRequest = YAHOO.util.Connect.asyncRequest(
            'POST', 
            '/task/add-comment/format/json', 
            {
                success : function (asyncResponse) {
                    Fisma.Task.commentCallback.call(Fisma.Task, asyncResponse);
                },

                failure : function (o) {
                    Fisma.Util.showAlertDialog('Comment can not be saved.');
                }
            }, 
            postData);

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
                responseStatus = {
                    success : false,
                    message : "Invalid response from server."
                };
            } else {
                throw e;
            }
        }

        if (!responseStatus.success) {
            var alertMessage = "Error: " + responseStatus.message;
            Fisma.Util.showAlertDialog(alertMessage);

            return;
        }

        Fisma.Task.handleCommentCallback.call(Fisma.Task, responseStatus.comment, Fisma.Task.yuiPanel);
    },

    /**
     * Handle successful comment events by inserting the latest comment into the top of the comment table
     * 
     * @param comment An object containing the comment record values
     * @param yuiPanel A reference to the modal YUI dialog
     */
    handleCommentCallback : function (comment, yuiPanel) {
        var taskDataTable = Fisma.Registry.get('taskDataTable');
        var row = taskDataTable.getTrEl(Fisma.Task.commentConfig.target);

        // Get the comment cell element
        var i, commentCell;
        for (i = 0; i < row.cells.length; i++ ) {
            if(YAHOO.util.Dom.hasClass(row.cells[i], "yui-dt-col-comment")) {
                commentCell = row.cells[i];
                break;
            }
        }

        // Hide YUI dialog
        yuiPanel.hide();
        yuiPanel.destroy();

        var commemtBlock = '<p>' + comment.username + ' ' + comment.createdTs + '<br>' + comment.comment + '</p>';

        commentCell.firstChild.innerHTML = commemtBlock + commentCell.firstChild.innerHTML;

        // Highlight the added row so the user can see that it worked
        var rowBlinker = new Fisma.Blinker(
            100,
            6,
            function () {
                taskDataTable.highlightCell(commentCell);
            },
            function () {
                taskDataTable.unhighlightCell(commentCell);
            }
        );

        rowBlinker.start();
    },

    /**
     * Handle a panel close event by canceling the POST request
     */
    closePanel : function () {
        if (this.asyncRequest) {
            YAHOO.util.Connect.abort(this.asyncRequest);
        }
    },

    /**
     * Update the value of status cell after firing the status menu item
     *
     * @param {String} sType String representing the name of the event that was fired.
     * @param {Array} aArgs Array of arguments sent when the event was fired.
     * @param {Object} oValue Object contains id, type, objectId and target
     */
    statusMenuItemClick: function(sType, aArgs, oValue) {
        var taskDataTable = Fisma.Registry.get('taskDataTable');
        var newValue = this.cfg.getProperty("text");
        var row = taskDataTable.getTrEl(oValue.target);

        // Get the status cell element
        var i, statusCell;
        for (i = 0; i < row.cells.length; i++ ) {
            if(YAHOO.util.Dom.hasClass(row.cells[i], "yui-dt-col-status")) {
                statusCell = row.cells[i];
                break;
            }
        }

        var postData = Fisma.Util.convertObjectToPostData({
            csrf: document.getElementById('finding_detail').elements.csrf.value,
            id: oValue.id,
            objectId: oValue.objectId,
            field: 'status',
            value: this.cfg.getProperty("text"),
            type: oValue.type
        });

        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/task/edit/format/json',
            {
                success : function (asyncResponse) {
                    var result;

                    try {
                        result = YAHOO.lang.JSON.parse(asyncResponse.responseText).response;
                    } catch (e) {
                        result = {success : false, message : e};
                    }

                    if (result.success) {

                        // Set the new value to cell
                        statusCell.firstChild.innerHTML = newValue;

                        var rowBlinker = new Fisma.Blinker(
                                100,
                                6,
                                function () {
                                    taskDataTable.highlightCell(statusCell);
                                },
                                function () {
                                    taskDataTable.unhighlightCell(statusCell);
                                }
                            );

                        rowBlinker.start();

                        Fisma.Util.message('Task has been updated.', 'info', true);
                    } else {
                        Fisma.Util.message(result.message, 'warning', true);
                    }
                },

                failure : function (o) {
                    Fisma.Util.showAlertDialog('Save task failed.');
                }
            },
            postData);
    },

    /**
     * Delete a task
     *
     * @param event Required to implement an event handler but not used
     * @param config Object contains the id, objectId and module type for this task
     */
    deleteRecord : function (event, config) {
        var dataTable = Fisma.Registry.get('taskDataTable');

        var deleteRecords = [];
        deleteRecords.push(YAHOO.lang.JSON.stringify(config));

        var config = {text : 'Delete this record?',
                      func : 'Fisma.Task.doDelete',
                      args : deleteRecords};

        Fisma.Util.showConfirmDialog(null, config);
    },

    /**
     * Implement to delete a record
     *
     * @param record Array contains the data of this task
     */
    doDelete : function (record) {
        var dataTable = Fisma.Registry.get('taskDataTable');

        // Flushes cache so that datatable will reload data instead of use cache
        dataTable.getDataSource().flushCache();

        var task = YAHOO.lang.JSON.parse(record);
        var postData = Fisma.Util.convertObjectToPostData({
            csrf: document.getElementById('finding_detail').elements.csrf.value,
            taskId: task.id,
            objectId: task.objectId,
            type: task.type
        });
  
        // Submit request to delete records
        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/task/delete/format/json',
            {
                success : function(o) {
                    if (o.responseText !== undefined) {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;

                        Fisma.Util.message(response.message, response.status, true);
                    }

                    dataTable.showTableMessage("Loading...");

                    dataTable.sortColumn(dataTable.getColumn('ecd'), YAHOO.widget.DataTable.CLASS_ASC);
                },
                failure : function(o) {
                    var text = 'An error occurred while trying to delete the record.';
                    text += ' The error has been logged for administrator review.'; 
                    Fisma.Util.message(text, "warning", true);
                }
            },
            postData);
    }
};
