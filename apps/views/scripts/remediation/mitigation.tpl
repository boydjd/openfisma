     <div class="barleft">
     <div class="barright">
     <p><b>Mitigation Strategy</b><span></span></p>
     </div>
     </div>

            <table cellpadding="5" class="tipframe">
                <tr><th align="left">Course of Action</th></tr>
                <tr>
                    <td align="left">
                        <b target="type" <?php
        if(('NEW' == $this->poam['status'] || 'OPEN' == $this->poam['status'])&& isAllow('remediation','update_finding_course_of_action')){
            echo 'class="editable"';
        }?> >Type:&nbsp;</b>
                    <span name="poam[type]" id="type" type="select" 
                       href="/metainfo/list/o/type/format/html/">
                        <?php echo $this->poam['type']; ?>
                    </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b target="action_planned" <?php 
           if(in_array($this->poam['status'],array('NEW','OPEN'))
                && isAllow('remediation','update_finding_course_of_action')){
               echo 'class="editable"';
           }?> >Description:&nbsp;</b>
                        <span name="poam[action_planned]" id="action_planned" 
                         type="textarea" rows="5" cols="160" >
       <?php echo nl2br($this->poam['action_planned']); ?>           
                        </span>
                    </td>
                </tr>
            </table>
            <!-- End Course of Action Table -->

            <!-- Resources Required for Course of Action Table -->
            <table width="100%" cellpadding="5" class="tipframe">
                <th align="left">
                <span target="action_resources" <?php
        if(in_array($this->poam['status'],array('NEW','OPEN'))
           && isAllow('remediation','update_finding_resources')){
            echo 'class="editable"';
        } ?> >Resources Required for Course of Action</span></th>
                <tr>
                    <td>
                        <span name="poam[action_resources]" id="action_resources" type="textarea" rows="5" cols="160"> 
                        <?php echo nl2br($this->poam['action_resources']); ?> 
                        </span>
                    </td>
                </tr>
            </table>
            <!-- End Resources Required for Course of Action Table -->

            <!-- ECD Table -->
            <table cellpadding="5" class="tipframe">
                <tr><th align="left">Completion Date</th></tr>
                <?php
                    if(!empty($this->poam['action_est_date'])
                        && $this->poam['action_est_date'] != $this->poam['action_current_date']){
                ?>
                <tr><td><i>The "Original ECD" is used for FISMA reporting to OMB and may not be changed.<br>
                           The "ECD" is used for agency purposes and may be modified.</i></td>
                </tr>
                <tr>
                    <td align="left">
                        <b target="est_date">Original Expected Completion Date:&nbsp;</b>
                        <?php echo $this->poam['action_est_date']; ?>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <td>
                        <b target="action_est_date"
                        <?php 
                            if(isAllow('remediation','update_finding_course_of_action')
                                && in_array($this->poam['status'], array('NEW', 'OPEN'))){
                                echo 'class="editable"';
                            }
                        ?> >Expected Completion Date:&nbsp;</b>
                        <span name="poam[action_current_date]" id="action_est_date" class="date" type="text"> 
                        <?php echo nullGet($this->poam['action_current_date'], '0000-00-00'); ?>
                        </span>
                        <?php 
                            if (!empty($this->poam['ecd_justification'])) {
                            echo '<b>Justification:</b>&nbsp;'.$this->poam['ecd_justification'].' --<i>by '.$this->justification['name_first'].' '.$this->justification['name_last'].' ON '.$this->justification['time'].'</i>';
                            }
                            if (!empty($this->poam['action_est_date'])) { 
                        ?>
                            <div id="ecd_justification" style="display:none">
                                <i>Input ECD change justification here</i>:&nbsp;<input type="text" name="poam[ecd_justification]" value="<?php echo $this->poam['ecd_justification'];?>" size="100px">
                            </div>
                            <span name="poam[ecd_justification]" id="ecd_justification" type="text" size="100px"> 
                            </span>
                        <?php } ?>

                    </td>
                </tr>
                <tr>
                    <td><b>Actual Completion Date:&nbsp;</b>
                    <?php echo nullGet($this->poam['action_actual_date'],'<i>(action not yet completed)</i>');?>
                    </td>
                </tr>
            </table>
            <!-- End ECD Table -->
