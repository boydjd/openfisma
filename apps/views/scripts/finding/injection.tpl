<div class="barleft">
<div class="barright">
<p><b>Spreadsheet Upload</b><span></p>
</div>
</div>
<br>
<table width="95%" align="center" border="0">
    <tr>
        <td>
            <table border="0" cellpadding="10" cellspacing="0" class="tbframe">
                <?php if($this->error_msg != ''){?>
                <tr>
                    <td align="center"><b>Results:</b></td>
                    <td align="left">
                      <font color="Red"><?php echo $this->error_msg;?></font>
                    </td>
                </tr>
                <?php }?>
                <tr>
                    <td align="center" NOWRAP><strong>Step 1.</strong></td>
                    <td align="left">
                        Download EXCEL template file from  
                        <a href="/zfentry.php/finding/template">here</a>.
                    </td>
                </tr>

                <tr>
                    <td align="center" NOWRAP><strong>Step 2.</strong></td>
                    <td align="left">
                        Fill the work sheet with your finding data and save it as CSV format.
                    </td>
                </tr>

                <tr>
                    <td align="center" NOWRAP><strong>Step 3.</strong></td>
                    <td>Upload the CSV file here.
                    <form action="/zfentry.php/finding/injection" method="POST" enctype="multipart/form-data">
                        <input type="file" name="csv">
                        <input type="submit"></form>
                    </td>
                </tr>
    
                <tr>
                    <td align="center" NOWRAP><strong>Step 4.</strong></td>
                    <td>
                        View the injection summary or download error log file which contains data with wrong format then go to step 1.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
