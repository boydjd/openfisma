<table  width="100%" align="center" cellspacing="0" cellpadding="0" background="/images/install/bg_table.gif">
    <tr>
        <td colspan="2"><div class="installer">
                <h4>Software Requirement Check</h4>
                <ul class="nolist">
                    <li class="<?php echo $this->checklist['version'];?>"> Currently
                        PHP version is <?php echo phpversion(); ?></li>
                </ul>
            </div></td>
    </tr>
    <tr>
        <td width='50%'><div class="back"><a class="button" href="<?php echo $this->back; ?>" >Back</a></div></td>
        <td width='50%'><div class="next"><a class="button" href="<?php echo $this->next; ?>" >Next</a></div></td>
    </tr>
</table>
