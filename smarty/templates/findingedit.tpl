<!-- HEADER TEMPLATE INCLUDE -->
{include file="header.tpl" title="$pageTitle" name="$pageName"} 
<!-- END HEADER TEMPLATE INCLUDE --> 

{literal}
<script language="javascript" src="javascripts/ajax.js"></script>
<script language="javascript" src="javascripts/func.js"></script>
<script language="javascript" src="javascripts/form.js"></script>
{/literal}

<!-- Heading Block -->
<table class="tbline">              
<tr><td id="tbheading">Finding Creation</td><td id="tbtime">{$now}</td></tr>        
</table>
<!-- End Heading Block -->

<br>

{if $act eq 'new'}
{if $msg ne ""}
<p><b><u>{$msg}</u></b></p>
{/if}

<form name="finding" method="post" action="findingdetail.php" onsubmit="return qok();">
<input type="hidden" name="act"           value="{$act}">
<input type="hidden" name="do"            value="create">
<input type="hidden" name="vuln_offset"   value="0">
<input type="hidden" name="NUM_VULN_ROWS" value="50">

    	<table border="0" align="center" cellpadding="5">
			<tr>
				<td>
                    <input name="button" type="submit" id="button" value="Create Finding" style="cursor:hand;">
                    <input name="button" type="reset" id="button" value="Reset Form" style="cursor:hand;">
                </td>
			</tr>
            
            <tr>
				<td>
					<!-- Begin General Information Table -->
					<table border="0" width="800" cellpadding="5" class="tipframe">
						<tr>
            				<th align="left">General Information</th>
						</tr>
            			<tr>
							<td>
								
								<table border="0" cellpadding="1" cellspacing="1">
									<tr>
										<td align="right"><b>Discovered Date:</b></td>
										<td>
											<!-- Begin Date Discovered Table: Date Input and Date Select Image -->
											<table border="0" cellpadding="0" cellspacing="0">
												<tr>
													<td><input type="text" name="discovereddate" size="12" maxlength="10" value="{$discovered_date}">&nbsp;</td>
													<td><span onclick="javascript:show_calendar('finding.discovereddate');"><img src="images/picker.gif" width=24 height=22 border=0></span></td>
												</tr>
											</table>
                                            <!-- End Date Discovered Table: Date Input and Date Select Image -->
										</td>
									</tr>
									<tr>
										<td align="right"><b>Finding Source:</b></td>
										<td>
                                        	<select name="source">
											{foreach from=$source_list key=sid item=sname}
											<option value="{$sid}">{$sname}</option>
											{/foreach}
											</select>
 										</td>
									</tr>
								</table>
							
                            </td>
						</tr>
            			<tr>
            				<td><b>Enter Description of Finding:<b><br>
								<textarea name="finding_data" cols="60" rows="5" style="border:1px solid #44637A; width:100%; height:70px;"></textarea>
                			</td>
            			</tr>
					</table>
                    <!-- End General Information Table -->
        		</td>
 			</tr>
			<tr>
				<td>
					<!-- Asset Information Table -->
					<table border="0" width="800" cellpadding="5" class="tipframe">
						<th align="left" colspan="2">Asset Information</th>
                        <tr>
							<td colspan="2">
								<!-- System Name and Asset Search Table -->
                            	<table border="0" cellpadding="5">
									<tr>
										<td><b>System:<b></td>
										<td>
                                        	<select name="system" onchange="return loadAssetList('ajaxsearch.php');">
											<option value="">--Any--</option>
											{foreach from=$system_list key=sid item=sname}
											{if $sid eq $system }
											<option value="{$sid}" selected>{$sname}</option>
											{else}
											<option value="{$sid}">{$sname}</option>
											{/if}
											{/foreach}
											</select>&nbsp;
                                     	</td>
										<td><b>Asset Name:<b></td>
										<td>
                                        	<input type="text" name="asset_needle" value="" maxlength="10" size="10">&nbsp;
                                     	</td>
										<td>
                                            <input name="button" type="button" id="button" value="Search Assets" onClick="return loadAssetList('ajaxsearch.php');" style="cursor:hand;">
                                            <input name="button" type="button" id="button" value="Create Asset" onClick="window.location='asset.create.php'"style="cursor:hand;">
                                       	</td>
									</tr>
								</table>
                                <!-- End System Name and Asset Search Table -->
							</td>
						</tr>
						<tr>
							<td width="200" align="center">
            					<select name="asset_list" size="10" style="width: 190px;" onchange="loadAsset('ajaxsearch.php');">
								<option value="">--None--</option>
								{foreach from=$asset_list key=sid item=sname}
								<option value="{$sid}">{$sname}</option>
								{/foreach}
								</select>
                         	</td>
							<td width="600" align="center" valign="top">
								<fieldset style="height:115; border:1px solid #44637A; padding:5">
								<legend><b>Asset Information</b></legend>
								<div id="assetarea"></div>
								</fieldset>
							</td>
						</tr>
					</table>
                    <!-- Asset Information Table -->
				</td>
			</tr>
		</table>

</form>

{else}
<p>{$noright}</p>
{/if}

{include file="footer.tpl"}

<script>
var theFloaters = new floaters();
//alert(document.body.Width);
theFloaters.addItem('tip','document.body.clientWidth','0','',0);
theFloaters.play();

//loadAsset('ajaxsearch.php');
</script>
