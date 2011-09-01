(function() {
    var Lang = YAHOO.lang,
        Event = YAHOO.util.Event,
        NDT = YAHOO.widget.NestedDataTable;

    var SCT = function (container, id) {
        this._securityAuthorizationId = id;

        var responseSchema = {
            resultsList : "records",
            metaFields : { 
                totalRecords : "totalRecords"
            }
        };
        
        var masterDataSource = new YAHOO.util.XHRDataSource('/sa/security-authorization/control-table-master/format/json/id/' + id);
        masterDataSource.connMethodPost = false;
        masterDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
        masterDataSource.responseSchema = responseSchema;
        
        var nestedDataSource = new YAHOO.util.XHRDataSource('/sa/security-authorization/control-table-nested/format/json');
        nestedDataSource.connMethodPost = false;
        nestedDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
        nestedDataSource.responseSchema = responseSchema;

        var masterColumnDefs = [
            { key: "code", label: "Code" },
            { key: "name", label: "Name" },
            { key: "class", label: "Class" },
            { key: "family", label: "Family" },
            { key: 'addEnhancements', label: "Add Enhancements", formatter: this._actionFormatter },
            { key: 'editCommonControl', label: "Edit Common Control", formatter: this._actionFormatter },
            { key: 'removeControl', label: "Remove Control", formatter: this._actionFormatter }
        ];
        var nestedColumnDefs = [
            { key: "number", label: "Enhancement" },
            { key: 'removeEnhancement', label: "Remove Enhancement", formatter: this._actionFormatter }
        ];
        
        var masterConfiguration = {
            generateNestedRequest: function (record) {
                return "/id/" + record.getData("id");
            }
        };
        var nestedConfiguration = {
            masterTable: this
        };

        SCT.superclass.constructor.call(this,
            container,
            masterColumnDefs, masterDataSource,
            nestedColumnDefs, nestedDataSource,
            masterConfiguration, nestedConfiguration);

        SCT._instanceMap[container] = this;
    };

    Fisma.SecurityControlTable = SCT;

    /**
     * @static
     */
    SCT._instanceMap = [];

    /**
     * @static
     */
    SCT.getByName = function(name) {
        return SCT._instanceMap[name];
    };

    Lang.extend(SCT, NDT, {
        /**
         * Security Authorization id
         */
        _securityAuthorizationId: null,

        _toggleFormatter: function (el, oRecord, oColumn, oData) {
            if (oRecord.getData('hasEnhancements')) {
                Fisma.SecurityControlTable.superclass._toggleFormatter.apply(this, arguments);
            }
        },

        _actionFormatter: function (el, oRecord, oColumn, oData) {
            var master = this.configs.masterTable ? this.configs.masterTable : this;
            if (oColumn.key != "addEnhancements" || oRecord.getData("hasMoreEnhancements")) {
                el.innerHTML = oColumn.label;
                var fn = master["_" + oColumn.key];
                Event.addListener(el, "click", fn, oRecord, master);
            }
        },

        _addEnhancements: function(ev, obj) {
            var id = this._securityAuthorizationId,
                securityControlId = obj.getData("securityControlId"),
                panel = Fisma.HtmlPanel.showPanel("Add Security Control", null, null, { modal : true }),
                getUrl = "/sa/security-authorization/add-enhancements/format/html/id/" + id + "/securityControlId/" + securityControlId,
                ctObj = this;
            var callbacks = {
                success: function(o) {
                    var panel = o.argument;
                    panel.setBody(o.responseText);
                    panel.center();
                },
                failure: function(o) {
                    var panel = o.argument;
                    panel.destroy();
                    alert('Error getting "add control" form: ' + o.statusText);
                },
                argument: panel
            };
            YAHOO.util.Connect.asyncRequest( 'GET', getUrl, callbacks);
        },
        _editCommonControl: function(ev, obj) {
            var id = this._securityAuthorizationId,
                securityControlId = obj.getData("securityControlId"),
                panel = Fisma.HtmlPanel.showPanel("Edit Common Security Control", null, null, { modal : true }),
                getUrl = "/sa/security-authorization/edit-common-control/format/html/id/" + id + "/securityControlId/" + securityControlId,
                ctObj = this;
            var callbacks = {
                success: function(o) {
                    var panel = o.argument;
                    panel.setBody(o.responseText);
                    panel.center();
                },
                failure: function(o) {
                    var panel = o.argument;
                    panel.destroy();
                    alert('Error getting "edit common control" form: ' + o.statusText);
                },
                argument: panel
            };
            YAHOO.util.Connect.asyncRequest( 'GET', getUrl, callbacks);
        },

        _removeControl: function (ev, obj) {
            var actionUrl = "/sa/security-authorization/remove-control/format/json" +
                            "/id/" + this._securityAuthorizationId,
                post = "securityControlId=" + obj.getData("securityControlId");
            this._removeEntry(actionUrl, post);
        },
    
        _removeEnhancement: function (ev, obj) {
            var actionUrl = "/sa/security-authorization/remove-enhancement/format/json" +
                            "/id/" + this._securityAuthorizationId,
                post = "securityControlEnhancementId=" + obj.getData("securityControlEnhancementId");
            this._removeEntry(actionUrl, post);
        },
    
        _removeEntry: function (actionUrl, post) {
            var callbacks = {
                success: function(o) {
                    var json = YAHOO.lang.JSON.parse(o.responseText);
                    if (json.result == 'ok') {
                        // @todo: dynamically remove the row from the table
                        window.location = window.location;
                    } else {
                        alert(json.result);
                    }
                },
                failure: function(o) {
                    alert('Error: ' + o.statusText);
                }
            };
            YAHOO.util.Connect.asyncRequest( 'POST', actionUrl, callbacks, post);
        }

    });
})();
