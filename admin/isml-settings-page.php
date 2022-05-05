<div class="isml__loader">

</div>

<div class="isml__page row">

	<div class="col-xs-12 col-sm-12 col-md-8 col-lg-8">

		<div class="isml__message"></div>

		<div class="row">

			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<h1>
					<?php esc_html_e( 'Imageshop Sync Settings', 'imageshop' ); ?>
				</h1>
			</div>

		</div>

		<div class="row">

			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<p>
					<?php esc_html_e( 'The Imageshop plugin will automatically replace your Media Library with the Imageshop media bank, giving you direct access to your organizations entire media portfolio.', 'imageshop' ); ?>
				</p>

				<p>
					<?php esc_html_e( 'To make use of the Imageshop services, you will need to register for an account.', 'imageshop' ); ?>

					<a href="https://www.imageshop.no" target="_blank">
						<?php esc_html_e( 'Create a new Imageshop account, or view your account details.', 'imageshop' ); ?>
					</a>
				</p>
			</div>

		</div>

		<form method="POST" action="options.php">
			<?php settings_fields( 'isml_settings' ); ?>

			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<h2>
						<?php esc_html_e( 'Connection settings', 'imageshop' ); ?>
					</h2>
				</div>

			</div>

			<div class="isml__block">

				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<label for="isml_api_key">
							<?php esc_html_e( 'Imageshop Key:', 'imageshop' ); ?>
						</label>
					</div>

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-10">
						<input id="isml_api_key" name="isml_api_key" type="text" class="regular-text code"
							value="<?php echo esc_attr( get_option( 'isml_api_key' ) ); ?>"
						/>
					</div>

				</div>

				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<button type="button" name="test" class="button button-primary isml__test__connection">
							<?php esc_html_e( 'Check the connection', 'imageshop' ); ?>
						</button>
					</div>
				</div>
			</div>
			<div class="isml__block">
				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
						<h2>
							<?php esc_html_e( 'Full sync commands', 'imageshop' ); ?>
						</h2>
					</div>

				</div>

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<button type="button" name="test" class="button button-primary isml__sync_wp_to_imageshop">
							<?php esc_html_e( 'Sync local WP images to Imageshop clowd', 'imageshop' ); ?>
						</button>
					</div>

				</div>

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<button type="button" name="test" class="button button-primary isml__sync_imageshop_to_wp">
							<?php esc_html_e( 'Sync Imageshop clowd images to local WP ', 'imageshop' ); ?>
						</button>
					</div>

				</div>
			</div>

			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<input type="hidden" name="action" value="update"/>
					<?php submit_button(); ?>
				</div>

			</div>

		</form>

	</div>

</div>
