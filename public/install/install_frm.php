<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>OpenFISMA Custom Installation</title>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo _INSTALL_CHARSET ?>" />
  <style type="text/css" media="all"><!-- @import url(../xoops.css); --></style>
  <link rel="stylesheet" type="text/css" media="all" href="style.css" />
</head>
<body style="margin: 0; padding: 0;">
<form action='index.php' method='post'>
<table  align="center" width="778"  cellpadding="0" cellspacing="0" background="img/bg_table.gif" >
  <tr height=23>
    <td width="180"><img src="img/hbar_left.gif" width="100%" height="23" alt="" /></td>
    <td width="448" background="img/hbar_middle.gif">&nbsp;</td>
    <td width="150"><img src="img/hbar_right.gif" width="100%" height="23" alt="" /></td>
  </tr>
  <tr height=80>
    <td width="180" bgcolor="#FFFFFF" align=right><a href="index.php"><img src="img/OpenFISMA.gif" alt="OpenFISMA Logo" /></a></td>
    <td width="448" bgcolor="#FFFFFF" >&nbsp;</td>
    <td width="150" bgcolor="#FFFFFF" >&nbsp;</td>
  </tr>
  <tr height=23>
    <td width="180"><img src="img/hbar_left.gif" width="100%" height="23" alt="" /></td>
    <td width="448" background="img/hbar_middle.gif">&nbsp;</td>
    <td width="150"><img src="img/hbar_right.gif" width="100%" height="23" alt="" /></td>
  </tr>
</table>

<table  width="778" align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
  <tr>
    <td width='5%'>&nbsp;</td>
    <td colspan="3"><?php if(!empty($title)) echo '<h4 style="margin-top: 10px; margin-bottom: 5px; padding: 10px;">'.$title.'</h4>';
                                  echo '<div style="padding: 10px;text-align:center;">'.$content.'</div>'; 
                           ?></td>
    <td width='5%'>&nbsp;</td>
  </tr>
  <tr>
    <td width='5%'>&nbsp;</td>
    <td width='35%' align='left'><?php echo b_back($b_back); ?></td>
    <td width='20%' align='center'><?php echo b_reload($b_reload); ?></td>
    <td width='35%' align='right'><?php echo b_next($b_next); ?></td>
    <td width='5%'>&nbsp;</td>
  </tr>
  <tr>
    <td colspan="5">&nbsp;</td>
  </tr>
</table>

<table width="778" cellspacing="0" align="center" cellpadding="0"  background="img/bg_table.gif">
  <tr>
    <td width="150"><img src="img/hbar_left.gif" width="100%" height="23" alt="" /></td>
    <td width="478" background="img/hbar_middle.gif">&nbsp;</td>
    <td width="150"><img src="img/hbar_installer_right.gif" width="100%" height="23" alt="" /></td>
  </tr>
</table>
</form>
</body>
</html>
