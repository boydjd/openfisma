<!--Product with Open Vulnerabilities Report -->
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
    <tr align="center">
        <td class="tdc" colspan="4"><b><?php echo $this->type_list[$this->type];?></b></td>
    </tr>
    <tr align="center">
        <th width="13%">Vendor</th>
        <th width="13%">Product</th>
        <th width="13%">Version</th>
        <th width="13%">#of Open Vulnerabilities</th>
    </tr>
    <?php foreach($this->rpdata as $item){ ?>
    <tr align="center">
        <td class="tdc"><?php echo $item['Vendor'];?></td>
        <td class="tdc"><?php echo $item['Product'];?></td>
        <td class="tdc"><?php echo $item['Version'];?></td>
        <td class="tdc"><?php echo $item['NumoOV'];?></td>
    </tr>
    <?php } ?>
</table>
