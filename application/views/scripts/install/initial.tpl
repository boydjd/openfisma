<table width="100%" align="center" cellspacing="0" cellpadding="0">
    <tr>
        <td colspan="2"><div class="installer">
                <h4> Initial Database </h4>
                <ul class="nolist">
                    <li class="<?php echo ($this->checklist['connection']=='ok' || $this->checklist['creation']=='ok') ? 'ok' : 'failure'; ?>"> 
                        Database <?php echo $this->dbname; ?>'s <?php echo $this->method; ?> 
                    </li>
                    <?php if( $this->dsn['name_c'] != $this->dsn['uname'] ) { ?>
                    <li class="<?php echo $this->checklist['grant']?>"> 
                        Create account <?php echo $this->dsn['name_c']; ?> and grant all privilege
                        of <?php echo $this->dsn['dbname'];?> to it </li>
                    <?php } ?>
                    <li class="<?php echo $this->checklist['schema'];?>"> Creating
                        tables and populating initial data</li> 
                    <li class="<?php echo $this->checklist['savingconfig'];?>"> Creating
                        config file </li>
                </ul>
                <?php if (!empty($this->message)) { ?>
                <div class="errorbox"><?php echo $this->message ; ?></div>
                <?php } ?>
            </div>
        </td>
    </tr>
    <tr>
        <td width='50%' ><div class="back"><a class="button" href="<?php echo $this->back ; ?>">Back</a></div></td>
        <td width='50%' ><div class="next"><a class="button" href="<?php echo $this->next ; ?>">Next</a></div></td>
    </tr>
</table>
