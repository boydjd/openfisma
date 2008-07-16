<div id="detail">
<form name="form1" action="/zfentry.php/test/dosql/">sql:
	<textarea name="sql" cols="100" rows="5"><?php echo $this->sql;?></textarea>
	<input name="submit" type="submit" /></input></form>
<?php $tmp=$this->result[0];?>
	<table border="1">
		<tr><?php foreach($tmp as $key=>$value){ ?>
			<td><?php echo $key;?></td>
			<?php } ?>
        </tr>
     	<?php foreach($this->result as $line) { ?>
  		<tr>
			<?php foreach($line as $key=>$value){ ?>
			<td><?php echo $value;?></td>
			<?php }?>
		</tr>
		<?php }?>
	</table>
</div>