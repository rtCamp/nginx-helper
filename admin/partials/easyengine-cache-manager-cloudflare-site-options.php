<?php
/**
 * Display the Cloudflare settings of one site on multisite.
 *
 * A site uses the network's Cloudflare settings unless a super admin sets its own here, for example because
 * it is in another Cloudflare zone. The saved API token is never shown again: an empty field keeps it.
 * Any site admin can set up the cache rule of their own site.
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */

global $nginx_helper_admin;

if ( ! $nginx_helper_admin || ! is_multisite() || ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'nginx-helper' ) );
}

// Changing the credentials is for super admins only, as they include an API token.
$ec_can_configure = current_user_can( 'manage_network_options' );
$ec_option        = Nginx_Helper_Admin::CF_SITE_OPTION;

if ( $ec_can_configure && isset( $_POST['ec_cf_site_settings_save'], $_POST['ec_cf_site_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_cf_site_settings_nonce'] ) ), 'ec_cf_site_settings_nonce' ) ) {
	if ( ! empty( $_POST['ec_cf_use_network'] ) ) {
		delete_option( $ec_option );
		$ec_saved_message = __( 'This site now uses the network settings.', 'nginx-helper' );
	} else {
		$ec_stored = (array) get_option( $ec_option, array() );
		$ec_token  = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token'] ) ) : '';
		$ec_zone   = isset( $_POST['zone_id'] ) ? sanitize_text_field( wp_unslash( $_POST['zone_id'] ) ) : '';
		$ec_ttl    = isset( $_POST['default_cache_ttl'] ) ? sanitize_text_field( wp_unslash( $_POST['default_cache_ttl'] ) ) : '';

		// An empty token field keeps the saved token. A token set by constant cannot be replaced.
		if ( defined( 'EASYENGINE_CACHE_MANAGER_CLOUDFLARE_API_TOKEN' ) && ! empty( EASYENGINE_CACHE_MANAGER_CLOUDFLARE_API_TOKEN ) ) {
			$ec_token = '';
		} elseif ( '' === $ec_token && ! empty( $ec_stored['api_token'] ) ) {
			$ec_token = $ec_stored['api_token'];
		}

		$ec_new = array();

		foreach ( array(
			'api_token'         => $ec_token,
			'zone_id'           => $ec_zone,
			'default_cache_ttl' => '' === $ec_ttl ? '' : absint( $ec_ttl ),
		) as $ec_key => $ec_value ) {
			if ( '' !== $ec_value ) {
				$ec_new[ $ec_key ] = $ec_value;
			}
		}

		if ( empty( $ec_new ) ) {
			delete_option( $ec_option );
		} else {
			update_option( $ec_option, $ec_new, false );
		}

		$ec_saved_message = __( 'Settings saved.', 'nginx-helper' );
	}
}

// Setting up the cache rule uses the settings of this site.
$nginx_helper_admin->handle_cf_cache_rule_update();

$ec_network  = $nginx_helper_admin->get_network_cloudflare_settings();
$ec_effective = $nginx_helper_admin->get_cloudflare_settings();
$ec_own      = (array) get_option( $ec_option, array() );
$ec_locked   = ! empty( $ec_network['api_token_enabled_by_constant'] );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Cloudflare Cache for this site', 'nginx-helper' ); ?></h1>

	<?php if ( ! empty( $ec_saved_message ) ) : ?>
		<div class="updated"><p><?php echo esc_html( $ec_saved_message ); ?></p></div>
	<?php endif; ?>

	<?php $nginx_helper_admin->cf_page_rule_save_display_admin_notices(); ?>

	<p>
		<?php
		if ( empty( $ec_own ) ) {
			esc_html_e( 'This site uses the network Cloudflare settings.', 'nginx-helper' );
		} else {
			esc_html_e( 'This site has its own Cloudflare settings. Anything it does not set uses the network settings.', 'nginx-helper' );
		}

		if ( $ec_can_configure ) {
			echo ' ';
			esc_html_e( 'Set any of the fields below to use different ones for this site only.', 'nginx-helper' );
		}
		?>
	</p>

	<?php if ( ! $ec_effective['is_enabled'] ) : ?>
		<p><?php esc_html_e( 'Cloudflare is not set up for this site. A network administrator can add the API token and zone ID.', 'nginx-helper' ); ?></p>
	<?php endif; ?>

	<?php if ( $ec_can_configure ) : ?>
	<form method="post" action="#" name="ec_cf_site_settings_form">
		<?php wp_nonce_field( 'ec_cf_site_settings_nonce', 'ec_cf_site_settings_nonce' ); ?>
		<input type="hidden" value="1" name="ec_cf_site_settings_save"/>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><label for="cf_site_api_token"><?php esc_html_e( 'API Token', 'nginx-helper' ); ?></label></th>
				<td>
					<input <?php echo $ec_locked ? 'disabled' : ''; ?> name="api_token" id="cf_site_api_token" type="password" autocomplete="off" value=""
						placeholder="<?php echo ! empty( $ec_own['api_token'] ) ? esc_attr__( 'Saved for this site', 'nginx-helper' ) : ''; ?>"/>
					<p class="description">
						<?php
						if ( $ec_locked ) {
							esc_html_e( 'The token is set by a constant for the whole network and cannot be replaced for one site.', 'nginx-helper' );
						} else {
							esc_html_e( 'Leave empty to keep the saved token, or to use the network token. The saved token is never shown.', 'nginx-helper' );
						}
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cf_site_zone_id"><?php esc_html_e( 'Zone ID', 'nginx-helper' ); ?></label></th>
				<td>
					<input name="zone_id" id="cf_site_zone_id" type="text" autocomplete="off"
						value="<?php echo esc_attr( isset( $ec_own['zone_id'] ) ? $ec_own['zone_id'] : '' ); ?>"
						placeholder="<?php echo esc_attr( $ec_network['zone_id'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cf_site_ttl"><?php esc_html_e( 'Default Cache TTL (seconds)', 'nginx-helper' ); ?></label></th>
				<td>
					<input name="default_cache_ttl" id="cf_site_ttl" type="number" min="0"
						value="<?php echo esc_attr( isset( $ec_own['default_cache_ttl'] ) ? $ec_own['default_cache_ttl'] : '' ); ?>"
						placeholder="<?php echo esc_attr( $ec_network['default_cache_ttl'] ); ?>"/>
				</td>
			</tr>
			<?php if ( ! empty( $ec_own ) ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Network settings', 'nginx-helper' ); ?></th>
				<td>
					<label for="ec_cf_use_network">
						<input type="checkbox" name="ec_cf_use_network" id="ec_cf_use_network" value="1"/>
						<?php esc_html_e( 'Use the network settings for this site (removes the settings above)', 'nginx-helper' ); ?>
					</label>
				</td>
			</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php submit_button( __( 'Save Changes', 'nginx-helper' ), 'primary' ); ?>
	</form>
	<?php endif; ?>

	<?php if ( $ec_effective['is_enabled'] ) : ?>
		<form name="easyengine_cache_manager_add_cache_rule" method="POST">
			<?php
			wp_nonce_field( 'easyengine_cache_manager_add_cache_rule_nonce', 'easyengine_cache_manager_add_cache_rule_nonce' );
			submit_button( __( 'Setup Cache Rules', 'nginx-helper' ), 'secondary', 'easyengine_cache_manager_add_cache_rule_save', false );
			?>
		</form>
	<?php endif; ?>
</div>
