<div class="barleft">
<div class="barright">
<p><b>Asset Edit</b></p>
</div>
</div>
<form name="assetedit" method="post" action="/zfentry.php/asset/update/id/<?php echo $this->id;?>">
<table width="810" border="0" align="center">
<tr>
    <td><input type="hidden" name="prod_id" />
	    <input name="input" type="submit" value="Update Asset"/>
        <input type="reset" name="button" id="button" value="Reset" />
     </td>
</tr>
	<tr>
    	<td>
            <table border="0" width="800" cellpadding="5" class="tipframe">
			    <tr><th colspan="6" align="left"> General Information</th>
                <tr>
					<td valign="center" ><b>Asset Name </b></td>
					<td valign="center" colspan="4">
                        <input name="name" type="text" value="<?php echo $this->asset['name'];?>" size="23" maxlength="23">  
                    </td>
				</tr>
				<tr>
					<td valign="center" ><b>System:</b></td>
					<td valign="center" colspan="4">
                        <?php echo $this->formSelect('system_id',$this->asset['system_id'],null,$this->system_list);?></td>
				</tr>
				<tr>
					<td valign="center" ><b>Network:</b></td>
					<td valign="center" colspan="4"><?php echo $this->formSelect('network_id',$this->asset['network_id'], null, $this->network_list); ?></td>
				</tr>
				<tr>
					<td valign="center" ><b>IP Address:</b></td>
					<td valign="center" colspan="4">
                        <input type="text" name="address_ip" value="<?php echo $this->asset['ip'] ?>" maxlength="23" size="23">
						<input type="radio" name="addrtype" value="1" onClick="javascript:changeAddrType(this);" {$chked1}> IPV4
						<input type="radio" name="addrtype" value="2" onClick="javascript:changeAddrType(this);" {$chked2}> IPV6 </td>
				</tr>
				<tr>
					<td valign="center" ><b>Port:</b></td>
					<td valign="center" colspan="4"><input type="text" name="address_port" value="<?php echo $this->asset['port'] ?>" maxlength="5" size="5"></td>
				</tr>
                <tr>
                    <td valign="center"><b>Product</b></td>
                    <td valign="center"><?php echo $this->asset['prod_name'];?></td>
                    <td valign="center"><b>Vendor</b></td>
                    <td valign="center"><?php echo $this->asset['prod_vendor'];?></td>
                    <td valign="center"><b>Version</b></td>
                    <td valign="center"><?php echo $this->asset['prod_version'];?></td>
                </tr>
			</table>
        </td>
	</tr>
</table>
</form>
