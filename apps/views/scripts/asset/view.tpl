<div class="barleft">
<div class="barright">
<p><b>Asset Detail</b></p>
</div>
</div>
<table align="center" class="tipframe">
    <tr>
        <td width="50%">
            <table width='100%' class='tbframe' cellpadding="5">
                <th align='left'>Asset Information</th>
                <tr><td valign='center' align='left'><b>Name: </b><?php echo $this->asset['name'];?></td></tr>
                <tr><td valign="center" align="left"><b>System:</b><?php echo $this->asset['source'];?></td></tr>
                <tr><td valign="center" align="left"><b>Date Created:</b><?php echo $this->asset['created_date'];?>
                     (<?php echo $this->asset['source'];?>)</td>
                </tr>
            </table>
        </td>
        <td width="50%">
            <table width="100%" class='tbframe' cellpadding="5">
                <th align='left'>Product Information</th>
                <tr><td><b>Vendor: </b><?php echo $this->asset['prod_vendor'];?></td></tr>
                <tr><td><b>Product: </b><?php echo $this->asset['prod_name'];?></td></tr>
                <tr><td><b>Version: </b><?php echo $this->asset['prod_version'];?></td></tr>
            </table>
        </td>
    </tr>
</table>
<table class="tipframe" cellpadding="5">
    <th align='left'>Address Information</th>
    <tr>
        <td>
            <table width='100%'>
                <tr>
                    <td width='50%'><b>Network: </b>(<?php echo $this->asset['net_nickname'];?>)
                        <?php echo $this->asset['network_name'];?></td>
                    <td width='50%'><b>IP Address: </b><?php echo $this->asset['ip'];?></td>
                </tr>
                <tr>
                    <td width='50%'><b>Date Discovered: </b><?php echo $this->asset['created_date'];?></td>
                    <td width='50%'><b>Port: </b><?php echo $this->asset['port'];?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
