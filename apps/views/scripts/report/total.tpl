<?php
    $colnum = 10;
    $colwidth = floor(100/($colnum+1));
    $total = 0;
    foreach($this->rpdata[1] as $res){
        $total = $total+$res['num'];
    }
?>
<!-- Total# of Systems /w Open Vulnerabilities -->
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width=10></td>
        <td align="left"><b>Total # of system with open vulnerability: </b> <?php echo $this->rpdata[0];?></td>
        <td align="right"><b>Total # of vulnerabilities: </b></td>
        <td width=10 align="left"><?php echo $total;?></td>
        <td width=10></td>
    </tr>
</table>
    <?php
        $i = 0;
        foreach($this->rpdata[1] as $rec){
            $i++;
            if($i % $colnum == 1){
    ?>
    <br>
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0"  class="tipframe">
    <tr align="center">
        <td width="<?php echo $colwidth;?>%">
            <table border="0" cellpadding="5" cellspacing="0"  width="100%" height="100%">
                <tr><th>Systems</th></tr>
                <tr><th nowrap>Open Vulnerabilities</th></tr>
            </table>
        </td>
            <?php $rbflag = 0;
                } 
            ?>        
        <td width="<?php echo $colwidth;?>%">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
                <tr><td class="tdc" align="center"><?php echo $rec['nick'];?></td></tr>
                <tr><td class="tdc" align="center"><?php echo $rec['num'];?></td></tr>
            </table>
        </td>
        <?php if($i % $colnum == 0){ ?>
    </tr>
</table>
        <?php $tbflag = 1;
              }
           }
           if(isset($tbflag) && $tbflag != 1){
               $sumtd = $colnum-$i%$colnum;
               if($sumtd != $colnum){
                   $sumtd = $sumtd + 1;
                   foreach($sumtd as $addtd){
        ?>
    <td width="<?php echo $colwidth;?>%">
<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
    <tr><td class="tdc" align="center">&nbsp;</td></tr>
    <tr><td class="tdc" align="center">&nbsp;</td></tr>
</table>
</td>
<?php } 
}
?>
</tr>
</table>
<?php } ?>
