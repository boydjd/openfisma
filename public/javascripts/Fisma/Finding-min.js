Fisma.Finding={commentTable:null,commentCallback:function(f,b){var d=this;var c={timestamp:f.createdTs,username:f.username,comment:f.comment};this.commentTable.addRow(c);this.commentTable.sortColumn(this.commentTable.getColumn(0),YAHOO.widget.DataTable.CLASS_DESC);var a=new Fisma.Blinker(100,6,function(){d.commentTable.highlightRow(0)},function(){d.commentTable.unhighlightRow(0)});a.start();var e=document.getElementById("findingCommentsCount").firstChild;e.nodeValue++;b.hide();b.destroy()},editEcdJustification:function(){var a=document.getElementById("currentChangeDescription");a.style.display="none";var c=a.firstChild.nodeValue;var b=document.createElement("input");b.type="text";b.value=c;b.name="finding[ecdChangeDescription]";a.parentNode.appendChild(b)},showSecurityControlSearch:function(){var b=document.getElementById("securityControlSearchButton");b.style.display="none";var a=document.getElementById("findingSecurityControlSearch");a.style.display="block"},handleSecurityControlSelection:function(){var a=document.getElementById("securityControlContainer");a.innerHTML='<img src="/images/loading_bar.gif">';var c=document.getElementById("finding[securityControlId]");var b=escape(c.value);YAHOO.util.Connect.asyncRequest("GET","/security-control-catalog/single-control/id/"+b,{success:function(d){a.innerHTML=d.responseText},failure:function(d){alert("Unable to load security control definition.")}})}};