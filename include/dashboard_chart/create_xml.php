<?PHP

require_once("PoamSummary.class.php");


function generate_summary($u = NULL, $s = NULL) {

  // clear our counters
  $total_open   = 0;
  $total_en     = 0;
  $total_eo     = 0;
  $total_ep     = 0;
  $total_es     = 0;
  $total_closed = 0;

  $total_none   = 0;
  $total_cap    = 0;
  $total_fp     = 0;
  $total_ar     = 0;

  $total_items  = 0;

  // get the user's systems
  $systems = $u->getSystemIdsByRole();

  // loop through the systems
  if(is_array($systems)){
      while ($system = array_pop($systems)) {
    
    	// set the new system
    	$s->setSystemId($system);
    
    	// count by type
    	$total_none += $s->poamCount('NONE', NULL);
    	$total_cap  += $s->poamCount('CAP',  NULL);
    	$total_fp   += $s->poamCount('FP',   NULL);
    	$total_ar   += $s->poamCount('AR',   NULL);
    
    	// count by status
    	$total_open   += $s->poamCount(NULL, 'OPEN');
    	$total_en     += $s->poamCount(NULL, 'EN');
    	$total_eo     += $s->poamCount(NULL, 'EO');
    	$total_ep     += $s->poamCount(NULL, 'EP');
    	$total_es     += $s->poamCount(NULL, 'ES');
    	$total_closed += $s->poamCount(NULL, 'CLOSED');
    
      } // while systems
  }
  // grab the total
  $total_items = $total_none + $total_cap + $total_fp + $total_ar;

  // return our summary
  return Array('total_items'  => $total_items,
			   'total_none'   => $total_none, 
			   'total_cap'    => $total_cap, 
			   'total_fp'     => $total_fp, 
			   'total_ar'     => $total_ar,
			   'total_open'   => $total_open, 
			   'total_en'     => $total_en, 
			   'total_eo'     => $total_eo, 
			   'total_ep'     => $total_ep, 
			   'total_es'     => $total_es, 
			   'total_closed' => $total_closed
			   );

} // generate_summary()


function create_xml_1($open = 0, $ot = 0, $od = 0, $ep = 0, $es = 0, $closed = 0)
{	
	// grab a sum of all items passed in
	$total = $open + $ot + $od + $ep + $es + $closed;

	// Pie chart blows up if fed all zeros
	if($total == 0) { $open = 1; }

	// generate our chart content
	$rss_content = "<chart>
	<chart_data>
		<row>
			<string></string>
			<string>NEW:New Item</string>
			<string>OT:On-Time</string>
			<string>OD:OverDue</string>
			<string>EP:Evidence Provided</string>
			<string>ES:Evidence Submitted</string>			
			<string>OC:Officially Closed</string>			
		</row>
		<row>
			<string></string>
			<number>$open</number>
			<number>$ot</number>
			<number>$od</number>
			<number>$ep</number>
			<number>$es</number>
			<number>$closed</number>
		</row>
	</chart_data>

	<chart_grid_h alpha='20' color='ffffff' thickness='1' type='solid' />
	
	<chart_rect x='150'   positive_color='ffffff' positive_alpha='20' negative_color='ff0000' negative_alpha='10' />
	
	<chart_type>pie</chart_type>
	
	<chart_value color='ffffff' alpha='90' font='arial' bold='true' size='10' position='inside' prefix='' suffix='' decimals='0' separator='' as_percentage='true'   />

	<draw>
		<text color='000000' alpha='10' font='arial' rotation='0' bold='false' size='30' x='0' y='140' width='300' height='150' h_align='center' v_align='bottom'>|||||||||||||||||||||||||||||||||||||||||||||||</text>
	</draw>

	<legend_label layout='vertical' bullet='circle' font='arial' bold='0' size='9' color='000000' alpha='100' />
	<legend_rect fill_color='ffffff' fill_alpha='10' line_color='000000' line_alpha='0' line_thickness='0' />

	<series_color>
		<color>ddaa41</color>
		<color>88dd11</color>
		<color>4e62dd</color>
		<color>ff8811</color>
		<color>4d4d4d</color>
		<color>5a4b6e</color>
	</series_color>
	<series_explode>
		<number>0</number>
		<number>0</number>
		<number>0</number>
	</series_explode>

    </chart>";
	

	// write to the file
	$handle = fopen("temp/dashboard1.xml", "w+");
	if (fwrite($handle, $rss_content) === FALSE) {
		echo "Cannot write to file ($filename)";
		exit;
	}

} // create_xml_1


