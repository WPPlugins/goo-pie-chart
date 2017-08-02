<?php
  /**
 * Plugin Name: Goo Pie Chart
 * Plugin URI: www.gootools.net/wordpress-plugins/goo-pie-chart/
 * Description: Insert animated pie chart graph to your post or page. Boost your data visualisation.
 * Version: 1.1.5
 * Author: Aleksandar Milivojevic
 * Author URI: www.gootools.net/
 * License: GPL2
 */
 
 // ------------------ NO DIRECT ACCESS
 if ( !function_exists( 'add_action' ) ) 
     {
        echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
        die();
     }
 
  // ------------------ quick settings page    
 if ( ! function_exists( 'goopiechart_add_settings_link' ) )   
    {
         function goopiechart_add_settings_link( $links ) 
             {
                $settings_link = '<a href="admin.php?page=goo-piechart-plugin-settings">' . __( 'Settings' ) . '</a>';  
                array_unshift( $links, $settings_link );
                  return $links;
             }
        $plugin = plugin_basename( __FILE__ );
        add_filter( "plugin_action_links_$plugin", 'goopiechart_add_settings_link' );
    }

 
 
   
 function goopiechart_enqueued_assets()  
    {
        wp_enqueue_style( 'goo-style-pie-chart', plugin_dir_url( __FILE__ ).'style/goo_pie_chart.css', array() , '1.1.5' );
        wp_enqueue_script( 'goo-pie-chart-script', plugin_dir_url( __FILE__ ) . 'js/goo-pie-chart.js', array( 'jquery' ), '1.1.5', true ); 
    }
   
  

 function goo_piecharter($atts) 
    {
          if( is_singular() ) 
            {  
                $plugin = new goo_piechart_class();
                
                return $plugin->html_plugin($atts);
                
            }
          return '';
    }
 
 
 
 if ( is_admin() ) 
     {
        require_once( plugin_dir_path( __FILE__ ) . 'admin.php' );
     }
 
//------------------------------  ACTIVATION & DEACTIVATION  ----------------------------------------->>
function goo_pie_chart_activate()   
 {

    $chk_opt = get_option('goopiechart_options');  
	if ( $chk_opt === false )
		{
			$plugin = new goo_piechart_class();  
			add_option('goopiechart_options', $plugin->options);
			unset ($plugin);
		}
}
 
function goo_pie_chart_deactivate() 
{
   
   delete_option( 'goopiechart_options' );
}
 
register_activation_hook( __FILE__, 'goo_pie_chart_activate' );
register_deactivation_hook( __FILE__, 'goo_pie_chart_deactivate' );   
 

 
//---------------------------------------------------------------------------------------------------<<
//------------------------ACTIONS------------------------------->>>> 
    
 add_shortcode('goo-pie-chart', 'goo_piecharter');   
 
 add_action( 'wp_enqueue_scripts', 'goopiechart_enqueued_assets' );
 
