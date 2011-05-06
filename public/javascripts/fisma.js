/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @fileoverview Main js file
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 *
 * @todo      Start migrating functionality out of this file. 
 *            Eventually this file needs to be removed 
 */

var Fisma = {};

$P = new PHP_JS();

String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g,"");
}

var readyFunc = function () {
    var zfDebugYuiLoggingTab = document.getElementById('zfdebug_yui_logging_tab');
    
    if (zfDebugYuiLoggingTab) {
        var logReader = new YAHOO.widget.LogReader(
            zfDebugYuiLoggingTab, 
            {
                draggable : false,
                verboseOutput : false,
                width : '95%'
            }
        );
        
        logReader.hideCategory("info");
        logReader.hideCategory("time");
        logReader.hideCategory("window");
        logReader.hideCategory("iframe");
    }
    
    var calendars = YAHOO.util.Selector.query('.date');
    for(var i = 0; i < calendars.length; i ++) {
        YAHOO.util.Event.on(calendars[i].getAttribute('id')+'_show', 'click', callCalendar, calendars[i].getAttribute('id'));
    }
    
    // switch Aging Totals or Date Opened and End 
    var searchAging = function (){
        if (document.getElementById('remediationSearchAging')) {
            var value = document.getElementById('remediationSearchAging').value.trim();
        } else {
            return ;
        }
        var dateBegin = document.getElementById('created_date_begin');
        var dateEnd = document.getElementById('created_date_end');
        if (value == '0') {
            dateBegin.disabled = false;
            dateEnd.disabled = false;
            document.getElementById('show1').disabled = false;
            document.getElementById('show2').disabled = false;
        } else {
            dateBegin.disabled = true;
            dateEnd.disabled = true;
            dateBegin.value = '';
            dateEnd.value = '';
            document.getElementById('show1').disabled = true;
            document.getElementById('show2').disabled = true;
        }
    }
    YAHOO.util.Event.on('remediationSearchAging', 'change', searchAging);
    searchAging();
    
    var searchAging1 = function (){
        if (document.getElementById('created_date_begin') 
                && document.getElementById('created_date_end')) {
            var value1 = document.getElementById('created_date_begin').value.trim();
            var value2 = document.getElementById('created_date_end').value.trim();
        } else {
            return ;
        } 
        if(value1 != '' || value2 != '') {
            document.getElementById('remediationSearchAging').disabled = true;
        } else {
            document.getElementById('remediationSearchAging').disabled = false;
        }
    }
    YAHOO.util.Event.on('created_date_begin', 'change', searchAging1);
    YAHOO.util.Event.on('created_date_end', 'change', searchAging1);
    searchAging1();
    
    var changeEncrypt = function () {
        if (document.getElementById('encrypt')) {
            var obj = document.getElementById('encrypt');
        } else {
            return;
        }
        var value = obj.value.trim();
        if (value == 'sha256') {
             obj.style.display = '';
        } else {
             obj.style.display = 'none';
        }
    }
    YAHOO.util.Event.on('encrypt', 'change', changeEncrypt);
    changeEncrypt();

    //
    YAHOO.util.Event.on('function_screen', 'change', search_function);
    search_function();
    //
    YAHOO.util.Event.on('add_function', 'click', function() {
        var options = new YAHOO.util.Selector.query('#availableFunctions option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('existFunctions').appendChild(options[i]);
            }
        }
        return false;  
    });
    //
    YAHOO.util.Event.on('remove_function', 'click', function() {
        var options = YAHOO.util.Selector.query('#existFunctions option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('availableFunctions').appendChild(options[i]);
            }
        }
        return false;
    });
    //
    YAHOO.util.Event.on(YAHOO.util.Selector.query('form[name=assign_right]'), 'submit', 
    function (){
        var options = YAHOO.util.Selector.query('#existFunctions option');
        for (var i = 0; i < options.length; i ++) {
            options[i].selected = true;
        }
    });
    //
    YAHOO.util.Event.on('searchAsset', 'click', searchAsset);
    //
    YAHOO.util.Event.on('search_product' ,'click', searchProduct);
    //
    YAHOO.util.Event.on(YAHOO.util.Selector.query('.confirm'), 'click', function(){
        var str = "DELETING CONFIRMATION!";
        if(confirm(str) == true){
            return true;
        }
        return false;
    });
    //
    asset_detail();
    //
    getProdId();
}

