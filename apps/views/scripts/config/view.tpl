<div class="barleft">
<div class="barright">
<p><b>Account Policies</b>
</div>
</div>
<b><?php echo $this->msg;?></b>
<form name="config" method="post" action="/config/save/">
<table align="center" cellpadding="5" cellspacing="1" class="tipframe">
<?php foreach($this->configs as $c) { ?>
    <tr >
        <td class="conf_key" ><?php echo $c['description']; ?></td>
        <td class="conf_value" >
            <input type="text" name="keys[<?php echo $c['key'];?>]" value="<?php echo $c['value'];?>" size="30">
        </td>
    </tr>
<?php } ?>

    <tr>
        <td>
        <input name="button" type="reset" id="button" value="Reset" style="cursor:pointer;">
        <input name="button" type="submit" id="button" value="Submit"  style="cursor:pointer;">
        </td>
    </tr>
</table>
</form>

