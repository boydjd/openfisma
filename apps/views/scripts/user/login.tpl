<p class="message">   
<?php 
    if(isset($this->error) ) {
        echo $this->error; 
    }
?>
</p>
<form method="post" action="/zfentry.php/user/login">
    <ul id="login">
	<li>Username: <input type="text" name="username" value="" ></li>
	<li>Password: <input type="password" name="userpass" value=""></li>
	<li class='submit'><input type="submit" value="Login" ></li>
    </ul>
</form>
<p id='warning'>
This is a United States Government Computer system. We encourage its use by authorized staff, auditors, and contractors. Activity on this system is subject to monitoring in the course of systems administration and to protect the system from unauthorized use. Users are further advised that they have no expectation of privacy while using this system or in any material on this system. Unauthorized use of this system is a violation of Federal Law and will be punished with fines or imprisonment (P.L. 99-474) Anyone using this system expressly consents to such monitoring and acknowledges that unauthorized use may be reported to the proper authorities. 
</p>


