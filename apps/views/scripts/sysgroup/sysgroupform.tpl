<style type="text/css">
#sysgroupform dl {
    border:1px #44637A solid;
    padding:10px;
}
#sysgroupform dt {
    float:left;
    width:200px;
    background:#e3e3e3;
    height:20px;
    text-align:right;
    color:#44637A;
    font-weight:bold;
    line-height:20px;
    vertical-align:middle;
}
#sysgroupform span {
    float:left;
    padding-right:10px;
}
.errors {
    color:red;
}
</style>
<div class="barleft">
    <div class="barright">
    <p><b><?php echo $this->title;?> System Group Information</b>
    </div>
</div>
<div class="block">
    <font color="blue">*</font> = Required Field 
    <?php echo $this->form->setAttrib('id','sysgroupform'); ?>
</div>
