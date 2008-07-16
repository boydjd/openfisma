	<chart>
	<chart_data>
		<row>
            <null />
			<string>NEW:New Item</string>
			<string>OT:On-Time</string>
			<string>OD:OverDue</string>
			<string>EP:Evidence Provided</string>
			<string>ES:Evidence Submitted</string>			
			<string>OC:Officially Closed</string>			
		</row>
		<row>
            <string>count</string>
			<number><?php echo $this->summary['NEW']; ?></number>
			<number><?php echo $this->summary['OPEN']+$this->summary['EN']-$this->summary['EO'];?></number>
			<number><?php echo $this->summary['EO']; ?></number>
			<number><?php echo $this->summary['EP']; ?></number>
			<number><?php echo $this->summary['ES']; ?></number>
			<number><?php echo $this->summary['CLOSED']; ?></number>
		</row>
	</chart_data>

	<chart_grid_h alpha='20' color='ffffff' thickness='1' type='solid' />
	
	<chart_rect x='150'   positive_color='ffffff' positive_alpha='20' negative_color='ff0000' negative_alpha='10' />
	
	<chart_type><?php
        if( array_sum($this->summary) == 0) {
            echo 'bar';
        }else{
            echo 'pie';
        }
    ?></chart_type>
	
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
		<number>1</number>
		<number>0</number>
		<number>0</number>
		<number>0</number>
		<number>0</number>
		<number>0</number>
	</series_explode>
    </chart>

