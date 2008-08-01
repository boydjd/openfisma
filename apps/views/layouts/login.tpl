<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
    <title>Login</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link rel="stylesheet" type="text/css" href="<?php echo burl(); ?>/stylesheets/login.css">
    <link rel="icon"
          type="image/ico"
          href="<?php echo burl()?>/images/favicon.ico" />
</head>

<body>
    <div id="container">
    <div id="header"></div>
    <div id="headbar"><img src="<?php echo burl(); ?>/images/login_title.gif" ></div>
            <?php echo $this->layout()->CONTENT; ?>
    <div id='bottom'>
        <table width="100%">
            <tr><td colspan=2><hr style="color:#a2b4c2; background-color:#a2b4c2;" size="1"></td></tr>
            <tr> <!--<td> Found a Bug? or Have a Suggestion? <a href="https://sourceforge.net/tracker/?group_id=208522" target="_blank">Report it Here</a> </td>-->
                 <td align="right"> <i>Powered by <a href="http://www.openfisma.org">OpenFISMA</a></i> </td>
            </tr>
            </table>
        </div>
    </div>
</body>

</html>
