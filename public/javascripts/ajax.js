/** @todo start migrating functionality out of this file. eventually this file needs to be removed */
String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g,"");
}

$(document).ready(function(){
   
   //Data Table Row Highlighting and Selection        
   $(".tbframe tr").mouseover(function() {
       $(this).addClass("over");}).mouseout(function() {
            $(this).removeClass("over");})
   $(".tbframe tr:even").addClass("alt");

   //show hand if the row can open a link
   if ($('.tbframe').attr('cursor') == 'hand') {
       $('.tbframe tr td').css('cursor','pointer');

       //Click the table row to open the link
       $(".tbframe tr").click(function() {
           var link = $(this).find("td:last-child a").attr('href');
           if (link) {
               window.location.href = link
           }
       });
   }

   $("a[@name=select_all]").click(function(){
       $(":checkbox").attr( 'checked','checked' );
   });
   $("a[@name=select_none]").click(function(){
       $(":checkbox").attr( 'checked','' );
   });

   $('input.date').datepicker({dateFormat:'yymmdd',
                showOn: 'both', 
                buttonImageOnly: true,
                buttonImage: '/images/calendar.gif'
                });

    $("select[name='poam[system_id]']").change(function(){
        searchAsset();
    });

    $("select#poamSearchStatus").change(function(){
        var value = $(this).val().trim();
        if (!(value == '0' 
            || value == 'CLOSED'
            || value == 'NOT-CLOSED'
            || value == 'NOUP-30'
            || value == 'NOUP-60'
            || value == 'NOUP-90')) {
            $("select#poamSearchOnTime").removeAttr("disabled");
        } else {
            $("select#poamSearchOnTime").attr("disabled", "disabled");
        }
    }).trigger('change');

    $("select#remediationSearchAging").change(function(){
        var value = $(this).val().trim();
        if (value == '0') {
            $("input#created_date_begin").removeAttr("disabled");
            $("input#created_date_end").removeAttr("disabled");
            $('input.date').datepicker("enable");
        } else {
            $("input#created_date_begin").attr("disabled", "disabled");
            $("input#created_date_begin").val('');
            $("input#created_date_end").attr("disabled", "disabled");
            $("input#created_date_end").val('');
            $('input.date').datepicker("disable");
        }
    }).trigger('change');
    
    $("input#created_date_begin").change(function (){
        if($(this).val().trim() != '' || $("input#created_date_end").val().trim() != '') {
            $("select#remediationSearchAging").attr("disabled", "disabled");
        } else {
            $("select#remediationSearchAging").attr("disabled", "");
        }
    }).trigger('change');
    
    $("input#created_date_end").change(function (){
        if($(this).val().trim() != '' || $("input#created_date_begin").val().trim() != '') {
            $("select#remediationSearchAging").attr("disabled", "disabled");
        } else {
            $("select#remediationSearchAging").attr("disabled", "");
        }
    }).trigger('change');
    
    $("select#encrypt").change(function(){
        if ($(this).val().trim() == 'sha256') {
             $("#encryptKey").show();
        } else {
             $("#encryptKey").hide();
        }
    }).trigger('change');

    $("select[name='function_screen']").change(function(){
        search_function();
    }).trigger('change');
    
    $('#add_function').click(function() {
        return !$('#available_functions option:selected').remove().appendTo('#exist_functions');  
    });  
    $('#remove_function').click(function() {  
        return !$('#exist_functions option:selected').remove().appendTo('#available_functions');  
    }); 

    $('#addNotificationEvents').click(function() {
        return !$('#availableEvents option:selected').remove().appendTo('#enableEvents');
    });
	
	$('#removeNotificationEvents').click(function() {
        return !$('#enableEvents option:selected').remove().appendTo('#availableEvents');
    });

    $('#add_role').click(function() {
        return !$('#available_roles option:selected').remove().appendTo('#assign_roles');
    });

    $('#add_role').click(function() {
        search_privilege();
    }).trigger('click');

    $('#remove_role').click(function() {
        return !$('#assign_roles option:selected').remove().appendTo('#available_roles');
    });

    $('#remove_role').click(function() {
        search_privilege();
    });


    $('#add_privilege').click(function() {
        return !$('#available_privileges option:selected').remove().appendTo('#assign_privileges');
    });

    $('#remove_privilege').click(function() {
        return !$('#assign_privileges option:selected').remove().appendTo('#available_privileges');
    });

    $("form[name='assign_right']").submit(function() {  
        $('#exist_functions option').each(function(i) {  
            $(this).attr("selected", "selected");  
        });
    }); 

    $("form[name='assign_role']").submit(function() {
        $('#assign_roles option').each(function(i) {  
            $(this).attr("selected", "selected");  
        });
        $('#assign_privileges option').each(function(i) {
            $(this).attr("selected", "selected");
        });
    }); 

    $("form[name='event_form']").submit(function() {
        $('#enableEvents option').each(function(i) {  
            $(this).attr("selected", "selected");  
        });
    });

    asset_detail();
    
    $("input#search_asset").click(function(){
        searchAsset();
    }).trigger('click');

    $("input#search_product").click(function(){
        searchProduct();
    });

    getProdId();

    $("#checkaccount").click(function(){
        var account = $("input[name='account']").val();
        var account = encodeURIComponent(account);
        var url = "/account/checkaccount/format/html/account/"+account;
        $.ajax({ url:url, type:"GET",dataType:"html", success:function(msg){message(msg);} });
    });

    $(".confirm").click(function(){
        var str = "DELETING CONFIRMATION!";
        if(confirm(str) == true){
            return true;
        }
        return false;
    });
});

