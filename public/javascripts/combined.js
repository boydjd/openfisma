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
 * @fileoverview Configuration file for tiny_mce, configuration options may be found at the following website.
 *               http://wiki.moxiecode.com/index.php/TinyMCE:Configuration
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

tinyMCE.init({
	theme : "advanced",
	mode : "textareas",
	cleanup : false,
	element_format : "html",
	plugins : "spellchecker, searchreplace, insertdatetime, print, fullscreen",
	plugin_insertdate_dateFormat : "%Y-%m-%d",
	plugin_insertdate_timeFormat : "%H:%M:%S",
	browsers : "msie,gecko,safari,opera",
	theme_advanced_buttons1 : "bold, italic, underline, |, bullist, numlist, |, outdent, indent, |, cut, copy, paste, |, undo, redo, |, spellchecker, |, search, replace, |, insertdate, inserttime, link, unlink, |, print, fullscreen",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	spellchecker_rpc_url : '/javascripts/tiny_mce/plugins/spellchecker/rpc.php',
	spellchecker_languages : "+English=en"
});
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

// Required for AC_RunActiveContent
// @TODO Move into own file

var requiredMajorVersion = 9;
var requiredMinorVersion = 0;
var requiredRevision = 45;

var Fisma = {};

String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g,"");
}

