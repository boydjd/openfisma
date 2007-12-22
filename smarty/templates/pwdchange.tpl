{if $firstlogin ne true}
{include file="header.tpl" title="OVMS" name="Change password"}
{else}
<html>
<head>
	
<title>OVMS - Change password</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
{literal}
<link rel="stylesheet" type="text/css" href="stylesheets/main.css">
{/literal}
</head>

<body marginheight="0" marginwidth="0" topmargin="0" leftmargin="0" rightmargin="0" onLoad="" bgcolor="#ffffff">
<table width="1000" height="30" border="0" background="images/body_vline.jpg">
<tr>
	<td>&nbsp;</td>
</tr>
</table>

<table width="1000" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="2%">&nbsp;</td>
	<td width="96%">

{/if}

{literal}
<!-- <script language="JavaScript" src="script/pass.js"></script> -->
{/literal}


<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbline">
<tr>
	<td valign="bottom"><!--<img src="images/greenball.gif" border="0"> --><b>Change Password</b></td>
	<td align="right" valign="bottom">{$now}</td>
</tr>
</table>

<table width="70%" cellspacing=3 border=0 align="center">
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
{if $chgflag eq true }
<tr>
	<td colspan="2"><font color="red">
	{if $chgreturn eq 1 }
	You are not logged in, please click <a href="index.php"><b>here</b></a> to login.
	{/if}
	{if $chgreturn eq 2 }
	Your old password does not match what we have on file, please try again. 
	{/if}
	{if $chgreturn eq 3 }
	The passwords you typed do not match, please carefully retype both passwords to continue.
	{/if}
	{if $chgreturn eq 4 }
This password does not meet the password complexity requirements.<br>
Please create a password that adheres to these complexity requirements:<br>
--The password must be at least 8 character long<br>
--The password must contain at least 1 lower case letter (a-z), 1 upper case letter (A-Z), and 1 digit (0-9)<br>
--The password can also contain National Characters if desired (Non-Alphanumeric, !,@,#,$,% etc.)<br>
--The password cannot be the same as your last 3 passwords<br>
--The password cannot contain your first name or last name<br>
	{/if}
	{if $chgreturn eq 5 }
	Unable to chanage password, please try again later.
	{/if}
	</font></td>
</tr>
<tr>
	<td>&nbsp;</td>
</tr>
{/if}

{if $chgflag eq true and $chgreturn eq 0 }
<tr>
	<td colspan="2" align="center"><font color="red">Password changed successfully. Please log in again.</font></td>
</tr>
{else}
<form name="chgpwd" method="post" action="pwdchange.php">	
<input type="hidden" name="firstname" value="{$firstname}">
<input type="hidden" name="lastname" value="{$lastname}">
<tr>
	<td align="center" colspan="2" style="font-weight:bold;font-family:tahoma;font-size:11px;">Please Create a New Password Below:</td>
</tr>
<tr>
	<td colspan="2">
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tbframe">
	<tr>
		<td class="thc" align="right">Old Password:&nbsp;</td>
		<td class="tdc">&nbsp;<input type="password" name="oldpass" value=""></td>
	</tr>
	<tr>
		<td class="thc" align="right">New Password:&nbsp;</td>
		<td class="tdc">&nbsp;<input type="password" name="newpass" value=""></td>
	</tr>
	<tr>
		<td class="thc" align="right">Confirm Password:&nbsp;</td>
		<td class="tdc">&nbsp;<input type="password" name="cfmpass" value=""></td>
	</tr>
	</table>
	</td>
</tr>
<tr>
	<td align="center" colspan="2"><input name="reset" type="image" value="Reset" src="images/button_reset.png" onclick="$('form[name=chgpwd]').get(0).reset();return false;">
	&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="submit" type="image" value="Submit" src="images/button_submit.png"></td>
</tr>
</form>
{/if}
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
</table>

{include file="footer.tpl"}
