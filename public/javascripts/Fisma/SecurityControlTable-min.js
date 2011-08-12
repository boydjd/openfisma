(function(){var d=YAHOO.lang,a=YAHOO.util.Event,c=YAHOO.widget.NestedDataTable;var b=function(f,g){this._securityAuthorizationId=g;var h={resultsList:"records",metaFields:{totalRecords:"totalRecords"}};var j=new YAHOO.util.XHRDataSource("/sa/security-authorization/control-table-master/format/json/id/"+g);j.connMethodPost=false;j.responseType=YAHOO.util.DataSource.TYPE_JSON;j.responseSchema=h;var l=new YAHOO.util.XHRDataSource("/sa/security-authorization/control-table-nested/format/json");l.connMethodPost=false;l.responseType=YAHOO.util.DataSource.TYPE_JSON;l.responseSchema=h;var e=[{key:"code",label:"Code"},{key:"name",label:"Name"},{key:"class",label:"Class"},{key:"family",label:"Family"},{key:"addEnhancements",label:"Add Enhancements",formatter:this._actionFormatter},{key:"editCommonControl",label:"Edit Common Control",formatter:this._actionFormatter},{key:"removeControl",label:"Remove Control",formatter:this._actionFormatter}];var i=[{key:"number",label:"Enhancement"},{key:"removeEnhancement",label:"Remove Enhancement",formatter:this._actionFormatter}];var k={generateNestedRequest:function(n){return"/id/"+n.getData("id")}};var m={masterTable:this};b.superclass.constructor.call(this,f,e,j,i,l,k,m);b._instanceMap[f]=this};Fisma.SecurityControlTable=b;b._instanceMap=[];b.getByName=function(e){return b._instanceMap[e]};b.addControl=function(h,i){var j=i,e=Fisma.HtmlPanel.showPanel("Add Security Control",null,null,{modal:true}),f="/sa/security-authorization/add-control/format/html/id/"+j;var g={success:function(l){var k=l.argument;k.setBody(l.responseText);k.center()},failure:function(l){alert('Error getting "add control" form: '+l.statusText);var k=l.argument;k.destroy()},argument:e};YAHOO.util.Connect.asyncRequest("GET",f,g,null)};d.extend(b,c,{_securityAuthorizationId:null,_toggleFormatter:function(e,f,g,h){if(f.getData("hasEnhancements")){Fisma.SecurityControlTable.superclass._toggleFormatter.apply(this,arguments)}},_actionFormatter:function(f,h,i,j){var g=this.configs.masterTable?this.configs.masterTable:this;if(i.key!="addEnhancements"||h.getData("hasMoreEnhancements")){f.innerHTML=i.label;var e=g["_"+i.key];a.addListener(f,"click",e,h,g)}},_addEnhancements:function(h,k){var l=this._securityAuthorizationId,i=k.getData("securityControlId"),e=Fisma.HtmlPanel.showPanel("Add Security Control",null,null,{modal:true}),f="/sa/security-authorization/add-enhancements/format/html/id/"+l+"/securityControlId/"+i,j=this;var g={success:function(n){var m=n.argument;m.setBody(n.responseText);m.center()},failure:function(n){var m=n.argument;m.destroy();alert('Error getting "add control" form: '+n.statusText)},argument:e};YAHOO.util.Connect.asyncRequest("GET",f,g)},_editCommonControl:function(h,k){var l=this._securityAuthorizationId,i=k.getData("securityControlId"),e=Fisma.HtmlPanel.showPanel("Edit Common Security Control",null,null,{modal:true}),f="/sa/security-authorization/edit-common-control/format/html/id/"+l+"/securityControlId/"+i,j=this;var g={success:function(n){var m=n.argument;m.setBody(n.responseText);m.center()},failure:function(n){var m=n.argument;m.destroy();alert('Error getting "edit common control" form: '+n.statusText)},argument:e};YAHOO.util.Connect.asyncRequest("GET",f,g)},_removeControl:function(g,h){var f="/sa/security-authorization/remove-control/format/json/id/"+this._securityAuthorizationId,e="securityControlId="+h.getData("securityControlId");this._removeEntry(f,e)},_removeEnhancement:function(g,h){var f="/sa/security-authorization/remove-enhancement/format/json/id/"+this._securityAuthorizationId,e="securityControlEnhancementId="+h.getData("securityControlEnhancementId");this._removeEntry(f,e)},_removeEntry:function(g,e){var f={success:function(i){var h=YAHOO.lang.JSON.parse(i.responseText);if(h.result=="ok"){window.location=window.location}else{alert(h.result)}},failure:function(h){alert("Error: "+h.statusText)}};YAHOO.util.Connect.asyncRequest("POST",g,f,e)}})})();