function search_function() {
    var trigger = YAHOO.util.Selector.query('select[name=function_screen]');
    if (trigger == ''){return;}
    var param = name = '';
    var options = YAHOO.util.Selector.query('select[name=function_screen] option');

    for (var i = 0; i < options.length; i++) {
        if (options[i].selected == true) {
            name = options[i].text;
        }
    }
    if('' != name){
        param += '/screen_name/'+name;
    }
    var kids = YAHOO.util.Selector.query('#existFunctions option');
    var existFunctions = '';
    for (var i=0;i < kids.length;i++) {
        if (i == 0) {
            existFunctions += kids[i].value;
        } else {
            existFunctions += ',' + kids[i].value;
        }
    }
    var url = document.getElementById('function_screen').getAttribute('url')
              + '/do/availableFunctions' + param + '/existFunctions/'+existFunctions;
    var request = YAHOO.util.Connect.asyncRequest('GET', url, 
        {success: function(o){
                   document.getElementById('availableFunctions').parentNode.innerHTML = '<select style="width: 250px;" name="availableFunctions" id="availableFunctions" size="20" multiple="">'+o.responseText+'</select>';
                },
        failure: handleFailure});
}

var handleFailure = function(o){alert('error');}

function getProdId(){
    var trigger= document.getElementById('productId');
    YAHOO.util.Event.on(trigger, 'change', function (){
        document.getElementById('productId').value = trigger.value;
    });
}

var searchProduct = function (){
    var trigger = document.getElementById('search_product');
    var url = trigger.getAttribute('url');
    
    var productInput = YAHOO.util.Selector.query('input.product');
    for(var i = 0;i < productInput.length; i++) {
        if (productInput[i].value != undefined && productInput[i].value != '') {
            url += '/' + productInput[i].name + '/' + productInput[i].value;
        }
    }
    YAHOO.util.Connect.asyncRequest('GET', url, 
    {success: function(o){
                document.getElementById('productId').parentNode.innerHTML = o.responseText;
                document.getElementById('productId').style.width = "400px";
                getProdId();
              },
    failure: handleFailure});
}

var searchAsset = function() {
    var trigger = new YAHOO.util.Element('orgSystemId');
    if(trigger.get('id') == undefined){
        return ;
    }
    var sys = trigger.get('value');
    var param =  '';
    if(0 != parseInt(sys)){
        param +=  '/system_id/' + sys;
    }
    var assetInput = YAHOO.util.Selector.query('input.assets');
    for(var i = 0;i < assetInput.length; i++) {
        if (assetInput[i].value != undefined && assetInput[i].value != '') {
            param += '/' + assetInput[i].name + '/' + assetInput[i].value;
        }
    }
    var url = document.getElementById('orgSystemId').getAttribute("url") + param;
    YAHOO.util.Connect.asyncRequest('GET', url, 
    {success:function (o){
        document.getElementById('assetId').options.length = 0;
        var records = YAHOO.lang.JSON.parse(o.responseText);
        records = records.table.records;
        for(var i=0;i < records.length;i++){
            document.getElementById('assetId').options.add(new Option(records[i].name, records[i].id));
        }
    },
    failure: handleFailure});
}

function asset_detail() {
    YAHOO.util.Event.on('assetId', 'change', function (){
        var url = this.getAttribute("url") + this.value;
        YAHOO.util.Connect.asyncRequest('GET', url, {
            success:function (o){
                document.getElementById('asset_info').innerHTML = o.responseText
            },
            failure: handleFailure});
    });
}

function message(msg, model, clear) {
    clear = clear || false;

    msg = $P.stripslashes(msg);
    if (document.getElementById('msgbar')) {
        var msgbar = document.getElementById('msgbar'); 
    } else {
        return;
    }
    if (msgbar.innerHTML && !clear) {
        msgbar.innerHTML = msgbar.innerHTML + msg;
    } else {
        msgbar.innerHTML = msg;
    }

    msgbar.style.fontWeight = 'bold';
    
    if( model == 'warning')  {
        msgbar.style.color = 'red';
        msgbar.style.borderColor = 'red';
        msgbar.style.backgroundColor = 'pink';
    } else {
        msgbar.style.color = 'green';
        msgbar.style.borderColor = 'green';
        msgbar.style.backgroundColor = 'lightgreen';
    }
    msgbar.style.display = 'block';
}

