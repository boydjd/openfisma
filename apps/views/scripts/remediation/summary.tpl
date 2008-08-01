<div class="barleft">
<div class="barright">
<p><b>Remediation Summary</b></p>
</div>
</div>
<table align="center" class="tbframe">
    <tr align="center">
        <th>Action Owner</th>
        <th>New</th>
        <th>Open</th>
        <th>EN</th>
        <th>EO</th>
        <!--th>EP</th-->
        <th>EP (SSO)</th>
        <th>EP (S&P)</th>
        <th>ES</th>
        <th>CLOSED</th>
        <th>Total</th>
    </tr>

    <!-- SUMMARY LOOP -->
    <?php 
    define('BASE', burl()."/panel/remediation/sub/searchbox/s/search/system_id/");
    foreach($this->summary as $sid=>$row){
        $base_url = BASE . $sid;
    ?>
    <tr>
        <td width='45%' align='left'   class='tdc'>
             <?php echo $this->systems[$sid];?></td>
        <td align='center' class='tdc'><?php echo $row['NEW'] == ''?'-':'<a href="'.$base_url.'/status/NEW">'.$row['NEW'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['OPEN']== ''?'-':'<a href="'.$base_url.'/status/OPEN">'.$row['OPEN'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['EN']== ''?'-':'<a href="'.$base_url.'/status/EN">'.$row['EN'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['EO']== ''?'-':'<a href="'.$base_url.'/status/EO">'.$row['EO'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['EP_SSO']==''?'-':'<a href="'.$base_url.'/status/EP-SSO">'.$row['EP_SSO'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['EP_SNP']==''?'-':'<a href="'.$base_url.'/status/EP-SNP">'.$row['EP_SNP'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['ES']==''?'-':'<a href="'.$base_url.'/status/ES">'.$row['ES'].'</a>';?></td>
        <td align='center' class='tdc'><?php echo $row['CLOSED']==''?'-':'<a href="'.$base_url.'/status/CLOSED">'.$row['CLOSED'].'</a>';?></td>       
        <td align='center' class='tdc'><b><?php echo $row['TOTAL']==''?'0':'<a href="'.$base_url.'">'.$row['TOTAL'].'</a>';?></b></td>
    </tr>
    <?php }?>
    <tr>
        <td width='45%' align='center' class='tdc'><b>TOTALS</b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['NEW']==''?'0':'<a 
href="' . burl() . '/panel/remediation/sub/searchbox/s/search/status/NEW">'.$this->total['NEW'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['OPEN']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/OPEN">'.$this->total['OPEN'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['EN']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/EN">'.$this->total['EN'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['EO']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/EO">'.$this->total['EO'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['EP_SSO']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/EP-SSO">'.$this->total['EP_SSO'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['EP_SNP']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/EP-SNP">'.$this->total['EP_SNP'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['ES']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/ES">'.$this->total['ES'].'</a>';?></b></td>
        <td class='tdc'align='center'><b><?php echo $this->total['CLOSED']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search/status/CLOSED">'.$this->total['CLOSED'].'</a>';?></b></td>     
        <td class='tdc'align='center'><b><?php echo $this->total['TOTAL']==''?'0':'<a 
href="'.burl().'/panel/remediation/sub/searchbox/s/search">'.$this->total['TOTAL'].'</a>';?></b></td>
    </tr>
</table>
<br>
