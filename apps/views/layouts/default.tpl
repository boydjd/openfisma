<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/jquery/jquery.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/jquery/jquery.ui.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/ajax.js"></script>

<!--[If lte IE 6]>
<style type="text/css" >
@import url("/stylesheets/ie.css");
</style>
<![endif]-->

<style type="text/css">
<!--
@import url("/stylesheets/layout.css");
@import url("/stylesheets/fisma.css");
@import url("/stylesheets/datepicker.css");
@import url("/stylesheets/main.css");
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
        <tr> <td > Found a Bug? or Have a Suggestion? <a href="https://sourceforge.net/tracker/?group_id=208522" target="_blank">Report it Here</a> </td>
             <td align="right"> <i>Powered by <a href="http://www.openfisma.org">OpenFISMA</a></i> </td>
        </tr>
        </table>
</div><!--bottom-->

</div><!--content-->

</div><!--container-->

</body>
</html>
