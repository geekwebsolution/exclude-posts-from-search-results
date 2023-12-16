<?php
if(!defined('ABSPATH')) exit;

if( ! class_exists( 'Gwsepfsr_Exclude_Posts_from_Search_Results_Core' ) ) {

	class Gwsepfsr_Exclude_Posts_from_Search_Results_Core{

        public function __construct(){

			add_action( 'admin_init', function(){
				/**Exclude Post*/
				$args = array('public' => true,'show_ui' => true,);
				$post_types = get_post_types( $args, 'names' );
				unset($post_types['attachment']);
				add_action( 'add_meta_boxes', array($this,'epfsr_post_meta_box') );
				add_action('save_post', array($this, 'epfsr_save_metabox'), 10, 2);
				foreach ($post_types as $key => $pt) {
					add_filter( 'manage_edit-'.$pt.'_columns', array($this,'epfsr_edit_listing_columns') ) ;
					add_action( 'manage_'.$pt.'_posts_custom_column', array($this,'epfsr_manage_columns'), 10, 2 );
					add_filter( 'manage_edit-'.$pt.'_sortable_columns', array($this,'epfsr_sortable_columns'));
					add_filter('views_edit-'.$pt.'',array($this,'epfsr_exclude_filter_post'));
				}
				add_action('quick_edit_custom_box', array($this, 'epfsr_add_quick_edit' ), 10, 2);
				add_action( 'admin_print_footer_scripts-edit.php', array($this, 'epfsr_quickedit_post' ) );
				add_filter( 'pre_get_posts', array($this,'epfsr_posts_sorting_filtering') );
			});
		}

		/**Exclude POsts*/
		public function epfsr_post_meta_box(){
			$args = array('public' => true,'show_ui' => true,);
			$post_types = get_post_types( $args, 'names' );
			unset($post_types['attachment']);
			foreach ($post_types as $key => $pt) {
				add_meta_box(
					'ser_pt_metabox',
					__( 'Exclude Post', 'exclude-posts-from-search-results' ),
					array($this,'ser_post_metabox'),
					$pt,
					'side',
					'high'
				);
			}
		}

		public function ser_post_metabox($post){
			global $post;
			$meta = get_post_meta( $post->ID );
			$pt_checkbox_value = ( isset( $meta['epfsr_exclude_post_value'][0] ) &&  'yes' === $meta['epfsr_exclude_post_value'][0] ) ? 'yes' : '';
			?>
				<p>
					<label><input type='checkbox' name='epfsr_exclude_post_value' value='yes' <?php checked( $pt_checkbox_value, 'yes' ); ?> /><?php esc_attr_e( 'Exclude Post from Search Results', 'exclude-posts-from-search-results' ); ?></label>
				</p>
			<?php
		}

		public function epfsr_save_metabox($post_id){
			if(isset($_POST['_wp_http_referer'])) {
				$args = array('public' => false,'show_ui' =>true);
				$post_types = get_post_types( $args, 'names' );
				unset($post_types['wp_block']);
				$c_pt = get_current_screen()->post_type;
				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
				if (in_array($c_pt,$post_types)) {return;}
				if ( !current_user_can( 'manage_options' ) ) {return;}
				if (isset($_POST['epfsr_exclude_post_value'])) {
					$exclude_value = sanitize_text_field( $_POST['epfsr_exclude_post_value'] );
					update_post_meta( $post_id, 'epfsr_exclude_post_value', $exclude_value );
				}else {
					update_post_meta( $post_id, 'epfsr_exclude_post_value', '' );
				}
			}
		}

		public function epfsr_edit_listing_columns( $columns ) {
			$reordered_columns = array();
			foreach( $columns as $key => $column){
				$reordered_columns[$key] = $column;
				if( $key ==  'date' ){
					$reordered_columns['epfsr_exclude'] = __( 'Search Exclude','exclude-posts-from-search-results' );
				}
			}
			return $reordered_columns;
		}
		
		public function epfsr_manage_columns( $column, $post_id ) {
			global $post;
			if($column == 'epfsr_exclude'){
				$exclude = get_post_meta( $post_id, 'epfsr_exclude_post_value', true );
				if($exclude == 'yes'){
					printf( '<input type="hidden" data-gwsepfsr_exclude="%s" id="ser-'.$post_id.'"><span class="dashicons dashicons-yes-alt gwsepfsr_right_icon"></span>',$exclude);
				}else{
					printf( '<input type="hidden" data-gwsepfsr_exclude="%s" id="ser-'.$post_id.'">',$exclude);
				}
			}
		}

		public function epfsr_sortable_columns( $columns ) {
			$columns['epfsr_exclude'] = 'epfsr_exclude_post_value';
			return $columns;
		}

		public function epfsr_posts_sorting_filtering( $query ) {
			if( ! is_admin() || ! $query->is_main_query() ) {return;}
			$meta_key = 'epfsr_exclude_post_value';
			$meta_query = array(
								'relation' => 'OR',
								array(
									'key'     => $meta_key,
									'compare' => 'NOT EXISTS',
								),
								array(
									'relation' => 'OR',
										array(
											'key'     => $meta_key,
											'value'   => 'yes',
											'compare' => '=',
										),
										array(
											'key'     => $meta_key,
											'value'   => '',
											'compare' => '=',
										),
								),
							);
			$meta_filter_query = array(
										'relation' => 'AND',
										array(
											'key' => $meta_key,
											'value' => 'yes'
										),
									);
			if ( 'epfsr_exclude_post_value' === $query->get( 'orderby') ) {
				$query->set( 'meta_query', $meta_query );
			}
			if ('ser_exclude' === $query->get( 'ser_post_filter')) {
				$query->set( 'meta_query', $meta_filter_query );
			}
			return $query;
		}

		public function epfsr_add_quick_edit($column_name, $post_type){
			if (!($column_name === 'epfsr_exclude')) 
			{	
				return;
			}else {
				switch ($column_name) {
					case 'epfsr_exclude':
						?>
							<fieldset class='inline-edit-col-right'>
								<div class='inline-edit-col'>
									<div class='inline-edit-group wp-clearfix'>
										<label class='alignleft'>
											<input type='checkbox' name='epfsr_exclude_post_value' class='epfsr-exclude-post' value='yes'>
											<span class='checkbox-title'><?php _e('Exclude Post From Search Result','exclude-posts-from-search-results');?></span>
										</label>
									</div>
								</div>
							 </fieldset>
						<?php
						break;
					default:
						break;
				}
			}
		}

		public function epfsr_quickedit_post() {
			global $pagenow;

			if ($pagenow !== 'edit.php') {
				return;
			}
			?>
				<script type='text/javascript'>
					jQuery(function($) {
						var $wpar_inline_editor = inlineEditPost.edit;
						inlineEditPost.edit = function(id) {
							$wpar_inline_editor.apply(this, arguments);
							var $post_id = 0;
							if (typeof(id) == 'object') {
								$post_id = parseInt(this.getId(id));
							}
							if ($post_id != 0) {
								var $edit_row = jQuery('#edit-' + $post_id);
								var $post_row = jQuery('#post-' + $post_id);
								var $exclude = jQuery('.column-epfsr_exclude input[data-gwsepfsr_exclude]', $post_row).attr('data-gwsepfsr_exclude');
								jQuery('.epfsr-exclude-post', $edit_row).prop('checked', $exclude );
							}
						}
					});
				</script>
			<?php
		}

		public function epfsr_exclude_filter_post($views) {
			global $wp_query,$pagenow;
			if( ( is_admin() ) && 'edit.php' == $pagenow ) {
				
         		$sc = get_current_screen();
			 	$pt = $sc->post_type;
				$meta_key = 'epfsr_exclude_post_value';
				$query = array(
					'post_type'   => $pt,
					'meta_key'    => $meta_key,
					'meta_value'  => 'yes',
				);
				$result = new WP_Query($query);
				if (isset($_GET['ser_post_filter'])) {
					$class = ($wp_query->query_vars['ser_post_filter'] == 'ser_exclude') ? ' class="current"' : '';
				}
				else {
					$class = '';
				}
				$url =  add_query_arg( array( 'ser_post_filter' => 'ser_exclude','post_type' => $pt,'order' => 'asc' ), admin_url( 'edit.php' ) );
				$views['ser_post_filter'] = sprintf(__('<a href="%s"'. $class .'>'.__('Exclude Post','exclude-posts-from-search-results').'<span class="count">(%d)</span></a>', __('Exclude Post','exclude-posts-from-search-results') ), add_query_arg( array( 'ser_post_filter' => 'ser_exclude','post_type' => $pt ), admin_url( 'edit.php' ) ), $result->found_posts);
		
				return $views;
		
			}
		}

	}
	new Gwsepfsr_Exclude_Posts_from_Search_Results_Core();
}