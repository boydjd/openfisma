/** @todo start migrating functionality out of this file. eventually this file needs to be removed */
String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g,"");
}

var readyFunc = function () {
    //Data Table Row Highlighting and Selection
    var trs = YAHOO.util.Selector.query('.tbframe tr');
    YAHOO.util.Event.on(trs, 'mouseover', 
    function() {
        YAHOO.util.Dom.addClass(this, 'over');
    });
    YAHOO.util.Event.on(trs, 'mouseout', 
    function() {
        YAHOO.util.Dom.removeClass(this, 'over');
    });
    YAHOO.util.Dom.addClass(YAHOO.util.Selector.filter(trs, ':nth-child(even)'), 'alt');

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
        var options = new YAHOO.util.Selector.query('#available_functions option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('exist_functions').appendChild(options[i]);
            }
        }
        return false;  
    });
    //
    YAHOO.util.Event.on('remove_function', 'click', function() {
        var options = YAHOO.util.Selector.query('#exist_functions option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('available_functions').appendChild(options[i]);
            }
        }
        return false;
    });
    //
    YAHOO.util.Event.on('addNotificationEvents', 'click', function (){
        var options = YAHOO.util.Selector.query('#availableEvents option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('existEvents').appendChild(options[i]);
            }
        }
    });
    //
    YAHOO.util.Event.on('removeNotificationEvents', 'click', function (){
        var options = YAHOO.util.Selector.query('#existEvents option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('availableEvents').appendChild(options[i]);
            }
        }
    });

    YAHOO.util.Event.on('event_form', 'submit', function (){
        var options = YAHOO.util.Selector.query('#existEvents option');
        for (var i = 0; i < options.length; i ++) {
            options[i].selected = true;
        }
    });

    //
    YAHOO.util.Event.on(YAHOO.util.Selector.query('form[name=assign_right]'), 'submit', 
    function (){
        var options = YAHOO.util.Selector.query('#exist_functions option');
        for (var i = 0; i < options.length; i ++) {
            options[i].selected = true;
        }
    });
    //
    YAHOO.util.Event.on(YAHOO.util.Selector.query('form[name=event_form]'), 'submit', 
    function (){
        var options = YAHOO.util.Selector.query('#enableEvents option');
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
    var kids = YAHOO.util.Selector.query('#exist_functions option');
    var exist_functions = '';
    for (var i=0;i < kids.length;i++) {
        if (i == 0) {
            exist_functions += kids[i].value;
        } else {
            exist_functions += ',' + kids[i].value;
        }
    }
    var url = document.getElementById('function_screen').getAttribute('url')
              + '/do/available_functions' + param + '/exist_functions/'+exist_functions;
    var request = YAHOO.util.Connect.asyncRequest('GET', url, 
        {success: function(o){
                   document.getElementById('available_functions').parentNode.innerHTML = '<select style="width: 250px;" name="available_functions" id="available_functions" size="20" multiple="">'+o.responseText+'</select>';
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

function ev_deny(formname){
    if (!form_confirm(document.finding_detail, 'deny the evidence')) {
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

function ms_comment(formname){
    if (!form_confirm(document.finding_detail, 'deny the mitigation')) {
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
    msgbar.innerHTML = msg;
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

function addBookmark(title, url){
    if(window.sidebar){ // Firefox
        window.sidebar.addPanel(title, url,'');
    }else if(window.opera){ //Opera
        var a = document.createElement("A");
        a.rel = "sidebar";
        a.target = "_search";
        a.title = title;
        a.href = url;
        a.click();
    } else if(document.all){ //IE
        window.external.AddFavorite(url, title);
    }
}

/**
 * Highlights search results according to the keywords which were used to search
 *
 * @param node object
 * @param keyword string
 */ 
function highlight(node,keywords) {
    if (!keywords) {
        return true;
    }

	keywords = keywords.split(',');
	for (var i in keywords) {
		keyword = keywords[i];

		// Iterate into this nodes childNodes
		if (node && node.hasChildNodes) {
			var hi_cn;
			for (hi_cn=0;hi_cn<node.childNodes.length;hi_cn++) {
				highlight(node.childNodes[hi_cn],keyword);
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
 * @todo english
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
                if(_v != elements[i].value) changed = true;
            }
            if (e_type == 'checkbox' || e_type == 'radio') {
                var _v = elements[i].checked ? 'on' : 'off';  
                if(_v != elements[i].getAttribute('_value')) changed = true;  
            }
        } else if (tag_name == 'SELECT') {
            var _v = elements[i].getAttribute('_value');    
            if(typeof(_v) == 'undefined')   _v = '';    
            if(_v != elements[i].options[elements[i].selectedIndex].value) changed = true;  
        } else if (tag_name == 'TEXTAREA') {
            var _v = elements[i].getAttribute('_value');
            if(typeof(_v) == 'undefined')   _v = '';
            var textarea_val = elements[i].value ? elements[i].value : elements[i].innerHTML;
            if(_v != textarea_val) changed = true;
        }
    }

    if(changed) {
        if (confirm('WARNING: You have unsaved changes on the page. If you continue, these changes will be lost. If you want to save your changes, click "Cancel" now and then click "Save Changes".') == true) {
            return true;
        }
    } else {
        if (confirm('WARNING: You are about to '+action+'. This action cannot be undone. Please click "Ok" to confirm your action or click "Cancel" to stop.') == true) {
            return true;
        }
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
                                                
                                                callback();
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

function callCalendar(evt, ele){
    alert('ele is ' + ele);
    showCalendar(ele, ele+'_show');
}

function showCalendar(block, trigger){
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
            if ('finding[expectedCompletionDate]' == Dom.get(block).name) {
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
