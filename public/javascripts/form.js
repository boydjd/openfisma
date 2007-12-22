function isIP(ip,flag) 
{ 
	var i;
	var segnum = 0;
	if (flag) 
		segnum = 4;   //ipv4
	else 
		segnum = 6;   //ipv6

	var scount=0; 
	
	
	var iplength = ip.length; 
	var Letters = "1234567890."; 
	for (i = 0; i < ip.length; i++) 
	{ 
	   var CheckChar = ip.charAt(i); 
	   if (Letters.indexOf(CheckChar) == -1) 
	   { 
			return false; 
	   } 
	} 

	var strs;
	addr = ip.split(".");
	scount = addr.length;
	
	  
	if(scount!=segnum) 
	{ 
	    return false; 
	} 
	
	pattern1 = /^[0-9]$/;
	pattern = /^[1-9][0-9]{1,2}$/;
	
	for (i = 0; i < scount; i++)
	{
		str = addr[i];
		if (str.length == 0) return false;
		if (str.length == 1) 
		{
			if (!pattern1.test(str)) return false;
		}	
		if (str.length == 2 || str.length ==3 )
		{
			if (pattern1.test(str)) return false;
		}
		if (str.length > 3) return false;
		if (str< 0 || str >255) 
		{
			return false; 
		} 
		
	}

	return true;
}


function isEmpty(el)
{
	return ((el == null) || (el.length == 0))
}

function IsURL(url)
{
	if(url == "http://")
		return false;
	
	return true;
}

/*
function IsEmail(address)
{
	if(isEmpty(address))
		return false;
	
	if(address.indexOf(" ",0) != -1)
		return false;
	
	var atPos = address.indexOf("@",0);
	if(atPos == -1)
		return false;
	
	var dotPos = address.indexOf(".", atPos+1);
	if(dotPos == -1)
		return false;
	
	return true;
}
*/


function IsStrNull(string)
{
	if(isEmpty(string))
		return true;

	var i;
	var c;
	for(i=0;i<=string.length-1;i++)
	{
		c = string.charAt(i);
		if(c != ' ')
			return false;
	}
	return true;
}


function IsNumber(s) // check number
{
	var digits = "0123456789";
	var i = 0;
	var sLength = s.length;
	
	while((i < sLength))
	{
		var c = s.charAt(i);
		if(digits.indexOf(c) == -1)
			return false;
		i++;
	}

	return true;
}


function IsFloat(s) // check float
{
	var digits = "0123456789.";
	var i = 0;
	var sLength = s.length;
	
	while((i < sLength))
	{
		var c = s.charAt(i);
		if(digits.indexOf(c) == -1)
			return false;
		i++;
	}

	return true;
}



function openSmallWindow(url) 
{
	window.open(url,"smallWindow","width=500,height=300,scrollbars,resizable");
	return false;
}


function Jtrim(str)  //remove blankspace
{
	var i = 0;
	var len = str.length;
	var j = len - 1;
	flagbegin = true;
	flagend = true;
	
	if(str == "")
		return str;
	
	while(flagbegin == true && i < len)
	{
		if(str.charAt(i) == " ")
		{
			i = i + 1;
			flagbegin = true;
		}
		else
		{
			flagbegin = false;
		}
	}

	while(flagend == true && j >= 0)
	{
		if(str.charAt(j) == " ")
		{
			j = j - 1;
			flagend = true;
		}
		else
		{
			flagend = false;
		}
	}

	if(i > j)
		return "";

	trimstr = str.substring(i, j + 1);
	
	return trimstr;
}

function IsEmail(s) // check email
{	
	if(s.length > 100)
	{
		return false;
	}	
	
	var regu = "^(([0-9a-zA-Z]+)|([0-9a-zA-Z]+[_.0-9a-zA-Z-]*[0-9a-zA-Z]+))@([a-zA-Z0-9-]+[.])+([a-zA-Z]{2}|net|NET|com|COM|gov|GOV|mil|MIL|org|ORG|edu|EDU|int|INT)$";
	var re = new RegExp(regu);
	if(s.search(re) != -1)
		return true;
	else
		return false;
}


function IsMoney(s)
{
	strRef = "1234567890.";
	
	if(!IsEmpty(s))
		return false;
		
	for(i=0; i<s.length; i++)
	{
		tempChar = s.substring(i, i+1);
		if(strRef.indexOf(tempChar,0) == -1)
		{
			return false; 
		}
		else
		{
			tempLen = s.indexOf(".");
			if(tempLen != -1)
			{
				strLen = s.substring(tempLen + 1, s.length);
				if(strLen.length > 2)
				{
					return false; 
				}
			}
		}
	}
	
	return true;
}

function IsLeapYear(year) // check Leap year
{ 
	if((year % 4 == 0 && year % 100 != 0) || (year % 400 == 0)) 
		return true; 
	else
		return false; 
}


// check datetime
function IsDatetime(s)
{
	if(s.length > 0)
	{
		datetime = Jtrim(s);
		if(datetime.length == 19)
		{
			sdate = datetime.substring(0,10);
			stime = datetime.substring(11,19);
			if(IsDate(sdate) && IsTime(stime))
				return true;
			else
				return false;
		}
		else
			return false;
	}
	
	// null is valid
	return true;
}

