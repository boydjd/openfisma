<div class="barleft">
    <div class="barright">
        <p><b>General Policies</b> 
    </div>
</div>

<div class="block"> 
    <?php echo $this->generalform->setAttrib('class','cform'); ?> 
</div>


<?php  if('ldap' == readSysConfig('auth_type',true)){ ?>

<div class="barleft">
<div class="barright">
<p><b>LDAP Configurations</b>&nbsp;[<a href="/panel/config/sub/ldapupdate/">New</a>]</p>
</div>
</div>


<div class="block">
<table width="100%" class="tbframe">
<tr>
    <th>LDAP Connection</th>
    <th>Domain Name</th>
    <th>use ssl</th>
    <th>Edit</th>
    <th>Del</th>
</tr>
<?php 
function makeLdapUrl($value)
{
    $url = 'LDAP://';
    if (!empty($value['username'])) {
        $url .= $value['username'];
        if (!empty($value['password'])) {
            $url .= ':' . $value['password'];
        }
        $url .= '@';
    }
    $url .= $value['host'];

    if (!empty($value['port'])) {
        $url .= ':' .$value['port'];
    }
    return $url;
}

foreach ($this->ldaps as $opt) { 
    echo '<tr>
           <td class="tdc">', makeLdapUrl($opt), '</td>';
    echo '<td class="tdc">', $opt['domain_name'], '</td>';
    echo '<td class="tdc">', $opt['use_ssl']?'yes':'no', '</td>';
    echo '<td class="tdc" >
        <a href="/panel/config/sub/ldapupdate/id/'.$opt['id'].'" title="edit the LDAP configuration">
        <img src="/images/edit.png" border="0"></a>
    </td>';
    echo '<td class="tdc">
        <a href="/panel/config/sub/ldapdel/id/' .$opt['id']. '" class="confirm";">
        <img src="/images/del.png" border="0"></a>
    </td>';
    echo '</tr>';
}

?>
</table>
</div>


<?php } ?>

