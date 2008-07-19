<table  width="778" align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
  <tr>
    <td width='5%'>
       &nbsp; 
    </td>
    <td colspan="3">
      <h4 style="margin-top: 10px; margin-bottom: 5px; padding: 10px;">
         Initial Database 
      </h4>
      <ul class="nolist">
        <li class="<?php echo $this->checklist['is_connect']?>">
           Create(Connect) database <?php echo $this->dbname 
          ?>
        </li>
        <li class="<?php echo $this->checklist['is_grant']?>">
           Create account <?php echo $this->uname 
          ?>
           and grant all privilege of <?php echo $this->dbname 
          ?>
           to it 
        </li>
        <li class="<?php echo $this->checklist['is_create_table']?>">
           Create tables and init
        </li>
        <li class="<?php echo $this->checklist['is_write_config']?>">
           Create config file
        </li>
      </ul>
    </td>
    <td width='5%'>
       &nbsp; 
    </td>
  </tr>
  <tr>
    <td width='5%'>
       &nbsp; 
    </td>
    <td width='35%' align='left'>
      <a class="button" href="<?php echo $this->back ; ?>">Back</a>
    </td>
    <td width='20%' align='center'>
    </td>
    <td width='35%' align='right'>
      <span style='font-size:85%;'>Now,please directly go to index page! >></span><input type='hidden' name='op' value='complete' /><a class="button" href="<?php echo $this->next ; ?>">Next</a>
    </td>
    <td width='5%'>
       &nbsp; 
    </td>
  </tr>
  <tr>
    <td colspan="5">
       &nbsp; 
    </td>
  </tr>
</table>
