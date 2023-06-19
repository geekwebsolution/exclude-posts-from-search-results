<?php
if(!defined('ABSPATH')) exit;

if( ! class_exists( 'Gwsepfsr_Exclude_from_Search_Results_Settings' ) ) {
    
	class Gwsepfsr_Exclude_from_Search_Results_Settings{

        private $options,$useroptions;

        public function __construct(){
            add_action('admin_menu', array($this,'epfsr_options_page'));
            add_action( 'admin_init', array($this,'epfsr_register_settings_init') );
		}

        public function epfsr_options_page() {
            add_menu_page(
                __('Search Exclude','exclude-posts-from-search-results'),
                __('Search Exclude','exclude-posts-from-search-results'),
                'manage_options',
                'epfsr-settings',
                array($this,'epfsr_settings_html'),
            );
        }

        public function epfsr_settings_html(){
            global $wp;
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __('You do not have sufficient permissions to access this page.', 'exclude-posts-from-search-results'));
            }
            $this->options = get_option( 'gwsepfsr_options' );
            $this->useroptions = get_option( 'gwsepfsr_user_options' );
            $default_tab = null;
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;
            ?>
                <div class="gwsepfsr-main-form">
                    <div class="wrap">
                        <h2 class="gwsepfsr-h2-title"><?php _e('Exclude Posts From Search Results','exclude-posts-from-search-results'); ?></h2>
                        <?php
                            settings_errors();
                        ?>
                        <span class="gwsepfsr-success"></span>
                        <nav class="gwsepfsr-nav-tab">
                            <a href="?page=epfsr-settings" class="epfsr-nav-tab <?php if($tab == null):?>epfsr-nav-tab-active<?php endif; ?>"><?php _e('Exclude By Keywords','exclude-posts-from-search-results'); ?></a>
                            <a href="?page=epfsr-settings&tab=posts" class="epfsr-nav-tab <?php if($tab == 'posts'):?>epfsr-nav-tab-active<?php endif; ?>"><?php _e('Excluded Posts','exclude-posts-from-search-results'); ?></a>
                            <a href="?page=epfsr-settings&tab=roles" class="epfsr-nav-tab <?php if($tab == 'roles'):?>epfsr-nav-tab-active<?php endif; ?>"><?php _e('User Roles','exclude-posts-from-search-results'); ?></a>
                            <a href="javascript:void(0)" class="epfsr-nav-tab gwsepfsr-pro-tab"><?php _e('Exclude By Post Types','exclude-posts-from-search-results'); ?> <div class="gwsepfsr-pro-tag-wp"><span class="gwsepfsr-pro-tag"><?php _e('pro','exclude-posts-from-search-results'); ?></span></div></a>
                            <a href="javascript:void(0)" class="epfsr-nav-tab gwsepfsr-pro-tab"><?php _e('Excluded Terms','exclude-posts-from-search-results'); ?> <div class="gwsepfsr-pro-tag-wp"><span class="gwsepfsr-pro-tag"><?php _e('pro','exclude-posts-from-search-results'); ?></span></div></a>
                            <a href="javascript:void(0)" class="epfsr-nav-tab gwsepfsr-pro-tab"><?php _e('Excluded Authors','exclude-posts-from-search-results'); ?> <div class="gwsepfsr-pro-tag-wp"><span class="gwsepfsr-pro-tag"><?php _e('pro','exclude-posts-from-search-results'); ?></span></div></a>
                            <a href="?page=epfsr-settings&tab=get_pro" class="epfsr-nav-tab <?php if($tab === 'get_pro'):?>epfsr-nav-tab-active<?php endif; ?>"><?php _e('Get Pro *','exclude-posts-from-search-results'); ?> <img width="17" height="17" src="<?php echo esc_url(GWSEPFSR_PLUGIN_URL . '/images/crown.svg'); ?>" alt="Crown"></a>
                        </nav>

                        <form method="post" action="options.php">
                            <?php settings_fields( 'gwsepfsr_settings' ); ?>
                            <div class="gwsepfsr-form">
                                <div class="gwsepfsr-keyword-form gwsepfsr-sec <?php if($tab == null):?>gwsepfsr-active-sec<?php endif; ?>">
                                    <?php 
                                        do_settings_sections( 'gwsepfsr_keyword_section' ); 
                                        submit_button( __( 'Save Settings','exclude-posts-from-search-results' ) );
                                    ?>
                                </div>
                            </div>
                        </form>

                        <div class="gwsepfsr-form">
                            <div class="gwsepfsr-posts-form gwsepfsr-sec <?php if($tab == 'posts'):?>gwsepfsr-active-sec<?php endif; ?>">
                                <?php 
                                    do_settings_sections( 'gwsepfsr_posts_section' ); 
                                ?>
                            </div>
                        </div>
                        
                        <form method="post" action="options.php">
                            <?php settings_fields( 'gwsepfsr_user_settings' ); ?>
                            <div class="gwsepfsr-form">
                                <div class="gwsepfsr-roles-form gwsepfsr-sec <?php if($tab == 'roles'):?>gwsepfsr-active-sec<?php endif; ?>">
                                    <?php 
                                        do_settings_sections( 'gwsepfsr_roles_section' ); 
                                        submit_button( __( 'Save Settings','exclude-posts-from-search-results' ) );
                                    ?>
                                </div>
                            </div>
                        </form>

                        <div class="gwsepfsr-form">
                            <div class="gwsepfsr-get-pro-form gwsepfsr-sec <?php if($tab == 'get_pro'):?>gwsepfsr-active-sec<?php endif; ?>">
                                <?php 
                                    do_settings_sections( 'gwsepfsr_get_pro_section' ); 
                                ?>
                            </div>
                        </div>
                    
                    </div>

                </div>
            <?php
        }

        public function epfsr_register_settings_init(){

            /**Exclude Keywords And Sentence */
            register_setting(
                'gwsepfsr_settings',
                'gwsepfsr_options',
                array(),
            );
            add_settings_section(
                'gwsepfsr_keyword_setting',
                __( 'Exclude Posts From Search Results by Keyword and Sentence', 'exclude-posts-from-search-results' ),
                array(),
                'gwsepfsr_keyword_section'
            );
            add_settings_field(
                'epfsr_custom_word', 
                __('Custom Keyword and Sentence :', 'exclude-posts-from-search-results' ), 
                array( $this, 'epfsr_custom_word_callback' ), 
                'gwsepfsr_keyword_section', 
                'gwsepfsr_keyword_setting',           
                [
                    'label_for' => 'epfsr_custom_word',
                    'class' => 'gwsepfsr_custom_word',
                ]
            );

            /**Exclude Posts */
            add_settings_section(
                'gwsepfsr_posts_setting',
                __( 'List of Posts, Excluded From Search Results', 'exclude-posts-from-search-results' ),
                array( $this, 'epfsr_posts_callback' ),
                'gwsepfsr_posts_section',
                'gwsepfsr_posts_setting'
            );

            /**User Roles */
            register_setting(
                'gwsepfsr_user_settings',
                'gwsepfsr_user_options',
                array($this,'epfsr_sanitize_user')
            );
            add_settings_section(
                'gwsepfsr_roles_setting',
                __( 'All User roles', 'exclude-posts-from-search-results' ),
                array(),
                'gwsepfsr_roles_section'
            );
            add_settings_field(
                'epfsr_user_role', 
                __('Exclude User Role :', 'exclude-posts-from-search-results' ), 
                array( $this, 'epfsr_roles_callback' ), 
                'gwsepfsr_roles_section', 
                'gwsepfsr_roles_setting',           
                [
                    'label_for' => 'epfsr_user_role',
                    'class' => 'gwsepfsr_user_role',
                ]
            );

            /**Get Pro */
            add_settings_section(
                'gwsepfsr_get_pro_setting',
                __( 'Get Pro *', 'exclude-posts-from-search-results' ),
                array( $this, 'epfsr_get_pro_callback' ),
                'gwsepfsr_get_pro_section',
                'gwsepfsr_get_pro_setting'
            );
        }

        public function epfsr_sanitize_user($input){
            $new_input = array();
            if( isset( $input['epfsr_user_role'] ) ){
                $new_input['epfsr_user_role'] = array_map( 'esc_attr', $input['epfsr_user_role'] );
            }
            return $new_input;
        }

        public function epfsr_custom_word_callback(){
            if (isset($this->useroptions) && !empty($this->useroptions)) {
                $exRole = $this->useroptions['epfsr_user_role'];
                $ex_role = [];
                foreach ($exRole as $key => $value) {
                    $ex_role[] = ucwords(str_replace('_', ' ', $value));
                }
                $exRoles = implode(', ' , $ex_role);
            }
            ?>
                <textarea name='gwsepfsr_options[epfsr_custom_word]' class="gwsepfsr-custom-word-tag" id="gwsepfsr_custom_word_tag">
                    <?php if (isset($this->options['epfsr_custom_word']) && !empty($this->options['epfsr_custom_word'])) {
                        echo esc_attr($this->options['epfsr_custom_word']);
                    } ?>
                </textarea>
                <ul class="gwsepfsr-list">
                    <li><?php _e('Note:','exclude-posts-from-search-results'); ?></li>
                    <li><p class="gwsepfsr-note"><?php _e('Enter any keyword or sentence to exclude the posts from search results that contain these keywords or sentences in their title, content, or excerpt.','exclude-posts-from-search-results'); ?></p></li>
                </ul>
                <?php if(isset($exRoles) && !empty($exRoles)){ ?>
                    <p><b><?php  esc_attr_e($exRoles); ?></b> <?php _e('excluded user role, You can update from','exclude-posts-from-search-results'); ?> <a href="?page=epfsr-settings&tab=roles"><?php _e('Here','exclude-posts-from-search-results'); ?></a></p> 
            <?php
            }
        }

        public function epfsr_posts_callback(){
            global $wpdb,$wp;
            if (isset($_POST) && count($_POST) > 0) {
                if (wp_verify_nonce($_POST['search_exclude_post'], 'search_exclude_post_submit')) {
                    if (isset($_POST['epfsr_exclude_post_value']) && count($_POST['epfsr_exclude_post_value']) > 0) {
                        $arr1 = array_map( 'intval', $_POST['epfsr_exclude_post_remove_value'] );
                        $arr2 = array_map( 'intval', $_POST['epfsr_exclude_post_value'] );
                        $includeIds = array_diff($arr1,$arr2);
                        if (!empty($includeIds) && count($includeIds) > 0) {
                            foreach ($includeIds as $post_id){
                                update_post_meta( $post_id, 'epfsr_exclude_post_value', '' );
                            }
                        }
                    }
                    else {
                        $includeIds = array_map( 'intval', $_POST['epfsr_exclude_post_remove_value'] );
                        if (!empty($includeIds) && count($includeIds) > 0) {
                            foreach ($includeIds as $post_id){
                                update_post_meta( $post_id, 'epfsr_exclude_post_value', '' );
                            }
                        }
                    }
                }
            }
            $sql = "SELECT post_id FROM  {$wpdb->postmeta} WHERE meta_key = 'epfsr_exclude_post_value' AND meta_value = 'yes' ";
            $result = $wpdb->get_results( $sql,ARRAY_A );
            $postId = [];
            foreach ($result as $key => $value) {
                foreach ($value as $k => $v) {
                    $postId[] = $v;
                }
            }
            ?>
                <?php if (empty($postId)):?>
                    <p><?php _e('No Posts excluded from the search results yet.','exclude-posts-from-search-results'); ?></p>
                <?php else: ?>
                    <form method="post" action="admin.php?page=epfsr-settings&tab=posts" enctype="multipart/form-data">
                    
                        <table cellspacing="0" class="wp-list-table widefat fixed pages">
                            <thead>
                            <tr>
                                <th class="check-column" id="cb" scope="col"></th>
                                <th class="column-title manage-column" id="title" scope="col"><span><?php _e('Title','exclude-posts-from-search-results'); ?></span></th>
                                <th class="manage-column column-role" id="role" scope="col"><span><?php _e('Type','exclude-posts-from-search-results'); ?></span>
                            </tr>
                            </thead>

                            <tbody id="the-list">
                                <?php foreach ($postId as $key => $value) {
                                    if(post_type_exists(get_post_type($value)) == true){
                                        $editUrl = get_edit_post_link($value); 
                                        $ptName = get_post_type_object(get_post_type($value));
                                        ?>
                                            <tr valign="top" class="post-<?php esc_attr_e($value);?> page type-page status-draft author-self" >
                                                <th class="check-column" scope="row">
                                                    <input type="checkbox" value="<?php esc_attr_e($value);?>" name="epfsr_exclude_post_value[]" checked="checked">
                                                    <input type="hidden" name="epfsr_exclude_post_remove_value[]" value="<?php esc_attr_e($value);?>"></th>
                                                <td class="post-title page-title column-title">
                                                    <strong><a title="Edit “<?php esc_attr_e(get_the_title($value)); ?>”" href="<?php esc_attr_e($editUrl); ?>" class="row-title"><?php echo esc_attr(get_the_title($value)); ?></a></strong>
                                                </td>
                                                <td class="post column-exclude-post"><?php esc_attr_e($ptName->labels->singular_name); ?></td>
                                            </tr>
                                        <?php 
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php wp_nonce_field( 'search_exclude_post_submit','search_exclude_post'); ?>
                        
                        <p class="submit"><input type="submit" name="search_exclude_post_submit" class="button-primary gwsepfsr-save" value="<?php _e('Save Changes','exclude-posts-from-search-results'); ?>" /></p>
                    </form>
                <?php endif; ?>
            <?php
        }
        
        public function epfsr_roles_callback(){
            $this->options = get_option( 'gwsepfsr_user_options' );
            $roles_obj = new WP_Roles();
			$roles_names = $roles_obj->get_names();
            $roles_names['guest_user'] = "Guest User ". __('(Non-Login)',"exclude-posts-from-search-results");
            foreach ($roles_names as $key => $value) {
                ?>
                    <div class="epfsr-roles-field">
                        <input type="checkbox" name="gwsepfsr_user_options[epfsr_user_role][]" id="<?php esc_attr_e($key); ?>" value="<?php esc_attr_e($key); ?>" <?php 
                        if(!empty($this->options['epfsr_user_role'])){
                            if(in_array($key,$this->options['epfsr_user_role'])){ 
                                esc_attr_e("checked");}
                            }
                        ?>><label for="<?php esc_attr_e($key); ?>"><?php esc_attr_e($value); ?></label>
                    </div>
                <?php
            }
            printf('<ul class="gwsepfsr-user-role-ul gwsepfsr-list"><li><p>'.__('Note:','exclude-posts-from-search-results').'</p></li><li><p class="epfsr-user-roles gwsepfsr-note">'.__('By default, this plugin will exclude posts only for guest (non-logged-in) users, if you want to exclude posts for any specific role type then you can select that user role from here.','exclude-posts-from-search-results').'</p></li></ul>');
        }

        public function epfsr_get_pro_callback(){
            ?>
            <div class="epfsr-get-pro-list">
                <ul class="epfsr-get-pro-ul">
                    <li>
                        <div class="epfsr-check-title">
                            <span class="epfsr-check-icon">✓</span> 
                            <h4><?php _e( 'Exclude By Post Types', 'exclude-posts-from-search-results' ); ?></h4>
                        </div>
                        <div class="epfsr-check-description-wrap">
                            <div class="epfsr-check-description">
                                <span class="epfsr-check-icon">✓</span>
                                <p><?php _e( 'Exclude all posts of a post type from search results by just selecting the post type, no need to select individual posts. All the posts from selected post types will get excluded from the search results.', 'exclude-posts-from-search-results' ); ?></p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="epfsr-check-title">
                            <span class="epfsr-check-icon">✓</span> 
                            <h4><?php _e( 'Exclude Posts By Term(Category)', 'exclude-posts-from-search-results' ); ?></h4>
                        </div>
                        <div class="epfsr-check-description-wrap">
                            <div class="epfsr-check-description">
                                <span class="epfsr-check-icon">✓</span>
                                <p><?php _e( 'Exclude all posts of a specific Term from search results by just selecting the Term, no need to select individual posts for a specific Term. All the posts from selected Terms will get excluded from the search results.', 'exclude-posts-from-search-results' ); ?></p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="epfsr-check-title">
                            <span class="epfsr-check-icon">✓</span> 
                            <h4><?php _e( 'Exclude Posts By Author', 'exclude-posts-from-search-results' ); ?></h4>
                        </div>
                        <div class="epfsr-check-description-wrap">
                            <div class="epfsr-check-description">
                                <span class="epfsr-check-icon">✓</span>
                                <p><?php _e( 'Exclude all posts of a specific Author from search results, no need to select individual posts for a specific Term. All the posts from the selected Author will get excluded from the search results.', 'exclude-posts-from-search-results' ); ?></p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="epfsr-check-title">
                            <span class="epfsr-check-icon">✓</span> 
                            <span><?php _e( 'Timely', 'exclude-posts-from-search-results' ); ?> <a href="https://geekcodelab.com/contact/" target="_blank"><?php _e( 'Support', 'exclude-posts-from-search-results' ); ?></a> 24/7.</span>
                        </div>
                    </li>
                    <li>
                        <div class="epfsr-check-title">
                            <span class="epfsr-check-icon">✓</span> 
                            <span><?php _e( 'Regular Updates.', 'exclude-posts-from-search-results' ); ?></span>
                        </div>
                    </li>
                    <li>
                        <div class="epfsr-check-title">
                            <span class="epfsr-check-icon">✓</span> 
                            <span><?php _e( 'Well Documented.', 'exclude-posts-from-search-results' ); ?></span>
                        </div>
                    </li>
                </ul>
                <a href="https://geekcodelab.com/wordpress-plugins/exclude-posts-from-search-results-pro/" class="epfsr-sec-btn"><?php _e( 'Upgrade To Premium', 'exclude-posts-from-search-results' ); ?></a>
            </div>
            <?php
        }
    }
	new Gwsepfsr_Exclude_from_Search_Results_Settings();
}
?>