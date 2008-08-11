<script LANGUAGE="JavaScript" type="test/javascript" src="/javascripts/ajax.js"></script>

<div class="barleft">
  <div class="barright">
    <p><b>Finding Creation</b></p>
  </div>
</div>

<form name="finding" method="post" action="/panel/finding/sub/create/is/new" >
<table width="810" border="0" align="center" cellpadding="0">
    <tr><td>
        <input name="button" type="submit" id="button" value="Create Finding" >
        <input name="button" type="reset" id="button" value="Reset Form" >
    </td></tr>
    <tr><td>
        <table border="0" width="100%" cellpadding="5" class="tipframe">
            <tr> <th align="left">General Information</th> </tr>
            <tr> <td>
                    <table border="0" cellpadding="1" cellspacing="1">
                        <tr>
                            <td align="right"><b>Discovered Date:&nbsp;</b></td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input type="text" class="date" name="discovereddate" size="12" maxlength="10" value="<?php echo date('Ymd');?>" url=""></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right"><b>Finding Source:&nbsp;</b></td>
                            <td>
                            <?php 
                                $this->source[0] = '--Any--';
                                echo $this->formSelect('source', 0, null, $this->source); 
                            ?>
                                
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
            <tr>
                <td><b>Enter Description of Finding:<b><br>
                    <textarea name="finding_data" cols="60" rows="5" style="border:1px solid #44637A; width:100%; height:70px;"></textarea>
                </td>
            </tr>
        </table>
            </td>
            </tr>
            <tr>
                <td>
                    <table border="0" width="100%" cellpadding="5" class="tipframe">
                        <tr><th align="left">Asset Information
                        </th>
                        <th align="right"><a id="add_asset" href="/asset/create" title="Create New Asset">Create New Asset</a>
                        </th>
                        <tr>
                            <td colspan="2">
                                <table width="100%" border="0" cellpadding="5">
                                    <tr>
                                        <td><b>System:</b></td>
                                        <td>
                            <?php 
                                $this->system[0] = '--Any--';
                                echo $this->formSelect('system', 0, 
                                array('url'=>"/asset/search"), 
                                $this->system); 
                            ?>
                                        </td>
                                        <td><b>Asset Name:</b></td>
                                        <td><input class='assets' type="text" name="name" value="<?php echo $this->param['port']; ?>" size="10" />                                          &nbsp;                                        </td>
                                      </tr>
                                    <tr>
                                                    <td><b>IP Address:</b></td>
                                                    <td><input class='assets' type="text" name="ip" value="<?php echo $this->param['ip']; ?>" maxlength="23" /></td>
                                                    <td><b>Port:<b></b></b></td>
                                                    <td><input class='assets' type="text" name="port" value="<?php echo $this->param['port']; ?>" size="10" /></td>
                                                    </tr>
                                    <tr>
                                      <td>&nbsp;</td>
                                      <td><input id="search_asset" type="button" value="Search Assets" url='/asset/search' /></td>
                                      <td colspan=2 ><input type="reset" name="button2" id="button2" value="Reset" /></td>
                                      </tr>
                                </table><hr/>
                            </td>
                        </tr>
                        <tr>
                            <td width="200" align="center"><b>Asset Name:</b><div>
                                <select id="asset_list" name="asset_list" size="8" style="width: 190px;" >
                                </select></div>                            </td>
                            <td width="600" align="center" valign="top">
                                <fieldset style="height:115; border:1px solid #44637A; padding:5">
                                <legend><b>Asset Information</b></legend>
                                <div id="asset_info"></div>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
</form>
