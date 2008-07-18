<!-- Heading Block -->
<div class="barleft">
    <div class="barright">
        <p><b>Finding Description</b>
    </div>
</div>

<!-- FINDING DETAIL TABLE -->
<table align="center" class="tipframe">
    <!-- finding and asset row -->
    <tr>
        <!-- finding information -->
        <td width="50%" valign="top"><!-- FINDING TABLE -->
            <table cellpadding="5" width="100%" class="tbframe">
                <th align="left" colspan="2">Finding Information</th>
                <tr>
                    <td><b>POAM ID:&nbsp;</b><?php echo $this->poam['id'];?></td>
                </tr>
                <tr>
                    <td><b>Date Opened:&nbsp;</b><?php echo $this->poam['create_ts'];?></td>
                </tr>
                <tr>
                    <td><b>Source:&nbsp;</b> (<?php echo $this->poam['source_nickname'];?>) <?php echo $this->poam['source_name'];?></td>
                </tr>
                <tr>
                    <td><b>Status:&nbsp;</b>
                        <?php 
                    $st = $this->poam['status'];
                    if( 'EN' == $st ) {
                        $date = new Zend_Date($this->poam['action_est_date']);
                        if( $date->isEarlier(Zend_Date::now()) ){
                            $st = 'EO';
                        }
                    }
                    echo $st;
                ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b target="action_owner" <?php
        if(in_array( $this->poam['status'],array('NEW','OPEN'))
            && isAllow('remediation','update_finding_assignment')){
            echo ' class="editable"';
     }?> >Responsible System:&nbsp;</b>
                        <span name="poam[system_id]" id="action_owner" type="select" 
                   href="/zfentry.php/metainfo/list/o/system/format/html/">
    <?php echo $this->system_list[$this->poam['system_id']]; ?>
                        </span> </td>
                </tr>
            </table>
            <!-- FINDING TABLE -->
        </td>
        <!-- asset information -->
        <td width="50%" valign="top"><!-- ASSET TABLE -->
            <table cellpadding="5" width="100%" class="tbframe">
                <th align="left" colspan="2">Asset Information</th>
                <tr>
                    <td><b>Asset Owner:&nbsp;</b> <?php echo $this->system_list[$this->poam['asset_owner']];?></td>
                </tr>
                <tr>
                    <td><b>Asset Name:&nbsp;</b> <?php echo nullGet($this->poam['asset_name'],'(none given)');?> </td>
                </tr>
                <tr>
                    <td><b>Known Address(es):&nbsp;</b> <i><?php echo $this->network_list[$this->poam['network_id']],$this->poam['ip'],':',$this->poam['port']?> </i> </td>
                </tr>
                <tr>
                    <td><b>Product Information:&nbsp;</b> <i>
                        <?php  if( !empty($this->poam['product']) ){
                            echo $this->poam['product']['prod_vendor'].
                                 $this->poam['product']['prod_name'].
                                 $this->poam['product']['prod_version'];
                        }else{
                            echo '(not given)';
                        } ?>
                        </i> </td>
                </tr>
            </table>
            <!-- END ASSET TABLE -->
        </td>
    </tr>
</table>
<table border="0" cellpadding="5" cellspacing="1" class="tipframe" >
    <th align="left">Finding Description</th>
    <tr>
        <td><i> <?php echo nullGet($this->poam['finding_data'],'(none given)'); ?></i> </td>
    </tr>
</table>
<?php if( !empty($this->poam['vuln']) ) { ?>
<table border="0" cellpadding="5" cellspacing="1" class="tipframe">
    <th align='left'>Additional Vulnerability Detail</th>
        <!-- VULNERABILITY ROW(S) -->
        <?php foreach($this->poam['vuln'] as $row){ ?>
    <tr>
        <td colspan="2"><!-- VULERABILITIES TABLE -->
            <table border="0" cellpadding="5" cellspacing="1" width="100%">
                <tr>
                    <td><b>Vulnerability ID: </b><?php echo $row['type'].'-'.$row['seq'];?></td>
                </tr>
                <tr>
                    <td><b>Description: </b><?php echo $row['description'];?></td>
                </tr>
            </table>
            <!-- END VULERABILITIES TABLE -->
        </td>
    </tr>
    <?php }?>
</table>
<?php } ?>
<table cellpadding="5" width="100%" class="tipframe">
    <th align='left' ><span target='recommendation' <?php 
                if (in_array( $this->poam['status'],array('NEW','OPEN'))
                  && isAllow('remediation','update_finding_recommendation')) {
                    echo 'class="editable"';
                }
            ?> colspan='2'>Recommendation</span></th>
    <tr>
        <td colspan='2'>
            <span name="poam[action_suggested]" id="recommendation"
                  type="textarea" rows="3" cols="160"> <?php 
                      echo nl2br($this->poam['action_suggested']);
                  ?> </span>
        </td>
    </tr>
</table>
