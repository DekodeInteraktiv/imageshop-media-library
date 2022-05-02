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
						<?php esc_html_e( 'Connection settings', 'isml' ); ?>
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
					<h2>
						<?php esc_html_e( 'Sync settings', 'imageshop' ); ?>
					</h2>
				</div>

			</div>

			<div class="row">

				<div class="col-xs-1 col-sm-1 col-md-1 col-lg-1" style="width: 50px;">
					<input id="onlystorage" type="checkbox" name="isml_storage_file_only"
						value="1" <?php echo checked( get_option( 'isml_storage_file_only' ), 1 ); ?>
					/>
				</div>

				<div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
					<?php esc_html_e( 'Store files only in the Imageshop cloud and delete after successful upload.', 'imageshop' ); ?>

					<?php esc_html_e( 'In that case file will be removed from your server after being uploaded to cloud storage, that saves you space.', 'imageshop' ); ?>
				</div>

			</div>

			<div class="row">

				<div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
					<input id="isml_storage_file_delete" type="checkbox" name="isml_storage_file_delete"
						value="1" <?php echo checked( get_option( 'isml_storage_file_delete' ), 1 ); ?>
					/>
				</div>

				<div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
					<?php esc_html_e( 'Delete file from cloud storage as soon as it was removed from your library.', 'imageshop' ); ?>
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
