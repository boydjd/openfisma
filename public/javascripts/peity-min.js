(function(d,a){var c=d.fn.peity=function(f,e){if(a.createElement("canvas").getContext){this.each(function(){d(this).change(function(){var h=d.extend({},e);var g=this;d.each(h||{},function(j,k){if(d.isFunction(k)){h[j]=k.call(g)}});var i=d(this).html();c.graphers[f].call(this,d.extend({},c.defaults[f],h));d(this).trigger("chart:changed",i)}).trigger("change")})}return this};c.graphers={};c.defaults={};c.add=function(e,f,g){c.graphers[e]=g;c.defaults[e]=f};function b(g,e){var f=a.createElement("canvas");f.setAttribute("width",g);f.setAttribute("height",e);return f}c.add("pie",{colours:["#FFF4DD","#FF9900"],delimeter:"/",radius:16},function(e){var i=d(this);var f=e.radius/2;var n=i.text().split(e.delimeter);var k=parseFloat(n[0]);var j=parseFloat(n[1]);var m=-Math.PI/2;var l=(k/j)*Math.PI*2;var h=b(e.radius,e.radius);var g=h.getContext("2d");g.beginPath();g.moveTo(f,f);g.arc(f,f,f,l+m,(l==0)?Math.PI*2:m,false);g.fillStyle=e.colours[0];g.fill();g.beginPath();g.moveTo(f,f);g.arc(f,f,f,m,l+m,false);g.fillStyle=e.colours[1];g.fill();i.wrapInner(d("<span>").hide()).append(h)});c.add("line",{colour:"#c6d9fd",strokeColour:"#4d89f9",strokeWidth:1,delimeter:",",height:16,max:null,width:32},function(e){var l=d(this);var h=b(e.width,e.height);var q=l.text().split(e.delimeter);var n=Math.max.apply(Math,q.concat([e.max]));var k=e.height/n;var f=e.width/(q.length-1);var p=[];var j;var g=h.getContext("2d");g.beginPath();g.moveTo(0,e.height);for(j=0;j<q.length;j++){var r=k*q[j];var o=j*f;var m=e.height-r;p.push({x:o,y:m});g.lineTo(o,m)}g.lineTo(e.width,e.height);g.fillStyle=e.colour;g.fill();g.beginPath();g.moveTo(0,p[0].y);for(j=0;j<p.length;j++){g.lineTo(p[j].x,p[j].y)}g.lineWidth=e.strokeWidth;g.strokeStyle=e.strokeColour;g.stroke();l.wrapInner(d("<span>").hide()).append(h)});c.add("bar",{colour:"#4D89F9",delimeter:",",height:16,max:null,width:32},function(e){var l=d(this);var h=b(e.width,e.height);var p=l.text().split(e.delimeter);var n=Math.max.apply(Math,p.concat([e.max]));var k=e.height/n;var f=e.width/p.length;var g=h.getContext("2d");g.fillStyle=e.colour;for(var j=0;j<p.length;j++){var q=k*p[j];var o=j*f;var m=e.height-q;g.fillRect(o,m,f,q)}l.wrapInner(d("<span>").hide()).append(h)})})(jQuery,document);