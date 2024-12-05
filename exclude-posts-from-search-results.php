<?php
/*
* @wordpress-plugin
* Plugin Name: Exclude Posts from Search Results
* Description: This plugin excludes posts and pages from site's search results without harming SEO performance.
* Author: Geek Code Lab
* Version: 1.9
* Author URI: https://geekcodelab.com/
* Text Domain : exclude-posts-from-search-results
* Domain Path: /languages
*/

if(!defined('ABSPATH')) exit;

define("GWSEPFSR_BUILD",'1.9');
if(!defined("GWSEPFSR_PLUGIN_DIR_PATH"))
    define( 'GWSEPFSR_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

if(!defined("GWSEPFSR_PLUGIN_URL"))
	define("GWSEPFSR_PLUGIN_URL",plugins_url().'/'.basename(dirname(__FILE__)));


if (!defined("GWSEPFSR_PLUGIN_BASENAME"))
define("GWSEPFSR_PLUGIN_BASENAME", plugin_basename(__FILE__));

if (!defined("GWSEPFSR_PLUGIN_DIR"))
	define("GWSEPFSR_PLUGIN_DIR", plugin_basename(__DIR__));

require_once(GWSEPFSR_PLUGIN_DIR_PATH . 'updater/updater.php');

add_action('upgrader_process_complete', 'epfsr_updater_activate'); // remove  transient  on plugin  update

if( ! class_exists( 'Gwsepfsr_Exclude_Posts_from_Search_Results' ) ) {
	class Gwsepfsr_Exclude_Posts_from_Search_Results{

		private $options,$useroptions;

		public function __construct() {
			register_activation_hook( __FILE__, array($this,'epfsr_activation_callback') );
			add_action('init', array($this,'epfsr_text_domain'));
			$this->options = get_option( 'gwsepfsr_options' );
			$this->useroptions = get_option( 'gwsepfsr_user_options' );
			add_filter('is_protected_meta', array($this, 'epfsr_protected_meta' ), 10, 2);
			add_action( 'admin_print_styles', array($this,'epfsr_enqueue_styles'));
			add_action('admin_enqueue_scripts', array($this,'epfsr_enqueue_script'));
			add_filter( "plugin_action_links_".plugin_basename(__FILE__), array($this,'epfsr_plugin_settings_link'));
			add_action('init',function(){
				global $wp_roles;
				if (isset($this->useroptions['epfsr_user_role']) && !empty($this->useroptions['epfsr_user_role'])) {
					$userRole = $this->useroptions['epfsr_user_role'];
					$wp_roles = new WP_Roles();
					$all_roles = $wp_roles->get_names();
					$all_roles['guest_user'] = "Guest User(Non-Login)";
					$user = wp_get_current_user();
					$role = $user->roles;
					$inUser = false;
					if (is_user_logged_in()) {
						foreach ($role as $key => $value) {
							if ( in_array($value,$userRole) ) {
								$inUser =true;
							}
						}
					}else{
						if ( in_array('guest_user',$userRole) ) {
							$inUser =true;
						}
						
					}
					if ($inUser == true) {
						add_filter( 'pre_get_posts', array($this,'epfsr_search_filter') );
					}else{
						remove_filter( 'pre_get_posts', array($this,'epfsr_search_filter') );
					}
				}
				add_filter( 'query_vars', array($this,'epfsr_query_vars_filter') );
			});
		}
		
		public function epfsr_protected_meta($protected, $meta_key) {
			$meta_key == 'epfsr_exclude_post_value' ? true : $protected;
			return $meta_key;
		}

		public function epfsr_query_vars_filter($vars) {
			$vars[] .= 'ser_post_filter';
			return $vars;
		}

		public function epfsr_enqueue_styles() {
			if( is_admin() ) {
				$style = GWSEPFSR_PLUGIN_URL . '/css/gwsepfsr.css';
				$tagify = GWSEPFSR_PLUGIN_URL . '/css/tag.css';
				wp_enqueue_style( 'gwsepfsr-style', $style ,array() , GWSEPFSR_BUILD);
				wp_enqueue_style( 'gwsepfsr-tagify', $tagify ,array(), GWSEPFSR_BUILD);
			}
		}

		public function epfsr_enqueue_script() {
			wp_enqueue_script('jquery');
			$gwsepfsr = GWSEPFSR_PLUGIN_URL . '/js/admin/gwsepfsr.js';
			$tagify = GWSEPFSR_PLUGIN_URL . '/js/admin/tag.js';
			wp_enqueue_script( 'gwsepfsr-script', $gwsepfsr,array('jquery'), GWSEPFSR_BUILD, true );
			wp_enqueue_script( 'gwsepfsr-tagify', $tagify , array('jquery'), GWSEPFSR_BUILD);
		}

		public function epfsr_activation_callback() {
			epfsr_updater_activate();
			if ( is_plugin_active( 'exclude-posts-from-search-results-pro/exclude-posts-from-search-results-pro.php' ) ) {
                deactivate_plugins( 'exclude-posts-from-search-results-pro/exclude-posts-from-search-results-pro.php' );
            }
			$custom_keyword = '';
			$user_role = ['guest_user'];
			$gwsepfsr_settings_data = [];
			$gwsepfsr_user_settings_data = [];
			$this->options = get_option('gwsepfsr_options');
			$this->useroptions = get_option('gwsepfsr_user_options');
			if(!isset($this->options['epfsr_custom_word']))  $gwsepfsr_settings_data['epfsr_custom_word'] = $custom_keyword;
			if(!isset($this->useroptions['epfsr_user_role']))  $gwsepfsr_user_settings_data['epfsr_user_role'] = $user_role;
			if(count($gwsepfsr_settings_data) > 0)
			{
				update_option( 'gwsepfsr_options', $gwsepfsr_settings_data );
			}
			if(count($gwsepfsr_user_settings_data) > 0)
			{
				update_option( 'gwsepfsr_user_options', $gwsepfsr_user_settings_data );
			}
		}

		public function epfsr_search_filter( $query ) {
			global $wpdb,$wp_query;

			if( is_admin() && ! $query->is_search() ) return $query;
			/**Exclude Post */
			$postIds = $wpdb->get_results( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='epfsr_exclude_post_value' AND meta_value='yes'", ARRAY_A );
			$excludePostIds = [];
			if (isset($postIds) && !empty($postIds)) {
				foreach ($postIds as $aValue) {
					foreach ($aValue as $k => $v) {
						$excludePostIds[] = $v;
					}
				}
			}

			/**Specific Keyword Containing Post Exclude */
			$this->options = get_option('gwsepfsr_options');
			if (isset($this->options['epfsr_custom_word']) && !empty($this->options['epfsr_custom_word'])) {

				if( is_admin() || ! $query->is_search() ) return $query;
	
				global $wpdb;
				$words = $this->options['epfsr_custom_word'];
				$newWords = json_decode($words,true);
				$eWords = [];
				if (!empty($newWords)) {
					
					foreach ($newWords as $key => $value) {
						foreach ($value as $k => $v) {
							$eWords[] = rtrim($v, '.');
						}
					}
					$ids = [];
					foreach( $eWords as $word ){
						$word = addslashes($word);

						$ids[] = $wpdb->get_results( "SELECT `ID` FROM {$wpdb->posts} as wp WHERE (wp.post_title REGEXP '[[:<:]]{$word}[[:>:]]' OR wp.post_excerpt REGEXP '[[:<:]]{$word}[[:>:]]' OR wp.post_content REGEXP '[[:<:]]{$word}[[:>:]]' OR INSTR (wp.post_title , '$word') OR INSTR (wp.post_excerpt , '$word') OR INSTR (wp.post_content , '$word') ) AND wp.post_type != 'revision'" ,ARRAY_A);

					}
					$newIds = [];
					$finalIds = [];
					if (isset($ids)) {
						foreach ($ids as $key => $value) {
							foreach ($value as $k => $v) {
								foreach ($v as $finalKey => $finalValue) {
									$newIds[] = $finalValue;
								}
							}
						}
					}
					$finalIds = array_unique($newIds);
				}
			}
			if (!empty($excludePostIds) && !empty($finalIds)) {
				$array = array_unique(array_merge($excludePostIds, $finalIds));
				if ( ! $query->is_admin && $query->is_search && $query->is_main_query() ) {
					$query->set( 'post__not_in', $array );
				}
			}elseif (empty($excludePostIds) && !empty($finalIds)) {
				$excludePostIds = [];
				$array = array_unique(array_merge($excludePostIds, $finalIds));
				if ( ! $query->is_admin && $query->is_search && $query->is_main_query() ) {
					$query->set( 'post__not_in', $array );
				}
			}elseif (!empty($excludePostIds) && empty($finalIds)) {
				$finalIds = [];
				$array = array_unique(array_merge($excludePostIds, $finalIds));
				if ( ! $query->is_admin && $query->is_search && $query->is_main_query() ) {
					$query->set( 'post__not_in', $array );
				}
			}
			return $query;
		}

		public function epfsr_plugin_settings_link( $links ) {
			$support_link = '<a href="https://geekcodelab.com/contact/"  target="_blank" >' . __( 'Support', 'exclude-posts-from-search-results' ) . '</a>'; 
			array_unshift( $links, $support_link );

			$settings_link = '<a href="'. admin_url() .'admin.php?page=epfsr-settings">' . __( 'Settings', 'exclude-posts-from-search-results' ) . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}

		public function epfsr_text_domain() {
			load_plugin_textdomain('exclude-posts-from-search-results', false, basename(dirname(__FILE__)) . '/languages');
		}
	}
	new Gwsepfsr_Exclude_Posts_from_Search_Results();
}
require_once( GWSEPFSR_PLUGIN_DIR_PATH .'functions.php' );
require_once( GWSEPFSR_PLUGIN_DIR_PATH .'settings.php' );
?>