function toggleSearchOptions(obj) {
    var searchbox = document.getElementById('advanced_searchbox');
    if (searchbox.style.display == 'none') {
        searchbox.style.display = '';
        obj.value = 'Basic Search';
    } else {
        searchbox.style.display = 'none';
        obj.value = 'Advanced Search';
    }
}

function showJustification(){
    if (document.getElementById('ecd_justification')) {
        document.getElementById('ecd_justification').style.display = '';
    }
}

function addBookmark(obj, url){
    if (window.sidebar) { 
        // Firefox
        window.sidebar.addPanel(url.title, url.href,'');
    } else if (window.opera) {
        // Opera
        var a = document.createElement("A");
        a.rel = "sidebar";
        a.target = "_search";
        a.title = url.title;
        a.href = url.href;
        a.click();
    } else if (document.all) { 
        // IE
        window.external.AddFavorite(url.href, url.title);
    } else {
        alert("Your browser does not support automatic bookmarks. Please try to bookmark this page manually instead.");
    }
}

function switchYear(step){
    if( !isFinite(step) ){
        step = 0;
    }
    var oYear = document.getElementById('gen_shortcut');
    var year = oYear.getAttribute('year');
    year = Number(year) + Number(step);
    oYear.setAttribute('year', year);
    var url = oYear.getAttribute('url') + year + '/';
    var tmp = YAHOO.util.Selector.query('#gen_shortcut span:nth-child(1)');
    tmp[0].innerHTML = year;
    tmp[0].parentNode.setAttribute('href', url);
    tmp[1].parentNode.setAttribute('href', url + 'q/1/');
    tmp[2].parentNode.setAttribute('href', url + 'q/2/');
    tmp[3].parentNode.setAttribute('href', url + 'q/3/');
    tmp[4].parentNode.setAttribute('href', url + 'q/4/');
}

/**
 * Check the form if has something changed but not saved
 * if nothing changes, then give a confirmation
 * @param dom check_form checking form
 * @param str user's current action
 */
function form_confirm (check_form, action) {
    var changed = false;
    
    elements = YAHOO.util.Selector.query("[name*='finding']");
    for (var i = 0;i < elements.length; i ++) {
        var tag_name = elements[i].tagName.toUpperCase();
        if (tag_name == 'INPUT') {
            var e_type = elements[i].type;
            if (e_type == 'text' || e_type == 'password') {
                var _v = elements[i].getAttribute('_value');
                if(typeof(_v) == 'undefined')   _v = '';
                if(_v != elements[i].value) {
                    ; //this logic is broken... needs a complete rewrite
                }
            }
            if (e_type == 'checkbox' || e_type == 'radio') {
                var _v = elements[i].checked ? 'on' : 'off';  
                if(_v != elements[i].getAttribute('_value')) {
                    changed = true;  
                }
            }
        } else if (tag_name == 'SELECT') {
            var _v = elements[i].getAttribute('_value');    
            if(typeof(_v) == 'undefined')   _v = '';    
            if(_v != elements[i].options[elements[i].selectedIndex].value) {
                changed = true;  
            }
        } else if (tag_name == 'TEXTAREA') {
            var _v = elements[i].getAttribute('_value');
            if(typeof(_v) == 'undefined')   _v = '';
            var textarea_val = elements[i].value ? elements[i].value : elements[i].innerHTML;
            if(_v != textarea_val) {
                changed = true;
            }
        }
    }

    if(changed) {
        if (confirm('WARNING: You have unsaved changes on the page. If you continue, these'
                  + ' changes will be lost. If you want to save your changes, click "Cancel"' 
                  + ' now and then click "Save Changes".')) {
            return true;
        }
        else {
            return false;
        }
    }
    
    if (confirm('WARNING: You are about to ' + action + '. This action cannot be undone.'
              + ' Please click "Ok" to confirm your action or click "Cancel" to stop.')) {
        return true;
    }

    return false;
}