function shortcut(step){
    if( !isFinite(step) ){
        step = 0;
    }
    var year = $("span[name=gen_shortcut]").attr('year');
    year = Number(year) + Number(step);
    var url = $("span[name=gen_shortcut]").attr('url')+year+'/';
    $("span[name=year]").html( year );
    $("span[name=year]").parent().attr( 'href', url);
    $("span[name=q1]").parent().attr( 'href', url+'q/1/' );
    $("span[name=q2]").parent().attr( 'href', url+'q/2/' );
    $("span[name=q3]").parent().attr( 'href', url+'q/3/' );
    $("span[name=q4]").parent().attr( 'href', url+'q/4/' );
}

function searchAsset( ){
    var trigger = $("select[name='poam[system_id]']");
    var sys = trigger.children("option:selected").attr('value');
    var param =  '';
    if( null != sys){
        param +=  '/sid/' + sys;
    }
    $("input.assets").each(function(){
        if( $(this).attr('value') ){
            param += '/' + $(this).attr('name') + '/' + $(this).attr('value');
        }
    });
    var url = trigger.attr("url") + param ;
    $("select[name='poam[asset_id]']").parent().load(url,null,function(){
        asset_detail();
    });
}

function asset_detail() {
    $("select[name='poam[asset_id]']").change(function(){
        var url = $(this).attr('url')+ $(this).children("option:selected").attr('value');
        $("div#asset_info").load(url,null);
    });
}

function search_function() {
    var trigger = $("select[name='function_screen']");
    var param = '';
    var name = trigger.children("option:selected").attr('value');

    if( null != name){
        param += '/screen_name/'+name;
    }
    var kids = $("#exist_functions").children();
    var exist_functions = '';
    for (var i=0;i < kids.length;i++) {
        if (i == 0) {
            exist_functions += kids[i].value;
        } else {
            exist_functions += ',' + kids[i].value;
        }
    }
    
    var url = trigger.attr("url") + '/do/available_functions' + param + '/exist_functions/'+exist_functions;
    $("select[name='available_functions']").load(url,null);
}

function search_privilege() {
    var trigger = $('#assign_roles');
    var param = '';
    var fid ='';
    $('#assign_roles option').each(function(i) {
        fid += $(this).attr('value') + '-';
    });
    if( null != fid){
        param = '/assign_roles/'+fid;
    }
    var url = trigger.attr("url") + param;
    $("select[name='available_privileges']").load(url,null);
}

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
    var dw = $(document).width();
    var dh = $(document).height();
    $('<div id="full"></div>')
                .width(dw).height(dh)
                .css({backgroundColor:"#000000", marginTop:-1*dh, opacity:0, zIndex:10})
                .appendTo("body")
                .fadeTo(1, 0.4);

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
    var dw = $(document).width();
    var dh = $(document).height();
    $('<div id="full"></div>')
                .width(dw).height(dh)
                .css({backgroundColor:"#000000", marginTop:-1*dh, opacity:0, zIndex:10})
                .appendTo("body").fadeTo(1, 0.4);

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
    var trigger= $("select[name='prod_list']");
    trigger.change(function(){
        var prod_id= trigger.children("option:selected").attr('value');
        $("input[name='prod_id']").val(prod_id);
    });
}

function searchProduct(){
    var trigger = $("input#search_product");
    var url = trigger.attr('url');
    $("input.product").each(function(){
        if($(this).attr('value')){
            url += '/' + $(this).attr('name') + '/' + $(this).attr('value');
        }
    });
    $("select[name='prod_list']").parent().load(url,null,function(){
        getProdId();
    });
}

function message( msg ,model){
    $("#msgbar").html(msg).css('font-weight','bold');
    if( model == 'warning')  {
        $("#msgbar").css('color','red');
    } else {
        $("#msgbar").css('color','green');
        $("#msgbar").css('border-color','green');
        $("#msgbar").css('background-color','lightgreen');
    }
    $("#msgbar").css('display','block');
}

function showJustification(){
    $("div#ecd_justification").show()
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
