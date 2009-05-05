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

   $('input.date').datepicker({dateFormat:'yymmdd',
                showOn: 'both', 
                buttonImageOnly: true,
                buttonImage: '/images/calendar.gif'
                });
                
    // enable or disable 'on time' type by finding status
    var filterStatus = function () {
        if (document.getElementById('poamSearchStatus')) {
            var value = document.getElementById('poamSearchStatus').value.trim();
        } else {
            return ;
        }
        if (!(value == '0' 
            || value == 'CLOSED'
            || value == 'NOT-CLOSED'
            || value == 'NOUP-30'
            || value == 'NOUP-60'
            || value == 'NOUP-90')) {
            document.getElementById('poamSearchOnTime').disabled = false;
        } else {
            document.getElementById('poamSearchOnTime').disabled = true;
        }
    }
    YAHOO.util.Event.on('poamSearchStatus', 'change', filterStatus);
    filterStatus();
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
            $('input.date').datepicker("enable");
        } else {
            dateBegin.disabled = true;
            dateEnd.disabled = true;
            dateBegin.value = '';
            dateEnd.value = '';
            $('input.date').datepicker("disable");
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
                document.getElementById('enableEvents').appendChild(options[i]);
            }
        }
    });
    //
    YAHOO.util.Event.on('removeNotificationEvents', 'click', function (){
        var options = YAHOO.util.Selector.query('#enableEvents option');
        for (var i = 0; i < options.length; i ++) {
            if (options[i].selected == true) {
                document.getElementById('availableEvents').appendChild(options[i]);
            }
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
    YAHOO.util.Event.on('checkaccount', 'click', function () {
        var account = document.getElementsByName('account')[0].value;
        account = encodeURIComponent(account);
        var url = "/account/checkaccount/format/html/account/"+account;
        
    });
    //
    YAHOO.util.Event.on('search_asset', 'click', searchAsset);
    searchAsset();
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
    if (!exist_functions) {
        return;
    }
    var url = document.getElementById('function_screen').getAttribute('url')
              + '/do/available_functions' + param + '/exist_functions/'+exist_functions;
    var request = YAHOO.util.Connect.asyncRequest('GET', url, 
        {success: function(o){
                   document.getElementById('available_functions').innerHTML = o.responseText;
                },
        failure: handleFailure});
}
var handleFailure = function(o){alert('error');}

function upload_evidence() {
    if (!form_confirm(document.poam_detail, 'Upload Evidence')) {
        return false;
    }
    // set the encoding for a file upload
    document.finding_detail.enctype = "multipart/form-data";
    panel('Upload Evidence', document.finding_detail, '/remediation/upload-form');
    return false;
}

function ev_deny(formname){
    if (!form_confirm(document.poam_detail, 'deny the evidence')) {
        return false;
    }
    var dw = YAHOO.util.Dom.getDocumentWidth();
    var dh = YAHOO.util.Dom.getDocumentHeight();
    var maskDiv = document.createElement('DIV');
    maskDiv.id = 'full';
    maskDiv.style.width = dw+'px';
    maskDiv.style.height = dh+'px';
    maskDiv.style.backgroundColor = '#000000';
    maskDiv.style.marginTop = -1*dh+'px';
    maskDiv.style.opacity = 0.4;
    maskDiv.style.zIndex = 10;
    document.body.appendChild(maskDiv);

    var content = document.createElement('div');
    var p = document.createElement('p');
    p.appendChild(document.createTextNode('Comments:'));
    content.appendChild(p);
    var dt = document.createElement('textarea');
    dt.rows = 5;
    dt.cols = 60;
    dt.name = 'comment';
    content.appendChild(dt);

    $('<div title="Evidence Denial"></div>').append(content).
        dialog({position:'middle', width: 500, height: 240, resizable: true,modal:true,
            close:function(){
                $('#full').remove();
            },
            buttons:{
                'Cancel':function(){
                    $(this).dialog("close");
                },
                'Continue':function(){
                    var form1 = formname;
                    var comments = $("textarea[name=comment]",this).val();
                    form1.elements['comment'].value = comments;
                    form1.elements['decision'].value = 'DENY';
                    var submitEa = document.createElement('input');
                    submitEa.type = 'hidden';
                    submitEa.name = 'submit_ea';
                    submitEa.value = 'DENY';
                    form1.appendChild(submitEa);                    
                    form1.submit();
                }
            }
        });
}

function ms_comment(formname){
    if (!form_confirm(document.poam_detail, 'deny the mitigation')) {
        return false;
    }
    var dw = YAHOO.util.Dom.getDocumentWidth();
    var dh = YAHOO.util.Dom.getDocumentHeight();
    var maskDiv = document.createElement('DIV');
    maskDiv.id = 'full';
    maskDiv.style.width = dw+'px';
    maskDiv.style.height = dh+'px';
    maskDiv.style.backgroundColor = '#000000';
    maskDiv.style.marginTop = -1*dh+'px';
    maskDiv.style.opacity = 0.4;
    maskDiv.style.zIndex = 10;
    document.body.appendChild(maskDiv);

    var content = document.createElement('div');
    var p = document.createElement('p');
    p.appendChild(document.createTextNode('Comments:'));
    content.appendChild(p);
    var dt = document.createElement('textarea');
    dt.rows = 5;
    dt.cols = 60;
    dt.name = 'comment';
    content.appendChild(dt);
    
    $('<div title="Mitigation Strategy Denial"></div>').append(content).
        dialog({position:'middle', width: 500, height: 440, resizable: true,modal:true,
            close:function(){
                $('#full').remove();
            },
            buttons:{
                'Cancel':function(){
                    $(this).dialog("close");
                },
                'Continue':function(){
                    var form2 = formname;
                    var comment = $("textarea[name=comment]",this).val();
                    form2.elements['comment'].value = comment;
                    form2.elements['decision'].value = 'DENIED';
                    var submitMsa = document.createElement('input');
                    submitMsa.name = 'submit_msa';
                    submitMsa.value = 'DENY';
                    form2.appendChild(submitMsa);
                    form2.submit();
                }
            }
        });
}

function getProdId(){
    var trigger= document.getElementsByName('prod_list')[0];
    YAHOO.util.Event.on(trigger, 'change', function (){
        document.getElementsByName('prod_id')[0].value = trigger.value;
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
                document.getElementsByName('prod_list')[0].innerHTML = o.responseText;
                getProdId();
              },
    failure: handleFailure});
}

function searchAsset(){
    var trigger = new YAHOO.util.Element('poam-system_id');
    if(trigger.get('id') == undefined){
        return ;
    }
    var sys = trigger.get('value');
    var param =  '';
    if(0 != parseInt(sys)){
        param +=  '/sid/' + sys;
    }
    var assetInput = YAHOO.util.Selector.query('input.assets');
    for(var i = 0;i < assetInput.length; i++) {
        if (assetInput[i].value != undefined && assetInput[i].value != '') {
            param += '/' + assetInput[i].name + '/' + assetInput[i].value;
        }
    }
    var url = document.getElementById('poam-system_id').getAttribute("url") + param;
    YAHOO.util.Connect.asyncRequest('GET', url, 
    {success:function (o){
        document.getElementById('poam-asset_id').parentNode.innerHTML = o.responseText;
        asset_detail();
    },
    failure: handleFailure});
}

function asset_detail() {
    YAHOO.util.Event.on('poam-asset_id', 'change', function (){
        var url = this.getAttribute("url") + this.value;
        YAHOO.util.Connect.asyncRequest('GET', url, {
            success:function (o){document.getElementById('asset_info').innerHTML = o.responseText},
            failure: handleFailure});
    });
}

function message( msg ,model){
    var msgbar = new YUI.util.Element('msgbar');
    msgbar.innerHTML = msg;
    msgbar.style.fontWeight = 'bold';
    
    if( model == 'warning')  {
        msgbar.style.color = 'red';
    } else {
        msgbar.style.color = 'green';
        msgbar.style.borderColor = 'green';
        msgbar.style.borderColor = 'lightgreen';
    }
    msgbar.style.display = 'block';
}

function showJustification(){
    document.getElementById('ecd_justification').style.display = '';
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

/**
 * @todo english
 * Check the form if has something changed but not saved
 * if nothing changes, then give a confirmation
 * @param dom check_form checking form
 * @param str user's current action
 */
function form_confirm (check_form, action) {
    var changed = false;    
    $(':text, :password, textarea', check_form).each(function() {    
        var _v = $(this).attr('_value');    
        if(typeof(_v) == 'undefined')   _v = '';    
        if(_v != $(this).val()) changed = true;    
    });    
   
    $(':checkbox, :radio', check_form).each(function() {    
        var _v = this.checked ? 'on' : 'off';  
        if(_v != $(this).attr('_value')) changed = true;    
    });    
    
    $('select', check_form).each(function() {    
        var _v = $(this).attr('_value');    
        if(typeof(_v) == 'undefined')   _v = '';    
        if(_v != this.options[this.selectedIndex].value) changed = true;  
    });

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
function panel(title, parent, src) {
    var newPanel = new YAHOO.widget.Panel('panel', {width:"540px", modal:true} );
    newPanel.setHeader(title);
    newPanel.setBody("Loading...");
    newPanel.render(parent);
    newPanel.center();
    newPanel.show();
    // Load the help content for this module
    YAHOO.util.Connect.asyncRequest('GET', 
                                    src,
                                    {
                                        success: function(o) {
                                            // Set the content of the panel to the text of the help module
                                            o.argument.setBody(o.responseText);
                                            // Re-center the panel (because the content has changed)
                                            o.argument.center();
                                        },
                                        failure: function(o) {alert('Failed to load the specified panel.');},
                                        argument: newPanel
                                    }, 
                                    null);
}

var e = YAHOO.util.Event;
e.onDOMReady(readyFunc);