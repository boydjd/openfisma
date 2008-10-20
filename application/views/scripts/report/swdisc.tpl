<!-- Software Discovered Through Vulnerability Assessments -->
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe"  >
    <tr align="center">
        <td class="tdc" colspan="3"><b><?php echo $this->type_list[$this->type];?></b></td>
    </tr>
    <tr align="center">
        <th>Vendor</th>
        <th>Product</th>
        <th>Version</th>
    </tr>
        <?php foreach($this->rpdata as $item){ ?>
    <tr align="center">
        <td class="tdc"><?php echo $item['Vendor'];?></td>
        <td class="tdc"><?php echo $item['Product'];?></td>
        <td class="tdc"><?php echo $item['Version'];?></td>
    </tr>
        <?php } ?>
</table>
