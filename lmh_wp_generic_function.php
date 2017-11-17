<?php
/**
* Generic function file. Also contains a list of plugins that should be included on each site.
*
* @version 1.1
* @since 2017/11/17
* @author  lorin.hardy@gmail.com
* 
* @link https://wordpress.org/plugins/ssl-insecure-content-fixer/ "SSL Insecure Content Fixer -- removes loading-over-http errors."
* @link https://wordpress.org/plugins/post-smtp/ "Postman SMTP -- makes emailing from Wordpress easy to do from another account."
* @link https://wordpress.org/plugins/wordfence/ "Wordfence Security -- firewall and malware protection for Wordpress."
*/

function custom_login_logo() {
	$directory = wp_upload_dir(); 
	echo '<style type="text/css">';
	echo 'h1 a { background-image: url("' . $directory['baseurl'] . '/2013/02/gone-fishin-logo-fisherman.png") !important; background-size:auto !important; height:110px !important; width:auto !important; }';
	echo '</style>';
}
add_action('login_head', 'custom_login_logo');

function _remove_script_version( $src ){
    $parts = explode( '?ver', $src );
        return $parts[0];
}
add_filter( 'script_loader_src', '_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', '_remove_script_version', 15, 1 );

function register_bottom_bar_menu() {
  register_nav_menu('bottom-bar-menu',__( 'Bottom Bar Menu' ));
}
add_action( 'init', 'register_bottom_bar_menu' );

function shortcode_permalink_generator($atts) {
	extract(shortcode_atts(array(
		'id' => 1,
		'text' => ""  // default value if none supplied
    ), $atts));
    
    if ($text) {
        $url = get_permalink($id);
        return "<a href='$url'>$text</a>";
    } else {
	   return get_permalink($id);
	}
}
add_shortcode('permalink', 'shortcode_permalink_generator');

/*-------------------------------------------------------------------------------
	Make Content Blocks Public and available to visual composer; in order for
	this to work however, you need to go into the visual composer "Role Manager"
	and grant custom access to the content_block post type
-------------------------------------------------------------------------------*/
function filter_content_block_init() {
	$content_block_public = true;
	return $content_block_public;
}
add_filter('content_block_post_type','filter_content_block_init');

/*-------------------------------------------------------------------------------
	Class that adds content block as element in visual composer
-------------------------------------------------------------------------------*/
class VCContentBlockAddonClass {
    function __construct() {
        // We safely integrate with VC with this hook
        add_action( 'init', array( $this, 'integrateWithVC' ) );
        // Use this when creating a shortcode addon
        add_shortcode( 'custom_content_block', array( $this, 'render_content_block' ) );
    }
    public function integrateWithVC() {
        // Check if Visual Composer is installed
        if ( ! defined( 'WPB_VC_VERSION' ) ) {
            // Display notice that Visual Compser is required; we don't need to show this if a site isn't using visual
            // composer or content blocks.
            //add_action('admin_notices', array( $this, 'showVcVersionNotice' ));
            return;
        }
        /*
        Add your Visual Composer logic here.
        Lets call vc_map function to "register" our custom shortcode within Visual Composer interface.
        More info: http://kb.wpbakery.com/index.php?title=Vc_map
        */
        $blocks = get_posts( 'post_type="content_block"&numberposts=-1' );
        $blocks_array = array();
        if ( $blocks ) {
            foreach ($blocks as $block) {
                $blocks_array[$block->post_title] = $block->ID;
            }
        } else {
            $blocks_array["No content blocks found"] = 0;
        }
        vc_map(array(
            "name" => __("Content Block", "mk_framework"),
            "description" => 'Add custom content block',
            "base" => "custom_content_block",
            'icon' => 'icon-wpb-ui-tab-content',
            "category" => __('Content', 'vc_content'),
            "params" => array(
                array(
                    "type" => "dropdown",
                    "heading" => __("Content Block", "mk_framework"),
                    "param_name" => "id",
                    'save_always' => true,
                    "admin_label" => true,
                    "value" => $blocks_array,
                    "description" => __("Choose previously created Content Blocks from the drop down list.", "mk_framework")
                ),
                array(
                    "type" => "textfield",
                    "heading" => __("Extra class name", "mk_framework"),
                    "param_name" => "el_class",
                    "value" => "",
                    "description" => __("If you wish to style particular content element differently, then use this field to add a class name and then refer to it in Custom CSS Shortcode or Masterkey Custom CSS option.", "mk_framework")
                ),
                array(
                    "type" => "hidden_input",
                    "param_name" => "cb_class",
                    "value" => "",
                    "description" => __("", "mk_framework")
                )
            )
        ));
    }
    /*
    Shortcode logic how it should be rendered
    */
    public function render_content_block( $atts ) {
      extract( shortcode_atts( array(
        'title' => '',
        'id' => '',
        'margin_top' => '0',
        'margin_bottom' => '20',
        'cb_class' => '',
        'el_class' => ''
      ), $atts ) );
      //echo $id;
      $post = get_post($id);
      $cb_class = $post->post_name;

      $output = '';
      $output .= wpb_widget_title( array('title' => $title, 'extraclass' => 'wpb_content_block_heading') );
      $output .= apply_filters( 'vc_content_block_shortcode', do_shortcode( '[content_block id=' . $id . ' class="content_block ' . $el_class . ' ' . $cb_class .'"]' ) );
      return $output;
    }
    /*
    Load plugin css and javascript files which you may need on front end of your site
    */
    public function loadCssAndJs() {
      //wp_register_style( 'vc_extend_style', plugins_url('assets/vc_content-block.css', __FILE__) );
      //wp_enqueue_style( 'vc_extend_style' );
      // If you need any javascript files on front end, here is how you can load them.
      //wp_enqueue_script( 'vc_extend_js', plugins_url('assets/vc_extend.js', __FILE__), array('jquery') );
    }
    /*
    Show notice if your plugin is activated but Visual Composer is not
    */
    public function showVcVersionNotice() {
        $plugin_data = get_plugin_data(__FILE__);
        echo '
        <div class="updated">
          <p>'.sprintf(__('<strong>%s</strong>The VC Content Blocks plugin requires <strong><a href="http://bit.ly/vcomposer" target="_blank">Visual Composer</a></strong> plugin to be installed and activated on your site.', 'vc_extend'), $plugin_data['Name']).'</p>
        </div>';
    }
}
// Finally initialize code
new VCContentBlockAddonClass();

