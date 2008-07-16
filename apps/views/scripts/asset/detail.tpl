<table border="0" width="100%" cellpadding="3" cellspacing="1">
<tr>
    <td>
    <table border="0"cellpadding="3" cellspacing="1">
    <tr>
        <td>&nbsp;</td>
        <td align="right"><b>System:</b></td>
        <td colspan="7"> <?php echo $this->asset['sname']; ?> </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td align="right"><b>IP Address:</b></td>
        <td colspan="7">
        <?PHP echo $this->asset['ip'];?>
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td align="right"><b>Product:</b></td>
        <td><?php echo $this->asset['pname']; ?></td>
        <td>&nbsp;</td>
        <td align="right"><b>Vendor:</b></td>
        <td><?php echo $this->asset['pvendor']; ?></td>
        <td>&nbsp;</td>
        <td align="right"><b>Version:</b></td>
        <td><?php echo $this->asset['pversion']; ?></td>
    </tr>
    </table>
    </td>
</tr>
</table>
