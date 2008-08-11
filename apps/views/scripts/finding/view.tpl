<br>
<?php 
    if($this->msg != '') {
        echo'<p><b><u>'.$this->msg.'</u></b></p>';
    }
?>
<table align="center">
    <tr>
        <td><!-- Button Row: Status, Update Status, Convert to POAM, and Back -->
            <table>
                <tr>
                    <?php if($this->act == 'edit' && $this->finding['status'] == 'NEW') { ?>
                    <td><a style="text-decoration:none" href="/finding/edit/do/update/fid/<?php echo $this->finding['id'];?>">
                        <button>Delete</button>
                        </a> </td>
                    <?php  } else { ?>
                    <td><b>Status: </b> <?php echo $this->finding['status'];?></td>
                    <?php } 
?>
                </tr>
            </table>
            <!-- End Button Row -->
        </td>
    <tr>
        <td><!-- General and Asset Tables -->
            <table width="900">
                <tr>
                    <td valign="top"><!-- General Information Table -->
                        <table width="450" class="tipframe">
                            <tr>
                                <th align="left">General Information</th>
                            </tr>
                            <tr>
                                <td><table>
                                        <tr>
                                            <td align="right"><b>POAM ID:</b></td>
                                            <td>&nbsp;<?php echo $this->finding['id'];?></td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Finding Source:</b></td>
                                            <td>&nbsp;<?php echo $this->finding['source_name'];?></td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Date Discovered:</b></td>
                                            <td>&nbsp;<?php echo $this->finding['discover_ts'];?></td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Date Opened:</b></td>
                                            <td>&nbsp;<?php echo $this->finding['create_ts'];?></td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Date Closed:</b></td>
                                            <td>&nbsp;<?php echo $this->finding['close_ts'];?></td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Date Modified:</b></td>
                                            <td>&nbsp;<?php echo $this->finding['modify_ts'];?></td>
                                        </tr>
                                    </table></td>
                            </tr>
                        </table>
                        <!-- End General Infomation Table -->
                    </td>
                    <td valign="top"><!-- Asset Information Table -->
                        <table width="450" class="tipframe">
                            <tr>
                                <th align="left" >Asset:&nbsp;<?php echo $this->asset['asset_name'];?> </th>
                            </tr>
                            <tr>
                                <td><table>
                                        <tr>
                                            <td align="right"><b>System: </b></td>
                                            <td>&nbsp; <?php echo $this->finding['system_name'];?> </td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>IP Address: </b></td>
                                            <td>&nbsp; <?php echo $this->finding['ip'].":".$this->finding['port'];?> </td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Network: </b></td>
                                            <td>&nbsp; <?php echo nullGet($this->finding['network_name'],'');?> </td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Vendor: </b></td>
                                            <td>&nbsp; <?php echo isset($this->finding['prod_vendor'])?$this->finding['prod_vendor']:'';?> </td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Product: </b></td>
                                            <td>&nbsp; <?php echo isset($this->finding['prod_name'])?$this->finding['prod_name']:'';?> </td>
                                        </tr>
                                        <tr>
                                            <td align="right"><b>Version: </b></td>
                                            <td>&nbsp; <?php echo isset($this->finding['prod_version'])?$this->finding['prod_version']:'';?> </td>
                                        </tr>
                                    </table></td>
                            </tr>
                        </table>
                        <!-- End Asset Information Table -->
                    </td>
                </tr>
            </table>
            <!-- End General and Asset Tables -->
        </td>
    <tr>
    
    <td>
    
    <!-- Finding Description -->
    <table class="tipframe">
        <tr>
            <th align="left" colspan="3">Finding Information:
        </td>
        
        </tr>
        
        <tr>
            <td></td>
            <td width="100%"><b>Description: </b> <br>
                <table width="770"  class="tbframe" bgcolor="#eeeeee">
                    <tr>
                        <td><?php echo $this->finding['finding_data'];?>&nbsp;</td>
                    </tr>
                </table></td>
            <td></td>
        </tr>
    </table>
    <!-- End Finding Description -->
    </td>
    
    </tr>
    
    <tr>
    
    <td>
    
    <!-- Vulnerability Information -->
    <table class="tipframe">
        <tr>
            <th align="left" colspan="3">Vulnerability:
        </td>
        
        </tr>
        
        <?php if(!empty($this->finding['vuln_arr'])){
                        foreach($this->finding['vuln_arr'] as $vseq=>$vobj){ 
                    ?>
        <tr>
            <td width="10"></td>
            <td width="100%"><table border="0" width="100%" cellpadding="3" cellspacing="1">
                    <tr>
                        <td><table border="0" width="100%">
                                <tr>
                                    <td align="right" width="120"><b>Vulnerability
                                        ID: <b></td>
                                    <td><?php echo $vobj['vuln_seq'];?></td>
                                </tr>
                                <tr>
                                    <td align="right" width="120"><b>Description: <b></td>
                                    <td><table border="0" width="637" cellpadding="3" cellspacing="1" class="tbframe" bgcolor="#eeeeee">
                                            <tr>
                                                <td><?php echo $vobj['vuln_desc_primary'];
                                                            if($vobj['vuln_desc_secondary'] != '0'){
                                                                echo $vobj['vuln_desc_secondary'];
                                                        }?> </td>
                                            </tr>
                                        </table></td>
                                </tr>
                            </table></td>
                    </tr>
                    <tr>
                        <td><table border="0" width="100%">
                                <tr>
                                    <td><fieldset style="border:1px solid #44637A; padding:5">
                                        <legend><b>The vulnerability will cost the
                                        loss of</b></legend>
                                        <table border="0" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                    <?php 
                                                        if($vobj['vuln_loss_confidentiality'] == 1){
                                                            echo 'checked';
                                                        } else {
                                                            echo 'disabled';
                                                        }
                                                        echo '>';
                                                        if($vobj['vuln_loss_confidentiality'] == 0){
                                                            echo'<font color="#888888">';
                                                        }
                                                    ?> Condidentiality</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                    <?php
                                                        if($vobj['vuln_loss_security_admin'] == 1){
                                                            echo 'checked';
                                                        } else {
                                                            echo 'disabled';
                                                        }
                                                        echo '>';
                                                        if($vobj['vuln_loss_security_admin'] == 0){
                                                            echo '<font color="#888888">';
                                                        }
                                                    ?> Security Admin</td>
                                            </tr>
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                    <?php
                                                        if($vobj['vuln_loss_availability'] == 1){
                                                            echo 'checked';
                                                        }else{
                                                            echo 'disabled';
                                                        }
                                                        echo '>';
                                                        if($vobj['vuln_loss_availability'] == 0){
                                                            echo '<font color="#888888">';
                                                        }
                                                    ?> Availability</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                    <?php
                                                        if($vobj['vuln_loss_security_user'] == 1){
                                                            echo 'checked';
                                                        }else {
                                                            echo 'disabled';
                                                        }
                                                        echo '>';
                                                        if($vobj['vuln_loss_security_user'] == 0){
                                                            echo '<font color="#888888">';
                                                        }
                                                    ?> Security User</td>
                                            </tr>
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                    <?php
                                                        if($vobj['vuln_loss_integrity'] == 1){
                                                            echo 'checked';
                                                        }else {
                                                            echo 'disabled';
                                                        }
                                                        echo '>';
                                                        if($vobj['vuln_loss_integrity'] == 0){
                                                            echo '<font color="#888888">';
                                                        }
                                                    ?> Integrity</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                    <?php
                                                        if($vobj['vuln_loss_security_other'] == 1){
                                                            echo 'checked';
                                                        }else {
                                                            echo 'disabled';
                                                        }
                                                        echo '>';
                                                        if ($vobj['vuln_loss_security_other'] == 0){
                                                            echo '<font color="#888888">';
                                                        }
                                                     ?> Security Other</td>
                                            </tr>
                                        </table>
                                        </fieldset></td>
                                    <td>&nbsp;</td>
                                    <td><fieldset style="border:1px solid #44637A; padding:5">
                                        <legend><b>Type of Vulnerability</b></legend>
                                        <table border="0" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_access'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_access'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Access</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_input_buffer'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_input_buffer'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Input Buffer</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_exception'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_exception'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Exception</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_other'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_other'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Other</td>
                                            </tr>
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_input'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_input'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Input</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_race'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_race'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Race</td>
                                                <td colspan="2"><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_environment'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_environment'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Environment</td>
                                            </tr>
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_input_bound'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_input_bound'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Input Bound</td>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_design'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_design'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Design</td>
                                                <td colspan="2"><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                                <?php
                                                if($vobj['vuln_type_config'] == 1){
                                                    echo 'checked';
                                                } else {
                                                    echo 'disabled';
                                                }
                                                echo '>';
                                                if($vobj['vuln_type_config'] == 0){
                                                    echo '<font color="#888888">';
                                                }
                                                ?> Config</td>
                                            </tr>
                                        </table>
                                        </fieldset></td>
                                    <td>&nbsp;</td>
                                    <td><fieldset style="border:1px solid #44637A; padding:5">
                                        <legend><b>Vulnerability Range</b></legend>
                                        <table border="0" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                            <?php
                                            if($vobj['vuln_range_local'] == 1){
                                                echo 'checked';
                                            } else {
                                                echo 'disabled';
                                            }
                                            echo '>';
                                            if($vobj['vuln_range_local'] == 0){
                                                echo '<font color="#888888">';
                                            }
                                            ?> Local</td>
                                            </tr>
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                            <?php
                                            if($vobj['vuln_range_remote'] == 1){
                                                echo 'checked';
                                            } else {
                                                echo 'disabled';
                                            }
                                            echo '>';
                                            if($vobj['vuln_range_remote'] == 0){
                                                echo '<font color="#888888">';
                                            }
                                            ?> Remote</td>
                                            </tr>
                                            <tr>
                                                <td><input type="checkbox" name="vuln" value="1" onclick="return false;"
                                            <?php
                                            if($vobj['vuln_range_user'] == 1){
                                                echo 'checked';
                                            } else {
                                                echo 'disabled';
                                            }
                                            echo '>';
                                            if($vobj['vuln_range_user'] == 0){
                                                echo '<font color="#888888">';
                                            }
                                            ?> User</td>
                                            </tr>
                                        </table>
                                        </fieldset></td>
                                </tr>
                            </table></td>
                    </tr>
                </table></td>
            <td width="10"></td>
        </tr>
        <?php
        } 
    }
    ?>
    </table>
    <!-- End Vulnerability Information -->
    </td>
    
    </tr>
    
</table>
