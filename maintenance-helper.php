<?php
/*
Plugin Name: Maintenance Helper
Plugin URI: http://4sure.com.au
Description: This plugin generates an email template that will be used to send updates to the client.
Version: 1.0
Author: 4sure Online
Author URI: http://4sure.com.au
License: GPL2
Network: true
*/

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
			add_shortcode( 'broken_links', array( $this, 'shortcode_broken_links' ) );
			add_shortcode( 'maintenance_month', array( $this, 'shortcode_maintenance_month' ) );
			add_shortcode( 'analytics_link', array( $this, 'shortcode_analytics_link' ) );
			add_shortcode( 'updates', array( $this, 'shortcode_updates' ) );
			add_shortcode( 'maintenancehelper', array( $this, 'shortcode_maintenancehelper' ) );
		}
	}

	public function maintenance_styles() {
		wp_enqueue_style( 'maintenance-styles', plugin_dir_url( __FILE__ ) . 'css/style.css' );
	}

	public function add_options_page() {
		add_menu_page( 'Maintenance Helper', 'Maintenance Helper', 'manage_options', 'maintenance-helper', array( $this, 'generate_mailchimp_markup' ), 'dashicons-clipboard' );
	}

	public function add_submenu_page(){
		add_submenu_page( 'settings.php', 'Maintenance Helper', 'Maintenance Helper', 'manage_options', 'maintenance-helper.php', array( $this, 'generate_mailchimp_markup' ), 'dashicons-clipboard' );
	}

	public function maintenance_settings() {
		register_setting( 'maintenance-fields', 'client_name' );
		register_setting( 'maintenance-fields', 'broken_links' );
		register_setting( 'maintenance-fields', 'maintenance_month' );
		register_setting( 'maintenance-fields', 'analytics_link' );
		register_setting( 'maintenance-fields', 'maintenancehelper' );
	}

	public function generate_mailchimp_markup() { ?>

		<div id="maintenance-helper" class="wrap">
			<h1>Maintenance Helper</h1>
			<?php
				$fields = $this->get_fields();
				echo $fields;
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
					Email<br /><br />
					<button id="copy-text" class="button button-primary" onclick="copyToClipboard('#content')">Copy to Clipboard</button>
					<p class="message" style="display: none;"><i>Copied to Clipboard</i></p>
					</th>
					<td><div id="content" class="content" style="background: #ffffff; padding: 20px;"><?= do_shortcode( get_option('maintenancehelper') ); ?></div></td>
				</tr>
			</table>
	    </div>

		<script>
		function copyToClipboard(element) {
			var $temp = jQuery("<div style='background: #ffffff;'>");
			jQuery("body").append($temp);
			$temp.attr("contenteditable", true)
				.html(jQuery(element).html()).select()
				.on("focus", function() { document.execCommand('selectAll',false,null); })
				.focus();
			document.execCommand("copy");
			$temp.remove();

			jQuery('.message').slideDown();
			setTimeout(function() {
				jQuery('.message').slideUp();
			}, 5000);
		}
		</script>
	<?php
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
					<th scope="row">Broken Links <br/ ><i>[broken_links]</i></th>
					<td><input type="text" name="broken_links" value="<?= esc_attr( get_option('broken_links') ) ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Select Month <br/ ><i>[maintenance_month]</i></th>
					<td>
						<?php $options = $this->get_months(); $value = get_option('maintenance_month'); ?>
						<select name="maintenance_month" value="<?= esc_attr( get_option('maintenance_month') ) ?>">
							<option value="" <?php if( get_option('maintenance_month') == '' ) { echo 'selected'; } ?>>Select Month</option>
							<?php foreach( $options as $month ) { ?>
							<option value=<?= $month ?> <?= $selected = $month == $value ? 'selected' : ''; ?>><?= $month; ?></option>
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
			<?php submit_button( 'Save and Generate' ); ?>
		</form>
	<?php
	}

	public function shortcode_client_name( $atts ) {
		return $name = get_option( 'client_name' );
	}

	public function shortcode_broken_links( $atts ) {
		return $links = get_option( 'broken_links' );
	}
	public function shortcode_maintenance_month( $atts ) {
		return $month = get_option( 'maintenance_month' );
	}

	public function shortcode_analytics_link( $atts ) {
		return $link = '<a href="'. get_option( 'analytics_link' ) .'" target="_blank">View Analytics</a>';
	}

	public function shortcode_maintenancehelper( $atts ) {
		return $content = get_option( 'maintenancehelper' );
	}
	public function shortcode_updates( $atts ) {
		return $updates = $this->get_maintenance_markup();
	}
}
add_action( 'plugins_loaded', array( 'Maintenance_Helper' , 'get_instance' ) );