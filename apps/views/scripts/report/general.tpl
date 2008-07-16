<?php 
$type_list = array('' =>'Please Select Report',
                   '1'=>'NIST Baseline Security Controls Report',
                   '2'=>'FIPS 199 Categorization Breakdown',
                   '3'=>'Products with Open Vulnerabilities',
                   '4'=>'Software Discovered Through Vulnerability Assessments',
                   '5'=>'Total # of Systems /w Open Vulnerabilities');
$action_list = array(1=>'blscr',2=>'fips',3=>'prods',4=>'swdisc',5=>'total');
?>
<div class="barleft">
<div class="barright">        
<p><b>General Reports</b></p>
</div>
</div>
<form name="filter" method="post" action="/zfentry.php/panel/report/sub/general/s/search">
<table width="95%" align="center" border="0">
    <tr>
        <td>
            <table cellpadding="5" class="tipframe">
                <tr>
                    <td><b>Report</b></td>
                    <td>
                        <?php echo $this->formSelect('type',$this->type,null,$type_list);?>
                    </td>
                    <td>
                        <input type="submit" value="Generate">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</form>
<?php
    if(!empty($this->type)){
        $url = "/zfentry.php/report/".$action_list[$this->type];
?>
<div class="barleft">
<div class="barright">
<p><b>Report: <?php echo $type_list[$this->type];?></b>
    <span>
    <a target='_blank' href="<?php echo $url.'/format/pdf'; ?>"><img src="/images/pdf.gif" border="0"></a>
    <a href="<?php echo $url.'/format/xls'; ?>"><img src="/images/xls.gif" border="0"></a>
    </span>
</div>
</div>
<?php } ?>
