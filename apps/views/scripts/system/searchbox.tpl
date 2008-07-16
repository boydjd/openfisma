<?php
$fid_array = array('name'=>'System Name',
                   'nickname'=>'NickName',
                   'primary_office'=>'Primary Office',
                   'confidentiality'=>'Confidentiality',
                   'integrity'=>'Integrity',
                   'availability'=>'Avalilability',
                   'type'=>'type');
?>

<div class="barleft">
<div class="barright">
<p><b>System Administration</b>
</div>
</div>

<table class="tbframe" align="center"  width="98%">
    <tbody>
        <tr>
            <th>[<a id="system_list" href="/zfentry.php/panel/system/sub/list">System List</a>] (total: <?php echo $this->total;?>)</th>
            <th>[<a id="add_system" href="/zfentry.php/panel/system/sub/create" title="add new Systems">Add System</a>]</th>
            <th>
                <table align="center">
                    <tbody>
                        <tr height="22">
                            <td><b>Page:&nbsp;</b></td>
                            <td><?php echo $this->links['all'];?></td>
                            <td>|</td>
                        </tr>
                    </tbody>
                </table>
            </th>
            <th>
                <table align="center">
                <form name="query" method="post" action="/zfentry.php/panel/system/sub/list">
                    <tbody>
                        <tr>
                            <td><b>Query:&nbsp;</b></td>
                            <td><?php echo $this->formSelect('fid',nullGet($this->fid,'name'),null,$fid_array);?></td>
                            <td><input name="qv" value="<?php echo $this->qv;?>" title="Input your query value" size="10" maxlength="40" type="text"></td>

                             <td><input value="Search" title="submit your request" type="submit"></td>
                        </tr>
                    </tbody>
                </form>
                </table>
            </th>
        </tr>
    </tbody>
</table>
