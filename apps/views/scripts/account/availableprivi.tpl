<?php foreach($this->available_privileges as $row){ ?>
    <option value="<?php echo $row['function_id'];?>" title="<?php echo $row['function_name'];?>">
        <?php echo $row['function_name'];?>
    </option>
<?php } ?>