function dump(arr) {
    var text = '' + arr;
    for (i in arr) {
        if ('function' != typeof(arr[i])) {
            text += i + " : " + arr[i] + "\n";
        }
    }
    alert(text);
} 

var e = YAHOO.util.Event;
e.onDOMReady(readyFunc);

function callCalendar(evt, ele) {
    showCalendar(ele, ele+'_show');
}

function showCalendar(block, trigger) {
    var Event = YAHOO.util.Event, Dom = YAHOO.util.Dom, dialog, calendar;

    var showBtn = Dom.get(trigger);
    
    var dialog;
    var calendar;
    
    // Lazy Dialog Creation - Wait to create the Dialog, and setup document click listeners, until the first time the button is clicked.
    if (!dialog) {
        function resetHandler() {
            Dom.get(block).value = '';
            closeHandler();
        }

        function closeHandler() {
            dialog.hide();
        }

        dialog = new YAHOO.widget.Dialog("container", {
            visible:false,
            context:[block, "tl", "bl"],
            draggable:true,
            close:true
        });
        
        dialog.setHeader('Pick A Date');
        dialog.setBody('<div id="cal"></div><div class="clear"></div>');
        dialog.render(document.body);

        dialogEl = document.getElementById('container');
        dialogEl.style.padding = "0px"; // doesn't format itself correctly in safari, for some reason

        dialog.showEvent.subscribe(function() {
            if (YAHOO.env.ua.ie) {
                // Since we're hiding the table using yui-overlay-hidden, we 
                // want to let the dialog know that the content size has changed, when
                // shown
                dialog.fireEvent("changeContent");
            }
        });
    }

    // Lazy Calendar Creation - Wait to create the Calendar until the first time the button is clicked.
    if (!calendar) {

        calendar = new YAHOO.widget.Calendar("cal", {
            iframe:false,          // Turn iframe off, since container has iframe support.
            hide_blank_weeks:true  // Enable, to demonstrate how we handle changing height, using changeContent
        });
        calendar.render();

        calendar.selectEvent.subscribe(function() {
            if (calendar.getSelectedDates().length > 0) {
                var selDate = calendar.getSelectedDates()[0];
                // Pretty Date Output, using Calendar's Locale values: Friday, 8 February 2008
                //var wStr = calendar.cfg.getProperty("WEEKDAYS_LONG")[selDate.getDay()];
                var dStr = (selDate.getDate() < 10) ? '0'+selDate.getDate() : selDate.getDate();
                var mStr = (selDate.getMonth()+1 < 10) ? '0'+(selDate.getMonth()+1) : (selDate.getMonth()+1);
                var yStr = selDate.getFullYear();

                Dom.get(block).value = yStr + '-' + mStr + '-' + dStr;
            } else {
                Dom.get(block).value = "";
            }
            dialog.hide();
            if ('finding[currentEcd]' == Dom.get(block).name) {
                validateEcd();
            }
        });

        calendar.renderEvent.subscribe(function() {
            // Tell Dialog it's contents have changed, which allows 
            // container to redraw the underlay (for IE6/Safari2)
            dialog.fireEvent("changeContent");
        });
    }

    var seldate = calendar.getSelectedDates();

    if (seldate.length > 0) {
        // Set the pagedate to show the selected date if it exists
        calendar.cfg.setProperty("pagedate", seldate[0]);
        calendar.render();
    }
    dialog.show();
}

function updateTimeField(id) {
    var hiddenEl = document.getElementById(id);
    var hourEl = document.getElementById(id + 'Hour');
    var minuteEl = document.getElementById(id + 'Minute');
    var ampmEl = document.getElementById(id + 'Ampm');
    
    var hour = hourEl.value;
    var minute = minuteEl.value;
    var ampm = ampmEl.value;
    
    if ('PM' == ampm) {
        hour = parseInt(hour) + 12;
    }
    
    hour = $P.str_pad(hour, 2, '0', 'STR_PAD_LEFT');
    minute = $P.str_pad(minute, 2, '0', 'STR_PAD_LEFT');    
    
    var time = hour + ':' + minute + ':00';
    hiddenEl.value = time;
}
