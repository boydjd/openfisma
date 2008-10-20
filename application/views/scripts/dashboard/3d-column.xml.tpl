  <chart>
	<chart_data>
		<row>
			<null/>
			<string></string>
		</row>	
		<row>
			<string>NEW</string>
			<number><?php echo $this->summary['NEW']; ?></number>
		</row>
		<row>
			<string>OT</string>
			<number><?php echo $this->summary['OPEN']+$this->summary['EN']-$this->summary['EO'];?></number>
		</row>
		<row>
			<string>OD</string>
			<number><?php echo $this->summary['EO'];?></number>
		</row>
		<row>
			<string>EP</string>
			<number><?php echo $this->summary['EP'];?></number>
		</row>
		<row>
			<string>ES</string>
			<number><?php echo $this->summary['ES'];?></number>
		</row>
		<row>
			<string>OC</string>
			<number><?php echo $this->summary['CLOSED'];?></number>
		</row>

	</chart_data>

	<chart_type><?php echo $this->chart_type; ?></chart_type>

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
</chart>
