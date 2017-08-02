<?php
//FILE: goo_tools.php
//FILE VER:1.02 (JULY 2016.)
  
  // ------------------ NO DIRECT ACCESS
 if ( !function_exists( 'add_action' ) ) 
     {
        echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
        die();
     }
 
  
  function FileExistsGoo($url)    // $url - preferably a fully qualified URL
    {
        if (($url == '') || ($url == null)) { return false; }
        $response = wp_remote_head( $url, array( 'timeout' => 5 ) );
        $accepted_status_codes = array( 200, 301, 302 );
        if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), $accepted_status_codes ) ) {
            return true;
        }
        return false;
    }
  
  function goo_tools_plugin_settings_function()
    {
        wp_enqueue_style( 'goo-style-admin', plugin_dir_url( __FILE__ ).'admin/goo_tools_admin.css' );
        wp_enqueue_script( 'goo-script-admin', plugin_dir_url( __FILE__ ) . 'admin/goo_tools_admin.js', array( 'jquery' ), '1.0', true );
        
                    //contact server RETRIEVE products info!!!
        $response = wp_remote_get( esc_url_raw( 'http://www.gootools.net/product_info/'.'plugins_remote.php?action=allplugins') );   
         
        $solved_response = json_decode($response['body'], true);
        $installed_active = '';
        $installed_inactive = '';
        $recomanded = '';
        $dir = plugins_url();
        foreach ( $solved_response as $key => $value )
            { 
                if ( FileExistsGoo($dir.'/'.$value['file'].'/'.$value['file'].'.php') )
                    {
                        if ( function_exists($value['fname']) )
                            {
                            	if ( $value['premium'] == true )
									{
										$cl_name = $value['plugin_class'];
										$cl = new $cl_name;
										$button = ( $cl->amx_c() == $cl->amx_k() ) ? '<span class="goo-tools-b-span"><strong>THANKS</strong>' : '<a href="'.$value['plugin_url'].'" target="_blank"><span class="goo-tools-b-span"><strong>GO PRO</strong></span></a>';  
									}
									else
									{
										$button = '<a href="http://www.gootools.net/donate/" target="_blank"><span class="goo-tools-b-span"><strong>Donate</strong></span></a>';			
									}
                            	
                                $installed_active.='<div class="goo_tools_product">
                                                     <div class="goo_tools_product_title">'.$value['name'].'</div>
                                                     <div>
                                                       <div class="goo_tools_icon" style="background: url(\''.plugin_dir_url( __FILE__ ).'/admin/stared_m.png\') no-repeat 0 0;"></div>
                                                       <div class="goo_tools_desription" style="display:none;">'.$value['description'].'</div>
                                                       <div class="goo_tools_button" style="display:none;">'
                                                       		.$button.
                                                       '</div>
                                                       <div style="height:50px;"><a href="'.$value['plugin_url'].'" target="_blank"><span style="vertical-align:middle; display:inline-block; padding: 10px 0 0 0;">visit plugin page</span></a></div>
                                                     </div>
                                                    </div>';
                            }
                            else
                            {
                            	if ( $value['premium'] == true )
									{
										$button =  '<a href="'.$value['plugin_url'].'" target="_blank"><span class="goo-tools-b-span"><strong>GO PRO</strong></span></a>';  
									}
									else
									{
										$button = '<a href="http://www.gootools.net/donate/" target="_blank"><span class="goo-tools-b-span"><strong>Donate</strong></span></a>';			
									}
                                $installed_inactive.= '<div class="goo_tools_product">
                                                     <div class="goo_tools_product_title">'.$value['name'].'</div>
                                                     <div>
                                                       <div class="goo_tools_icon" style="background: url(\''.plugin_dir_url( __FILE__ ).'/admin/stared_m.png\') no-repeat 0 0;"></div>
                                                       <div class="goo_tools_desription" style="display:none;">'.$value['description'].'</div>
                                                       <div class="goo_tools_button" style="display:none;">'.$button.'</div>
                                                       <div style="height:50px;"><a href="'.$value['plugin_url'].'" target="_blank"><span style="vertical-align:middle; display:inline-block; padding: 10px 0 0 0;">visit plugin page</span></a></div>
                                                     </div>
                                                    </div>';
                            }
                    }
                    else
                    {
						if ( $value['premium'] == true )
							{
								$button =  '<a href="'.$value['plugin_url'].'" target="_blank"><span class="goo-tools-b-span"><strong>GO PRO</strong></span></a>';  
							}
							else
							{
								$button = '<a href="http://www.gootools.net/donate/" target="_blank"><span class="goo-tools-b-span"><strong>Donate</strong></span></a>';			
							}
                        $recomanded.= '<div class="goo_tools_product">
                                                     <div class="goo_tools_product_title">'.$value['name'].'</div>
                                                     <div>
                                                       <div class="goo_tools_icon" style="background: url(\''.plugin_dir_url( __FILE__ ).'/admin/stared_m.png\') no-repeat 0 0;"></div>
                                                       <div class="goo_tools_desription" style="display:none;">'.$value['description'].'</div>
                                                       <div class="goo_tools_button" style="display:none;">
                                                       '.$button.'	
                                                       	</div>
                                                       	<div style="height:50px;"><a href="'.$value['plugin_url'].'" target="_blank" style="outline:none; "><span style="vertical-align:middle; display:inline-block; padding: 10px 0 0 0; ">visit plugin page</span></a></div>
                                                     </div>
                                                     </div>
                                                    </div>';
                    }
            }
                    
                    ?>
                        <div class="wrap">
                        	<p>
                        		<img src="<?php echo plugin_dir_url( __FILE__ ).'/admin/logo_bb.png'; ?> ">
                        	</p>
                        <h1>Welcome to Goo Tools Wordpress plugins</h1>
                        	<p>
                        		Thank you for using our products. Our product database get larger every day. Here you will find part of our plugins you might be interested for, or visit our web <a href="http://www.gootools.net/" target="_blank">site</a>.
                        		
                        	</p>
                        <p>
                            <?php 
                                if ($installed_active != '' ) {echo '<h3 class="gt active">ACTIVE PLUGINS</h3><p>'.$installed_active.'</p><div class="gt_clear"></div><hr>';}
                                if ($installed_inactive != '' ) {echo '<h3 class="gt deactivated">DEACTIVATED PLUGINS</h3><p>'.$installed_inactive.'</p><div class="gt_clear"></div><hr>';} 
                                if ($recomanded != '' ) {echo '<h3 class="gt recommended">RECOMMENDED PLUGINS</h3><p>'.$recomanded.'</p><div class="gt_clear"></div><hr>';}  
                            ?>
                            Need help? <a href="http://www.gootools.net/contact-us/" target="_blank">Contact us</a>.
                        </p>
                        </div>
                    <?php
    }
?>
