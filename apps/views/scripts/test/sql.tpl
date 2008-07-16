<div id="detail"> Get user name :<b id="user"><?php echo $this->user_name ?></b><br>
Get user_id sql: <?php echo $this->user_name_qry ?> <br>
User_ID:<?php echo $this->user_id ?>
<p>Get system_id sql: <?php echo $this->system_id_qry?> <br>
system_id:<?php echo $this->system_id_list ?></p>
<table border="1">
	<tbody>
		<tr>
			<td>sql</td>
			<td>value</td>
		</tr>
		<tr>
			<td><?php echo $this->count_open_qry ?></td>
			<td><?php echo $this->count_open ?></td>
		</tr>
		<tr>
			<td><?php echo $this->count_en_qry ?></td>
			<td><?php echo $this->count_en ?></td>
		</tr>
		<tr>
			<td><?php echo $this->count_eo_qry ?></td>
			<td><?php echo $this->count_eo ?></td>
		</tr>
	</tbody>
</table>
</div>