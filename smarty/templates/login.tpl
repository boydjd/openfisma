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

{/literal}

</head>

<body>

<!-- Header spacer -->
<table border="0" cellpadding="0" cellspacing="0" height="60" width="100%" background="images/title_vline.jpg"><tr><td></td></tr></table>
<!-- End Header spacer -->

<!-- Login Image and spacer -->
<table border="0" cellpadding="0" cellspacing="0" height="82" width="100%" background="images/login_bg.jpg">
	<tr>
		<td align="right"><img src="images/login_title.gif" border="0"></td>
		<td width="50">&nbsp;</td>
	</tr>
</table>
<!-- End Login Image and spacer -->

<!-- Login and Warning Banner Table -->
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
						
						<!-- Display error messages back to user -->
						<table border="0" cellpadding="3" cellspacing="2" width="100%" class="tipframe">
							<tr>
								<td class="msg">This is a United States Government Computer system. We encourage
                                    its use by authorized staff, auditors, and contractors. Activity on this
                                    system is subject to monitoring in the course of systems administration and
                                    to protect the system from unauthorized use. Users are further advised that
                                    they have no expectation of privacy while using this system or in any
                                    material on this system. Unauthorized use of this system is a violation of
                                    Federal Law and will be punished with fines or imprisonment (P.L. 99-474)
                                    Anyone using this system expressly consents to such monitoring and
                                    acknowledges that unauthorized use may be reported to the proper authorities.
                                    &nbsp;
                                </td>
							</tr>
						</table>
						<!-- End Display error messages back to user -->
		
					</td>
					<td width="30%" align="right" valign="top">
		
						<form name="login" method="post" action="login.php">
						<input type="hidden" name="login" value="1">

						<table border="0" cellpadding="2" cellspacing="3">
							<tr>
								<td align="right"><font color="#eeeeee">Username:</font></td>
								<td><input type="text" name="username" value="" size="16"></td>
							</tr>
		
							<tr>
								<td align="right"><font color="#eeeeee">Password:</font></td>
								<td><input type="password" name="userpass" value="" size="16"></td>
							</tr>
							<tr>
								<td colspan="2" align="right"><input type="submit" value="Login" name="log" class="button"></td>
							</tr>
		
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
	<td align="right"><font color="#dddddd">Powered by OpenFISMA</a></font></td>
</tr>
</table>

</body>
</html>
