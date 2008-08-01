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
                       href="<?php echo burl()?>/metainfo/list/o/type/format/html/">
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

            <div style="width:95%;margin:0 5px" class="selectdate" url="<?php echo burl(); ?>">
            <b target="est_date" <?php
        if(in_array($this->poam['status'],array('OPEN','NEW')) && 
            isAllow('remediation','update_est_completion_date')){
            echo ' class="editable" ';
        }?> >Estimated Completion Date:&nbsp;</b>
            <span name="poam[action_est_date]" id="est_date" class="date" type="text">
            <?php echo nullGet($this->poam['action_est_date'],'0000-00-00'); ?>
            </span>&nbsp;&nbsp;&nbsp;
            <b>Actual Completion Date:&nbsp;</b>
            <?php echo nullGet($this->poam['action_actual_date'],'<i>(action not yet completed)</i>');?>
            </div>
