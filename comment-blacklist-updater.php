<?php
/*
 Plugin Name: Comment Blacklist Updater
 Plugin URI: https://apasionados.es/blog/
 Description: Update the "Comment Blacklist" in Settings / Discussion with a list terms of a remote or local source. By default it get's the data from Github (wordpress-comment-blacklist by Grant Hutchinson) but you can also get them from any URL or from a local blacklist.txt file.
 Author: Apasionados
 Author URI: https://apasionados.es/
 Version: 1.2.2
 Text Domain: comment-blacklist-updater
 Domain Path: /languages
 License: GPL v3
 License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
*/
/*
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if( ! defined( 'CBU_MANAGER_BASE ' ) ) {
	define( 'CBU_MANAGER_BASE', plugin_basename(__FILE__) );
}
if( ! defined( 'CBU_MANAGER_DIR' ) ) {
	define( 'CBU_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
}
if( ! defined( 'CBU_MANAGER_VER' ) ) {
	define( 'CBU_MANAGER_VER', '1.0.1' );
}
	
	function comment_blacklist_updater_admin_enqueue($hook_suffix) {
		if($hook_suffix == 'options-discussion.php') {
			echo '<style>.comment-blacklist-updater-source, .comment-blacklist-updater-local, .comment-blacklist-updater-exclude {background-color:#fff4f4;} </style>';
		}
	}
	add_action('admin_enqueue_scripts', 'comment_blacklist_updater_admin_enqueue');
	

class CBU_Manager_Core
{
	/* Static property to hold our singleton instance @var $instance */
	static $instance = false;
	/* constructor */
	private function __construct() {
	// Check if the current user is an admin
		add_action		(	'plugins_loaded',					array(  $this,  'textdomain'					)			);
		add_action		(	'admin_init',						array(	$this,	'load_settings'					)			);
		add_action		(	'admin_init',						array(	$this,	'update_blacklist_admin'		)			);
		add_action		(	'admin_init',						array(	$this,	'update_blacklist_manual'		)			);
		add_action		(	'admin_notices',					array(	$this,	'manual_update_notice'			)			);
		register_activation_hook	(	__FILE__,				array(	$this,	'run_initial_process'			)			);
		register_deactivation_hook	(	__FILE__,				array(	$this,	'remove_settings'				)			);
	}

	/* If an instance exists, this returns it.  If not, it creates one and retuns it. */ /* @return $instance */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function textdomain() { /* PLUGINS_LOADED: Load textdomain for localization */
		load_plugin_textdomain(  'comment-blacklist-updater', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	 
	public function run_initial_process() { /* ADMIN_INIT: takes any existing terms in the native blacklist field and copies them over to our new 'local' field and then the update is run for the first time */
		$current	= get_option( 'blacklist_keys' );
		if ( $current ) {
			update_option( 'blacklist_local', $current );
		}
		self::blacklist_process_loader(); // run the updater
	}
	
	public function remove_settings() { /* remove custom settings and delete the transients on plugin deactivation */
		// delete options
		delete_option( 'blacklist_local' );
		delete_option( 'blacklist_exclude' );
		delete_option( 'blacklist_last_update' );
		delete_option( 'blacklist_github_source_updated' );
		delete_option( 'use_wordpress_comment_blacklist_splorp' );
		delete_option( 'apa_another_blacklist_url' );
		// delete transients
		delete_transient( 'blacklist_update_process' ); // deletes _transient_ and _transient_timeout_ transient
		delete_transient( 'blacklist_github_update_check' ); // deletes _transient_ and _transient_timeout_ transient
	}

	public function load_settings() { /* Register new settings and load settings fields */
		// add blacklist info, custom list, and exclusion field setting with sanitation callback
		register_setting( 'discussion', 'blacklist_local', array( $this, 'blacklist_data_sanitize' ) );
		register_setting( 'discussion', 'blacklist_exclude', array( $this, 'blacklist_data_sanitize' ) );
		register_setting( 'discussion', 'blacklist_last_update' );
		register_setting( 'discussion', 'blacklist_github_source_updated' );
		register_setting( 'discussion', 'use_wordpress_comment_blacklist_splorp' );
		register_setting( 'discussion', 'apa_another_blacklist_url' );
		add_settings_field( 'blacklist-source', __( 'Blacklist Source', 'comment-blacklist-updater' ), array( $this, 'source_field' ), 'discussion', 'default', array( 'class' => 'comment-blacklist-updater-source' ) ); // load the source list field
		add_settings_field( 'blacklist-local', __( 'Local Blacklist', 'comment-blacklist-updater' ), array( $this, 'local_field' ), 'discussion', 'default', array( 'class' => 'comment-blacklist-updater-local' ) ); // load the custom list field
		add_settings_field( 'blacklist-exclude', __( 'Excluded Terms', 'comment-blacklist-updater' ), array( $this, 'exclude_field' ), 'discussion', 'default', array( 'class' => 'comment-blacklist-updater-exclude' ) ); // load the exclusion field
	}

	
	public function source_field() { /* Field to be added to the discussion settings for listing blacklist source URLs. */ /* @return HTML the data field for the list */
		$timezone_string = get_option( 'timezone_string' );
        date_default_timezone_set( $timezone_string );
		wp_nonce_field('apa_comment_blacklist_updater_nonce', 'apa_comment_blacklist_updater_action');
		echo '<fieldset>';
			echo '<legend id="blacklist-source" class="screen-reader-text"><span>' . __( 'Blacklist Source', 'comment-blacklist-updater' ) . '</span>';
		echo '</legend>';
		echo '<p>';
			echo '<label>' . __( 'Data from the sources below will be loaded into the comment blacklist automatically.', 'comment-blacklist-updater' ) . '</label>';
			echo '<span style="float:right;"><i>' . __( 'Settings added by the <strong>Comment Blacklist Updater</strong> plugin', 'comment-blacklist-updater' ) . '</i></span>';
		echo '</p>';
		$upload_dir = wp_upload_dir(); // Check if there is a local blacklist.txt file in the uploads folder.
		$local_blacklist = $upload_dir['baseurl'] . '/blacklist.txt';
		$cbu_response = wp_remote_get( $local_blacklist );
		$cbu_http_status = wp_remote_retrieve_response_code( $cbu_response );
		if ($cbu_http_status==200) {
			$local_blacklist_check_if_exists = ' ' . __( 'There is a local blacklist file at: ', 'comment-blacklist-updater' ) . '<a href="' . $upload_dir['baseurl'] . '/blacklist.txt' . '" target="_blank">' . $upload_dir['baseurl'] . '/blacklist.txt' . '</a>' . '.';
		} else {
			$local_blacklist_check_if_exists = ' ' . __( 'There is no local blacklist at: ', 'comment-blacklist-updater' ) . $upload_dir['baseurl'] . '.';
		}
		echo '<p>&nbsp;</p><p>' . __( 'If you want to include a <strong>local blacklist</strong> for the site, you can upload a blacklist.txt file to the UPLOADS folder and it will also be taken into account. The blacklist.txt file has to be in the root of the UPLOADS folder; it will not be recognized if it\'s for example in /uploads/2025/12/ and the file has to be accesible via http/https (if the access to the file is protected it can\'t be used).', 'comment-blacklist-updater' ) . $local_blacklist_check_if_exists . '</p>';

		if ( get_option( 'use_wordpress_comment_blacklist_splorp' ) === 'show') {
			$use_wordpress_comment_blacklist_splorp = 'checked';
		} else {
			$use_wordpress_comment_blacklist_splorp = '';
		}
		echo '<p>' . '<label for "use_wordpress_comment_blacklist_splorp"><input type="checkbox" id="use_wordpress_comment_blacklist_splorp" name="use_wordpress_comment_blacklist_splorp" value="' . 'show' .'" ' . $use_wordpress_comment_blacklist_splorp . '>' . __( 'Do you want to use the <strong>blacklist of <a href="http://www.splorp.com/" target="_blank">Grant Hutchinson</a></strong> which is maintained on <a href="https://github.com/splorp/wordpress-comment-blacklist/">Github</a> (check if you want to use it)?', 'comment-blacklist-updater' ) . '</label>';
		echo ' <i>' . __( 'Please keep in mind that if there is no other blacklist source defined, this will be used as default, even if it\'s not selected.', 'comment-blacklist-updater' ) . '</i></p>';
		echo '<p>' . __( 'If you want to <strong>use another blacklist</strong>, please paste the URL here: ', 'comment-blacklist-updater' ) . '</p>';
		echo '<p><input type="text" id="apa_another_blacklist_url" size="100" name="apa_another_blacklist_url" value="' . esc_textarea( get_option( 'apa_another_blacklist_url' ) ) . '" />';
		$url = get_option( 'apa_another_blacklist_url' );
		if ( ! empty ( $url ) ) {
			if(preg_match( '/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$url)){
				echo ' ' . __( 'The URL looks ok (please keep in mind that here we don\'t check if it exists, just if the URL looks ok).', 'comment-blacklist-updater' ) . '</p>';
			} else {
				echo ' ' . __( 'The URL doesn\'t seem to be valid. Please check the URL.', 'comment-blacklist-updater' ) . '</p>';
			}
		} else {
			echo '</p>';
		}

		if ( function_exists( 'rkv_cblm_replace_blacklist_sources' ) ) {
			$rkv_cblm_replace_blacklist_sources_exists = ' ' . __( 'The "rkv_cblm_replace_blacklist_sources" function exists so there is done some filtering on the blacklist sources. Please check your functions.php to see if the function adds another blacklist or replaces all of them', 'comment-blacklist-updater' );
		} else {
			$rkv_cblm_replace_blacklist_sources_exists = ' ' . __( 'We can\'t find the "rkv_cblm_replace_blacklist_sources" function on your WordPress installation.', 'comment-blacklist-updater' );
		}
		echo '<p>&nbsp;</p><p>' . __( 'P.D. You can also use the <strong>filter "cblm_sources"</strong> to replace all the blacklists or to add more. If you replace all blacklists with the filter, the above settings will be ignored.', 'comment-blacklist-updater' ) . $rkv_cblm_replace_blacklist_sources_exists . '</p>';

		echo '<p>&nbsp;</p><p><strong>' . __( 'These are the blacklist sources that are used to update the COMMENT BLACKLIST', 'comment-blacklist-updater' ) . ":</strong>";
		$sources	= self::blacklist_sources(); // fetch the sources
		if ( ! $sources || empty( $sources ) ) { // display message if sources are empty
			echo '<p class="description">' . __( 'No blacklist sources have been defined.', 'comment-blacklist-updater' ) . '</p>';
		}
		echo '<ol>';
		foreach( (array) $sources as $source ) { // loop through the sources and display a list with icon to view
			if ( $source == 'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt' ) {
				if( false === get_transient( 'blacklist_github_update_check' ) || empty( get_option('blacklist_github_source_updated') )  ) {
					$cbu_response = wp_remote_get( 'https://api.github.com/repos/splorp/wordpress-comment-blacklist' );
					$cbu_http_status = wp_remote_retrieve_response_code( $cbu_response );
					if ($cbu_http_status==200) {
						$cbu_body = wp_remote_retrieve_body( $cbu_response );
						$cbu_response2 = ( json_decode($cbu_body, true) );
						$timestamp = $cbu_response2['pushed_at'];
						if ($timestamp != -1) { //otherwise unknown
							$friendly_date = date_i18n( get_option('date_format'), strtotime( $timestamp ) );
							$friendly_time = date_i18n( get_option('time_format'), strtotime( $timestamp ) );
						}
						update_option( 'blacklist_github_source_updated', $timestamp );
						$live_update = 'YES';
					}
				} else {
					$friendly_date = date_i18n( get_option('date_format'), strtotime( get_option('blacklist_github_source_updated') ) );
					$friendly_time = date_i18n( get_option('time_format'), strtotime( get_option('blacklist_github_source_updated') ) );
					$live_update = 'NO';
				}
				if ( empty ( $friendly_date ) ) { $friendly_date = 'UNKNOWN'; };
				if ( empty ( $friendly_time ) ) { $friendly_time = 'UNKNOWN'; };
				if ( $friendly_date == date_i18n( get_option('date_format'), strtotime("now") ) ) {
					$listupdated = ' (' . __( 'Blacklist source updated today', 'comment-blacklist-updater' ) . ')' . ' <!–– ' . $live_update . ' -->';
				} else {
					$listupdated = ' (' . __( 'Blacklist source updated on', 'comment-blacklist-updater' ) . ': ' . $friendly_date . ' ' . $friendly_time . ')' . ' <!––' . $live_update . '-->';
				}					
				echo '<li>' . esc_url( $source ) . '&nbsp;<a class="imgedit-help-toggle" href="' . esc_url( $source ) . '" title="' . __( 'View external source', 'comment-blacklist-updater' ) . '" target="_blank"><span class="dashicons dashicons-external"></span></a>&nbsp;' . $listupdated . '</li>';
				set_transient( 'blacklist_github_update_check', 1, apply_filters( 'cblm_update_schedule', DAY_IN_SECONDS ) ); // set transient to only check once a day for the last update of the Github list
			} else {
				$cbu_response = wp_remote_get( $source );
				$cbu_http_status = wp_remote_retrieve_response_code( $cbu_response );
				if ($cbu_http_status==200) {
					$timestamp = wp_remote_retrieve_header( $cbu_response, 'last-modified' );
					if ($timestamp != -1) { //otherwise unknown
						$friendly_date = date_i18n( get_option('date_format'), strtotime( $timestamp ) );
						$friendly_time = date_i18n( get_option('time_format'), strtotime( $timestamp ) );
					} else {
						$friendly_date = __( 'UNKNOWN', 'comment-blacklist-updater' );
						$friendly_time = __( 'UNKNOWN', 'comment-blacklist-updater' );
					}
					if ( $friendly_date == date_i18n( get_option('date_format'), strtotime( "now" ) ) ) {
						$listupdated = ' (' . __( 'Blacklist source updated today', 'comment-blacklist-updater' ) . ')';
					} else {
						$listupdated = ' (' . __( 'Blacklist source updated on', 'comment-blacklist-updater' ) . ': ' . $friendly_date . ' ' . $friendly_time . ')';
					}
					echo '<li>' . esc_url( $source ) . '&nbsp;<a class="imgedit-help-toggle" href="' . esc_url( $source ) . '" title="' . __( 'View external source', 'comment-blacklist-updater' ) . '" target="_blank"><span class="dashicons dashicons-external"></span></a>&nbsp;' . $listupdated . '</li>';
				} else {
					echo '<li>' . esc_url( $source ) . '&nbsp;<a class="imgedit-help-toggle" href="' . esc_url( $source ) . '" title="' . __( 'View external source', 'comment-blacklist-updater' ) . '" target="_blank"><span class="dashicons dashicons-external"></span></a>&nbsp;' . '(ERROR trying to access file: ' . $curl_http_status . ')</li>';
				}
			}
		}
		echo '</ol>';

		$transient_timeout_blacklist_update_process = get_option ( '_transient_timeout_blacklist_github_update_check' );
		//var_dump($transient_timeout_blacklist_update_process);
		echo '<p id="run-manual-update">' . __( 'Blacklist updated on', 'comment-blacklist-updater' ) . ': ' . date_i18n( get_option('date_format'), get_option( 'blacklist_last_update' ) ) . ' ' . date_i18n( get_option('time_format'), get_option( 'blacklist_last_update' ) ) . '. ' . __( 'Automatic updates are performed every 24 hours', 'comment-blacklist-updater' ) . ', ' . __( 'Next automatic update will be on', 'comment-blacklist-updater' ) . ': ' . date_i18n( get_option('date_format'), $transient_timeout_blacklist_update_process ) . ' ' . date_i18n( get_option('time_format'), $transient_timeout_blacklist_update_process ) . '. ' . __( 'If you want you can run a manual update now', 'comment-blacklist-updater' ) . ': </p>';
		$apa_comment_blacklist_updater_nonce_var = wp_create_nonce( 'apa_comment_blacklist_updater_nonce_updater' );
		echo '<a class="button button-secondary" href=" ' . admin_url( 'options-discussion.php' ) . '?cblm-update=manual&nonce-cblm-update-manual=' . $apa_comment_blacklist_updater_nonce_var . '">' . __( 'Run manual update', 'comment-blacklist-updater' ) . '</a>'; // Show button for manual updates
	}

	public function local_field() { /* Field to be added to the discussion settings for user sourced terms to allow remote updates to be overwritten without losing local changes */ /* @return HTML the data field for user terms */
		echo '<fieldset>';
			echo '<legend class="screen-reader-text"><span>' . __( 'Local Blacklist', 'comment-blacklist-updater' ) . '</span>';
		echo '</legend>';
		echo '<p>';
			echo '<label for="blacklist_local">' . __( 'Any terms entered below will be added to the data retrieved from the blacklist sources but only when the update is done (either manually or automatically). If you add information here, save and the run a manual update. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'comment-blacklist-updater' ) . '</label>';
		echo '</p>';
		echo '<p>';
			echo '<textarea id="blacklist_local" class="large-text code" cols="50" rows="6" name="blacklist_local">'. esc_textarea( get_option( 'blacklist_local' ) ) . '</textarea>';
		echo '</p>';
	}
	public function exclude_field() { /* Field to be added to the discussion settings for terms to be excluded from any remote file source */ /* @return HTML the data field for excluded terms */
		echo '<fieldset>';
			echo '<legend class="screen-reader-text"><span>' . __( 'Excluded Terms', 'comment-blacklist-updater' ) . '</span>';
		echo '</legend>';
		echo '<p>';
			echo '<label for="blacklist_exclude">' . __( 'Any terms entered below will be excluded from the blacklist updates but only when the update is done (either manually or automatically). <strong>If you add information here, save and the run a manual update</strong>. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'comment-blacklist-updater' ) . '</label>';
		echo '</p>';
		echo '<p>';
			echo '<textarea id="blacklist_exclude" class="large-text code" cols="50" rows="6" name="blacklist_exclude">'. esc_textarea( get_option( 'blacklist_exclude' ) ) . '</textarea>';
		echo '</p>';
	}

	public function blacklist_data_sanitize( $input ) { /* sanitize the user data list inputs (exclusion and local) */ /* @param  string $input the data entered in a settings field */ /* @return string $input the exclude list sanitized */
		return stripslashes( $input );
	}
	
	public function update_blacklist_admin() { /* Update function run automatically via admin */
		// check for the transient
		if( false === get_transient( 'blacklist_update_process' ) ) {
			self::blacklist_process_loader();
			update_option( 'blacklist_last_update', strtotime("now") );
		}
	}

	public function update_blacklist_manual() {	/* Manual update function run via click on button */
		if ( ! isset( $_REQUEST['cblm-update'] ) || isset( $_REQUEST['cblm-update'] ) && $_REQUEST['cblm-update'] !== 'manual' ) { // check for query string
			return;
		}
		$apa_nonce_check = $_REQUEST['nonce-cblm-update-manual'];
		if (!current_user_can('manage_options') || !wp_verify_nonce($apa_nonce_check, 'apa_comment_blacklist_updater_nonce_updater')) {
			wp_die('You do not have sufficient permissions to access this page.');
		}
		self::blacklist_process_loader(); // run updater
		update_option( 'blacklist_last_update', strtotime("now") );
		$redirect	= admin_url( 'options-discussion.php' ) . '?cblm-update=success'; // redirect to query string after update
		wp_redirect( $redirect );
		exit();
	}

	public function manual_update_notice() { /* Display success message after manual update */ /* @return HTML success message */
		if ( ! isset( $_REQUEST['cblm-update'] ) || isset( $_REQUEST['cblm-update'] ) && $_REQUEST['cblm-update'] !== 'success' ) { // check for our query string
			return;
		}
		echo '<div id="setting-error-settings_updated" class="updated settings-error">';
			echo '<p><strong>' . __( 'Blacklist terms were updated successfully.', 'comment-blacklist-updater' ) . '</strong></p>';
		echo '</div>';
	}
	
	static function blacklist_process_loader() { /* The function for updating the blacklist. First it fetches the remote blacklist data; then filters it against our exclusion list and last appends the items in our local list. Once completed, the blacklist_keys option is updated. */
		$data	= self::fetch_blacklist_data(); // run data fetch function
		if ( ! $data || empty( $data ) ) { // return if we have no data
			return;
		}
		$list	= self::create_blacklist_string( $data ); // handle the exclusion comparison and convert it into a string
		if ( ! $list || empty( $list ) ) { // return if we have no list
			return;
		}
		update_option( 'blacklist_keys', $list ); // update the option
		set_transient( 'blacklist_update_process', 1, apply_filters( 'cblm_update_schedule', 60*60*24 ) ); // set transient
		return;
	}
	
	static function fetch_blacklist_data() { /* Fetch the data from each of our blacklist sources, parse and clean it, then return it */ /* @return array $data a merged array of the combined data lists */
		$sources	= self::blacklist_sources(); // fetch blacklist files
		if ( ! $sources || empty( $sources ) ) { // return if our source is empty
			return;
		}
		$data	= ''; // set empty item for appending data source(s)
		foreach( $sources as $source ) { // loop through and fetch each data source
			$data	.= self::parse_data_source( esc_url( $source ) )."\n";
		}
		if ( ! $data ) { // return if no data exists
			return;
		}
		$data	= self::datalist_clean( $data ); // run it through the cleaner
		return $data; // send back data
	}
	
	static function create_blacklist_string( $data ) { /* Take array of terms and run it against the exclusion list (if one exists) and then join it into a single string with proper line breaks */ /* @param array $data an array of all the terms to be added */ /* @return string the data in a single string with line breaks */
		$exclude	= get_option( 'blacklist_exclude' ); // check for the exclude list
		if ( empty( $exclude ) ) { // if we don't have exclusions, merge it and send it back
			return self::datalist_merge( $data );
		}
		$exclude_array	= self::datalist_clean( $exclude ); // if we have exclusions, run comparisons to the exclude list
		$merged_array	= self::datalist_compare( $data, $exclude_array ); // run  comparison function
		$listdata		= self::datalist_merge( $merged_array ); // merge the existing data and filter duplicates
		return stripslashes( $listdata ); // run one final sanitation on it and return
	}

	static function blacklist_sources() { /* Load the blacklist files(with GitHub as default) and return it. Includes filter to change or add additional sources. */ /* @return array $lists blacklist source(s) */
		$lists	= array();
		if ( get_option( 'use_wordpress_comment_blacklist_splorp ' ) === 'show') { // Add SPLORP Blacklist if the checkbox is checked.
			$lists[] = 'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt';
		}
		if ( ! empty( get_option( 'apa_another_blacklist_url' ) ) ) { // Check if there is another URL with a blacklist defined
			$url = get_option( 'apa_another_blacklist_url' );
			if(preg_match( '/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$url)){
				$lists[] = get_option( 'apa_another_blacklist_url' );
			}
			//$lists[] = get_option( 'apa_another_blacklist_url' );
		}
		$upload_dir = wp_upload_dir(); // Check if there is a local blacklist.txt file in the uploads folder.
		$local_blacklist = $upload_dir['baseurl'] . '/blacklist.txt';
		$args = array( 'redirection' => 0 ); 
		$cbu_response = wp_remote_get( $local_blacklist, $args );
		$cbu_http_status = wp_remote_retrieve_response_code( $cbu_response );
		//$cbu_redirection = wp_remote_retrieve_headers( $cbu_response );
		//var_dump ($cbu_redirection);
		if ( $cbu_http_status==200 ) {
			$lists[] = $local_blacklist;
		}
		if ( empty( $lists ) ) { // If the list is empty we use the SPLORP Blacklist by default.
			$lists[] = 'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt';
		}
		return apply_filters( 'cblm_sources', (array) $lists );
	}
	
	static function parse_data_source( $source ) { /* Take a data source and attempt to retrieve the data from it */ /* @param string $source the actual data source */ /* @return mixed $data an array or string of data based on source method */
		if ( ! $source ) { // return if we have no data to parse
			return;
		}
		$result	= ''; // set result blank to begin
		$args	= array( // set args for wp_remote_get
			'sslverify' => false
		);
		$response   = wp_remote_get( $source, $args ); // run the get action itself
		if( is_wp_error( $response ) ) { // error. return.
			return;
		}
		//if ( ! $response['headers']['status_code'] = '200' ) { return; }
		$result	= wp_remote_retrieve_body( $response ); // pull out the body result
		if ( empty( $result ) ) { // return if it's empty
			return;
		}
		$result	= apply_filters( 'cblm_parse_data_result', $result, $source ); // add filter for some other parse method we aren't aware of
		if ( $result && is_array( $result ) ) { // convert it to a list if it's an array by chance
			$result	= implode( "\n", $result );
		}
		if ( ! $result ) { // if a method failed, return
			return;
		}
		return trim( $result ); // send it back trimmed
	}
	
	static function datalist_clean( $text ) { /* Runs through the list data and makes sure the line breaks are done properly, which is due to how Windows servers store stuff. Then explodes it into an array for various comparison functions later */ /* @param string $text the actual data file */ /* @return array $data a cleaned up array line break style */
		$data	= preg_replace( '/\n$/', '', preg_replace( '/^\n/', '', preg_replace( '/[\r\n]+/', "\n", $text ) ) );
		return explode( "\n", $data );
	}
	
	static function datalist_compare( $source, $compare ) { /* Compare two arrays and remove any matching elements */ /* @param array $source the source array */ /* @param array $compare the array to run the comparison against */ /* @return array the resulting array */
		return array_diff( $source, $compare ); // run our comparisons and return
	}
	
	static function datalist_merge( $data ) { /* Fetch existing local blacklist and merge it to the update, then filter out duplicates */ /* @param array $data the blacklist data array */ /* @return string the merged list in a line break string */
		$local	= get_option( 'blacklist_local' ); // get local data
		if ( ! empty( $local ) ) { // run through the cleaning filter if local entries exists
			$local	= self::datalist_clean( $local );
		}
		$local	= ! empty( $local ) ? (array) $local : array(); // ensure proper array casting
		$listmerge	= array_merge( $local, $data ); // merge it to a single array
		$listunique	= array_unique( $listmerge ); // filter our uniques
		return implode( "\n", $listunique ); // implode it back to a list and return it
	}
}

$CBU_Manager_Core = CBU_Manager_Core::getInstance(); // Instantiate class
