<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 
<html>
<head>
	
<title>OVMS - Change password</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
{literal}
<link rel="stylesheet" type="text/css" href="stylesheets/main.css">
{/literal}
</head>

<br>
<!-- Heading Block -->
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="13"><img src="images/left_circle.gif" border="0"></td>
		<td bgcolor="#DFE5ED"><b>Change Password</b></td>
		<td bgcolor="#DFE5ED" align="right"></td>
		<td width="13"><img src="images/right_circle.gif" border="0"></td>
	</tr>
</table>
<!-- End Heading Block -->
<br>
<table width="70%" cellspacing="3" border="0" align="center">
	<tr>
		<td colspan="2">
			{if $errmsg neq ""}
				<font color="red">{$errmsg}</font>
			{/if}
			{if $chgflag eq true && $errmsg eq ""}
				<font color="green">Password changed successfully.</font>
			{/if}
		</td>
	</tr>

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
		<td align="center" colspan="2"><input type="reset" value="Reset" onclick="$('form[name=chgpwd]').get(0).reset();return false;">
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="submit" value="Submit"></td>
	</tr>

	</form>

</table>
<br>
{include file="footer.tpl"}
