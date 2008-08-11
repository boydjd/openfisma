<?php
    $this->plugin_list[0] = '---Select Plugin---';
    $this->source_list[0] = '---Select Finding Source---';
    $this->system_list[0] = '---Select System---';
    $this->network_list[0] = '---Select Network---';
?>
<div class="barleft">
  <div class="barright">
    <p><b>Upload Scan Results</b></p>
  </div>
</div>
<form name="finding_upload" action="/finding/import" enctype="multipart/form-data" method="POST">
<table width="90%" align="center">
    <tr>
        <td>
            <table align="left" border="0" cellpadding="5" class="tipframe">
                <th align="left" colspan="2">Finding Upload</th>
                <tr>
                    <td align="right"><b>Plugin:<b></td>
                    <td align="left">
                        <?php echo $this->formSelect('plugin',0,null,$this->plugin_list);?>
                    </td>
                </tr>
                <tr>
                    <td align="right"><b>Finding Source:<b></td>
                    <td align="left">
                        <?php echo $this->formSelect('source',0,null,$this->source_list);?>
                    </td>
                </tr>
                <tr>
                    <td align="right"><b>System:<b></td>
                    <td align="left">
                        <?php echo $this->formSelect('system_id',0,null,$this->system_list);?>
                    </td>
                </tr>
                <tr>
                    <td align="right"><b>Network:<b></td>
                    <td align="left">
                        <?php echo $this->formSelect('network',0,null,$this->network_list);?>
                    </td>
                </tr>
                <tr>
                    <td align="right"><b>Results File:<b></td>
                    <td><input type="file" name="upload_file"></td>
                </tr>
                <tr align="right">
                    <td colspan="2"><input type="submit" name="submit_button" value="Submit"></td> 
                <tr>
            </table>
        </td>
    </tr>
</table>
</form>
<br>
