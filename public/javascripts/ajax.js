var xmlhttp;

function initAjax() {
	xmlhttp = false;

	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	}
	catch(e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		catch(e) {
			xmlhttp = false;
		}
	}

	if(!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		xmlhttp = new XMLHttpRequest();
		xmlhttp.overrideMimeType('text/xml');
	}
}


function DataSet(xmldoc, tagLabel) {
	this.xmlObj = xmldoc.getElementsByTagName(tagLabel);

	//3个方法
	this.getCount = getCount;
	this.getData = getData;
	this.getAttribute = getAttribute;
}


function getCount() {
	return this.xmlObj.length;
}


function getData(index, tagName) {
	if(index >= this.count)
		return "index overflow";
	var node = this.xmlObj[index];
	var str = node.getElementsByTagName(tagName)[0].firstChild.data;
	return str;
}


function getAttribute(index, tagName) {
	if(index >= this.count)
		return "index overflow";
	var node = this.xmlObj[index];
	var str = node.getAttribute(tagName);
	return str;
}


function showStatus(tip) {
	document.getElementById("tip").innerHTML = tip;
}



var delta = 1;//0.5;
var collection;

function floaters() {
	this.items = [];
	this.addItem = function(id,x,y,content,flag)
	{
		m = '<DIV id="'+id+'" style="font-size:9pt;background-color: #345678; color:#ffffff;Z-INDEX: 10; POSITION: absolute; left:'+(typeof(x)=='string'?eval(x):x)+';top:'+(typeof(y)=='string'?eval(y):y)+'">'+content+'</DIV>';
		document.write(m);
		var newItem = {};
		newItem.object = document.getElementById(id);
		newItem.x = typeof(x)=='string'? x + " - followObj.clientWidth" : x;
		newItem.y = y;
		newItem.x_step = 1;
		newItem.y_step = 1;
		newItem.bFloat = flag;

		this.items[this.items.length] = newItem;
	}
	
	this.play = function()
	{
		collection = this.items
		setInterval('play()', 10);
	}
}

function play()
{
	for(var i = 0; i < collection.length; i++)
	{
		var followObj = collection[i].object;
		var followObj_bFloat = collection[i].bFloat;
		var followObj_x = (typeof(collection[i].x) == 'string' ? eval(collection[i].x) : collection[i].x);
		var followObj_y = (typeof(collection[i].y) == 'string' ? eval(collection[i].y) : collection[i].y);
		
		if(followObj_bFloat) {
			// 浮动移动广告
			var c_x = parseInt(followObj.style.left) + collection[i].x_step;;
			var c_y = parseInt(followObj.style.top) + collection[i].y_step;;

			if(c_x <= 0 || c_x == document.body.clientWidth - 100) {
				collection[i].x_step = -1 * collection[i].x_step;
			}
			else if(c_x > document.body.clientWidth - 100) {
				c_x = document.body.clientWidth - 101;
			}

			if(c_y <= 0 || c_y == document.body.clientHeight - 100) {
				collection[i].y_step = -1 * collection[i].y_step;
			}
			else if(c_y > document.body.clientHeight - 100) {
				c_y = document.body.clientHeight - 101;
			}
			//alert(c_x);
			//alert(c_y);
			followObj.style.left = c_x;
			followObj.style.top = c_y;
		}
		else {
			//if(followObj.offsetLeft) {
				if(followObj.offsetLeft != (document.body.scrollLeft + followObj_x)) {
					var dx = (document.body.scrollLeft + followObj_x - followObj.offsetLeft) * delta;
					dx = (dx > 0 ? 1 : -1) * Math.ceil(Math.abs(dx));
					followObj.style.left = followObj.offsetLeft + dx;
				}
				

				if(followObj.offsetTop != (document.body.scrollTop + followObj_y)) {
					var dy = (document.body.scrollTop + followObj_y - followObj.offsetTop) * delta;
					dy = (dy > 0 ? 1 : -1) * Math.ceil(Math.abs(dy));
					followObj.style.top = followObj.offsetTop + dy;
				}
			//}
		}

		followObj.style.display = '';
	}
} 


/****************************************************************************/
// load vulerability by key filter
function loadVulnerList(url) {
	initAjax();

	// code for Mozilla, etc.
	document.getElementById('vlist').innerHTML = "Loading...";
	var needle   = document.finding.vulner_needle.value;
	var num_rows = document.finding.NUM_VULN_ROWS.value;
	var offset   = document.finding.vuln_offset.value;
	if(xmlhttp) {	
		xmlhttp.onreadystatechange = vulnerSearchChange;
		xmlhttp.open("POST", url, true);
		xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		var post_data = "vulner_needle=" + needle + "&NUM_VULN_ROWS=" + num_rows + "&vuln_offset=" + offset;
		xmlhttp.send(post_data);
	}	
}

function vulnerSearchChange() {
	// if xmlhttp shows "loaded"
	if(xmlhttp.readyState == 4) {
		// if "OK"
		if(xmlhttp.status == 200) {
			document.getElementById('vlist').innerHTML = displayVulnerSearch(xmlhttp.responseXML);
			//alert(displayByXML(xmlhttp.responseXML));
		}
		else {
			alert("Server is busy, please try again!");
		}
		showStatus("");
	}
	else {
		showStatus("&nbsp;Loading...&nbsp;");
	}
}


