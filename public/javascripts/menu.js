
//Navigation Script-------------------------------------------------------------------------//

var currentParent;
var currentMenu;
var menuToHide;
var fadestep;
var cleared = true;
var browserdetect;


function Pos(thisitem) {
	if(typeof(thisitem) != 'object') {
		thisitem = document.getElementById(thisitem);
	}
	var ww = thisitem.offsetWidth;
	var hh = thisitem.offsetHeight;
	for (var xx = 0,yy = 0; thisitem != null; thisitem = thisitem.offsetParent) {
		xx += thisitem.offsetLeft;
		yy += thisitem.offsetTop;
	}
	return {Left:xx, Top:yy, Right:xx + ww, Bottom:yy + hh}
}


function ShowMenu(thisitem, menu) {
	cleared = false
	currentParent = thisitem;

	if(typeof(menu) != 'object') {
		menu = document.getElementById(menu);
	}
	if(currentMenu == menu) {
		if(!cleared)
			clearInterval(fadestep);
		currentMenu = null;
	}
	else if(currentMenu != null)
	{
		InstantHide(currentMenu);
		if (!cleared) 
			clearInterval(fadestep);
		currentMenu = null;
	}

	currentMenu = menu;
	var is_ie=(navigator.userAgent.toLowerCase().indexOf("msie")!=-1 && document.all);

	if (is_ie) {
		currentMenu.filters.alpha.opacity = 100;
	}
	else{
		currentMenu.style.MozOpacity = 1;
	}
	currentMenu.style.left = Pos(currentParent).Left;
	currentMenu.style.top = Pos(currentParent).Bottom;
	currentMenu.style.visibility = 'visible';
	//thisitem.style.backgroundColor = '#F8F9B3';
}


function HoldMenu() {
//    return;
	ShowMenu(currentParent, currentMenu);
}


function HideMenu(hideMenu) {
	if(typeof(hideMenu) != 'object') {
		hideMenu = document.getElementById(hideMenu);
	}
	if(menuToHide != hideMenu && menuToHide != null) {
		InstantHide(menuToHide);
	}

	menuToHide = hideMenu;
	FadeMenu();
}


function FadeMenu() {
	fadestep = setInterval("FadeLevel()", 25)
}


function FadeLevel() {
    
    if (!menuToHide) return;
    
	if (browserdetect == "ie") {
	    if (menuToHide.filters.alpha.opacity > 0)
	    {
		  menuToHide.filters.alpha.opacity -= 10;
	    }
	    else{
	        InstantHide(menuToHide);
	        menuToHide = null;
	    }
	}
	else if (browserdetect == "mz") {
	    if (menuToHide.style.MozOpacity == 0.0){
		  menuToHide.style.MozOpacity -= .1;
	    }
	    else{
	        InstantHide(menuToHide);
	        menuToHide = null;
	    }
	}
	else {
		menuToHide.style.visibility = 'hidden';
	}
}


function InstantHide(iHideMenu) {
	clearInterval(fadestep);
	cleared = true
	iHideMenu.style.visibility = 'hidden';
}


function msdelay(mseconds) { //delay by the input milliseconds
	starttime = new Date()
	while(1) {
		nowtime = new Date()
		diff = nowtime - starttime
		if(diff > mseconds) {
			break;
		}
	}
}

function swapColor(menu, flag){
    var fg = '#F8F9B3';
    var bg = '#44637A';
    var wh = '#FFFFFF';
    var nc = menu.firstChild;
    while(nc.nodeName!='A' && nc.nodeName!='SPAN') nc=nc.nextSibling;
    if (flag == 'on'){
        menu.style.backgroundColor = fg;
        nc.style.color = bg;
    }
    else{
        menu.style.backgroundColor = bg;
        nc.style.color = wh;
    }
}

$(document).ready(function(){
   $("#status").change(function select_overdue(){
       var disable = "";
       if( 0 == $("#status option:selected").attr("has_datepicker") ) {
           disable = "disabled";
       }
       $("#overdue").attr("disabled",disable);
   }).trigger('change'); 
});