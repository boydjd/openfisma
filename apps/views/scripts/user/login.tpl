<p class="message">   
<?php 
    if(isset($this->error) ) {
        echo $this->error; 
    }
?>
</p>
<form method="post" action="/user/login">
    <ul id="login">
	<li>Username: <input type="text" name="username" value="" ></li>
	<li>Password: <input type="password" name="userpass" value=""></li>
	<li class='submit'><input type="submit" value="Login" ></li>
    </ul>
</form>
<p id='warning'>
<?php echo nl2br(readSysConfig('use_notification'));?>
</p>


