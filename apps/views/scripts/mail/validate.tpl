<?php if ('update' == $this->actionType) {?>
This is an automatically generated message, please do not reply to this address.

You recently changed your e-mail address for <?php echo readSysConfig('system_name');?>.
<?php } if ('create' == $this->actionType) {?>
This is an automatically generated message, please do not reply to this address.

You recently changed your e-mail address for <?php echo readSysConfig('system_name');?>.
<?php } ?>

Please click here to confirm this e-mail address: http://<?php echo $_SERVER['HTTP_HOST'];?>/user/emailvalidate/id/<?php echo $this->userId;?>/code/<?php echo $this->validateCode;?>
