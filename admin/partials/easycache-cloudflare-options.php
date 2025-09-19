<?php
/**
 * Display cloudflare options of the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */

global $nginx_helper_admin;

$settings_save_args = array(
	'api_token',
	'zone_id',
	'default_cache_ttl',
	'easycache_cf_settings_nonce',
	'easycache_settings_save'
);

foreach ( $settings_save_args as $val ) {
	if ( isset( $_POST[ $val ] ) ) {
		$all_inputs[ $val ] = wp_strip_all_tags( $_POST[ $val ] );
	}
}

if ( isset( $all_inputs['easycache_settings_save'] ) && isset( $all_inputs['easycache_cf_settings_nonce'] ) && wp_verify_nonce( $all_inputs['easycache_cf_settings_nonce'], 'easycache_cf_settings_nonce' ) ) {
	unset( $all_inputs['easycache_cf_settings_nonce'] );
	unset( $all_inputs['easycache_settings_save'] );

	if ( ! $nginx_helper_admin || ! method_exists( $nginx_helper_admin, 'get_cloudflare_default_settings' ) ) {
		return;
	}

	$default_args = $nginx_helper_admin->get_cloudflare_default_settings();

	$args = wp_parse_args( $all_inputs, $default_args );

	update_site_option( 'easycache_cf_settings', $args );

	echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'nginx-helper' ) . '</p></div>';
}

if( isset( $nginx_helper_admin ) && method_exists( $nginx_helper_admin, 'handle_cf_cache_rule_update' ) ) {
	$nginx_helper_admin->handle_cf_cache_rule_update();
	
}


$ec_site_settings = $nginx_helper_admin->get_cloudflare_settings();
?>

	<div class="ec-cf-settings">
		<form id="post_form" method="post" action="#" name="easycache_cf_settings_form" class="clearfix">
			<?php wp_nonce_field( 'easycache_cf_settings_nonce', 'easycache_cf_settings_nonce' ); ?>
			<input type="hidden" value="1" name="easycache_settings_save"/>
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Cloudflare Settings', 'nginx-helper' ); ?></h2>
				<div class="inside">
					<table class="form-table rtnginx-table">
						<tbody>
						<tr valign="top">
							<th scope="row">
								<h4><?php esc_html_e( 'API Token', 'nginx-helper' ) ?></h4>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Cloudflare API Token', 'nginx-helper' ) ?>
									</legend>
								</fieldset>
								<input <?php echo $ec_site_settings['api_token_enabled_by_constant'] ? "disabled" : "" ?>
									name="api_token" id="cf_api_token" type="password" class="password-input"
									value="<?php echo esc_attr( $ec_site_settings['api_token'] ) ?>"/>
								<?php
								if ( ! $ec_site_settings['api_token_enabled_by_constant'] ): ?>

									<button type="button" class="password-show-hide-btn"><span
											class="dashicons dashicons-hidden password-input-icon"></span></button>
								<?php endif; ?>
								<p><?php
									if ( $ec_site_settings['api_token_enabled_by_constant'] ) {
										esc_html_e( 'Field enabled by constant.', 'nginx-helper' );
									} else {
										esc_html_e( 'Required permissions: Zone.Cache Purge.', 'nginx-helper' );
									}
									?>
								</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<h4><?php esc_html_e( 'Zone ID', 'nginx-helper' ) ?></h4>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Cloudflare Zone ID', 'nginx-helper' ) ?>
									</legend>
								</fieldset>
								<input type="text" name="zone_id" id="zone_id"
									   value="<?php echo esc_attr( $ec_site_settings['zone_id'] ) ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<h4><?php esc_html_e( 'Default Cache TTL (seconds)', 'nginx-helper' ) ?></h4>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Cloudflare Default Cache TTL', 'nginx-helper' ) ?>
									</legend>
								</fieldset>
								<input name="default_cache_ttl" id="default_cache_ttl" type="number"
									   value="<?php echo esc_attr( $ec_site_settings['default_cache_ttl'] ) ?>"/>
								<p class="description"> <?php esc_html_e( 'Default: 604800 seconds (1 week).', 'nginx-helper' ); ?> </p>
							</td>
						</tr>
						</tbody>
					</table>
				</div>

			</div>
			<?php
			submit_button( __( 'Save Changes', 'nginx-helper' ), 'primary large', 'easycache_cf_settings_save', true );
			?>
		</form>
	</div>

<?php
if ( $ec_site_settings['is_enabled'] ) {
	?>
	<form name="easycache_add_cache_rule" method="POST" >
		<?php
			wp_nonce_field( 'easycache_add_cache_rule_nonce', 'easycache_add_cache_rule_nonce' );
			submit_button( __( 'Setup Cache Rules', 'nginx-helper' ), 'secondary large', 'easycache_add_cache_rule_save', false );
		?>

	</form>
	
	<?php
}
?>