//------------------------------------------------------------<<<<<  
   
   class goo_piechart_class
   	{
   		public $options = array();
        private $permalink_encoded;
		
		private static $graph_identifier = 0; //guarateed unique graph container indetifier for multi chart use on same page
		
		public function __construct()
            {
                $default_options = $this->goo_piechart_default_options();
                $this->options = get_option('goopiechart_options', $default_options); 
                if ( !isset($this->options['version']) || $this->options['version'] < $default_options['version'] ) { $this->options = $this->option_compatibility( $default_options, $this->options ); }   //compatibility check
                $permalink = get_permalink();
                $this->permalink_encoded = urlencode( $permalink );    
				
				self::$graph_identifier++;
				
            }
		
		public function unique_ind()  //return 	$graph_identifier
			{
				return self::$graph_identifier;
			}
			
		private function option_compatibility($default_options, $old_options)  //return options array()
            {
                //safely transit default to existing options, version differences, plain installation... 
                $new_options = $default_options;
                
				
				//preserve crucial old options
				foreach ( $old_options['plugin-options'] as $key => $value )
					{
						$new_options['plugin-options'][$key] = $value; //preserve already set
					}
				
				$new_options['product_info']['amx'] = $old_options['product_info']['amx'];
				$new_options['product_info']['checksum'] = $old_options['product_info']['checksum'];
				 
                return $new_options;
            }
			
		public function html_plugin($atts)   //return string
            {
                //plugin HTML output (via string)
                $ret_value = '';
				
				//default graph draw data
									 
				$default_arr=$this->options['plugin-options'];					
			
				//merge to workable array of options  --------------------------------------------------------------------------
				$input_arr = $default_arr; //input attributes solved
				foreach ($atts as $key => $value )
					{
						$input_arr[$key] = $value;
					}
				
				//prerequisites   ---------------------------------------------------------------------------------------------
				if ( !isset($atts['data']) ) { return ""; }  //no DATA to parse
				$temp = strpos($atts['data'], '=');
				if ( $temp === false ) { return ""; } //no at least one subpar value
				
				$temp = explode(',', $atts['data']);  
				$data_pairs = array();
				$sum = 0;  //TOTAL VALUE SUM
				foreach ( $temp as $key => $value )
					{
						$temp_val = explode('=', trim($value)); 
						if ( trim($temp_val[1]) > 0 ) //not numeric value representation :: DISCARD
							{
								$data_pairs[trim($temp_val[0])] = trim($temp_val[1]);
								$sum+=trim($temp_val[1]); 
							}
						
					}
					
				if ( count($data_pairs) == 0 )  //NO VALID PAIRS AT ALL DATA!!
					{
						 return ""; 
					}
				else
					{
						$input_arr['data_pairs'] = $data_pairs;  
					}  
					
				//calculate colors (bind to data)
				$i = 0;
				unset($input_arr['colors']);  //form new colors
				$temp_clr = ( isset($atts['colors']) ) ? explode(',', $atts['colors']) : array();
				foreach ( $input_arr['data_pairs'] as $key => $value )
					{
						if ( isset($temp_clr[$i]) )
							{
								$input_arr['colors'][$key] = $temp_clr[$i];
							}
						else
							{
								if ( isset($input_arr['force-shades']) && trim($input_arr['force-shades']) != "" ) //force shades
									{
										$shade = $this->find_shade($input_arr['force-shades'], count($input_arr['data_pairs']), $i+1); 
										if ( $shade !== false ) 
											{ $input_arr['colors'][$key] = $shade; }
											else
												{$input_arr['colors'][$key] = '#'.substr(md5($key.$value), 0, 6);} //duplicate code (prevention)
											
									}
								else
									{
										$input_arr['colors'][$key] = '#'.substr(md5($key.$value), 0, 6); //duplicate code
									}
								
							}
						$i++;  
						
						
						
					}	
					
				//all set...

				$pairs_percent = array(); //sum must be 100

				foreach ( $input_arr['data_pairs'] as $key => $value )
					{
						$pairs_percent[$key] = ($value*100)/$sum;	
					}
					
				if ( $input_arr['data-sort'] == 'on' ) //sorting array highest to lowest
					{
						arsort($pairs_percent);
					}
				$input_arr['pairs_percent'] = $pairs_percent;
				
				
				//find radius
				$input_arr['radius'] = ( $input_arr['width'] > $input_arr['height'] ) ? $input_arr['height'] / 2 : $input_arr['width'] / 2;
				$input_arr['radius'] = round($input_arr['radius']*2/3,0,PHP_ROUND_HALF_DOWN);
				
				//find X,Y pos
				$input_arr['x'] = $input_arr['width'] / 2;
				$input_arr['y'] = $input_arr['height'] / 2;
				
				//hover arc width 15% of arc
				$hover_stroke_width = round($input_arr['radius']*0.15); //TO BE ADDED(not in release ver yet)
				//CREATE CIRCLE PATHS
				$path = '';
				$index = 0;
				$angle_pos = 0;  //angle start position
				foreach ( $input_arr['pairs_percent'] as $key => $value )
					{
						$index++;	
						$angle_start = $angle_pos;
						$angle_end = $angle_start + ( 360 * $value / 100 );  // $value => percentage;
						//find X,Y slide-hover margin
						$XiYi = $this->find_XY_slide_hover_margin($angle_start, $angle_end);
						
						$data_process = round($input_arr['x'],2).', '.round($input_arr['y'],2).', '.round($input_arr['radius'],2).', '.round($angle_start,2).', '.round($angle_end,2).', '.round($XiYi['X'],2).', '.round($XiYi['Y'],2); 
						$path.='<path class="'.$input_arr['container'].'-arc" id="'.$input_arr['container'].'-arc-'.$this->unique_ind().'-'.$index.'" fill="'.$input_arr['colors'][$key].'" stroke="'.$input_arr['outline-color'].'" stroke-width="1" '
								.' data-process="'.$data_process.'" data-anim="0" data-lasttime="0"'
								.'/>'."\n";
						//$path.='<path id="'.$input_arr['container'].'-arc-'.$this->unique_ind().'-'.$index.'-hover" fill="none" stroke="#446688" stroke-width="'.$hover_stroke_width.'" style="display:none;"'
						//		.'/>'."\n";
						
						$angle_pos = $angle_end;
					}
				
				
				// create drop shadow effect-------------------------------------
				//calculate shadow dimensions based on width & height
				$input_arr['dx'] = round( ($input_arr['width']*0.05),0 );
				$input_arr['dy'] = round( ($input_arr['height']*0.05),0 );
				$input_arr['deviation'] = ( $input_arr['width'] > $input_arr['height'] ) ? round( ($input_arr['height']*0.025),0 ) : round( ($input_arr['width']*0.025),0 ) ;
				
				$drop_shadow = '<defs>'
									.'<filter id="'.$input_arr['container'].'-effect-'.$this->unique_ind().'" x="0" y="0" width="200%" height="200%" style="z-index:-2;">'
										.'<feOffset result="offOut" in="SourceGraphic" dx="'.$input_arr['dx'].'" dy="'.$input_arr['dy'].'" />'
										.'<feColorMatrix result="matrixOut" in="offOut" type="matrix" values="0.2 0 0 0 0 0 0.2 0 0 0 0 0 0.2 0 0 0 0 0 1 0" />'
										.'<feGaussianBlur result="blurOut" in="matrixOut" stdDeviation="'.$input_arr['deviation'].'" />'
										.'<feBlend in="SourceGraphic" in2="blurOut" mode="normal" />'
									.'</filter>'
								.'</defs>';
				//   <path id="arc1" fill="none" stroke="#446688" stroke-width="20" />
					
				
				//--------------------------------------     CREATE   DATA TABLE    ----------------------------------------------------------------------------
				$index = 0;
				$table = '';
				
				$top_paddig_table = round((($input_arr['height']/2) - $input_arr['radius'])/2 ); 
				
				$table = '<div class="'.$input_arr['table-container-class'].'">';
				$table.='<table style="margin-top:'.$top_paddig_table.'px;" class="'.$input_arr['table-class'].'">';
				if ( isset($input_arr['caption']) )  //add caption if exists
					{
						$table.=( $input_arr['percent'] == 'on' ) ? '<tr><th colspan="3" style="line-height: 35px; background-color:'.$input_arr['caption-background'].'; ">'.htmlspecialchars($input_arr['caption']).'</th></tr>' : '<tr><th colspan="2" style="line-height: 35px; background-color:'.$input_arr['caption-background'].'; ">'.htmlspecialchars($input_arr['caption']).'</th></tr>';
					}
					
				if ( isset($input_arr['data-name']) )
					{
						$table.='<tr style="background-color:'.$input_arr['data-caption-bck'].';">';
						$table.= '<th>'.htmlspecialchars($input_arr['data-name']).'</th><th>'.htmlspecialchars($input_arr['data-amount']).'</th>';
						$table.= ( $input_arr['percent'] == 'on' ) ? '<th>( % )</th></tr>' : '</tr>';
					}								
				
				foreach ( $input_arr['pairs_percent'] as $key => $value )
					{
						$index++;	
						$table.='<tr id="'.$input_arr['container'].'-tr-'.$this->unique_ind().'-'.$index.'" class="'.$input_arr['container'].'-tr-'.$this->unique_ind().'">'
									.'<td style="min-height:25px;"><div style="border-radius: 3px; float:left; height: 17px; margin: 4px 8px 0 0; width: 17px; background-color:'.$input_arr['colors'][$key].';"></div><p style="display:table-cell; border:none; text-align:left; text-overflow: ellipsis">'.$key.'</p></td>'
									.'<td>'.$input_arr['data_pairs'][$key].'</td>';
						if ( $input_arr['percent'] == 'on' ) { $table.= '<td>'.round($value,1).'%</td>'; }
									
						$table.='</tr>';
					}
				
				$table.='</table>';
				$table.='</div>';
				
				$table = str_replace(array("\r", "\n"), '', $table);  // filter junk tab chars and line breaks
				
				//----------------------------------------------------------------------------------------------------------------------------------------------	
				$ret_value.='<div class="goo-pie-chart-main" style="float:left; margin: 3px 0 3px 0; width:'.$input_arr['width'].'px; height:'.$input_arr['height'].'px;" data-unique="'.$this->unique_ind().'" data-container="'.$input_arr['container'].'">'
							.'<svg viewBox="0 0 '.$input_arr['width'].' '.$input_arr['height'].'" width="'.$input_arr['width'].'" height="'.$input_arr['height'].'" id="'.$input_arr['container'].'-svg-'.$this->unique_ind().'" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" data-viewbox="0,0,'.$input_arr['width'].','.$input_arr['height'].'" >'
							.$drop_shadow
							.$path
  							.'</svg>'
							.'</div>';
				
				return '<div class="'.$input_arr['main-class'].'">'.$ret_value.$table.'</div>';
				
			}

		private function find_shade($hex_start_end, $count, $position) //$hex_start_end (string) = '#ffffff, #990000' {iput of $input_arr['force_shades']}; $count = no of shades; $position = position of shade (>0)
			{
				$temp_hex = explode(',', $hex_start_end);
				$start_hex = trim($temp_hex[0]);
				$end_hex = trim($temp_hex[1]);
				
				//check if it is hex color
				if( preg_match('/^#[a-f0-9]{6}$/i', $start_hex) && preg_match('/^#[a-f0-9]{6}$/i', $end_hex) )
					{
						if ( $position == 1 ) { return $start_hex; }
						if ( $position == $count ) { return $end_hex; }
						
						//find color
						list($start['r'], $start['g'], $start['b']) = sscanf($start_hex, "#%02x%02x%02x");
						list($end['r'], $end['g'], $end['b']) = sscanf($end_hex, "#%02x%02x%02x");
						
						$ret_rgb = array();
						foreach ( $start as $key => $value )
							{
								$increment = round((abs($start[$key] - $end[$key] ) / $count)*$position, 0, PHP_ROUND_HALF_DOWN);
								$ret_rgb[$key] = ( $start[$key] > $end[$key] ) ? $end[$key] + $increment : $start[$key] + $increment;
							}
							
						return '#' . sprintf('%02x', $ret_rgb['r']) . sprintf('%02x', $ret_rgb['g']) . sprintf('%02x', $ret_rgb['b']);
					}
					else
					{
						return false;
					}
				
			} 
		private function find_XY_slide_hover_margin($start_angle, $end_angle) // returning array{'X'=>0.xxxx , 'Y'=0.yy}
			{
				$middle_angle = ($end_angle - $start_angle) / 2 + $start_angle;
				
				//exact angle (EXIT IF IS HERE)
				if ( $middle_angle == 45) { return array('X' => 1, 'Y' => -1); }
				if ( $middle_angle == 90) { return array('X' => 1, 'Y' => 0); }
				if ( $middle_angle == 135) { return array('X' => 1, 'Y' => 1); }
				if ( $middle_angle == 180) { return array('X' => 0, 'Y' => 1); }
				if ( $middle_angle == 225) { return array('X' => -1, 'Y' => 1); }
				if ( $middle_angle == 270) { return array('X' => -1, 'Y' => 0); }
				if ( $middle_angle == 315) { return array('X' => -1, 'Y' => -1); }
				if ( $middle_angle == 360) { return array('X' => 0, 'Y' => -1); }
				
				//margins
				$a = ($middle_angle>90) ? ($middle_angle / 90) - floor($middle_angle / 90) : (($middle_angle+90) / 90) - floor(($middle_angle+90) / 90);
				$b = 1-$a;
				
				
				//find X,Y vectors by quadrant $middle_angle => (X>Y,...)
				if ( $a == $b )  //0.5=0.5  or 0=0
					{
						$x = $a;
						$y = $b;
					}
				else //rules
					{
						if ( ($middle_angle > 45 && $middle_angle < 135) || ($middle_angle > 225 && $middle_angle < 315) ) // (45->135; 225->315) X>Y
							{
								$x = ($a>$b) ? $a : $b;
								$y = ($a>$b) ? $b : $a;
							}
							
						if ( ($middle_angle>135 && $middle_angle<225) || $middle_angle < 45 || $middle_angle > 315 ) //(135->225)  Y<X
							{
								$y = ($a>$b) ? $a : $b;
								$x = ($a>$b) ? $b : $a;
							} 
					}
					
				//Positive or negative increment by quadrant
				if ( $middle_angle <= 90 ) // (+-)
					{
						$x = $x*(+1);  //zbog preglednosti
						$y = $y*(-1);
					}
					
				if ( $middle_angle > 90 && $middle_angle <= 180 ) // (++);
					{
						$x = $x*(+1);
						$y = $y*(+1);
					}
					
				if ( $middle_angle >180 && $middle_angle <= 270 ) // (-+)
					{
						$x = $x*(-1);
						$y = $y*(+1);
					}
				
				if ( $middle_angle > 270 ) // (--)
					{
						$x = $x*(-1);
						$y = $y*(-1);
					}  
					
				$ret_arr = array('X' => $x,
								 'Y' => $y);
				
				return $ret_arr;
			}
		
			
		public function html_admin()  //no return data
			{
				//HTML output
				//print_r($this->options); die();
                ?>
	            <div class="wrap">
	            <h2><?php echo $this->options['product_info']['name']; ?> - Plugin Settings</h2>
	            <p>Developed by <a href="<?php echo $this->options['product_info']['plugin_site']; ?>" target="_blank">Goo Tools</a> :: Visit official <a href="<?php echo $this->options['product_info']['plugin_page']; ?>" target="_blank">plugin page</a> with instructions. Current version <b><?php echo $this->options['version'];?></b></p>
	            <form method="post" action="options.php">
	                <?php settings_fields( 'goo-piechart-settings-group' ); ?>
	                <?php do_settings_sections( 'goo-piechart-settings-group' ); ?>
	                
	                <p>
	                	<u><strong>Short instructions:</strong></u><br>
	                	Shortcode basic usage: <b>[goo-pie-chart data="set1=75, set2=25"]</b>. This is <u>minimum</u> input needed for plugin to run.
	                </p>
	                
	                <h3>Shortcode option list</h3>
	                <p>
	                	Use anywhere in post as: <b>[goo-pie-chart option1 option2 option3...]</b> option order is arbitrary
	                </p>
	                <table style="width:100%; text-align: center; border-collapse: collapse;">
	                	<tr valign="top">
	                		<th style="padding:10px; border:1px solid gray; background-color:#4dc6ff;">name</th>
	                		<th style="padding:10px; border:1px solid gray; background-color:#4dc6ff;">syntax</th>
	                		<th style="padding:10px; border:1px solid gray; background-color:#4dc6ff; min-width: 150px;">default</th>
	                		<th style="padding:10px; border:1px solid gray; background-color:#4dc6ff;">description</th>
	                		<th style="padding:10px; border:1px solid gray; background-color:#4dc6ff;">example</th>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>data</b></td>
	                		<td style="padding:5px; border:1px solid gray;">data="{string}={integer}, {string}={integer}..."</td>
	                		<td style="padding:5px; border:1px solid gray;">/</td>
	                		<td style="padding:5px; border:1px solid gray;">Crucial. Minimum two data pairs. Data sets to be represented in pie chart.</td>	
	                		<td style="padding:5px; border:1px solid gray;">data="European Union=160, United States=80"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>colors</b></td>
	                		<td style="padding:5px; border:1px solid gray;">colors="{#hex_color}, {#hex_color}..."</td>
	                		<td style="padding:5px; border:1px solid gray;">/</td>
	                		<td style="padding:5px; border:1px solid gray;">Assign arbitrary color for each data pair.</td>	
	                		<td style="padding:5px; border:1px solid gray;">colors="#c1f404, #7e9404"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>height</b></td>
	                		<td style="padding:5px; border:1px solid gray;">height="{integer}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name='goopiechart_options[plugin-options][height]' value="<?php echo $this->options['plugin-options']['height']; ?>"/></td>
	                		<td style="padding:5px; border:1px solid gray;">Insert graph container height (px). This is max container height also. (responsive)</td>	
	                		<td style="padding:5px; border:1px solid gray;">height="300"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>width</b></td>
	                		<td style="padding:5px; border:1px solid gray;">width="{integer}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][width]" value="<?php echo $this->options['plugin-options']['width']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Insert graph container width (px). This is max container width also. (responsive)</td>	
	                		<td style="padding:5px; border:1px solid gray;">width="300"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>data-name</b></td>
	                		<td style="padding:5px; border:1px solid gray;">data-name="{string}"</td>
	                		<td style="padding:5px; border:1px solid gray;">/</td>
	                		<td style="padding:5px; border:1px solid gray;">Assign arbitrary caption for column containing data description. Use with <b>data-amount</b> option.</td>	
	                		<td style="padding:5px; border:1px solid gray;">data-name="Country"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>data-amount</b></td>
	                		<td style="padding:5px; border:1px solid gray;">data-amount="{string}"</td>
	                		<td style="padding:5px; border:1px solid gray;">amount</td>
	                		<td style="padding:5px; border:1px solid gray;">Assign arbitrary caption for column containing data values.  Use with <b>data-name</b> option. If omitted, but data-name is used, plugin will use default value.</td>	
	                		<td style="padding:5px; border:1px solid gray;">data-amount="in thousands"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>data-caption-bck</b></td>
	                		<td style="padding:5px; border:1px solid gray;">data-caption-bck="{#hex_color}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][data-caption-bck]" value="<?php echo $this->options['plugin-options']['data-caption-bck']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Assign data rows caption background color, Use with <b>data-name</b> option. If omitted, but data-name is used, plugin will use default value.</td>	
	                		<td style="padding:5px; border:1px solid gray;">data-caption-bck="#add8e6"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>caption</b></td>
	                		<td style="padding:5px; border:1px solid gray;">caption="{string}"</td>
	                		<td style="padding:5px; border:1px solid gray;">/</td>
	                		<td style="padding:5px; border:1px solid gray;">Assign table caption</td>	
	                		<td style="padding:5px; border:1px solid gray;">caption="Site visitors"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>caption-background</b></td>
	                		<td style="padding:5px; border:1px solid gray;">caption-background="{#hex_color}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][caption-background]" value="<?php echo $this->options['plugin-options']['caption-background']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Assign table caption background color, Use with <b>caption</b> option.</td>	
	                		<td style="padding:5px; border:1px solid gray;">caption-background="#add8e6"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>percent</b></td>
	                		<td style="padding:5px; border:1px solid gray;">percent="{on||off}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][percent]" value="<?php echo $this->options['plugin-options']['percent']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Exclude percent data row from table</td>	
	                		<td style="padding:5px; border:1px solid gray;">percent="off"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>outline-color</b></td>
	                		<td style="padding:5px; border:1px solid gray;">outline-color="{#hex_color}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][outline-color]" value="<?php echo $this->options['plugin-options']['outline-color']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Set chart slices outline color.</td>	
	                		<td style="padding:5px; border:1px solid gray;">outline-color="#66ff99"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>force-shades</b></td>
	                		<td style="padding:5px; border:1px solid gray;">force-shades="{#hex_color_start, #hex_color_end}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][force-shades]" value="<?php echo $this->options['plugin-options']['force-shades']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Render and assign colors according RGB spectre from “start” to “end” color to available data pairs.</td>	
	                		<td style="padding:5px; border:1px solid gray;">force-shades="#cce0ff, #003380"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>table-class</b></td>
	                		<td style="padding:5px; border:1px solid gray;">table-class="{string}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:200px;" type="text" name="goopiechart_options[plugin-options][table-class]" value="<?php echo $this->options['plugin-options']['table-class']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Assign your custom CSS class to data table.</td>	
	                		<td style="padding:5px; border:1px solid gray;">table-class="my_custom_table_class"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>table-container-class</b></td>
	                		<td style="padding:5px; border:1px solid gray;">table-container-class="{string}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:200px;" type="text" name="goopiechart_options[plugin-options][table-container-class]" value="<?php echo $this->options['plugin-options']['table-container-class']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Assign your custom CSS class to DIV holding your data table.</td>	
	                		<td style="padding:5px; border:1px solid gray;">table-container-class="my_custom_table_container_class"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>main-class</b></td>
	                		<td style="padding:5px; border:1px solid gray;">main-class="{string}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:200px;" type="text" name="goopiechart_options[plugin-options][main-class]" value="<?php echo $this->options['plugin-options']['main-class']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Assign your custom CSS class to <b>main</b> DIV holding your shortcode instance.</td>	
	                		<td style="padding:5px; border:1px solid gray;">main-class="my_main_container_class"</td>
	                	</tr>
	                	
	                	<tr>
	                		<td style="padding:5px; border:1px solid gray;"><b>data-sort</b></td>
	                		<td style="padding:5px; border:1px solid gray;">data-sort="{on||off}"</td>
	                		<td style="padding:5px; border:1px solid gray;"><input style="width:100px;" type="text" name="goopiechart_options[plugin-options][data-sort]" value="<?php echo $this->options['plugin-options']['data-sort']; ?>"></td>
	                		<td style="padding:5px; border:1px solid gray;">Sort data pairs from highest to lowest value. Turn <b>off</b> for manual sort (use data "as is inputted").</td>	
	                		<td style="padding:5px; border:1px solid gray;">data-sort="off"</td>
	                	</tr>
	                	
	                	
	                	
	                	
	                </table>
	                <p>
	                	<?php submit_button(); ?>
	                	or roll back to
	                	<input type="submit" id="delete" name="goopiechart_options[default]" value="default">
	                </p>
	                <p>
	                	<b><u>Notice:</u> all defaults can be overridden from shortcode input. Eg. if you turn off 'percent' option, you can always turn it on via short code for particular graph. Defaults are triggered only if option is omitted in shortcode.</b>
	                </p>	
	                
	            </form>
	            <hr>
	            </div>
	                
 
	            <?php 
			}
			
		public function goo_piechart_default_options()
            {
                $default_options = array(
                                        'version' => 1.150,
                                        
                                        'plugin-options' =>  array(
                                        			"width" => 400,
									 				"height" => 400,
									 				"container" => 'goo-pie-chart',
									 				'percent' => 'on',
									 				'data-amount' => 'amount',
									 				'caption-background' => 'transparent',
									 				'data-caption-bck' => 'transparent',
									 				'outline-color' => '#446688',
									 				'force-shades' => '',
									 				'table-class' => 'goo_pie_chart_table',
									 				'table-container-class' => 'goo_pie_chart_table_container',
									 				'main-class' => 'goo_pie_chart_main',
									 				'data-sort' => 'on'	
									 			),
                                        
                                        'product_info' => array(
                                                              'amx' => '',
                                                              'divider' => 1,
                                                              'checksum' => 0,
                                                              'name' => 'Goo Pie Chart',
                                                              'plugin_page' => 'http://www.gootools.net/wordpress-plugins/goo-pie-chart/',
                                                              'plugin_site' => 'http://www.gootools.net/'
                                                             )
                                        
                                        
                                 );
                return $default_options;
            }
   	}
    
   
   
   //-------------------------------------------------------------------------------------------------------------------------------------------------
   //-------------------------------------------------------------------------------------------------------------------------------------------------
   
  
                  
?>
