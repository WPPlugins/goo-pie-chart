<?php
  
  // ------------------ NO DIRECT ACCESS
 if ( !function_exists( 'add_action' ) ) 
     {
        echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
        die();
     }
 
  
    if ( !function_exists('goo_tools_plugin_settings_function') )
        {
            require_once( plugin_dir_path( __FILE__ ) . 'goo_tools.php' );  
        }
  
    add_action('admin_menu', 'goo_pie_chart_plugin_menu');  

    function goo_pie_chart_plugin_menu() 
        {
            if ( empty ( $GLOBALS['admin_page_hooks']['goo-tools-plugin-settings'] ) )
                {
                    add_menu_page('Goo Tools - Settings', 'Goo Tools Plugins', 'administrator', 'goo-tools-plugin-settings', 'goo_tools_plugin_settings_function', 'dashicons-admin-generic');
                }
            
            add_submenu_page('goo-tools-plugin-settings', 'Goo Pie Chart', 'Goo Pie Chart', 'administrator', 'goo-piechart-plugin-settings', 'goo_pie_chart_plugin_settings_f');   //  Goo Share This -> Goo Pie Chart  ||  goo-sharethis-plugin-settings -> goo-piechart-plugin-settings ||  goo_share_this_plugin_settings_f -> goo_pie_chart_plugin_settings_f   
        }
        
    function goo_pie_chart_plugin_settings_f()
        {
            $plugin_admin = new goo_piechart_class();    
            
            $plugin_admin->html_admin();
            return;
            
        }
        
    add_action( 'admin_init', 'goopiechart_plugin_settings' );  

    function goopiechart_plugin_settings() 
        {
            
            register_setting( 'goo-piechart-settings-group', 'goopiechart_options', 'goopiechart_option_callback' ); 
            
        }
        
    function goopiechart_option_callback($input)
        {
            $plugin_admin = new goo_piechart_class();

			//to default
			if ( isset($input['default']) ) { return $plugin_admin->goo_piechart_default_options(); }
			
            //new data
			foreach ( $input['plugin-options'] as $key => $value )
				{
					$plugin_admin->options['plugin-options'][$key] = $value;
				}
 
            return $plugin_admin->options;
            
        }
?>
