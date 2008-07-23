<table width="100%" align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
    <tr>
        <td colspan="2"><div class="installer">
                <h4> Initial Database </h4>
                <ul class="nolist">
                    <li class="<?php echo $this->checklist['is_cc']?>"> <?php echo $this->method; ?>
                        database <?php echo $this->dbname; ?> </li>
                    <li class="<?php echo $this->checklist['is_grant']?>"> Create
                        account <?php echo $this->uname; ?> and grant all privilege
                        of <?php echo $this->dbname;?> to it </li>
                    <li class="<?php echo $this->checklist['is_create_table'];?>"> Creating
                        tables and populating initial data</li> 
                    <li class="<?php echo $this->checklist['is_write_config'];?>"> Creating
                        config file </li>
                </ul>
            </div>
        </td>
    </tr>
    <tr>
        <td width='50%' ><div class="back"><a class="button" href="<?php echo $this->back ; ?>">Back</a></div></td>
        <td width='50%' ><div class="next"><a class="button" href="<?php echo $this->next ; ?>">Next</a></div></td>
    </tr>
</table>
