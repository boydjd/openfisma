This is an automatically generated message. You have chosen to receive event notifications
at this e-mail address. If you want to modify your notification preferences, please
log in to your account at http://openfisma.instance.com and select "User Preferences".

<?php
foreach( $this->notifyData as $row ){ ?>
--------------------------------------- 
Notification Type: <?php echo $row['event_name']; ?> 
Timestamp: <?php echo $row['timestamp']; ?> 
Event Text: <?php echo $row['event_text']; ?> 
<?php } ?>
---------------------------------------

