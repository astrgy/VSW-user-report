<?php
/**
 * @package HCPPatientReport
 * @version 1.0.0
 */
/*
Plugin Name: HCP User Report
Plugin URI: http://wordpress.org/extend/plugins/hih-user-report/
Description: Export Users data and metadata to a csv file.
Version: 1.0.0
*/
load_plugin_textdomain( 'hih-user-report', false, basename( dirname( __FILE__ ) ) . '/languages' );
/**
 * Main plugin class
 *
 * @since 0.1
 **/
class HIHUserReport {
	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'init', array( $this, 'generate_csv' ) );
		add_filter( 'esp_exclude_data', array( $this, 'exclude_data' ) );
	}
	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_users_page( __( 'User Report', 'hih-user-report' ), __( 'User Report', 'hih-user-report' ), 'list_users', 'hih-user-report', array( $this, 'users_page' ) );
	}
	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function generate_csv() {
		if ( isset( $_POST['_wpnonce-pp-eu-export-users-users-page_export'] ) ) {
			check_admin_referer( 'pp-eu-export-users-users-page_export', '_wpnonce-pp-eu-export-users-users-page_export' );
			$args = array(
				'fields' => 'all_with_meta'
				// 'role' => 'subscriber'
			);
			add_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
			$users = get_users( $args );
			remove_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
			if ( ! $users ) {
				$referer = add_query_arg( 'error', 'empty', wp_get_referer() );
				wp_redirect( $referer );
				exit;
			}
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) )
				$sitename .= '.';
			$filename = $sitename . 'users.' . date( 'Y-m-d-H-i-s' ) . '.csv';
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
			$exclude_data = apply_filters( 'esp_exclude_data', array() );
			global $wpdb;
			$data_keys = array(
				'ID', 'user_login', 'user_pass',
				'user_nicename', 'user_email', 'user_url',
				'user_registered', 'user_activation_key', 'user_status',
				'display_name'
			);

			$meta_keys = $wpdb->get_results( "SELECT distinct(meta_key) FROM $wpdb->usermeta" );
			$meta_keys = wp_list_pluck( $meta_keys, 'meta_key' );
			$fields = array_merge( $data_keys, $meta_keys );
			$headers = array();
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field, $exclude_data ) )
					unset( $fields[$key] );
				else
					$headers[] = '"' . ucfirst( $field ) . '"';
			}


			$headers = str_replace("_"," ",$headers );
			echo implode( ',', $headers ) . "\n";
			foreach ( $users as $user ) {
				$data = array();
				foreach ( $fields as $key => $field ) {
     
					$value = isset( $user->{$field} ) ? $user->{$field} : '';
					$value = is_array( $value ) ? serialize( $value ) : $value;
				
					
					// Yes or No
					if ($value === "0" || $value === "1") :
            $value = str_replace( '1', 'Yes', $value );
            $value = str_replace( '0', 'No', $value );
          endif;
          
          // Dosing Schedule
		// 			if ($value === "2" || $value === "4") :
        //     $value = str_replace( '2', '2 Weeks', $value );
        //     $value = str_replace( '4', '4 Weeks', $value );
        //   endif;
          
          // Remove time from Dates
          // 2018-08-16 05:14:07 to 2018-08-16
          // 19 chars long, and starting 19xx or 20xx
          if (strlen($value) === 19 &&
              (strpos($value, '19') === 0 || strpos($value, '20' ) === 0) ) :
            $value = substr($value, 0, 10);
          endif;
          
          // clear null values
          if ( $value === "null" ) :
	            $value = ' ';
          endif;       
          
          $data[] = '"' . str_replace( '"', '""', $value ) . '"';
				}
				echo implode( ',', $data ) . "\n";
			}
			exit;
		}
	}
	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function users_page() {
		if ( ! current_user_can( 'list_users' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'hih-user-report' ) );
?>

<form method="post" action="" enctype="multipart/form-data">
<?php wp_nonce_field( 'pp-eu-export-users-users-page_export', '_wpnonce-pp-eu-export-users-users-page_export' ); ?>
  <table class="form-table">
	<tr valign="top">
	  <p class="submit text-center">
			<input type="hidden" name="_wp_http_referer" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
			<input type="submit" class="button-primary" value="<?php _e( 'Download', 'hih-user-report' ); ?>" />
	  </p>
	</tr>
  </table>
</form>


<?php
	}
	public function exclude_data() {
		$exclude = array('ID','_yoast_wpseo_profile_updated','Bio', 'user_registered','user_login', 'user_pass', 'user_nicename', 'user_url', 'user_activation_key', 'user_status', 'display_name', 'nickname', 'description', 'rich_editing', 'syntax_highlighting', 'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front', 'locale', 'wp_capabilities', 'wp_user_level', 'gform-entry-id', 'dismissed_wp_pointers', 'show_welcome_panel', 'default_password_nag', 'wp_dashboard_quick_press_last_post_id', 'community-events-location', 'wp_user-settings', 'wp_user-settings-time', 'show_try_gutenberg_panel', 'meta-box-order_dashboard', 'gform_recent_forms', 'managenav-menuscolumnshidden', 'metaboxhidden_nav-menus', 'nav_menu_recently_edited', 'edit_post_per_page', 'yoast_wpseo_profile_updated', 'tgmpa_dismissed_notice_wp-mail-smtp', 'closedpostboxes_dashboard', 'metaboxhidden_dashboard', 'wpseo_title', 'wpseo_metadesc', 'wpseo_noindex_author', 'wpseo_content_analysis_disable', 'wpseo_keyword_analysis_disable', 'googleplus', '_gform-update-entry-id', 'logo', 'bio', 'company', 'dismiss_user_registration_menu', 'website', 'expertise', 'session_tokens', 'street_address', 'address_line_2', 'suburb', 'state', 'postcode', 'pending', 'phone', 'address', 'city', 'country', 'users_per_page', 'closedpostboxes_post', 'metaboxhidden_post','metaboxhidden_page','meta-box-order_page', 'entry_id', 'gform-entry-id', 'wp_wpseo-upsell-notice', 'wp_wpseo-recalibration-meta-notification','manageuserscolumnshidden', 'wpseo-remove-upsell-notice', 'wp_yoast_notifications', 'Expertise', 'Logo', 'Address Line 2','Street Address', 'Suburb', 'Phone','closedpostboxes_page','_gform-entry-id');
		return $exclude;
	}
	public function pre_user_query( $user_search ) {
		global $wpdb;
		$where = '';
		if ( ! empty( $_POST['start_date'] ) )
			$where .= $wpdb->prepare( " AND $wpdb->users.user_registered >= %s", date( 'Y-m-d', strtotime( $_POST['start_date'] ) ) );
		if ( ! empty( $_POST['end_date'] ) )
			$where .= $wpdb->prepare( " AND $wpdb->users.user_registered < %s", date( 'Y-m-d', strtotime( '+1 month', strtotime( $_POST['end_date'] ) ) ) );
		if ( ! empty( $where ) )
			$user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1$where", $user_search->query_where );
		return $user_search;
	}
	private function export_date_options() {
		global $wpdb, $wp_locale;
		$months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( user_registered ) AS year, MONTH( user_registered ) AS month
			FROM $wpdb->users
			ORDER BY user_registered DESC
		" );
		$month_count = count( $months );
		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;
		foreach ( $months as $date ) {
			if ( 0 == $date->year )
				continue;
			$month = zeroise( $date->month, 2 );
			echo '<option value="' . $date->year . '-' . $month . '">' . $wp_locale->get_month( $month ) . ' ' . $date->year . '</option>';
		}
	}
}
new HIHUserReport; ?>