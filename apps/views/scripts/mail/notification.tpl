This is an automatically generated message. You have chosen to receive event notifications
at this e-mail address. If you want to modify your notification preferences, please
log in to your account at http://<?php echo $_SERVER['SERVER_NAME']; ?> and select "User Preferences".

<?php
foreach( $this->notifyData as $row ){ ?>
--------------------------------------- 
<?php echo $row['event_text']; ?>
<?php echo $row['timestamp']; ?>
<?php } ?>
---------------------------------------

