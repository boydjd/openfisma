function selectall(thisform, headname, checkflag) {
	var formObj = document.forms[thisform];

	for(i=0; i<formObj.elements.length; i++) {
		if(formObj.elements[i].type == 'checkbox') {
			ename = formObj.elements[i].name;		
			if(ename.substring(0, headname.length) == headname) {
				formObj.elements[i].checked = checkflag;
			}
		}
	}
}

function deleteconfirm(thisform, headname, entryname) {
	var formObj = document.forms[thisform];
	var num = 0;
	
	for(i=0; i<formObj.elements.length; i++) {
		if(formObj.elements[i].type == 'checkbox') {
			ename = formObj.elements[i].name;		
			if(ename.substring(0, headname.length) == headname) {
				if(formObj.elements[i].checked)
					num++;
			}
		}
	}

	if(num > 0) {
		if(!confirm("Are you sure that you want to delete this " + entryname + "?")) {
			return false;
		}
	}
	else {
		alert("No " + entryname + " selected. Please select a " + entryname + " to delete!");
		return false;
	}
	
	return formObj.submit();
}


var vulner_title = "<table border='0' align='center' width='250' cellpadding='1' cellspacing='0' class='tbframe'>";
vulner_title = vulner_title + "<tr>";
vulner_title = vulner_title + "<th width='20'>&nbsp;</td>";
vulner_title = vulner_title + "<th width='130' align='left'>Vulnerability</td>";
vulner_title = vulner_title + "<th width='100' align='left'>Type</td>";
vulner_title = vulner_title + "</tr>";
vulner_title = vulner_title + "</table>";


function checkVulnerItem(thisform, headname) {
	var tObj = document.forms[thisform];
	var num = 0;
	for(j=0; j<tObj.elements.length; j++) {
		if(tObj.elements[j].type == 'checkbox') {
			cname = tObj.elements[j].name;		
			//alert(ename);
			if(cname.substring(0, headname.length) == headname) {
				num++;
			}
		}
	}
	if(num > 0)
		return true;
	else
		return false;
}


function checkVulner(thisform, headname, vunlvalue) {
	var tObj = document.forms[thisform];
	
	for(j=0; j<tObj.elements.length; j++) {
		if(tObj.elements[j].type == 'checkbox') {
			cname = tObj.elements[j].name;		
			//alert(ename);
			if(cname.substring(0, headname.length) == headname) {
				cvalue = tObj.elements[j].value;
				if(vunlvalue == cvalue) {
					//alert("check:" + vunlvalue + "=" + tevalue);
					return true;
				}
			}
		}
	}
	return false;
}


function addVulner(thisform, headname, destname) {

	var formObj = document.forms[thisform];
	var destarea = document.getElementById(destname);
	
	for(i=0; i<formObj.elements.length; i++) {
		if((formObj.elements[i].type == 'checkbox') && (formObj.elements[i].checked)) {
			ename = formObj.elements[i].name;
			//alert(ename);
			if(ename.substring(0, headname.length) == headname) {				
				evalue = formObj.elements[i].value;
				//alert("add:" + ename + "=" + evalue);
				if(!checkVulner(thisform, "vuln_-", evalue)) {
					pos = evalue.indexOf(":");
					vid = evalue.substring(0, pos);
					vtype = evalue.substring(pos + 1);
					msg = destarea.innerHTML;
					itemstr = "<div id=\"vuln_-"+i+"\">";
					itemstr = itemstr + "<table border=\"0\" width=\"250\" cellpadding=\"1\" cellspacing=\"0\" class='tbframe' style=\"border-top:#ffffff\">";
					itemstr = itemstr + "<tr>";
					itemstr = itemstr + "<td width=\"20\"><input type=\"checkbox\" name=\"vuln_-"+i+"\" value=\""+evalue+"\"></td>";
					itemstr = itemstr + "<td width=\"130\" style=\"border-left: 1px solid #BEBEBE;\">"+vid+"</td>";
					itemstr = itemstr + "<td width=\"100\" style=\"border-left: 1px solid #BEBEBE;\">"+vtype+"</td>";
					itemstr = itemstr + "</tr>";
					itemstr = itemstr + "</table>";
					itemstr = itemstr + "</div>";
					//alert(itemstr);
					destarea.innerHTML = msg + itemstr;
				}
				formObj.elements[i].checked = false;
			}
		}
	}
}

function removeVulner(thisform, headname, destname) {
	var rObj = document.forms[thisform];
	var dest = document.getElementById(destname);

	lmsg = "";
	var num = 0;
	for(i=0; i<rObj.elements.length; i++) {
		if(rObj.elements[i].type == 'checkbox') {
			vname = rObj.elements[i].name;
			if(vname.substring(0, headname.length) == headname) {
				removeObj = document.getElementById(vname);
				if(rObj.elements[i].checked) {
					num++;
					continue;
				}
				else {
					lmsg = lmsg + "<div id=\""+vname+"\">" + removeObj.innerHTML + "</div>";
					//alert(lmsg);
				}
			}
		}
	}
	if(num > 0)
		dest.innerHTML = vulner_title + lmsg;
}

function pageskip(frm, where) {
    var pageno  = parseInt($(':input[@name="pageno"]').val());
    var total   = parseInt($(':input[@name="totalpage"]').val());
    var next    = (pageno<total)?(pageno+1):(total);
    var prev    = (pageno<1)?1:(pageno-1);
    
    eval("$(':input[@name=\"pageno\"]').val("+where+");");
    $('form[@name="'+frm+'"]').submit();
}