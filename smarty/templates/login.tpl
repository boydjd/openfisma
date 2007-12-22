<html>
<head>
<title>User login</title>
{literal}
<style type="text/css">
body {
	margin: 0px;
	padding: 0px;
	background-color: #44627A;
}

body,p,td,input,select,textarea {
	font-family: Tahoma, verdana, arial, helvetica, sans-serif;
	font-weight: normal;
	font-size: 12px;
}

.msg {
	font-family: Tahoma, verdana, arial, helvetica, sans-serif;
	font-style: normal;
	color: #CCCCCC;
	height: 30px;
	font-size: 12px;
	background-color: #55758C;
	border: 1px solid #738DA0;
}

</style>
<!-- <script language="JavaScript" src="javascripts/pass.js"></script> -->

<script language="javascript">
<!--
function checkuser() {
	var user = document.login.username.value;
	var pass = document.login.userpass.value;
	
	if(user.length == 0) {
		alert("Sorry, You did not enter a Username. Please enter a Username to continue.");
		document.login.username.focus();
		return false;
	}

	if(pass.length == 0) {
		alert("Sorry, You did not enter a Password. Please enter a Password to continue.");
		document.login.userpass.focus();
		return false;
	}

	return true;
}
-->
</script>
{/literal}
</head>

<body>

<!--
<table border="0" cellpadding="0" cellspacing="0" height="60" width="100%" background="images/title_vline.jpg">
<tr>
	<td><img src="images/fl_logo.gif" border="0"></td>
</tr>
</table>
-->

<table border="0" cellpadding="0" cellspacing="0" height="82" width="100%" background="images/login_bg.jpg">
<tr>
	<td align="right"><img src="images/login_title.gif" border="0"></td>
	<td width="50">&nbsp;</td>
</tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="50">&nbsp;</td>
	<td align="right"><b><font color="#FCB907">{$errmsg}</font></b>&nbsp;</td>
	<td width="50">&nbsp;</td>
</tr>
<tr>
	<td width="50">&nbsp;</td>
	<td>
	<table border="0" width="100%">
	<tr>
		<td width="30%">&nbsp;</td>
		<td width="40%" valign="top">
		{if $warning neq "" }
		<table border="0" cellpadding="3" cellspacing="2" width="100%" class="tipframe">
		<tr>
			<td class="msg">{$warning}&nbsp;</td>
		</tr>
		</table>
		{else}
		&nbsp;
		{/if}
		</td>
		<td width="30%" align="right" valign="top">
		<form name="login" method="post" action="login.php" onSubmit="return checkuser();">
		{if $login eq ""}
		<input type="hidden" name="login" value="1">
		{else}
		<input type="hidden" name="login" value="{$login}">
		{/if}

		<table border="0" cellpadding="2" cellspacing="3">
		<tr>
			<td align="right"><font color="#eeeeee">Username:</font></td>
			<td><input type="text" name="username" value="{$username}" size="16"></td>
		</tr>
		
		{if $login eq 2}
		<tr>
			<td align="right"><font color="#eeeeee">Password:</font></td>
			<td><input type="password" name="userpass" value="" size="16" onBlur="checkpwd(document.login.username, document.login.userpass, 3, false);"></td>
		</tr>
		<tr>
			<td align="right"><font color="#eeeeee">Confirm:</font></td>
			<td><input type="password" name="cfmpass" value="" size="16"></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><input type="image" name="log" src="images/button_login.gif" border="0" onClick="return confirmpwd(document.login.userpass, document.login.cfmpass);"></td>
		</tr>
		{else}
		<tr>
			<td align="right"><font color="#eeeeee">Password:</font></td>
			<td><input type="password" name="userpass" value="" size="16"></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><input type="image" name="log" src="images/button_login.gif" border="0"></td>
		</tr>
		{/if}

		</table>
		</form>
		</td>
	</tr>
	</table>
	</td>
	<td width="50">&nbsp;</td>
</tr>
</table>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<table border="0" align="center" cellpadding="0" cellspacing="0" height="21" width="96%" background="images/line.gif">
<tr>
	<td>&nbsp;</td>
</tr>
</table>

<table border="0" align="center" cellpadding="0" cellspacing="0" width="96%">
<tr>
	<td align="right"><font color="#dddddd">Powered by the Operational Vulnerability Management System</font></td>
</tr>
</table>

</body>
</html>
