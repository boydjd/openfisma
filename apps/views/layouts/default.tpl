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

<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/jquery/jquery.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/jquery/jquery.ui.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/ajax.js"></script>
<link rel="icon"
      type="image/ico"
      href="images/favicon.ico" />

<style type="text/css">
<!--
@import url("/stylesheets/main.css");
@import url("/stylesheets/datepicker.css");
@import url("/stylesheets/jquery-ui-themeroller.css");
-->
</style>

</head>
<body>

<div id='container'>

<div id='top' >
        <?php echo $this->layout()->header; ?>
</div><!--top-->


<div id="content">

<div id='detail'>
        <?php echo $this->layout()->CONTENT; ?>
</div><!--detail-->

<div id='bottom'>
        <table width="100%">
        <tr><td colspan=2><hr style="color: #44637A;" size="1"></td></tr>
        <tr> <td>If you find bugs or wish to provide feedback, please contact the <a href="mailto:<?php echo readSysConfig('contact_email');?>?Subject=<?php echo readSysConfig('contact_subject');?>">administrator</a>.</td>
             <td align="right"><a href="/panel/user/sub/privacy/">Privacy Policy</a>&nbsp;|&nbsp;<i>Powered by <a href="http://www.openfisma.org">OpenFISMA</a></i> </td>
        </tr>
        </table>
</div><!--bottom-->

</div><!--content-->

</div><!--container-->

</body>
</html>