// check date
function IsDate(s){
	var datetime;
	var year, month, day;
	var gone, gtwo;
	
	if(s.length > 0)
	{
		if(s == "0" || s == "00/00/0000")
			return true;
			
		datetime = Jtrim(s);
		if(datetime.length == 10)
		{
			//month = datetime.substring(5, 7);
			month = datetime.substring(0, 2);
			if(isNaN(month) == true)
				return false;
			
			gone = datetime.substring(2, 3);
			//day = datetime.substring(8, 10);
			day = datetime.substring(3, 5);
			if(isNaN(day) == true)
				return false;

			//year = datetime.substring(0, 4);
			gtwo = datetime.substring(5, 6);
			year = datetime.substring(6, 10);
			if(isNaN(year) == true)
				return false;
		}
		
		if(((gone == "-") && (gtwo == "-")) || ((gone == "/") && (gtwo == "/")))
		{
			if(month < 1 || month > 12)
				return false;
			 
			if(day<1 || day > 31)
				return false; 
			
			if(month == 2)
			{  
				if(IsLeapYear(year) && day > 29)
				{ 
					// date must be 01 ~ 29 in Febrary! 
					return false; 
				}       
				if(!IsLeapYear(year) && day > 28)
				{ 
					// date must be 01 ~ 28 in Febrary! 
					return false; 
				} 
			}
			
			if((month == 4 || month == 6 || month == 9 || month== 11) && (day > 30))
			{ 
				// 4, 6, 9, 11, date is 01 ~ 30!
				return false; 
			}
		}
		else
		{
			return false;
		}
	}
	
	return true;
}

// check time
function IsTime(s){
	var datetime;
	var year, month, day;
	var gone, gtwo;
	
	if(s > 0)
	{
		datetime = Jtrim(s);
		if(datetime.length == 8)
		{
			hour = datetime.substring(0, 2);
			if(isNaN(hour) == true)
				return false;
			
			gone = datetime.substring(2, 3);
			minute = datetime.substring(3, 5);
			if(isNaN(minute) == true)
				return false;
			
			gtwo = datetime.substring(5, 6);
			second = datetime.substring(6, 8);
			if(isNaN(second) == true)
				return false;
		}
		
		if((gone == ":") && (gtwo == ":"))
		{
			if(hour < 0 || hour > 24)
				return false;
			
			if(minute < 0 || minute > 59)
				return false;
			
			if(second < 0 || second > 59)
				return false;
		}
		else
		{
			return false;
		}
	}
	
	return true;
}


function mustHaveCheckbox(formObj) {
	var num = 0;
	
	if(formObj.p_checkhead == null || formObj.p_checktip == null)
		return true;

	var checkhead = formObj.p_checkhead.value;
	var checktip = formObj.p_checktip.value;

	for(i=0; i<formObj.elements.length; i++) {
		if(formObj.elements[i].type == 'checkbox') {
			ename = formObj.elements[i].name;		
			if(ename.substring(0, checkhead.length) == checkhead) {
				//alert(ename);
				if(formObj.elements[i].checked == true)
					num++;
			}
		}
	}
	if(num == 0) {
		alert("Please select " + checktip + ".");
		return false;
	}

	return true;
}


function go(myform)
{
	var count=0;
	for(i=0; i<myform.elements.length; i++)
	{
		elementObj = myform.elements[i];

		if(elementObj.type == "text")
		{
			if(elementObj.getAttribute("isnull") == "no")
			{
				if(IsStrNull(elementObj.value))
				{
					alert("Please input " + elementObj.title + "!");
					elementObj.focus();
					return false;
				}
			}

			//alert(elementObj.datatype);
			if(elementObj.getAttribute("datatype") == "email" && elementObj.getAttribute("isemail") == "yes")
			{
				if(!IsEmail(elementObj.value))
				{
					alert("Please input a valid EMAIL address");
					elementObj.select();
					return false;
				}
			}
			else if(elementObj.getAttribute("datatype") == "number")
			{
				if(!IsNumber(elementObj.value))
				{
					alert("Please input a valid number");
					elementObj.select();
					return false;
				}
			}
			else if(elementObj.getAttribute("datatype") == "date")
			{
				if(!IsDate(elementObj.value))
				{
					alert("Please input a valid date");
					elementObj.select();
					return false;
				}
			}
			else if(elementObj.getAttribute("datatype") == "time")
			{
				if(!IsTime(elementObj.value))
				{
					alert("Please input a valid time");
					elementObj.select();
					return false;
				}
			}
			else if(elementObj.getAttribute("datatype") == "datetime")
			{
				if(!IsDatetime(elementObj.value))
				{
					alert("Please input a valid date/time");
					elementObj.select();
					return false;
				}
			}
		}
		else if(elementObj.type == "password")
		{
			confirmpassname = elementObj.name;
			if(confirmpassname.indexOf("_confirm") > 0)
				continue;
			confirmpassname = elementObj.name + "_confirm";
			confirmvalue = document.getElementById(confirmpassname).value;
			if(IsStrNull(elementObj.value))
			{
				if(confirm("Password field is empty - password will not be modified. Do you want to continue?") == false)
				{
					alert("Please input " + elementObj.title);
					elementObj.focus();
					return false;
				}
			}
			if(elementObj.value != confirmvalue) {
				alert("Password does not match confirmation. Please resubmit password and confirmation.");
				elementObj.select();
				return false;
			}
		}
		else if(elementObj.type == "textarea")
		{
			if(elementObj.getAttribute("isnull") == "no")
			{
				if(IsStrNull(elementObj.value))
				{
					alert("Please input " + elementObj.title + "£¡");
					elementObj.focus();
					return false;
				}
			
			
				if(IsStrNull(elementObj.value))
				{
					alert("Please input " + elementObj.title);
					elementObj.focus();
					return false;
				}
				
				if(elementObj.value.length > elementObj.getAttribute("datalength"))
				{
					alert("The " + elementObj.title + " length is greater than the character limit");
					elementObj.select();
					return false;
				}
			}
		}
	}

	return mustHaveCheckbox(myform);
//	return true;
}
