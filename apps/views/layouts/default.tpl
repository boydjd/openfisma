<?php
    echo $this->doctype();
?>
<html>
<head>
<?php
    $this->headTitle()->setSeparator(' - ');
    $this->headTitle()->prepend(readSysConfig('system_name'));
    echo $this->headTitle();
?>

<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/jquery/jquery.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/jquery/jquery.ui.js"></script>
<script LANGUAGE="JavaScript" type="text/javascript" src="/javascripts/ajax.js"></script>
<script LANGUAGE="javascript" type="text/javascript" src="/javascripts/tiny_mce/tiny_mce.js"></script>
<script LANGUAGE="javascript" type="text/javascript" src="/javascripts/selectallselectnone.js"></script>
<script LANGUAGE="javascript" type="text/javascript" src="/javascripts/deleteconfirm.js"></script>
<script LANGUAGE="javascript" type="text/javascript">
tinyMCE.init({
	mode : "textareas",
	theme: "simple"
});
</script>

<link rel="icon"
      type="image/ico"
      href="/images/favicon.ico" />

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
<div id='footer'>
        <?php echo $this->layout()->footer; ?>
</div>
</div><!--content-->

</div><!--container-->

</body>
</html>

