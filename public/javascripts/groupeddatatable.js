/*
Copyright (c) 2009, Mark Mansour. All rights reserved.
Code licensed under the BSD License: http://developer.yahoo.net/yui/license.txt
version: 2.8.0

Note: I'm adding this into my branch of the GroupedDataTable code.  I created it from
      Anthony Super's code with very significant reworking to make it more OO like.
*/
(function() {
    /*global YAHOO, document */
    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event,
        Util = YAHOO.util,
        Widget = YAHOO.widget,
        Lang = YAHOO.lang,
        DT = YAHOO.widget.DataTable;

    var GroupedDataTable = function(elContainer, aColumnDefs, oDataSource, oConfigs) {

        // If there is no 'groupBy' attribute in oConfigs return a plain YUI DataTable
        if (!oConfigs.groupBy) {
            return new YAHOO.widget.DataTable(elContainer, aColumnDefs, oDataSource, oConfigs);
        }

        this._groupBy = oConfigs.groupBy;

        // Set message for rows with no group
        this.MSG_NOGROUP = oConfigs.MSG_NOGROUP ? oConfigs.MSG_NOGROUP : "(none)";

        // If there is an existing row formatter, save it so I can call it after my own
        this._oldFormatRow = oConfigs.formatRow;

        // Now, set my own
        oConfigs.formatRow = this.rowFormatter;

        // you can use a varable before the declaration is finished?
        GroupedDataTable.superclass.constructor.call(this, elContainer, aColumnDefs, oDataSource, oConfigs);

        if(oConfigs.autoRender !== true && oConfigs.autoRender !== false) {
            oConfigs.autoRender = true;
        }

        if(oConfigs.autoRender === true) {
            this.initGroups(); // Not required but prevents flickering
        }

        // Re-initialise the groups when data is changed
        this.subscribe("sortedByChange", function(e) {
            this.initGroups(e);
        }); // Not required but prevents flickering

        // Unselect any group when a row is clicked
        this.subscribe("rowClickEvent", function(e) {
            this.unselectGroup(e);
        });

        // attach the DataTable object to the containing <table> element
        var container = YAHOO.util.Dom.get(elContainer);
        var table = YAHOO.util.Dom.getChildrenBy(container, function(el) { return el.tagName == "TABLE" ; })
        table[0].dataTable = this;
    };

    Widget.GroupedDataTable = GroupedDataTable;

    Lang.extend(GroupedDataTable, DT, {
        /**
        * The current group name. Used to determine when a new group starts when rowFormatter is called.
        * @property currentGroupName
        * @type {String}
        * @private
        */
        currentGroupName: null,

        /**
        * The groups found in the current data set.
        * @property groups
        * @type {Array}
        * @private
        */
        groups: [],

        /**
        * A flag to reset the group array. Set each time a new data set is passed.
        * @property resetGroups
        * @type {Boolean}
        * @private
        */
        resetGroups: true,

        /**
        * Event handler for group click.
        * @property groupClickEvent
        * @type {Event}
        */
        onGroupClick: new YAHOO.util.CustomEvent("onGroupClick", this),

        /**
        * The currently selected group
        * @property groupClickEvent
        * @type {Event}
        */
        selectedGroup: null,

        /**
        * A YUI DataTable custom row formatter. The row formatter must be applied to the DataTable
        * via the formatRow configuration property.
        * @method rowFormatter
        * @param tr {Object} To row to be formatted.
        * @param record {Object} To current data record.
        */
        rowFormatter: function(tr, record) {
            if (this.resetGroups) {
                this.groups = [];
                this.currentGroupName = null;
                this.resetGroups = false;
            }

            var groupName = record.getData(this._groupBy);

            if (groupName !== this.currentGroupName) {
                this.groups.push({ name: groupName, row: tr, record: record, group: null });
                Dom.addClass(tr, "group-first-row");
            }

            this.currentGroupName = groupName;
            return true;
        },

        /**
        * Initialises the groups for the current data set.
        * @method initGroups
        * @private
        */
        initGroups: function() {
            if (!this.resetGroups) {
                // Insert each group in the array
                for (var i = 0; i < this.groups.length; i++) {
                    this.groups[i].group = this.insertGroup(this.groups[i].name, this.groups[i].row);
                }

                this.resetGroups = true;
            }
        },

        /**
        * Inserts a group before the specified row.
        * @method insertGroup
        * @param name {String} The name of the group.
        * @param beforeRow {Object} To row to insert the group.
	* @return {String} Name of the group added
        * @private
        */
        insertGroup: function(name, row, insertBeforeRow) {
            var index = this.getRecordIndex(row);
            var group = document.createElement("tr");
            var groupCell = document.createElement("td");
            var numberOfColumns = this.getColumnSet().keys.length;
            var icon = document.createElement("div");

            if(insertBeforeRow != true && insertBeforeRow != false) {
                insertBeforeRow = true;
            }

            // Row is collapsed by default
            group.className = "group group-collapsed";
            groupCell.colSpan = numberOfColumns;  // setAttribute doesn't work in IE7
            if (Dom.hasClass(row, "yui-dt-first")) {
                // If this is the first row in the table, transfer the class to the group
                Dom.removeClass(row, "yui-dt-first");
                Dom.addClass(group, "group-first");
            }

            // Add a liner as per standard YUI cells
            var liner = document.createElement("div");
            liner.className = "liner";

            // Add icon
            icon.className = "icon";
            liner.appendChild(icon);

            // Add label
            var label = document.createElement("div");
            label.innerHTML = name ? this.visibleGroupName(name) : this.MSG_NOGROUP;
            label.className = "label";
            liner.appendChild(label);
            groupCell.appendChild(liner);
            group.appendChild(groupCell);

            // Insert the group
            if(insertBeforeRow) {
                Dom.insertBefore(group, row);
            } else {
                Dom.insertAfter(group, row);
            }

            // Attach visibility toggle to icon click
            Event.addListener(icon, "click", this.toggleVisibility, this);

            // Set up DOM events
            if (name.length > 0) { // Only if the group has a value
                Event.addListener(group, "mouseover", this.onGroupMouseover, this);
                Event.addListener(group, "mouseout", this.onGroupMouseout, this);
                Event.addListener(group, "mousedown", this.onGroupMousedown, this);
                Event.addListener(group, "mouseup", this.onGroupMouseup, this);
                Event.addListener(group, "click", this.onGroupClick, this);
                Event.addListener(group, "dblclick", this.onGroupDblclick, this);
            }
            else {
                // Disable the group
                Dom.addClass(group, "group-disabled");
            }

            this.fireEvent("insertGroupEvent", { group: group });

            // @TODO Make this into a separate method
            // Hide all subsequent rows in the group
            var row = Dom.getNextSibling(group);
            while (row && !Dom.hasClass(row, "group") &&
                !Dom.hasClass(row, "group-collapsed")) {
                    row.style.display = "none";

                row = Dom.getNextSibling(row);
            }
            return group;
        },

        /**
        * Handles the group select event.
        * @method onEventSelectGroup
        * @param type {String} The type of event fired.
        * @param e {Object} The selected group.
        * @private
        */
        onEventSelectGroup: function(args) {
            this.selectGroup(args);
        },

        /**
        * Selects a group.
        * @method selectGroup
        */
        selectGroup: function(args) {
            var target = args.target;
            var groupRow = this.getTrEl(target);

            // Do not re-select if already selected
            if (!this.selectedGroup || groupRow !== this.selectedGroup) {
                // Unselect any previous group
                this.unselectGroup(args);

                // Select the new group
                Dom.addClass(groupRow, "group-selected");
                this.selectedGroup = groupRow;

                // Unselect all rows in the data table
                var selectedRows = this.getSelectedTrEls();

                for (var i = 0; i < selectedRows.length; i++) {
                    this.unselectRow(selectedRows[i]);
                }

                var record = this.getGroupRecord(groupRow);
                this.fireEvent("groupSelectEvent", { record: record, el: groupRow });
            }
        },

        /**
        * Unselects any selected group.
        * @method unselectGroup
        */
        unselectGroup: function(args) {
            var target = args.target;
            var row = this.getTrEl(target);

            if (this.selectedGroup && row !== this.selectedGroup) {
                Dom.removeClass(this.selectedGroup, "group-selected");

                var record = this.getGroupRecord(this.selectedGroup);
                this.fireEvent("groupUnselectEvent", { record: record, el: this.selectedGroup });

                this.selectedGroup = null;
            }
        },

        /**
        * Toggles the visibility of the group specified in the event.
        * @method toggleVisibility
        * @param e {Event} The event fired from clicking the group.
        * @private
        */
        toggleVisibility: function(e, self) {
            var group = Dom.getAncestorByClassName(Event.getTarget(e), "group");
            var visibleState;

            // Change the class of the group
            if (Dom.hasClass(group, "group-expanded")) {
                visibleState = false;
                Dom.replaceClass(group, "group-expanded", "group-collapsed");
                self.fireEvent("groupCollapseEvent", { target: group, event: e });
            }
            else {
                visibleState = true;
                Dom.replaceClass(group, "group-collapsed", "group-expanded");
                self.fireEvent("groupExpandEvent", { target: group, event: e });
            }

            // Hide all subsequent rows in the group
            var row = Dom.getNextSibling(group);
            while (row && !Dom.hasClass(row, "group") &&
                !Dom.hasClass(row, "group-collapsed")) {
                if (visibleState) {
                    row.style.display = "";   // IE7 does not support 'table-row'
                }
                else {
                    row.style.display = "none";
                }

                row = Dom.getNextSibling(row);
            }
        },

        /**
        * For the given group identifier, returns the associated Record instance.
        * @method getGroupRecord
        * @param row {Object} DOM reference to a group TR element.
	    * @return {Object} The DataTable's record for the row
        * @private
        */
        getGroupRecord: function(groupRow) {
            for (var i = 0; i < this.groups.length; i++) {
                if (this.groups[i].group === groupRow) {
                    return this.groups[i].record;
                }
            }
        },

        /**
        * Overridden method which skips the TRs which are groups
        * @method getPreviousTrEl
        * @param row {Object} DOM reference to a group TR element.
	    * @return {HTMLElement} TR of the previous row
        * @private
        */
        getPreviousTrEl: function(row) {
            var currentRow = row;
            var previousRow = GroupedDataTable.superclass.getPreviousTrEl.call(this, currentRow);
            var firstRow = this.getFirstTrEl();

            if (previousRow == firstRow) {
                return null;
            }

            while (previousRow !== firstRow) {
                if (Dom.hasClass(previousRow, "group")) {
                    previousRow = GroupedDataTable.superclass.getPreviousTrEl.call(this, previousRow);
                } else {
                    return previousRow;  // skip the first row which is always a group
                }
            }

            return currentRow;
        },

        /**
        * Overridden method which skips the TRs which are groups
        * @method getNextTrEl
        * @param row {Object} DOM reference to a group TR element.
	    * @return {HTMLElement} TR of the next row
        * @private
        */
        getNextTrEl: function(row) {
            var nextRow = GroupedDataTable.superclass.getNextTrEl.call(this, row);
            var lastRow = this.getLastTrEl();

            while (nextRow !== lastRow) {
                if (Dom.hasClass(nextRow, "group")) {
                    nextRow = GroupedDataTable.superclass.getNextTrEl.call(this, nextRow);
                } else {
                    return nextRow;
                }
            }

            return lastRow;
        },


        /**
        * Check to see if the row is a group TR
        * @method isGroup
        * @param row {Object} DOM reference to a group TR element.
	    * @return {Boolean} if the row is a group
        * @public
        */
        isGroup: function(row) {
            return Dom.hasClass(row, 'group');
        },

        /**
        * Returns the number of rows which are groups that are above the row, where
        * the row represents a record, not a table index
        * @method isGroup
        * @param row {Object} DOM reference to a TR element of a record.
    	* @return {Number} count of the TRs above the row record
        * @public
        */
        groupRowsAboveRecord : function(row) {
            // if we are given a record number then convert it to a row
            var rowEl;
            if(Lang.isNumber(row)) {
                if(row >= this.getRecordSet().getLength()) {
                    row = this.getRecordSet().getLength() - 1;
                }
                rowEl = this.getRecordSet().getRecords()[row].getId();   // this.getRecord with an index > length returns weird results.
                // if the row is not visible because it has been added to the recordset and not the table,
                // then try the previous row
                if(Dom.get(rowEl) == null) {
                    if(this.getRecordSet().getRecords()[row - 1]) { // are there more records?
                        rowEl = this.getRecordSet().getRecords()[row - 1].getId();   // this.getRecord with an index > length returns weird results
                    } else {
                        return 0;  // there are no visible records
                    }

                }
            } else {
                rowEl = row;
            }

            var previousRow = GroupedDataTable.superclass.getTrEl.call(this,rowEl);
            var i = 0;
            while(previousRow != null) {
                if(Dom.hasClass(previousRow, "group")) {
                    i++;
                }
                previousRow = GroupedDataTable.superclass.getPreviousTrEl.call(this,previousRow);
            }
            return i;
        },

        /**
        * The name of the group for the table index
        * @method groupForTableIndex
        * @param row {Object} DOM reference to a TR element
    	* @return {String} name of the group for the row
        * @public
        */
        groupForTableIndex : function(row) {
            var previousRow = YAHOO.widget.GroupedDataTable.superclass.getTrEl.call(this,row);
            while(previousRow != null) {
                if(Dom.hasClass(previousRow, "group")) {
                    return this.groupName(previousRow);
                }
                previousRow = YAHOO.widget.GroupedDataTable.superclass.getPreviousTrEl.call(this,previousRow);
            }
            return null;
        },

        /**
        * The name of the group, given the actual group TR
        * @method groupName
        * @param row {HTMLElement} DOM reference to a TR element
    	* @return {String} The visible group's name
        * @private
        */
        groupName: function(row) {
            var label = Dom.getElementBy(function(el) { return Dom.hasClass(el, "label"); }, null, row);
            return label ? label.innerHTML : null;
        },

        /**
        * Provide a user friendly string for the group
        * @method visibleGroupName
        * @param name {Object} they group's key from the datastore
        * @return the user friendly version of the group name
        * @public
        */
        visibleGroupName: function(name) {
            return name;
        },

        /**
        * Find the HTMLElement for the group
        * @method getTRForGroup
        * @param groupName {String} they group's key from the datastore
        * @return {HTMLElement} representing the TR for the groupName or null if not found
        * @public
        */
        getTRForGroup: function(groupName) {
            var dt = this;
            var elTr = Dom.getFirstChildBy(this.getTbodyEl(), function(el) {
                return dt.isGroup(el) && dt.groupName(el) === groupName;
            });
            return elTr;
        },

        getDataTableFromId: function(id) {
            var el = Dom.get(id);

            // get the parent table
            var table = Dom.getAncestorByTagName(el, "TABLE");

            // return the dataTable attribute
            return table.dataTable;
        },


            /*
              Not used????
        getLastTableRowIndex: function(row) {
            var nextRow = YAHOO.widget.GroupedDataTable.superclass.getNextTrEl.call(this, row),
                rowNumber = row.sectionRowIndex;

            while(nextRow != null) {
                if(this.isGroup(nextRow)) {
                    return rowNumber;
                }
                rowNumber++;
                nextRow = YAHOO.widget.GroupedDataTable.superclass.getNextTrEl.call(this, nextRow);
            }

            return rowNumber;
        },

        getGroupIndex: function(tableIndex) {
            var previousRow = GroupedDataTable.superclass.getTrEl.call(this, tableIndex);
            var i = 0;
            while(previousRow != null) {
                if(Dom.hasClass(previousRow, "group")) {
                    i++;
                    return i;
                }
                previousRow = GroupedDataTable.superclass.getPreviousTrEl.call(this,previousRow);
            }
            return i;   // this should never happens as there will always be a group?
        },
            */

        onGroupMouseover: function(e, self) {
            self.fireEvent("groupMouseoverEvent", { target: Event.getTarget(e), event: e });
        },

        onGroupMouseout: function(e, self) {
            self.fireEvent("groupMouseoutEvent", { target: Event.getTarget(e), event: e });
        },

        onGroupMousedown: function(e, self) {
            self.fireEvent("groupMousedownEvent", { target: Event.getTarget(e), event: e });
        },

        onGroupMouseup: function(e, self) {
            self.fireEvent("groupMouseupEvent", { target: Event.getTarget(e), event: e });
        },

        onGroupClick: function(e, self) {
            self.fireEvent("groupClickEvent", { target: Event.getTarget(e), event: e });
        },

        onGroupDblclick: function(e, self) {
            self.fireEvent("groupDblclickEvent", { target: Event.getTarget(e), event: e });
        },

        onGroupSelect: function(e, self) {
            self.fireEvent("groupSelectEvent", { target: Event.getTarget(e), event: e });
        }

        // NOTE: destroy - should remove any events we've created and call the superclass

        /////////////////////////////////////////////////////////////////////////////
        //
        // Custom Events
        //
        /////////////////////////////////////////////////////////////////////////////

        /**
        * Fired when a group has a mouseover.
        *
        * @event groupMouseoverEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group has a mouseout.
        *
        * @event groupMouseoutEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group has a mousedown.
        *
        * @event groupMousedownEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group has a mouseup.
        *
        * @event groupMouseupEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group has a click.
        *
        * @event groupClickEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group has a dblclick.
        *
        * @event groupDblclickEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group is collapsed.
        *
        * @event groupCollapseEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group is expanded.
        *
        * @event groupExpandEvent
        * @param oArgs.event {HTMLEvent} The event object.
        * @param oArgs.target {HTMLElement} The TR element.
        */

        /**
        * Fired when a group is selected.
        *
        * @event groupSelectEvent
        * @param oArgs.el {HTMLElement} The selected TR element, if applicable.
        * @param oArgs.record {YAHOO.widget.Record} The selected Record.
        */

        /**
        * Fired when a group is unselected.
        *
        * @event groupUnselectEvent
        * @param oArgs.el {HTMLElement} The unselected TR element, if applicable.
        * @param oArgs.record {YAHOO.widget.Record} The unselected Record.
        */

    });
})();
