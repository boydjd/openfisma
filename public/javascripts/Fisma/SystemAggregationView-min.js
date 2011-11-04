(function(){var a=function(b){this._contentDiv=document.getElementById(b);if(YAHOO.lang.isNull(this._contentDiv)){throw"Invalid contentDivId"}this._storage=new Fisma.PersistentStorage("Aggregation.Tree")};a.prototype={_contentDiv:null,_treeView:null,_disposalCheckboxContainer:null,_disposalCheckbox:null,_loadingContainer:null,_treeViewContainer:null,_savePanel:null,_storage:null,render:function(){var b=this;Fisma.Storage.onReady(function(){b._disposalCheckboxContainer=document.createElement("div");b._renderDisposalCheckbox(b._disposalCheckboxContainer);b._contentDiv.appendChild(b._disposalCheckboxContainer);b._loadingContainer=document.createElement("div");b._renderLoading(b._loadingContainer);b._contentDiv.appendChild(b._loadingContainer);b._treeViewContainer=document.createElement("div");b._renderTreeView(b._treeViewContainer);b._contentDiv.appendChild(b._treeViewContainer)})},_renderDisposalCheckbox:function(b){this._disposalCheckbox=document.createElement("input");this._disposalCheckbox.type="checkbox";this._disposalCheckbox.checked=this._storage.get("includeDisposalSystem");YAHOO.util.Dom.generateId(this._disposalCheckbox);YAHOO.util.Event.addListener(this._disposalCheckbox,"click",this._handleDisposalCheckboxAction,this,true);b.appendChild(this._disposalCheckbox);var c=document.createElement("label");c.setAttribute("for",this._disposalCheckbox.id);c.appendChild(document.createTextNode("Display Disposed Systems"));b.appendChild(c);b.setAttribute("class","showDisposalSystem")},_renderLoading:function(b){var c=document.createElement("img");c.src="/images/spinners/small.gif";b.style.display="none";b.appendChild(c)},_showLoadingImage:function(){this._loadingContainer.style.display="block"},_hideLoadingImage:function(){this._loadingContainer.style.display="none"},_handleDisposalCheckboxAction:function(b){this._storage.set("includeDisposalSystem",this._disposalCheckbox.checked);this._renderTreeView()},_renderTreeView:function(b){this._showLoadingImage();var c="/system/aggregation-data/format/json";if(this._storage.get("includeDisposalSystem")===true){c+="/displayDisposalSystem/true"}YAHOO.util.Connect.asyncRequest("GET",c,{success:function(d){var e=YAHOO.lang.JSON.parse(d.responseText);this._treeView=new YAHOO.widget.TreeView(this._treeViewContainer);this._buildTreeNodes(e.treeData,this._treeView.getRoot());Fisma.TreeNodeDragBehavior.makeTreeViewDraggable(this._treeView,{dragFinished:{fn:this.handleDragDrop,context:this}});var f=this._treeView.getNodesBy(function(g){return g.depth<2});$.each(f,function(g,h){h.expand()});this._treeView.draw();this._buildContextMenu();this._hideLoadingImage()},failure:function(d){alert("Unable to load the organization tree: "+d.statusText)},scope:this},null)},_buildTreeNodes:function(c,f){for(var d in c){var g=c[d];var e="<b>"+PHP_JS().htmlspecialchars(g.label)+"</b> - <i>"+PHP_JS().htmlspecialchars(g.sysTypeLabel)+"</i>";var b=new YAHOO.widget.HTMLNode({html:e,systemId:g.id},f,false);b.contentStyle=g.orgType;if(g.sdlcPhase==="disposal"){b.contentStyle+=" disposal"}if(g.children.length>0){this._buildTreeNodes(g.children,b)}}},expandAll:function(){this._treeView.getRoot().expandAll()},collapseAll:function(){this._treeView.getRoot().collapseAll()},handleDragDrop:function(b,d,e,f){var c="/system/move-node/format/json/src/"+d.data.systemId+"/dest/"+e.data.systemId+"/dragLocation/"+f;if(YAHOO.lang.isNull(this._savePanel)){this._savePanel=new YAHOO.widget.Panel("savePanel",{width:"250px",fixedcenter:true,close:false,draggable:false,modal:true,visible:true});this._savePanel.setHeader("Saving...");this._savePanel.render(document.body)}this._savePanel.setBody('<img src="/images/loading_bar.gif">');this._savePanel.show();YAHOO.util.Connect.asyncRequest("GET",c,{success:function(h){var g=YAHOO.lang.JSON.parse(h.responseText);if(g.success){b.completeDragDrop(d,e,f);this._buildContextMenu();this._savePanel.hide()}else{this._displayDragDropError("Error: "+g.message)}},failure:function(g){this._displayDragDropError("Unable to reach the server to save your changes: "+g.statusText);this._savePanel.hide()},scope:this},null)},_displayDragDropError:function(e){var c=document.createElement("div");var g=document.createElement("p");g.appendChild(document.createTextNode(e));var f=document.createElement("p");var d=this;var b=new YAHOO.widget.Button({label:"OK",container:f,onclick:{fn:function(){d._savePanel.hide()}}});c.appendChild(g);c.appendChild(f);this._savePanel.setBody(c)},_buildContextMenu:function(){var c=["View"];var b=new YAHOO.widget.ContextMenu(YAHOO.util.Dom.generateId(),{trigger:this._treeView.getEl(),itemdata:c,lazyload:true});b.subscribe("click",this._contextMenuHandler,this,true)},_contextMenuHandler:function(f,b){var d=b[1].parent.contextEventTarget;var g=this._treeView.getNodeByElement(d);var c;var e=g.data.type;if(e=="agency"||e=="bureau"||e=="organization"){c="/organization/view/id/"+g.data.organizationId}else{c="/system/view/id/"+g.data.systemId}window.location=c}};Fisma.SystemAggregationView=a})();