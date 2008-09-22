<?php if ('update' == $this->actionType) {?>
This is an automatically generated message.You have changed your email address,please click this link to validate it,or you will not receive notices from System.
<?php } if ('create' == $this->actionType) {?>
This is an automatically generated message.You have created with this email addres,please click this link to validate,or you will not receive notices from System.
<?php } ?>

Please click: <?php echo $_SERVER['HTTP_HOST'];?>/user/emailvalidate/id/<?php echo $this->userId;?>/code/<?php echo $this->validateCode;?> to check it.