function displayVulnerSearch(xmlDoc) { 
	var vulner = new DataSet(xmlDoc, "vulner");  // tag name
	var count = vulner.getCount();
	
	var msg = "";
	
	msg = msg + "<table border='0' align='center' width='500' cellpadding='1' cellspacing='0' class='tbframe'>";
	if(count > 0) {
		msg = msg + "<tr>";
		msg = msg + "<th>&nbsp;</td>";
		msg = msg + "<th align='left' width='100'>Vulnerability</td>";
		msg = msg + "<th align='left' width='60'>Type</td>";
		msg = msg + "<th align='left'>Description</td>";
		msg = msg + "</tr>";
	}
	for(i=0; i<count; i++) {
		var vseq = vulner.getAttribute(i, "vuln_seq");
		var vtype = vulner.getAttribute(i, "vuln_type");
		var vdesc = vulner.getData(i, "vuln_desc")
		msg = msg + "<tr>";
		msg = msg + "<td class='tdc'><input type='checkbox' name='vuln__"+i+"' value='"+vseq+":"+vtype+"'></td>";
		msg = msg + "<td class='tdc' width='100'>&nbsp;"+vseq+"</td>";
		msg = msg + "<td class='tdc' width='100'>&nbsp;"+vtype+"</td>";
		msg = msg + "<td class='tdc'>"+vdesc+"</td>";
		msg = msg + "</tr>";		
	}
	msg = msg + "</table>";
	
	// Display previous/next buttons if data conditions permit.
	// If current offset is zero then no sense in offering a PREV button.
	// If max number of rows were returned then it's highly likely that
  // there are more where they came from.
	if(document.finding.vuln_offset.value != 0) {
	  msg = msg + "<span style='cursor: pointer' onclick='return page_vulns(false);'><img src='images/button_prev.gif' border='0'></span>";
	  }
	if(count == document.finding.NUM_VULN_ROWS.value) {
	  msg = msg + "<span style='cursor: pointer' onclick='return page_vulns(true);'><img src='images/button_next.gif' border='0'></span>";
	  }
	
	//alert(msg);
	return msg;
}


/****************************************************************************/
// display asset information
function loadAsset(url) {
	initAjax();

	// code for Mozilla, etc.
	//document.getElementById('vlist').innerHTML = "Loading...";
	var needle = document.finding.asset_list.options[document.finding.asset_list.selectedIndex].value;
	if(xmlhttp) {	
		xmlhttp.onreadystatechange = assetLoadChange;
		xmlhttp.open("POST", url, true);
		xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		var post_data = "assetid_needle=" + needle;
		xmlhttp.send(post_data);
	}	
}

function assetLoadChange() {
	// if xmlhttp shows "loaded"
	if(xmlhttp.readyState == 4) {
		// if "OK"
		if(xmlhttp.status == 200) {
			document.getElementById('assetarea').innerHTML = xmlhttp.responseText;
			//fillAssetSelected(xmlhttp.responseXML);
			//alert(displayByXML(xmlhttp.responseXML));
		}
		else {
			alert("Server is busy, please try again!");
		}
		showStatus("");
	}
	else {
		showStatus("&nbsp;Loading...&nbsp;");
	}
}



/****************************************************************************/
// select asset
function loadAssetList(url) {
	initAjax();

	// code for Mozilla, etc.
	//document.getElementById('vlist').innerHTML = "Loading...";
	var system_id = document.finding.system.options[document.finding.system.selectedIndex].value;
	var needle = document.finding.asset_needle.value;
	//var network_id = document.finding.network.options[document.finding.network.selectedIndex].value;
	//var ip = document.finding.ip.value;
	//var port = document.finding.port.value;
	if(xmlhttp) {	
		xmlhttp.onreadystatechange = assetSearchChange;
		xmlhttp.open("POST", url, true);
		xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		//var post_data = "asset_needle=yes&system_id=" + system_id + "&network_id=" + network_id + "&ip=" + ip + "&port=" + port;
		var post_data = "asset_needle=" + needle + "&system_id=" + system_id;
		xmlhttp.send(post_data);
	}	
}

function assetSearchChange() {
	// if xmlhttp shows "loaded"
	if(xmlhttp.readyState == 4) {
		// if "OK"
		if(xmlhttp.status == 200) {
			//document.getElementById('vlist').innerHTML = displayAssetSelected(xmlhttp.responseXML);
			fillAssetSelected(xmlhttp.responseXML);
			//alert(xmlhttp.responseText);
		}
		else {
			alert("Server is busy, please try again!");
		}
		showStatus("");
	}
	else {
		showStatus("&nbsp;Loading...&nbsp;");
	}
}


function fillAssetSelected(xmlDoc) { 
	var asset = new DataSet(xmlDoc, "asset");  //关心的标签名称 
	var count = asset.getCount();

	for(i=document.finding.asset_list.options.length - 1; i>=0; i--) {
		document.finding.asset_list.options[i] = null;
	}

	if(count > 0) {
		document.finding.asset_list.options[0] = new Option("--None--");
		document.finding.asset_list.options[0].value = "";

		for(i=0; i<count; i++) {
			var aid = asset.getAttribute(i,"asset_id");
			var aname = asset.getAttribute(i,"asset_name");

			document.finding.asset_list.options[i+1] = new Option(aname);
			document.finding.asset_list.options[i+1].value = aid;
		}
		//if(count == 1) {
		//	loadAsset('ajaxsearch.php');
		//}
	}
	else {
		document.finding.asset_list.options[0] = new Option("--None--");
		document.finding.asset_list.options[0].value = "";
	}
}

