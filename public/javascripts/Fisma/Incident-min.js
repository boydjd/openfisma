Fisma.Incident={commentTable:null,attachArtifactCallback:function(a){window.location.href=window.location.href},commentCallback:function(e,b){var d=this;var c={timestamp:e.createdTs,username:e.username,comment:e.comment};this.commentTable.addRow(c);this.commentTable.sortColumn(this.commentTable.getColumn(0),YAHOO.widget.DataTable.CLASS_DESC);var a=new Fisma.Blinker(100,6,function(){d.commentTable.highlightRow(0)},function(){d.commentTable.unhighlightRow(0)});a.start();b.hide();b.destroy()}};