var readyFunc = function () {
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

function upload_evidence() {
    if (!form_confirm(document.finding_detail, 'Upload Evidence')) {
        return false;
    }
    // set the encoding for a file upload
    document.finding_detail.enctype = "multipart/form-data";
    panel('Upload Evidence', document.finding_detail, '/remediation/upload-form');
    return false;
}

function ev_approve(formname){
    if (!form_confirm(document.finding_detail, 'approve the evidence package')) {
        return false;
    }

    var content = document.createElement('div');
    var p = document.createElement('p');
    p.appendChild(document.createTextNode('Comments (OPTIONAL):'));
    content.appendChild(p);
    var dt = document.createElement('textarea');
    dt.rows = 5;
    dt.cols = 60;
    dt.id = 'dialog_comment';
    dt.name = 'comment';
    content.appendChild(dt);
    var div = document.createElement('div');
    div.style.height = '20px';
    content.appendChild(div);
    var button = document.createElement('input');
    button.type = 'button';
    button.id = 'dialog_continue';
    button.value = 'Continue';
    content.appendChild(button);

    panel('Evidence Approval', document.finding_detail, '', content.innerHTML);
    document.getElementById('dialog_continue').onclick = function (){
        var form2 = formname;
        if  (document.all) { // IE
            var comment = document.getElementById('dialog_comment').innerHTML;
        } else {// firefox
            var comment = document.getElementById('dialog_comment').value;
        }
        form2.elements['comment'].value = comment;
        form2.elements['decision'].value = 'APPROVED';
        var submitMsa = document.createElement('input');
        submitMsa.type = 'hidden';
        submitMsa.name = 'submit_ea';
        submitMsa.value = 'APPROVED';
        form2.appendChild(submitMsa);
        form2.submit();
    }
}

function ev_deny(formname){
    if (!form_confirm(document.finding_detail, 'deny the evidence package')) {
        return false;
    }

    var content = document.createElement('div');
    var p = document.createElement('p');
    p.appendChild(document.createTextNode('Comments:'));
    content.appendChild(p);
    var dt = document.createElement('textarea');
    dt.rows = 5;
    dt.cols = 60;
    dt.id = 'dialog_comment';
    dt.name = 'comment';
    content.appendChild(dt);
    var div = document.createElement('div');
    div.style.height = '20px';
    content.appendChild(div);
    var button = document.createElement('input');
    button.type = 'button';
    button.id = 'dialog_continue';
    button.value = 'Continue';
    content.appendChild(button);

    panel('Evidence Denial', document.finding_detail, '', content.innerHTML);
    document.getElementById('dialog_continue').onclick = function (){
        var form2 = formname;
        if  (document.all) { // IE
            var comment = document.getElementById('dialog_comment').innerHTML;
        } else {// firefox
            var comment = document.getElementById('dialog_comment').value;
        }
        if (comment.match(/^\s*$/)) {
            alert('Comments are required in order to deny.');
            return;
        }
        form2.elements['comment'].value = comment;
        form2.elements['decision'].value = 'DENIED';
        var submitMsa = document.createElement('input');
        submitMsa.type = 'hidden';
        submitMsa.name = 'submit_ea';
        submitMsa.value = 'DENIED';
        form2.appendChild(submitMsa);
        form2.submit();
    }
}

function ms_approve(formname){
    if (!form_confirm(document.finding_detail, 'approve the mitigation strategy')) {
        return false;
    }

    var content = document.createElement('div');
    var p = document.createElement('p');
    var c_title = document.createTextNode('Comments (OPTIONAL):');
    p.appendChild(c_title);
    content.appendChild(p);
    var textarea = document.createElement('textarea');
    textarea.id = 'dialog_comment';
    textarea.name = 'comment';
    textarea.rows = 5;
    textarea.cols = 60;
    content.appendChild(textarea);
    var div = document.createElement('div');
    div.style.height = '20px';
    content.appendChild(div);
    var button = document.createElement('input');
    button.type = 'button';
    button.id = 'dialog_continue';
    button.value = 'Continue';
    content.appendChild(button);
    
    panel('Mitigation Strategy Approval', document.finding_detail, '', content.innerHTML);
    document.getElementById('dialog_continue').onclick = function (){
        var form2 = formname;
        if  (document.all) { // IE
            var comment = document.getElementById('dialog_comment').innerHTML;
        } else {// firefox
            var comment = document.getElementById('dialog_comment').value;
        }
        form2.elements['comment'].value = comment;
        form2.elements['decision'].value = 'APPROVED';
        var submitMsa = document.createElement('input');
        submitMsa.type = 'hidden';
        submitMsa.name = 'submit_msa';
        submitMsa.value = 'APPROVED';
        form2.appendChild(submitMsa);
        form2.submit();
    }
}

function ms_deny(formname){
    if (!form_confirm(document.finding_detail, 'deny the mitigation strategy')) {
        return false;
    }

    var content = document.createElement('div');
    var p = document.createElement('p');
    var c_title = document.createTextNode('Comments:');
    p.appendChild(c_title);
    content.appendChild(p);
    var textarea = document.createElement('textarea');
    textarea.id = 'dialog_comment';
    textarea.name = 'comment';
    textarea.rows = 5;
    textarea.cols = 60;
    content.appendChild(textarea);
    var div = document.createElement('div');
    div.style.height = '20px';
    content.appendChild(div);
    var button = document.createElement('input');
    button.type = 'button';
    button.id = 'dialog_continue';
    button.value = 'Continue';
    content.appendChild(button);
    
    panel('Mitigation Strategy Denial', document.finding_detail, '', content.innerHTML);
    document.getElementById('dialog_continue').onclick = function (){
        var form2 = formname;
        if  (document.all) { // IE
            var comment = document.getElementById('dialog_comment').innerHTML;
        } else {// firefox
            var comment = document.getElementById('dialog_comment').value;
        }
        if (comment.match(/^\s*$/)) {
            alert('Comments are required in order to submit.');
            return;
        }
        form2.elements['comment'].value = comment;
        form2.elements['decision'].value = 'DENIED';
        var submitMsa = document.createElement('input');
        submitMsa.type = 'hidden';
        submitMsa.name = 'submit_msa';
        submitMsa.value = 'DENIED';
        form2.appendChild(submitMsa);
        form2.submit();
    }
}

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

function message( msg ,model){
    if (document.getElementById('msgbar')) {
        var msgbar = document.getElementById('msgbar'); 
    } else {
        return;
    }
    if (msgbar.innerHTML) {
        msgbar.innerHTML = msgbar.innerHTML + msg;
    } else {
        msgbar.innerHTML = msg;
    }

    msgbar.style.fontWeight = 'bold';
    
    if( model == 'warning')  {
        msgbar.style.color = 'red';
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

/**
 * A hastily written helper function for highlightWord() that iterates over an array of keywords
 */
function highlight(node, keywords) {
    // Sometimes keyword is blank... in that case, just return
    if ('' == keywords) {
        return;
    }
    
    // Sort in reverse. If a word is a fragment of another word on this list, it will highlight the larger
    // word first
    keywords.sort();
    keywords.reverse();

    // Highlight each word
    for (var i in keywords) {
        highlightWord(node, keywords[i]);
    }
}

/**
 * Recursively searches the dom for a keyword and highlights it by appliying a class selector called
 * 'highlight'
 *
 * @param node object
 * @param keyword string
 */ 
function highlightWord(node, keyword) {
	// Iterate into this nodes childNodes
	if (node && node.hasChildNodes) {
		var hi_cn;
		for (hi_cn=0;hi_cn<node.childNodes.length;hi_cn++) {
			highlightWord(node.childNodes[hi_cn],keyword);
		}
	}

	// And do this node itself
	if (node && node.nodeType == 3) { // text node
		tempNodeVal = node.nodeValue.toLowerCase();
		tempWordVal = keyword.toLowerCase();
		if (tempNodeVal.indexOf(tempWordVal) != -1) {
			pn = node.parentNode;
			if (pn.className != "highlight") {
				// keyword has not already been highlighted!
				nv = node.nodeValue;
				ni = tempNodeVal.indexOf(tempWordVal);
				// Create a load of replacement nodes
				before = document.createTextNode(nv.substr(0,ni));
				docWordVal = nv.substr(ni,keyword.length);
				after = document.createTextNode(nv.substr(ni+keyword.length));
				hiwordtext = document.createTextNode(docWordVal);
				hiword = document.createElement("span");
				hiword.className = "highlight";
				hiword.appendChild(hiwordtext);
				pn.insertBefore(before,node);
				pn.insertBefore(hiword,node);
				pn.insertBefore(after,node);
				pn.removeChild(node);
			}
		}
	}
}

/**
 * Remove the highlight attribute from the editable textarea on remediation detail page
 *
 * @param node object 
 */
function removeHighlight(node) {
	// Iterate into this nodes childNodes
	if (node.hasChildNodes) {
		var hi_cn;
		for (hi_cn=0;hi_cn<node.childNodes.length;hi_cn++) {
			removeHighlight(node.childNodes[hi_cn]);
		}
	}

	// And do this node itself
	if (node.nodeType == 3) { // text node
		pn = node.parentNode;
		if( pn.className == "highlight" ) {
			prevSib = pn.previousSibling;
			nextSib = pn.nextSibling;
			nextSib.nodeValue = prevSib.nodeValue + node.nodeValue + nextSib.nodeValue;
			prevSib.nodeValue = '';
			node.nodeValue = '';
		}
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

/* temporary helper function to fix a bug in evidence upload for IE6/IE7 */
function panel(title, parent, src, html, callback) {
    var newPanel = new YAHOO.widget.Panel('panel', {width:"540px", modal:true} );
    newPanel.setHeader(title);
    newPanel.setBody("Loading...");
    newPanel.render(parent);
    newPanel.center();
    newPanel.show();
    
    if (src != '') {
        // Load the help content for this module
        YAHOO.util.Connect.asyncRequest('GET', 
                                        src,
                                        {
                                            success: function(o) {
                                                // Set the content of the panel to the text of the help module
                                                o.argument.setBody(o.responseText);
                                                // Re-center the panel (because the content has changed)
                                                o.argument.center();
                                                
                                                if (callback) {
                                                    callback();
                                                }
                                            },
                                            failure: function(o) {alert('Failed to load the specified panel.');},
                                            argument: newPanel
                                        }, 
                                        null);
    } else {
        // Set the content of the panel to the text of the help module
        newPanel.setBody(html);
        // Re-center the panel (because the content has changed)
        newPanel.center();
    }
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
 * @fileoverview Handle a click on the checkbox tree. Clicking a nested node will select all nodes inside of it,
 *               unless all of the subnodes are already selected, in which case it will deselect all subnodes.
 *               Holding down the option key while clicking disables this behavior.
 *               
 *               The checkbox tree DOM looks like this:
 *               <li><input type="checkbox" nestedLevel="0"><label></li>
 *                 <li><input type="checkbox" nestedLevel="1"><label></li>
 *                  <li><input type="checkbox" nestedLevel="2"><label></li>
 *               etc...
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

YAHOO.namespace("fisma.CheckboxTree");

YAHOO.fisma.CheckboxTree.rootNode;

YAHOO.fisma.CheckboxTree.handleClick = function(clickedBox, event) 
{
    // If the option key is held down, then skip all of this logic.
    if (event.altKey) {
        return;
    }

    var topListItem = clickedBox.parentNode;

    // If there are no nested checkboxes, then there is nothing to do
    var nextCheckbox = topListItem.nextSibling.childNodes[0];
    if (nextCheckbox.getAttribute('nestedLevel') > clickedBox.getAttribute('nestedLevel')) {
        var minLevel = clickedBox.getAttribute('nestedlevel');
        var checkboxArray = new Array();
        var allChildNodesChecked = true;

        // Loop through all of the subnodes and see which ones are already checked
        var listItem = topListItem.nextSibling;
        var checkboxItem = listItem.childNodes[0];
        while (checkboxItem.getAttribute('nestedLevel') > minLevel) {
            if (!checkboxItem.checked) {
                allChildNodesChecked = false;
            }
            
            checkboxArray.push(checkboxItem);
            
            if (listItem.nextSibling) {
                listItem = listItem.nextSibling;
                checkboxItem = listItem.childNodes[0];
            } else {
                break;
            }
        }
        
        // Update the node which the user clicked on
        if (allChildNodesChecked) {
            clickedBox.checked = false;
        } else {
            clickedBox.checked = true;
        }
        
        // Now iterate through child nodes and update them
        for (var i in checkboxArray) {
            var checkbox = checkboxArray[i];
            
            if (allChildNodesChecked) {
                checkbox.checked = false;
            } else {
                checkbox.checked = true;
            }
        }
    }
}
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
 * @fileoverview When a form containing editable fields is loaded (such as the tabs on the
 *               remediation detail page), this function is used to add the required click
 *               handler to all of the editable fields.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

function setupEditFields() {
    var editable = YAHOO.util.Selector.query('.editable');
    YAHOO.util.Event.on(editable, 'click', function (o){
        removeHighlight(document);
        var t_name = this.getAttribute('target');
        YAHOO.util.Dom.removeClass(this, 'editable'); 
        this.removeAttribute('target');
        if(t_name) {
             var target = document.getElementById(t_name);
             var name = target.getAttribute('name');
             var type = target.getAttribute('type');
             var url = target.getAttribute('href');
             var eclass = target.className;
             var cur_val = target.innerText ? target.innerText : target.textContent;
             var cur_html = target.innerHTML;
             if (type == 'text') {
                 target.outerHTML = '<input length="50" name="'+name+'" id="'+t_name+'" class="'+eclass+'" type="text" value="'+cur_val.trim()+'" />';
                 if (eclass == 'date') {
                     var target = document.getElementById(t_name);
                     target.onfocus = function () {showCalendar(t_name, t_name+'_show');};
                     calendarIcon = document.createElement('img');
                     calendarIcon.id = t_name + "_show";
                     calendarIcon.src = "/images/calendar.gif";
                     calendarIcon.alt = "Calendar";
                     target.parentNode.appendChild(calendarIcon);
                     YAHOO.util.Event.on(t_name+'_show', "click", function() {
                        showCalendar(t_name, t_name+'_show');
                     });
                 }
             } else if( type == 'textarea' ) {
                 var row = target.getAttribute('rows');
                 var col = target.getAttribute('cols');
                 target.outerHTML = '<textarea id="'+name+'" rows="'+row+'" cols="'+col+'" name="'+name+'">' + cur_html+ '</textarea>';
                 tinyMCE.execCommand("mceAddControl", true, name);
             } else {
                 YAHOO.util.Connect.asyncRequest('GET', url+'value/'+cur_val.trim(), {
                        success: function(o) {
                             if(type == 'select'){
                                 target.outerHTML = '<select name="'+name+'">'+o.responseText+'</select>';
                             }
                        },
                        failure: function(o) {alert('Failed to load the specified panel.');}
                    }, null);
             }
        }
    });
}

function validateEcd() {
    var obj = document.getElementById('expectedCompletionDate');
    var inputDate = obj.value;
    var oDate= new Date();
    var Year = oDate.getFullYear();
    var Month = oDate.getMonth();
    Month = Month + 1;
    if (Month < 10) {Month = '0'+Month;}
    var Day = oDate.getDate();
    if (Day < 10) {Day = '0' + Day;}
    if (inputDate.replace(/\-/g, "") <= parseInt(""+Year+""+Month+""+Day)) {
        alert("Warning: You entered an ECD date in the past.");
    }
}

if (window.HTMLElement) {
    HTMLElement.prototype.__defineSetter__("outerHTML",function(sHTML){
        var r=this.ownerDocument.createRange();
        r.setStartBefore(this);
        var df=r.createContextualFragment(sHTML);
        this.parentNode.replaceChild(df,this);
        return sHTML;
        });

    HTMLElement.prototype.__defineGetter__("outerHTML",function(){
    var attr;
        var attrs=this.attributes;
        var str="<"+this.tagName.toLowerCase();
        for(var i=0;i<attrs.length;i++){
            attr=attrs[i];
            if(attr.specified)
                str+=" "+attr.name+'="'+attr.value+'"';
            }
        if(!this.canHaveChildren)
            return str+">";
        return str+">"+this.innerHTML+"</"+this.tagName.toLowerCase()+">";
        });

    HTMLElement.prototype.__defineGetter__("canHaveChildren",function(){
    switch(this.tagName.toLowerCase()){
            case "area":
            case "base":
            case "basefont":
            case "col":
            case "frame":
            case "hr":
            case "img":
            case "br":
            case "input":
            case "isindex":
            case "link":
            case "meta":
            case "param":
            return false;
        }
        return true;
     });
}
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
 * @fileoverview Helper function for the on-line help feature in OpenFISMA
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

var helpPanels = new Array();
function showHelp(event, helpModule) {
    if (helpPanels[helpModule]) {
        helpPanels[helpModule].show();
    } else {
        // Create new panel
        var newPanel = new YAHOO.widget.Panel('helpPanel', {width:"400px"} );
        newPanel.setHeader("Help");
        newPanel.setBody("Loading...");
        newPanel.render(document.body);
        newPanel.center();
        newPanel.show();
        
        // Load the help content for this module
        YAHOO.util.Connect.asyncRequest('GET', 
                                        '/help/help/module/' + helpModule, 
                                        {
                                            success: function(o) {
                                                // Set the content of the panel to the text of the help module
                                                o.argument.setBody(o.responseText);
                                                // Re-center the panel (because the content has changed)
                                                o.argument.center();
                                            },
                                            failure: function(o) {alert('Failed to load the help module.');},
                                            argument: newPanel
                                        }, 
                                        null);
        
        // Store this panel to be re-used on subsequent calls
        helpPanels[helpModule] = newPanel;
    }
}
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
 * @fileoverview This function is unsafe because it selects all checkboxes on the page,
 *               regardless of what grouping they belong to.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 *
 * @todo Write a safe version of this function called selectAll that takes some kind
 *       of scope as a parameter so that it can be limited.
 */

function selectAllUnsafe() {
    var checkboxes = YAHOO.util.Dom.getElementsBy(
        function (el) {
            return (el.tagName == 'INPUT' && el.type == 'checkbox')
        }
    );
    for (i in checkboxes) {
        checkboxes[i].checked = 'checked';
    }
}

function selectAll() {
    alert("Not implemented");
}

function selectNoneUnsafe() {
    var checkboxes = YAHOO.util.Dom.getElementsBy(
        function (el) {
            return (el.tagName == 'INPUT' && el.type == 'checkbox')
        }
    );
    for (i in checkboxes) {
        checkboxes[i].checked = '';
    }
}

function selectNone() {
    alert("Not implemented");
}

function elDump(el) {
    props = '';
    for (prop in el) {
        props += prop + ' : ' + el[prop] + '\n';
    }
    alert(props);
}
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
 * @fileoverview Used to present the user an alert box asking them if they are sure they want to 
 *               delete the item they selected, the entryname should be defined in the form.
 *               If the user selects ok the function returns true, if the user selects cancel the 
 *               function returns false
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

function delok(entryname)
{
    var str = "Are you sure that you want to delete this " + entryname + "?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
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
 * @fileoverview Used for generate a complicated password and check account when create,
 *               update user and check user account when authentication is LDAP
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

function GeneratePassword () {
    var generatePasswordButton = document.getElementById('generate_password');
    YAHOO.util.Connect.asyncRequest('GET',
                                    '/user/generate-password/format/html',
                                    {
                                        success: function(o) {
                                            document.getElementById('password').value = o.responseText;
                                            document.getElementById('confirmPassword').value = o.responseText;
                                        },
                                        failure: function(o) {alert('Failed to generate password: ' + o.statusText);}
                                    },
                                    null);
    return false;
}

var check_account = function () {
    var account = document.getElementById('username').value;
    account = encodeURIComponent(account);
    var url = "/user/check-account/format/json/account/" + account;
    YAHOO.util.Connect.asyncRequest('GET',
                                    url,
                                    {
                                        success: function(o) {
                                            var data = YAHOO.lang.JSON.parse(o.responseText);
                                            message(data.msg, data.type);
                                        },
                                        failure: function(o) {alert('Failed to generate password: ' + o.statusText);}
                                    },
                                    null);
    return false;
};
//v1.7
// Flash Player Version Detection
// Detect Client Browser type
// Copyright 2005-2007 Adobe Systems Incorporated.  All rights reserved.
var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
var isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
var isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;

function ControlVersion()
{
	var version;
	var axo;
	var e;

	// NOTE : new ActiveXObject(strFoo) throws an exception if strFoo isn't in the registry

	try {
		// version will be set for 7.X or greater players
		axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
		version = axo.GetVariable("$version");
	} catch (e) {
	}

	if (!version)
	{
		try {
			// version will be set for 6.X players only
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");
			
			// installed player is some revision of 6.0
			// GetVariable("$version") crashes for versions 6.0.22 through 6.0.29,
			// so we have to be careful. 
			
			// default to the first public version
			version = "WIN 6,0,21,0";

			// throws if AllowScripAccess does not exist (introduced in 6.0r47)		
			axo.AllowScriptAccess = "always";

			// safe to call for 6.0r47 or greater
			version = axo.GetVariable("$version");

		} catch (e) {
		}
	}

	if (!version)
	{
		try {
			// version will be set for 4.X or 5.X player
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
			version = axo.GetVariable("$version");
		} catch (e) {
		}
	}

	if (!version)
	{
		try {
			// version will be set for 3.X player
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
			version = "WIN 3,0,18,0";
		} catch (e) {
		}
	}

	if (!version)
	{
		try {
			// version will be set for 2.X player
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
			version = "WIN 2,0,0,11";
		} catch (e) {
			version = -1;
		}
	}
	
	return version;
}

// JavaScript helper required to detect Flash Player PlugIn version information
function GetSwfVer(){
	// NS/Opera version >= 3 check for Flash plugin in plugin array
	var flashVer = -1;
	
	if (navigator.plugins != null && navigator.plugins.length > 0) {
		if (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
			var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
			var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
			var descArray = flashDescription.split(" ");
			var tempArrayMajor = descArray[2].split(".");			
			var versionMajor = tempArrayMajor[0];
			var versionMinor = tempArrayMajor[1];
			var versionRevision = descArray[3];
			if (versionRevision == "") {
				versionRevision = descArray[4];
			}
			if (versionRevision[0] == "d") {
				versionRevision = versionRevision.substring(1);
			} else if (versionRevision[0] == "r") {
				versionRevision = versionRevision.substring(1);
				if (versionRevision.indexOf("d") > 0) {
					versionRevision = versionRevision.substring(0, versionRevision.indexOf("d"));
				}
			}
			var flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
		}
	}
	// MSN/WebTV 2.6 supports Flash 4
	else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1) flashVer = 4;
	// WebTV 2.5 supports Flash 3
	else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1) flashVer = 3;
	// older WebTV supports Flash 2
	else if (navigator.userAgent.toLowerCase().indexOf("webtv") != -1) flashVer = 2;
	else if ( isIE && isWin && !isOpera ) {
		flashVer = ControlVersion();
	}	
	return flashVer;
}

// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
{
	versionStr = GetSwfVer();
	if (versionStr == -1 ) {
		return false;
	} else if (versionStr != 0) {
		if(isIE && isWin && !isOpera) {
			// Given "WIN 2,0,0,11"
			tempArray         = versionStr.split(" "); 	// ["WIN", "2,0,0,11"]
			tempString        = tempArray[1];			// "2,0,0,11"
			versionArray      = tempString.split(",");	// ['2', '0', '0', '11']
		} else {
			versionArray      = versionStr.split(".");
		}
		var versionMajor      = versionArray[0];
		var versionMinor      = versionArray[1];
		var versionRevision   = versionArray[2];

        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
		if (versionMajor > parseFloat(reqMajorVer)) {
			return true;
		} else if (versionMajor == parseFloat(reqMajorVer)) {
			if (versionMinor > parseFloat(reqMinorVer))
				return true;
			else if (versionMinor == parseFloat(reqMinorVer)) {
				if (versionRevision >= parseFloat(reqRevision))
					return true;
			}
		}
		return false;
	}
}

function AC_AddExtension(src, ext)
{
  if (src.indexOf('?') != -1)
    return src.replace(/\?/, ext+'?'); 
  else
    return src + ext;
}

function AC_Generateobj(objAttrs, params, embedAttrs) 
{ 
  var str = '';
  if (isIE && isWin && !isOpera)
  {
    str += '<object ';
    for (var i in objAttrs)
    {
      str += i + '="' + objAttrs[i] + '" ';
    }
    str += '>';
    for (var i in params)
    {
      str += '<param name="' + i + '" value="' + params[i] + '" /> ';
    }
    str += '</object>';
  }
  else
  {
    str += '<embed ';
    for (var i in embedAttrs)
    {
      str += i + '="' + embedAttrs[i] + '" ';
    }
    str += '> </embed>';
  }

  document.write(str);
}

function AC_FL_RunContent(){
  var ret = 
    AC_GetArgs
    (  arguments, ".swf", "movie", "clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
     , "application/x-shockwave-flash"
    );
  AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs);
}

function AC_SW_RunContent(){
  var ret = 
    AC_GetArgs
    (  arguments, ".dcr", "src", "clsid:166B1BCA-3F9C-11CF-8075-444553540000"
     , null
    );
  AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs);
}

function AC_GetArgs(args, ext, srcParamName, classid, mimeType){
  var ret = new Object();
  ret.embedAttrs = new Object();
  ret.params = new Object();
  ret.objAttrs = new Object();
  for (var i=0; i < args.length; i=i+2){
    var currArg = args[i].toLowerCase();    

    switch (currArg){	
      case "classid":
        break;
      case "pluginspage":
        ret.embedAttrs[args[i]] = args[i+1];
        break;
      case "src":
      case "movie":	
        args[i+1] = AC_AddExtension(args[i+1], ext);
        ret.embedAttrs["src"] = args[i+1];
        ret.params[srcParamName] = args[i+1];
        break;
      case "onafterupdate":
      case "onbeforeupdate":
      case "onblur":
      case "oncellchange":
      case "onclick":
      case "ondblclick":
      case "ondrag":
      case "ondragend":
      case "ondragenter":
      case "ondragleave":
      case "ondragover":
      case "ondrop":
      case "onfinish":
      case "onfocus":
      case "onhelp":
      case "onmousedown":
      case "onmouseup":
      case "onmouseover":
      case "onmousemove":
      case "onmouseout":
      case "onkeypress":
      case "onkeydown":
      case "onkeyup":
      case "onload":
      case "onlosecapture":
      case "onpropertychange":
      case "onreadystatechange":
      case "onrowsdelete":
      case "onrowenter":
      case "onrowexit":
      case "onrowsinserted":
      case "onstart":
      case "onscroll":
      case "onbeforeeditfocus":
      case "onactivate":
      case "onbeforedeactivate":
      case "ondeactivate":
      case "type":
      case "codebase":
      case "id":
        ret.objAttrs[args[i]] = args[i+1];
        break;
      case "width":
      case "height":
      case "align":
      case "vspace": 
      case "hspace":
      case "class":
      case "title":
      case "accesskey":
      case "name":
      case "tabindex":
        ret.embedAttrs[args[i]] = ret.objAttrs[args[i]] = args[i+1];
        break;
      default:
        ret.embedAttrs[args[i]] = ret.params[args[i]] = args[i+1];
    }
  }
  ret.objAttrs["classid"] = classid;
  if (mimeType) ret.embedAttrs["type"] = mimeType;
  return ret;
}
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
 * @fileoverview AutoComplete namespace 
 *
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @package   Fisma
 * @requires  YAHOO.widget.AutoComplete
 * @requires  YAHOO.widget.DS_XHR
 * @requires  Fisma
 * @version   $Id$
 */

Fisma.AutoComplete = function() {
    return {
        /**
         * Initializes the AutoComplete widget
         *
         * @param oEvent
         * @param aArgs
         * @param {Array} params 
         */
        init : function(oEvent, aArgs, params) {
            var acRDS = new YAHOO.widget.DS_XHR(params.xhr, params.schema);

            acRDS.responseType = YAHOO.widget.DS_XHR.TYPE_JSON;
            acRDS.maxCacheEntries = 500;
            acRDS.queryMatchContains = true;

            var ac = new YAHOO.widget.AutoComplete(params.fieldId, params.containerId, acRDS);

            ac.maxResultsDisplayed = 20;
            ac.forceSelection = true;

            /**
             * Override generateRequest method of YAHOO.widget.AutoComplete
             *
             * @param {String} query Query terms
             * @returns {String}
             */
            ac.generateRequest = function(query) {
                return params.queryPrepend + '"' + query + '"';
            };

            ac.itemSelectEvent.subscribe(Fisma.AutoComplete.subscribe, { hiddenFieldId: params.hiddenFieldId } );
        },
        /**
         * Sets value of hiddenField to item selected
         *
         * @param sType
         * @param aArgs
         * @param {Array} params
         */
        subscribe : function(sType, aArgs, params) {
            document.getElementById(params.hiddenFieldId).value = aArgs[2][1]['id'];
        }
    };
}();
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
 * @fileoverview Show recipient dialog and validate inputed email address
 *
 * @author    Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

Fisma.Email = function() {
    return {
        /**
         * Initializes the ShowRecipientDialog widget
         */
        showRecipientDialog : function() {
            // If someone opens the dialog, and then just closes the dialog, but leave pannel_c div there, 
            // it can be display two pannel_c div when open again, so we need remove it.
            var tabContainer = document.getElementById('tabContainer');
            if (document.getElementById("panel_c")) {
                tabContainer.removeChild(document.getElementById("panel_c"));
            }

            // Create a dialog
            var content = document.createElement('div');
            var p = document.createElement('p');
            var contentTitle = document.createTextNode('* Target E-mail Address:');
            p.appendChild(contentTitle);
            content.appendChild(p);

            // Add email address input to dialog
            var emailAddress = document.createElement('input');
            emailAddress.id = 'testEmailRecipient';
            emailAddress.name = 'recipient';
            content.appendChild(emailAddress);

            // Add line spacing to dialog
            var lineSpacingDiv = document.createElement('div');
            lineSpacingDiv.style.height = '10px';
            content.appendChild(lineSpacingDiv);

            // Add submmit button to dialog
            var sendBtn = document.createElement('input');
            sendBtn.type = 'button';
            sendBtn.id = 'dialogRecipientSendBtn';
            sendBtn.style.marginLeft = '10px';
            sendBtn.value = 'Send';
            content.appendChild(sendBtn);

            // Load panel
            panel('Test E-mail Configuration', tabContainer, '', content.innerHTML);

            // Set onclick handler to handle dialog_recipient 
            document.getElementById('dialogRecipientSendBtn').onclick = Fisma.Email.sendTestEmail;
        },

        /**
         * Send test email to specified recipient
         */
        sendTestEmail : function() {
            if (document.getElementById('testEmailRecipient').value == '') {
                /** @todo english */
                alert("Recipient is required.");
                document.getElementById('testEmailRecipient').focus();
                return false;
            }

            // Get dialog_recipient value to recipient
            var recipient = document.getElementById('testEmailRecipient').value;
            var form  = document.getElementById('email_config');
            form.elements['recipient'].value = recipient;

            // Post data through YUI
            YAHOO.util.Connect.setForm(form);
            YAHOO.util.Connect.asyncRequest('POST', '/config/test-email-config/format/json',
                                            {
                                                success: function(o) {
                                                    var data = YAHOO.lang.JSON.parse(o.responseText);
                                                    message(data.msg, data.type);
                                                },
                                                /** @todo english */
                                                failure: function(o) {alert('Failed to send mail: ' + o.statusText);}
                                            },
                                            null);

            // Remove panel
            var panelMask = document.getElementById("panel_mask");
            panelMask.style.visibility = "hidden";
            document.getElementById('tabContainer').removeChild(document.getElementById("panel_c"));
        }
    };
}();
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
 * @fileoverview The Finding Summary displays a tree of information systems and summary counts with expand/collapse
 * controls to navigate the tree structure of the information systems. Summary information is automatically rolled up
 * or drilled down as the user navigates the tree.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */
 
Fisma.FindingSummary = function() {
    return {
        /**
         * A pointer to the root node of the tree which is being displayed
         */
        treeRoot : null,
        
        /**
         * Holds the value of the type filter on the current page
         */
        filterType : null,
        
        /**
         * Holds the value of the status filter on the current page
         */
        filterSource : null, 
        
        /**
         * The number of tree levels to display during the initial render
         */
        defaultDisplayLevel : 2,
        
        /**
         * Renders the finding summary view
         * 
         * @todo this is still a monster function which is really unreadable
         * 
         * @param tableId DOM ID of the table which this gets rendered into
         * @param tree A tree structure which contains the counts which are rendered in this table
         * @param newTree If true, then this is the root node which is being rendered
         */         
        render : function(tableId, tree, newTree) {
            /**
             * Set the tree root first.
             */
            if (newTree) {
                this.treeRoot = tree;
            }
            
            // Get reference to the HTML table element which this is rendering into
            var table = document.getElementById(tableId);

            // Render each node at this level
            for (var nodeId in tree) {
                var node = tree[nodeId];

                // Append two rows ('ontime' and 'overdue') to the table for this node
                var firstRow = table.insertRow(table.rows.length);
                firstRow.id = node.nickname + "_ontime";
                
                var secondRow = table.insertRow(table.rows.length);
                secondRow.id = node.nickname + "_overdue";

                // The first cell of the first row is the system label
                var firstCell = firstRow.insertCell(0);

                // Determine which set of counts to show initially (single or all)
                node.expanded = (node.level < this.defaultDisplayLevel - 1);
                var ontime = node.expanded ? node.single_ontime : node.all_ontime;
                var overdue = node.expanded ? node.single_overdue : node.all_overdue;
                node.hasOverdue = this.hasOverdue(overdue);

                var expandControlImage = document.createElement('img');
                expandControlImage.className = 'control';
                expandControlImage.id = node.nickname + "Img"

                var expandControl = document.createElement('a');
                expandControl.appendChild(expandControlImage);

                // Does this node need an collapse/expand control?
                var needsExpandControl = node.children.length > 0;                
                if (needsExpandControl) {
                    expandControl.nickname = node.nickname;
                    expandControl.findingSummary = this;
                    expandControl.onclick = function () {
                        this.findingSummary.toggleNode(this.nickname); 
                        return false;
                    };
                    expandControlImage.src = "/images/" + (node.expanded ? "minus.png" : "plus.png");
                } else {
                    expandControlImage.src = "/images/leaf_node.png";
                }

                // Render the first cell on this row
                var firstCellDiv = document.createElement("div");
                firstCellDiv.className = "treeTable" + node.level + (needsExpandControl ? " link" : "");
                firstCellDiv.appendChild(expandControl);
                
                // The node icon is a graphical representation of what type of node this is: agency, bureau, etc.          
                var nodeIcon = document.createElement('img');
                nodeIcon.className = "icon";
                nodeIcon.src = "/images/" + node.orgType + ".png";
                expandControl.appendChild(nodeIcon);
                
                // Add text to the cell
                expandControl.appendChild(document.createTextNode(node.label));
                expandControl.appendChild(document.createElement('br'));
                expandControl.appendChild(document.createTextNode(node.orgTypeLabel));
                
                firstCell.appendChild(firstCellDiv);
                
                // Render the remaining cells on the this row (which are all summary counts)
                var i = 1; // start at 1 because the system label is in the first cell
                for (var c in ontime) {
                    count = ontime[c];
                    cell = firstRow.insertCell(i++);
                    if (c == 'CLOSED' || c == 'TOTAL') {
                        // The last two colums don't have the ontime/overdue distinction
                        cell.className = "noDueDate";
                    } else {
                        // The in between columns should have the ontime class
                        cell.className = 'onTime';                
                    }
                    this.updateCellCount(cell, count, node.id, c, 'ontime', node.expanded);
                }

                // Now add cells to the second row
                for (var c in overdue) {
                    count = overdue[c];
                    cell = secondRow.insertCell(secondRow.childNodes.length);
                    cell.className = 'overdue';
                    this.updateCellCount(cell, count, node.id, c, 'overdue', node.expanded);
                }

                // Hide both rows by default
                firstRow.style.display = "none";
                secondRow.style.display = "none";

                // Selectively display one or both rows based on current level and whether it has overdues
                if (node.level < this.defaultDisplayLevel) {
                    // set to default instead of 'table-row' to work around an IE6 bug
                    firstRow.style.display = '';  
                    if (node.hasOverdue) {
                        firstRow.childNodes[0].rowSpan = "2";
                        firstRow.childNodes[firstRow.childNodes.length - 2].rowSpan = "2";
                        firstRow.childNodes[firstRow.childNodes.length - 1].rowSpan = "2";
                        // set to default instead of 'table-row' to work around an IE6 bug
                        secondRow.style.display = '';  
                    }
                }

                // If this node has children, then recursively render the children
                if (node.children.length > 0) {
                    this.render(tableId, node.children);
                }
            }            
        },

        /**
         * A function to handle a user click to either expand or collapse a particular tree node
         * 
         * @param treeNode
         */        
        toggleNode : function (treeNode) {
            node = this.findNode(treeNode, this.treeRoot);
            if (node.expanded) {
                this.collapseNode(node, true);
                this.hideSubtree(node.children);
            } else {
                this.expandNode(node);
                this.showSubtree(node.children, false);
            }            
        },
        
        /**
         * Expand a tree node in the finding summary table
         * 
         * @param treeNode
         * @param recursive Indicates whether children should be recursively expanded
         */
        expandNode : function (treeNode, recursive) {
            // When expanding a node, switch the counts displayed from the "all" counts to the "single"
            treeNode.ontime = treeNode.single_ontime;
            treeNode.overdue = treeNode.single_overdue;
            treeNode.hasOverdue = this.hasOverdue(treeNode.overdue);

            // Update the ontime row first
            var ontimeRow = document.getElementById(treeNode.nickname + "_ontime");    
            var i = 1; // start at 1 b/c the first column is the system name
            for (c in treeNode.ontime) {
                count = treeNode.ontime[c];
                this.updateCellCount(ontimeRow.childNodes[i], count, treeNode.id, c, 'ontime', true);
                i++;
            }

            // Then update the overdue row, or hide it if there are no overdues
            var overdueRow = document.getElementById(treeNode.nickname + "_overdue");
            if (treeNode.hasOverdue) {
                // Do not hide the overdue row. Instead, update the counts
                var i = 0;
                for (c in treeNode.overdue) {
                    count = treeNode.overdue[c];
                    this.updateCellCount(overdueRow.childNodes[i], count, treeNode.id, c, 'overdue', true);
                    i++;
                }
            } else {
                // Hide the overdue row and adjust the rowspans on the ontime row to compensate
                ontimeRow.childNodes[0].rowSpan = "1";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "1";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "1";
                overdueRow.style.display = 'none';
            }

            // Update the control image and internal status field
            if (treeNode.children.length > 0) {
                document.getElementById(treeNode.nickname + "Img").src = "/images/minus.png";
            }
            treeNode.expanded = true;

            // If the function is called recursively and this node has children, then
            // expand the children.
            if (recursive && treeNode.children.length > 0) {
                this.showSubtree(treeNode.children, false);
                for (var child in treeNode.children) {
                    this.expandNode(treeNode.children[child], true);
                }
            }
        }, 
        
        /**
         * Collapse a tree node and all of its children
         * 
         * @param treeNode
         * @param displayOverdue ???
         */
        collapseNode : function (treeNode, displayOverdue) {
            // When collapsing a node, switch the counts displayed from the "single" counts to the "all"
            treeNode.ontime = treeNode.all_ontime;
            treeNode.overdue = treeNode.all_overdue;
            treeNode.hasOverdue = this.hasOverdue(treeNode.overdue);

            // Update the ontime row first
            var ontimeRow = document.getElementById(treeNode.nickname + "_ontime");
            var i = 1; // start at 1 b/c the first column is the system name
            for (c in treeNode.ontime) {
                count = treeNode.ontime[c];
                this.updateCellCount(ontimeRow.childNodes[i], count, treeNode.id, c, 'ontime', false);
                i++;
            }

            // Update the overdue row. Display the row first if necessary.
            var overdueRow = document.getElementById(treeNode.nickname + "_overdue");
            if (displayOverdue && treeNode.hasOverdue) {
                // Show the overdue row and adjust the rowspans on the ontime row to compensate
                ontimeRow.childNodes[0].rowSpan = "2";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "2";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "2";
                overdueRow.style.display = '';  // set to default instead of 'table-row' to work around an IE6 bug

                var i = 0;
                for (c in treeNode.all_overdue) {
                    count = treeNode.all_overdue[c];
                    this.updateCellCount(overdueRow.childNodes[i], count, treeNode.id, c, 'overdue', false);
                    i++;
                }
            }

            // If the node has children, then hide those children
            if (treeNode.children.length > 0) {
                this.hideSubtree(treeNode.children);
            }

            document.getElementById(treeNode.nickname + "Img").src = "/images/plus.png";
            treeNode.expanded = false;
        }, 
        
        /**
         * Hide an entire subtree
         * 
         * This differs from 'collapsing' a node because a collapsed node is still displayed, whereas a hidden subtree
         * isn't even displayed.
         * 
         * @param nodeArray This will generally be all of the children of a parent which is being collapsed.
         */
        hideSubtree : function (nodeArray) {
            for (nodeId in nodeArray) {
                node = nodeArray[nodeId];

                // Now update this node
                ontimeRow = document.getElementById(node.nickname + "_ontime");
                ontimeRow.style.display = 'none';
                overdueRow = document.getElementById(node.nickname + "_overdue");
                overdueRow.style.display = 'none';

                // Recurse through children
                if (node.children.length > 0) {
                    this.collapseNode(node, false);
                    this.hideSubtree(node.children);
                }
            }
        }, 
        
        /**
         * Make children of a node visible
         * 
         * @param nodeArray This will generally be all of the children of a parent node which is being expanded
         * @param recursive If true, then this makes the entire subtree visible. If false, then just the nodeArray is 
         * visible.
         */
        showSubtree : function (nodeArray, recursive) {
            for (nodeId in nodeArray) {
                node = nodeArray[nodeId];

                // Recurse through the child nodes (if necessary)
                if (recursive && node.children.length > 0) {
                    this.expandNode(node);
                    this.showSubtree(node.children, true);            
                }

                // Now update this node
                ontimeRow = document.getElementById(node.nickname + "_ontime");
                ontimeRow.style.display = '';  // set to default instead of 'table-row' to work around an IE6 bug
                overdueRow = document.getElementById(node.nickname + "_overdue");
                if (node.hasOverdue) {
                    ontimeRow.childNodes[0].rowSpan = "2";
                    ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "2";
                    ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "2";
                    overdueRow.style.display = '';  // set to default instead of 'table-row' to work around an IE6 bug
                }
            }               
        }, 
        
        /**
         * Collapse all nodes in the tree. This results in just the root node(s) being displayed, all others hidden.
         */
        collapseAll : function () {
            for (nodeId in this.treeRoot) {
                node = this.treeRoot[nodeId];
                this.collapseNode(node, true);
                this.hideSubtree(node.children);
            }            
        }, 
        
        /**
         * Expand all nodes in the tree. This results in all nodes being displayed.
         */
        expandAll : function () {
            for (nodeId in this.treeRoot) {
                node = this.treeRoot[nodeId];
                this.expandNode(node, true);
            } 
        }, 
        
        /**
         * Find a node by name in a given subtree
         * 
         * @param nodeName
         * @param tree
         * @return Either a node or boolean false
         */
        findNode : function (nodeName, tree) {
            for (var nodeId in tree) {
                node = tree[nodeId];
                if (node.nickname == nodeName) {
                    return node;
                } else if (node.children.length > 0) {
                    var foundNode = this.findNode(nodeName, node.children);
                    if (foundNode != false) {
                        return foundNode;
                    }
                }
            }
            
            return false;            
        }, 
        
        /**
         * Returns true if the specified node has any overdue items, false otherwise
         * 
         * A node has overdue items if any of the counts in its overdue array is greater than 0
         * 
         * @param An array of overdue counts for a particular node
         * @return boolean
         */
        hasOverdue : function (overdueCountArray) {
            for (var i in overdueCountArray) {
                if (overdueCountArray[i] > 0) {
                    return true;
                }
            }
            
            return false;
        },
        
        /**
         * Update the count that is displayed inside a particular cell
         * 
         * @param cell An HTML table cell
         * @param count The count to display
         * @param orgId Used to generate link
         * @param ontime Used to generate link
         * @param expanded Used to generate link
         */
        updateCellCount : function (cell, count, orgId, status, ontime, expanded) {
            if (!cell.hasChildNodes()) {
                // Initialize this cell
                if (count > 0) {
                    var link = document.createElement('a');
                    link.href = this.makeLink(orgId, status, ontime, expanded);
                    link.appendChild(document.createTextNode(count));
                    cell.appendChild(link);
                } else {
                    cell.appendChild(document.createTextNode('-'));
                }
            } else {
                // The cell is already initialized, so we may need to add or remove child elements
                if (cell.firstChild.hasChildNodes()) {
                    // The cell contains an anchor
                    if (count > 0) {
                        // Update the anchor text
                        cell.firstChild.firstChild.nodeValue = count;
                        cell.firstChild.href = this.makeLink(orgId, status, ontime, expanded);
                    } else {
                        // Remove the anchor
                        cell.removeChild(cell.firstChild);
                        cell.appendChild(document.createTextNode('-'));
                    }
                } else {
                    // The cell contains just a text node
                    if (count > 0) {
                        // Need to add a new anchor
                        cell.removeChild(cell.firstChild);
                        var link = document.createElement('a');
                        link.href = this.makeLink(orgId, status, ontime, expanded);
                        link.appendChild(document.createTextNode(count));
                        cell.appendChild(link);
                    } else {
                        // Update the text node value
                        cell.firstChild.nodeValue = '-';
                    }
                }
            }
        }, 
        
        /**
         * Generate the URI that a cell will link to
         * 
         * These search engine uses these parameters to filter the search based on the cell that was clicked
         * 
         * @param orgId
         * @param status
         * @param ontime
         * @param expanded
         * @return String URI
         */
        makeLink : function (orgId, status, ontime, expanded) {
            // CLOSED and TOTAL columns should not have an 'ontime' criteria in the link
            var onTimeString = '';
            if (!(status == 'CLOSED' || status == 'TOTAL')) {
                var onTimeString = '/ontime/' + ontime;
            }

            // Include any status
            var statusString = '';
            if (status != '') {
                statusString = '/status/' + escape(status);
            }

            // Include any filters
            var filterType = '';
            if (!YAHOO.lang.isNull(this.filterType) 
                && this.filterType != '') {
                filterType = '/type/' + this.filterType;
            }
            var filterSource = '';
            if (!YAHOO.lang.isNull(this.filterSource)
                && this.filterSource != '') {
                filterSource = '/sourceId/' + this.filterSource;
            }

            // Render the link
            var uri = '/panel/remediation/sub/search'
                    + onTimeString
                    + statusString
                    + '/responsibleOrganizationId/'
                    + orgId
                    + '/expanded/'
                    + expanded
                    + filterType
                    + filterSource;

            return uri;            
        }, 
        
        /**
         * Redirect to a URI which exports the summary table
         * 
         * @param format Only 'pdf' is valid at the moment.
         */
        exportTable : function (format) {
            var uri = '/remediation/summary-data/format/'
                    + format
                    + this.listExpandedNodes(this.treeRoot, '');

            document.location = uri;            
        }, 
        
        /**
         * Returns a URI paramter string that represents which nodes are expanded and which nodes are collapsed
         * 
         * This is used during export to make the exported tree mirror what the user sees in the browser
         * 
         * @param nodes A subtree to render into the return string
         * @param visibleNodes Pass a blank string. This is an accumulator which is used for recursive calls.
         * @return String URI
         */
        listExpandedNodes : function (nodes, visibleNodes) {
            for (var n in nodes) {
                var node = nodes[n];
                if (node.expanded) {
                    visibleNodes += '/e/' + node.id;
                    visibleNodes = this.listExpandedNodes(node.children, visibleNodes);
                } else {
                    visibleNodes += '/c/' + node.id;
                }
            }

            return visibleNodes;
        }
    };
};
