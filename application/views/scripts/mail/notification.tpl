This is an automatically generated message. You have chosen to receive event notifications
at this e-mail address. If you want to modify your notification preferences, please
log in to your account at <?php echo $this->hostUrl; ?> and select "User Preferences".

<?php
foreach( $this->notifyData as $row ){ ?>
---------------------------------------
Event: <?php echo "{$row['event_text']}\n"; ?>
Time:  <?php echo "{$row['timestamp']}\n"; ?>
<?php } ?>
---------------------------------------

