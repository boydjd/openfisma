<?php
    echo $this->doctype();
?>
<html>
<head>
<?php
    $this->headTitle()->setSeparator(' - ');
    $this->headTitle()->prepend('OpenFISMA');
    echo $this->headTitle();
?>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link rel="stylesheet" type="text/css" href="/stylesheets/login.css">
    <link rel="icon"
          type="image/ico"
          href="/images/favicon.ico" />
    <style> 
        a {color:#FFFFFF} 
        a:hover {color:#FFFF00} 
    </style>
</head>

<body>
    <div id="container">
    <div id="header"></div>
    <div id="headbar"><img src="/images/login_title.gif" ></div>
            <?php echo $this->layout()->CONTENT; ?>
    <div id='bottom'>
    <table width="100%">
        <tr>
            <td colspan=2><hr style="color: #44637A;" size="1"></td>
        </tr>
        <tr>
            <td align="right">
            <a href="mailto:<?php echo readSysConfig('contact_email');?>?Subject=<?php echo urlencode(readSysConfig('contact_subject'));?>">Contact Administrator</a>&nbsp;|&nbsp;
            <a href="/user/rob/">Rules of Behavior</a>&nbsp;|&nbsp;
            <a href="/user/privacy/">Privacy Policy</a>&nbsp;|&nbsp;
            <i>Powered by <a href="http://www.openfisma.org">OpenFISMA</a></i>
        </td>
    </tr>
</table>
    </div>
</body>

</html>