function create_xml_2($open = 0, $ot = 0, $od = 0, $ep = 0, $es = 0, $closed = 0) { 

  // get a sum of all findings
  $total = $open + $ot + $od + $ep + $es + $closed ;

  // Pie chart blows up if fed all zeros
  if($total == 0) { $open = 1; }

  // generate the content
  $rss_content = "<chart>
	<chart_data>
		<row>
			<null/>
			<string></string>
		</row>	
		<row>
			<string>NEW</string>
			<number>$open</number>
		</row>
		<row>
			<string>OT</string>
			<number>$ot</number>
		</row>
		<row>
			<string>OD</string>
			<number>$od</number>
		</row>
		<row>
			<string>EP</string>
			<number>$ep</number>
		</row>
		<row>
			<string>ES</string>
			<number>$es</number>
		</row>
		<row>
			<string>OC</string>
			<number>$closed</number>
		</row>

	</chart_data>

	<chart_type>3d column</chart_type>

	<chart_grid_h alpha='10' color='ffffff' thickness='0' type='solid' />
	<chart_pref rotation_x='0' rotation_y='0'  /> 
	<chart_rect  width='150' positive_color='ffffff' positive_alpha='10' negative_color='ff0000' negative_alpha='10' />
	<chart_value color='ffffff' alpha='90' font='arial' bold='true' size='10' position='over' prefix='' suffix='' decimals='0' separator='' as_percentage='true'   />

	
	<draw>
		<text color='000000' alpha='10' font='arial' rotation='45' bold='true' size='30' x='0' y='140' width='400' height='150' h_align='center' v_align='bottom'></text>
	</draw>
	<legend_label layout='horizontal' bullet='circle' font='arial' bold='true' size='9' color='000000' alpha='100' />
	<legend_rect y='190' fill_color='ffffff' fill_alpha='10' line_color='000000' line_alpha='0' line_thickness='0' />

	<series_color>
		<color>ddaa41</color>
		<color>88dd11</color>
		<color>4e62dd</color>
		<color>ff8811</color>
		<color>4d4d4d</color>
		<color>5a4b6e</color>
	</series_color>
</chart>		";
	
  // write to the file
  $handle = fopen("temp/dashboard2.xml", "w+");
  if (fwrite($handle, $rss_content) === FALSE) {
	echo "Cannot write to file ($filename)";
	exit;
  }

} // create_xml_2();



function create_xml_3($none = 0, $cap = 0, $fp = 0, $ar = 0) 
{ 

  // Pie chart blows up if fed all zeros
  $total = $none + $cap + $fp + $ar;
  if($total == 0) { $v_none = 1;  }

  // generate the xml
  $rss_content = "<chart>
	<chart_data>
		<row>
			<null/>
			<string>NONE</string>
			<string>CAP:Corrective Action Plan</string>
			<string>FP:False Positive</string>
			<string>AR:Accept Risk</string>
		</row>
		<row>
			<string></string>
			<number>$none</number>
			<number>$cap</number>
			<number>$fp</number>
			<number>$ar</number>
		</row>
	</chart_data>

	<chart_grid_h alpha='20' color='ffffff' thickness='1' type='solid' />
	
	<chart_rect x='150'    positive_color='ffffff'  positive_alpha='20' negative_color='ff0000' negative_alpha='10' />
	
	<chart_type>pie</chart_type>
	
	<chart_value color='ffffff' alpha='90' font='arial' bold='true' size='10' position='inside' prefix='' suffix='' decimals='0' separator='' as_percentage='true'   />

	<draw>
		<text color='000000' alpha='10' font='arial' rotation='0' bold='true' size='30' x='0' y='140' width='300' height='150' h_align='center' v_align='bottom'>|||||||||||||||||||||||||||||||||||||||||||||||</text>
	</draw>


	<legend_label layout='vertical' bullet='circle' font='arial' bold='true' size='9' color='000000' alpha='100' />

	<legend_rect fill_color='ffffff' fill_alpha='10' line_color='000000' line_alpha='0' line_thickness='0' />

	<series_color>
		<color>ddaa41</color>
		<color>88dd11</color>
		<color>4e62dd</color>
		<color>ff8811</color>
	</series_color>

</chart>		";
	
	
  $handle = fopen("temp/dashboard3.xml", "w+");
  if (fwrite($handle, $rss_content) === FALSE) {
	echo "Cannot write to file ($filename)";
	exit;
  }
  
} //create_xml_3();

?>
