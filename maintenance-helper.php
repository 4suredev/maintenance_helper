<?php
/*
Plugin Name: 4sure - Maintenance Helper
Plugin URI: http://4sure.com.au
Description: This plugin generates an email template that will be used to send updates to the client.
Version: 1.5.6
Author: 4sure Online
Author URI: http://4sure.com.au
License: GPL2
Network: true
*/
include_once( plugin_dir_path( __FILE__ ) . 'updater.php');
$updater = new Maintenance_helper_updater( __FILE__ ); 
$updater->set_username( '4suredev' ); 
$updater->set_repository( 'maintenance_helper' ); 
$updater->initialize(); 
if( ! class_exists( 'Maintenance_helper_updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}
class Maintenance_Helper {
	private static $instance;

	static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new Maintenance_Helper;

		return self::$instance;
	}
	private function __construct() {
		if( current_user_can( 'administrator' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'maintenance_styles' ) );
			add_action( 'admin_menu',         array( $this, 'add_options_page' ) );
			add_action( 'network_admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'admin_init', 	      array( $this, 'maintenance_settings' ) );

			add_shortcode( 'client_name', array( $this, 'shortcode_client_name' ) );
			add_shortcode( 'client_email', array( $this, 'shortcode_client_email' ) );
			add_shortcode( 'broken_links', array( $this, 'shortcode_broken_links' ) );
			add_shortcode( 'maintenance_month', array( $this, 'shortcode_maintenance_month' ) );
			add_shortcode( 'analytics_link', array( $this, 'shortcode_analytics_link' ) );
			add_shortcode( 'updates', array( $this, 'shortcode_updates' ) );
			add_shortcode( 'maintenancehelper', array( $this, 'shortcode_maintenancehelper' ) );
			add_shortcode( 'email_subject', array( $this, 'shortcode_email_subject' ) );
			add_shortcode( 'broken_links_count', array( $this, 'get_broken_links_count' ) );

			add_shortcode( 'notification_message', array( $this, 'shortcode_notification_message' ) );
			add_shortcode( 'bcc_email', array( $this, 'shortcode_bcc_email' ) );
			add_shortcode( 'site_name', array( $this, 'shortcode_sitename' ) );
		}
	}

	public function maintenance_styles() {
		wp_enqueue_style( 'maintenance-styles', plugin_dir_url( __FILE__ ) . 'css/style.css' );
	}

	public function add_options_page() {
		// add_menu_page( 'Maintenance Helper', 'Maintenance Helper', 'manage_options', 'maintenance-helper', array( $this, 'generate_mailchimp_markup' ), 'dashicons-clipboard' );
		add_submenu_page( 'tools.php', 'Maintenance Helper', 'Maintenance Helper', 'manage_options', 'maintenance-helper', array( $this, 'generate_mailchimp_markup' ) );
	}

	public function add_submenu_page(){
		add_submenu_page( 'settings.php', 'Maintenance Helper', 'Maintenance Helper', 'manage_options', 'maintenance-helper.php', array( $this, 'generate_mailchimp_markup' ), 'dashicons-clipboard' );
	}

	public function maintenance_settings() {
		register_setting( 'maintenance-fields', 'client_name' );
		register_setting( 'maintenance-fields', 'client_email' );
		register_setting( 'maintenance-fields', 'broken_links' );
		register_setting( 'maintenance-fields', 'maintenance_month' );
		register_setting( 'maintenance-fields', 'analytics_link' );
		register_setting( 'maintenance-fields', 'email_subject' );
		register_setting( 'maintenance-fields', 'bcc_email' );
		register_setting( 'maintenance-fields', 'site_name' );
		register_setting( 'maintenance-fields', 'maintenancehelper' );

		register_setting( 'notification-fields', 'notification_message' );
	}

	public function generate_mailchimp_markup() { ?>
		<div id="maintenance-helper" class="wrap">
			<h1>Maintenance Helper</h1>
			<!-- Additional -->
			<div class="maintenance-header">
				<div class="maintenance-header-inner">
					<p class="message" style="display: none;"><i>Copied to Clipboard</i></p>
					<div class="header-right">
						<button class="button button-primary header-btn" data-target="submit">Save and Generate</button>
						<button id="copy-text" class="button button-primary header-btn" onclick="copyToClipboard('#content')">Copy to Clipboard</button>
						<a href="mailto:<?= get_option('client_email'); ?>?<?php echo $bcc; ?>subject=<?php echo get_option('email_subject').' '.get_option('site_name').' - '.get_option('maintenance_month').' '.date('Y'); ?>" class="button button-primary send-email header-btn">Send Mail</a>
					</div>
				</div>
			</div>
			<!-- End -->
			<?php
				$fields = $this->get_fields();
				echo $fields;
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
					Email<br /><br />
					<p><i>Click the 'Copy to Clipboard' button to copy email content.</i></p>
					</th>
					<td><div id="content" class="content" style="background: #ffffff; padding: 20px;"><?= do_shortcode( get_option('maintenancehelper') ); ?></div></td>
				</tr>
				<tr valign="top">
					<th scope="row">
					<?php
						if(get_option('bcc_email')){
							$bccemail = get_option('bcc_email');
							$bcc = "bcc=".$bccemail."&";
						}
					?>
					</th>
				</tr>
			</table>
			<div class="notification-field">
				<h1>Custom Notification Message</h1>
				<?php 
					$notif_fields = $this->get_notifFields();
					echo $notif_fields;
				?>
			</div>
	    </div>

		<script>
		jQuery(function($){
			$("#maintenance-helper .header-btn[data-target=submit").click(function(){
				$("#maintenance-helper > form .save-generate #submit").trigger("click")
			});
		});
		function copyToClipboard(element) {
			var $temp = jQuery("<div style='background: #ffffff;'>");
			var content = jQuery("#maintenance-helper #content").html();
			jQuery("body").append($temp);
			
			//Clipboard API 
			$temp.attr("contenteditable", true)
				.html(jQuery(element).html()).select()
				.on("focus", function() { 
				  navigator.clipboard.write([
					new ClipboardItem({
					  "text/html": new Blob([content], { type: "text/html" }),
					  "text/plain": new Blob([content], { type: "text/plain" })
					})
				  ]);
				})
			.focus();
			$temp.remove();

			jQuery('.message').slideDown();
			setTimeout(function() {
				jQuery('.message').slideUp();
			}, 5000);
		}

		</script>
	<?php
	}

	static function custom_admin_notif() {
		$current = get_current_screen();

		if( get_option('notification_message') ) {
			if( $current->id === 'update-core' || $current->id === 'plugins' ) {
				echo "<div id='maintenance-notification' class='notice notice-warning'>".get_option('notification_message')."</div>";
			}
		}
		else {
			if( $current->id === 'update-core' || $current->id === 'plugins' ) {
				echo "<div id='maintenance-notification' class='notice notice-warning'><h3>Please do not run updates here!</h3><p><strong>Updates to WordPress core, themes and plugins are managed by 4sure Online as part of your maintenance plan.</strong> These updates are usually done once per month, and are thoroughly tested before being deployed to the live website.</p> <p>As plugins are third party software managed by a number of different developers, they do not have consistent or regular release cycles, so you may sometimes see some updates available when their releases come out in the period between the 4sure Online maintenance cycles.</p> <p>In the rare case that an update fixes a critical security issue, these updates are applied immediately.</p> <p>Any time spent addressing issues that arise from updates run by someone apart from 4sure Online <strong>will not be covered by your maintenance plan.</strong></p></div>";
			}
		}
	}

	public function get_maintenance_markup() {

		$markup = '';

		global $wp_version;

		$updates = get_core_updates();

		if ( $wp_version != $updates[0]->current ) {
			$markup .= 'We have upgraded WordPress Core:';
			$markup .= "<ul>";

			foreach( (array) $updates as $update ) {

				$markup .= "<li>From WordPress Version: " . esc_attr( $wp_version ) . " to WordPress ". $update->current . " on " . date( 'd F Y' ) . "</li>";
			}
			$markup .= "</ul>";
		}

		$plugins = get_plugin_updates();

		if ( ! empty ( $plugins ) ) {
			$markup .= "We have upgraded the following plugins on your site:";
			$markup .= "<ul>";
			foreach ( (array) $plugins as $plugin_file => $plugin_data ) {

				$details_url = self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_data->update->slug . '&section=changelog&TB_iframe=true&width=640&height=662');

				$markup .= "<li>";
				$markup .= $plugin_data->Name . ': ';
				$markup .= sprintf( __( 'Version %1$s to <a href="%2$s">%3$s</a>.' ), $plugin_data->Version, esc_url($details_url), $plugin_data->update->new_version );
				$markup .= "</li>";

			}
			$markup .= "</ul>";
		}

		$themes = get_theme_updates();

		if ( ! empty ( $themes ) ) {
			$markup .= "We have upgraded the following themes on your site:";
			$markup .= "<ul>";
			foreach ( (array) $themes as $theme_key => $theme ) {

				$markup .= "<li>";
				$markup .= $theme['Name'] . ': Version ' . $theme['Version'] . ' to '. $theme->update["new_version"] . '.';
				$markup .= "</li>";

			}
			$markup .= "</ul>";
		}

		return $markup;
	}

	public function get_months() {
		$months = array();
		
		for ($m=1; $m<=12; $m++) {
			$month = date('F', mktime(0,0,0,$m, 1, date('Y')));
			array_push( $months, $month );
		}

		return $months;
	}

	public function get_fields() { ?>
		<form method="post" action="options.php">
			<?php settings_fields('maintenance-fields'); ?>
			<?php do_settings_sections('maintenance-fields'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Client Name <br/ ><i>[client_name]</i></th>
					<td><input type="text" name="client_name" value="<?= esc_attr( get_option('client_name') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Site Name <br/ ><i>[site_name]</i></th>
					<td><input type="text" name="site_name" value="<?= esc_attr( get_option('site_name') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Client Email <br/ ><i>[client_email]</i></th>
					<td><input type="text" name="client_email" value="<?= esc_attr( get_option('client_email') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">BCC Email <br/ ><i>[bcc_email]</i></th>
					<td><input type="text" name="bcc_email" value="<?= esc_attr( get_option('bcc_email') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Broken Links <br/ ><i>[broken_links]</i></th>
					<?php $links = $this->get_broken_links_count() >= 0 ? $this->get_broken_links_count() : esc_attr( get_option('broken_links') ); //Options API as fallback ?>
					<td><input type="text" name="broken_links" value="<?= $links ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Select Month <br/ ><i>[maintenance_month]</i></th>
					<td>
						<?php $currmonth = date('F'); ?>
						<?php $options = $this->get_months(); $value = get_option('maintenance_month'); ?>
						<select name="maintenance_month" value="<?= esc_attr( $value ); ?>">
							<option value="" <?php if( $value  == '' ) { echo 'selected'; } ?>>Select Month</option>
							<?php foreach( $options as $month ) { ?>
							<option value=<?= $month ?> 
							<?php
								if($month == $currmonth){ 
									echo "selected";
								}
							?>> 
							<?= $month; ?>							 
							</option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Analytics Link <br/ ><i>[analytics_link]</i></th>
					<td><input type="text" name="analytics_link" value="<?= esc_attr( get_option('analytics_link') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Updates <br/ ><i>[updates]</i></th>
					<td><?= $this->get_maintenance_markup(); ?></td>
				</tr>
				<tr valign="top">
					<th scope="row">Email Subject <br/ ><i>[email_subject]</i></th>
					<td><input type="text" name="email_subject" value="<?= esc_attr( get_option('email_subject') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Email Content</th>
					<td>
					<?php
					$content = !empty( get_option('maintenancehelper') ) ? get_option( 'maintenancehelper' ) : '';
					$editor_id = 'maintenancehelper';
					$settings = array(
						'wpautop'		=> false,
						'media_buttons' => true,
					);
					wp_editor( $content, $editor_id, $settings );
					if( isset( $_POST['maintenancehelper'] ) ) {
						update_option( 'maintenancehelper', $_POST['maintenancehelper'] );
					}
					?>
				</td>
				</tr>
			</table>
			<div class="save-generate">
				<?php submit_button( 'Save and Generate' ); ?>
			</div>
		</form>
	<?php
	}
	
	public function get_notifFields(){ ?>
		<form method="POST" action="options.php">
			<?php settings_fields('notification-fields'); ?>
			<?php do_settings_sections('notification-fields') ?>
			<div class="row">
				<?php 
					$notif = !empty( get_option('notification_message') ) ? get_option( 'notification_message' ) : '';
					$editor_id = 'notification_message';
					$settings = array(
						'wpautop'		=> false,
						'media_buttons' => false,
					);
					wp_editor( $notif, $editor_id, $settings );
					if( isset( $_POST['notification_message'] ) ) {
						update_option( 'notification_message', $_POST['notification_message'] ); 
					}
				?>
				<?php submit_button( 'Save' ) ?>
			</div>
		</form>
	<?php
	}

	/**
	 * Get the broken links count from Broken Links plugin table
	 */
	private function get_broken_links_count() {
		if( class_exists( 'blcConfigurationManager' ) ) {
			global $wpdb;
	
			//get links with 'broken' status and are NOT dismissed
			$q = "SELECT * FROM {$wpdb->prefix}blc_links WHERE broken = 1 AND dismissed != 1";
	
			return $wpdb->query( $q );
		} else {
			return -1;
		}
	}

	public function shortcode_client_name( $atts ) {
		return $name = get_option( 'client_name' );
	}

	public function shortcode_client_email( $atts ) {
		return $name = get_option( 'client_email' );
	}

	public function shortcode_broken_links( $atts ) {
		$links = $this->get_broken_links_count() >= 0 ? $this->get_broken_links_count() : get_option( 'broken_links' ); 
		return $links;
	}
	public function shortcode_maintenance_month( $atts ) {
		return $month = get_option( 'maintenance_month' );
	}

	public function shortcode_analytics_link( $atts ) {
		return $link = '<a href="'. get_option( 'analytics_link' ) .'" target="_blank">View Analytics</a>';
	}

	public function shortcode_email_subject( $atts ) {
		return $content = get_option( 'email_subject' );
	}
	
	public function shortcode_maintenancehelper( $atts ) {
		return $content = get_option( 'maintenancehelper' );
	}

	public function shortcode_bcc_email ( $atts ) {
		return $name = get_option('bcc_email');
	}

	public function shortcode_sitename ( $atts ) {
		return $sitename = get_option('site_name');
	}

	public function shortcode_updates( $atts ) {
		return $updates = $this->get_maintenance_markup();
	}

	public function shortcode_notification_message ( $atts ) {
		return $notif = $this->get_option('notification_message');
	}
}
add_action( 'plugins_loaded', array( 'Maintenance_Helper' , 'get_instance' ) );

add_action( 'admin_notices', array( 'Maintenance_Helper' , 'custom_admin_notif' ) );