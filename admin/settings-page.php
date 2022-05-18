<?php
/**
 * Settings page.
 */

declare( strict_types = 1 );

namespace Imageshop\WordPress;

$imageshop = REST_Controller::get_instance();

$api_key           = \get_option( 'imageshop_api_key' );
$default_interface = \get_option( 'imageshop_upload_interface' );

?>
<div class="imageshop__loader">

</div>

<div class="imageshop__page row">

	<div class="col-xs-12 col-sm-12 col-md-8 col-lg-8">

		<div class="imageshop__message"></div>

		<div class="row">

			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<h1>
					<?php \esc_html_e( 'Imageshop Sync Settings', 'imageshop' ); ?>
				</h1>
			</div>

		</div>

		<div class="row">

			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<p>
					<?php \esc_html_e( 'The Imageshop plugin will automatically replace your Media Library with the Imageshop media bank, giving you direct access to your organizations entire media portfolio.', 'imageshop' ); ?>
				</p>

				<p>
					<?php \esc_html_e( 'To make use of the Imageshop services, you will need to register for an account.', 'imageshop' ); ?>

					<a href="https://www.imageshop.no" target="_blank">
						<?php \esc_html_e( 'Create a new Imageshop account, or view your account details.', 'imageshop' ); ?>
					</a>
				</p>
			</div>

		</div>

		<form method="POST" action="options.php">
			<?php \settings_fields( 'imageshop_settings' ); ?>

			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<h2>
						<?php \esc_html_e( 'Connection settings', 'imageshop' ); ?>
					</h2>
				</div>

			</div>

			<div class="imageshop__block">

				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<label for="imageshop_api_key">
							<?php \esc_html_e( 'Imageshop Key:', 'imageshop' ); ?>
						</label>
					</div>

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-10">
						<input id="imageshop_api_key" name="imageshop_api_key" type="text" class="regular-text code"
							value="<?php echo \esc_attr( $api_key ); ?>"
						/>
					</div>

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<label for="imageshop_upload_interface">
							<?php \esc_html_e( 'Default interface:', 'imageshop' ); ?>
						</label>
					</div>

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-10">
						<?php if ( ! empty( $api_key ) && $imageshop->test_valid_token() ) : ?>

						<select id="imageshop_upload_interface" name="imageshop_upload_interface">
							<?php
							$interfaces = $imageshop->get_interfaces();

							foreach ( $interfaces as $interface ) {
								\printf(
								'<option value="%d"%s>%s</option>',
									\esc_attr( $interface->Id ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$interface->ID` is defined by the SaaS API.
									\selected( $default_interface, $interface->Id, false ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$interface->Id` is defined by the SaaS API.
									\esc_html( $interface->Name ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$interface->Name` is defined by the SaaS API.
								);
							}
							?>
						</select>

						<?php else : ?>

						<em>
							<?php \esc_html_e( 'To see available interfaces, please save a valid Imageshop key first..', 'imageshop' ); ?>
						</em>

						<?php endif; ?>

					</div>

				</div>

				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<button type="button" name="test" class="button button-primary imageshop__test__connection">
							<?php \esc_html_e( 'Check the connection', 'imageshop' ); ?>
						</button>
					</div>
				</div>
			</div>
			<div class="imageshop__block">
				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
						<h2>
							<?php \esc_html_e( 'Full sync commands', 'imageshop' ); ?>
						</h2>
					</div>

				</div>

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<button type="button" name="test" class="button button-primary imageshop__sync_wp_to_imageshop">
							<?php \esc_html_e( 'Sync WordPress images to the Imageshop cloud', 'imageshop' ); ?>
						</button>
					</div>

				</div>

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<button type="button" name="test" class="button button-primary imageshop__sync_imageshop_to_wp">
							<?php \esc_html_e( 'Sync Imageshop cloud images to WordPress', 'imageshop' ); ?>
						</button>
					</div>

				</div>
			</div>

			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<input type="hidden" name="action" value="update"/>
					<?php \submit_button(); ?>
				</div>

			</div>

		</form>

	</div>

</div>
