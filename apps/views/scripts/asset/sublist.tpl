<div class="barleft">
<div class="barright">
<p><b>Asset Search Results</b>
</div>
</div>
<form id="assetresult" method="post" action="/asset/delete">
<table width="98%" align="center" border="0"><tr><td>
            <div id="asset_buttons" class="button_container">
            <a class="button" target='_blank' href="<?php echo $this->url.'/format/pdf'; ?>"><img src="/images/pdf.gif" height="16" width="16" align="top" border="0"></a>
            <a class="button" href="<?php echo $this->url.'/format/xls'; ?>"><img src="/images/xls.gif" height="16" width="16" border="0" align="top"></a>
            <?php if(Config_Fisma::isAllow('asset','delete')){ ?>
                <a class="button" href="#" name="select_all">Select All</a>
                <a class="button" href="#" name="select_none">Select None</a>
                <a class="button" href="#" name="delete_selected" onclick="document.forms.assetresult.submit();">Delete</a>
            <?php } if(Config_Fisma::isAllow('asset','create')){ ?>
                <a class="button" href="/asset/create" name="create_asset" >Create an Asset</a>
            <?php } ?>
            </div>
        </td><td align="right">
            <b>Page:&nbsp;</b><?php echo $this->links['all'];?>
        </td></tr>
</table>
<table width="98%" align="center" border="0" class="tbframe">
    <tr align="center">
        <th nowrap></th>
        <th>Asset Name</th>
        <th>System</th>
        <th>IP Address</th>
        <th>Port</th>
        <th>Product Name</th>
        <th>Vendor</th>
        <?php if(Config_Fisma::isAllow('asset','update')){
                 echo'<th nowrap>Edit</th>';
              }
              if(Config_Fisma::isAllow('asset','read')){
                 echo'<th nowrap>View</th>';
              }
        ?>
    </tr>
    <?php foreach($this->asset_list as $row){ ?>
    <tr>
        <?php if(Config_Fisma::isAllow('asset','delete')){ ?>
            <td align="center" class="tdc"><input type="checkbox" name="aid_<?php echo $row['aid'];?>" 
                value="<?php echo $row['aid'];?>"></td>
        <?php } else { ?>
            <td align="center" class="tdc">&nbsp;</td>
        <?php } ?>
        <td class="tdc">&nbsp;<?php echo $row['asset_name'];?></td>
        <td class="tdc">&nbsp;<?php echo $row['system_name'];?></td>
        <td class="tdc">&nbsp;<?php echo $row['address_ip'];?></td>
        <td class="tdc">&nbsp;<?php echo $row['address_port'];?></td>
        <td class="tdc">&nbsp;<?php echo $row['prod_name'];?></td>
        <td class="tdc">&nbsp;<?php echo $row['prod_vendor'];?></td>
        <?php if(Config_Fisma::isAllow('asset','update')){ ?>
        <td class="tdc" align="center"><a href="/panel/asset/sub/view/s/edit/id/<?php echo $row['aid'];?>"><img src="/images/edit.png" border="0"></a></td>
        <?php } if(Config_Fisma::isAllow('asset','read')){ ?>
        <td class="tdc" align="center"><a href="/panel/asset/sub/view/id/<?php echo $row['aid'];?>"><img src="/images/view.gif" border="0"></a></td>
        </tr>
        <?php } ?>
    <?php } ?>
</table>
</form>
