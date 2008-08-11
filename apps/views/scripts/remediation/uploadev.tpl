<div id="editorDIV" style="display: none;">
    <form enctype="multipart/form-data" method="POST"
         action="/panel/remediation/sub/upload_evidence/id/<?php echo $this->id;?>">
         <b>Select File:&nbsp;</b> <input type='file' name='evidence' size='40' value=''>
         <input type="submit" id="#upload_ev" value="Upload">
    </form>
    <ul style="margin-top:2em;list-style-type:disc">
        <li>Please submit <b>all evidence</b> for the finding in a <b>single package</b> (eg, zip file)</li>
        <li>Evidence submissions must be <b>under 10 megabytes</b> in size</li>
        <li>Please ensure no <b>Personally Identifiable Information</b> is included (eg, SSN, DOB)</li>
    </ul>
</div>
