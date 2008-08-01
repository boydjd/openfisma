<?php
$fid_array = array('name'=>'Role Name',
                   'nickname'=>'Nickname');
?>
<div class="barleft">
<div class="barright">
<p><b>Role Administration</b><span>
</div>
</div>
<table class="tbframe" align="center"  width="98%">
    <tbody>
        <tr>
            <th>[<a href="<?php echo burl()?>/panel/role/sub/list">Roles List</a>] (total: <?php echo 
$this->total;?>)</th>
            <th>[<a href="<?php echo burl()?>/panel/role/sub/create" title="Add New Role">Add 
Role</a>]</th>
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
                <form name="query" method="post" action="<?php echo burl()?>/panel/role/sub/list">
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
