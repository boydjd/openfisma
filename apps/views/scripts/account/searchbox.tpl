<div class="barleft">
<div class="barright">
<p><b>User Account Administration</b>
</div>
</div>
<table class="tbframe" align="center"  width="98%">
    <tbody>
        <tr>
            <th>[<a id="user_list" href="<?php echo burl()?>/panel/account/sub/list">User List</a>] (total: <?php echo $this->total;?>)</th>
            <th>[<a id="add_user" href="<?php echo burl()?>/panel/account/sub/create" title="add new Users">Add User</a>]</th>
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
                <form name="query" method="post" action="<?php echo burl()?>/panel/account/sub/list">
                    <tbody>
                        <tr>
                            <td><b>Query:&nbsp;</b></td>
                            <td><select name="fid">
                            <?php foreach($this->fid_array as $k=>$v){
                                if($k == $this->fid){
                                    $selected = " selected";
                                }
                                else {
                                    $selected = "";
                                }
                                echo'<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
                            }
                            ?>
                            </select></td>
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
