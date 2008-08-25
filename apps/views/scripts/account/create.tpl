<script language="javascript">
    $(function(){
        $(":button[name=select_all]").click(function(){
            $(":checkbox").attr( 'checked','checked' );
        });
        $(":button[name=select_none]").click(function(){
            $(":checkbox").attr( 'checked','' );
        });
    })
</script>
<script language="javascript" src="/javascripts/jquery/jquery.validate.js"></script>
<script language="javascript" src="/javascripts/account.validate.js"></script>
<div class="barleft">
    <div class="barright">
        <p><b>User Account Information</b>
    </div>
</div>

<div class="form_box">
<?= $this->form ?>
</div>

<br>
