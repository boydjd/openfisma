<table  width="778" align="center" cellspacing="0" cellpadding="0" background="img/bg_table.gif">
  <tr>
    <td width='5%'>
       &nbsp; 
    </td>
    <td colspan="3">
      <h4 style="margin-top: 10px; margin-bottom: 5px; padding: 10px;">
        Checking file and directory permissions..
      </h4>
      <table align='center' border="0">
        <tr>
          <td align='left'>
            <ul class="nolist">
              <?php
                        foreach($this->writables as $f) {
                            echo '<li class="ok">', $f ,' is writable</li>';
                        }
                        foreach($this->notwritables as $f) {
                            echo '<li class="failure">', $f ,' is not writable</li>';
                        }
              ?>
            </ul>
        
        </tr>
      </table>
      <div style="text-align:right;">
      </div>
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
    </td>
    <td width='20%' align='center'>
    </td>
    <td width='35%' align='right'>
      <span style='font-size:85%;'>General settings >></span>
        <?php if( empty( $this->next ) ) {
            echo '<button onclick="javascript:location.reload();">Refresh</button>';
        }else {
            echo "<a class='button' href=".$this->next." >Next</a>";
        }
      ?>
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
