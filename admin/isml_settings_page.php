<div class="isml__loader">

</div>

<div class="isml__page row">

	<div class="col-xs-12 col-sm-12 col-md-8 col-lg-8">

		<div class="isml__message"></div>

		<div class="row">

			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<h2>Imageshop Sync <?php
					_e('Settings', 'isml'); ?></h2>
			</div>

		</div>

		<div class="row">

			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<?php
				_e('Type in your Imageshop access information.', 'isml'); ?>
				<?php
				_e(
					'Don\'t have an account? <a  href="https://www.imageshop.no" target="_blank">Create it</a>',
					'isml'
				); ?>
			</div>

		</div>

		<form method="POST" action="options.php">

			<?php
			settings_fields('isml_settings'); ?>

			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<h4>
						<?php
						_e('Connection settings', 'isml'); ?>
					</h4>
				</div>

			</div>

			<div class="isml__block">

				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<label for="isml_api_key">
							<?php
							_e('Imageshop Key', 'isml'); ?>:
						</label>
					</div>

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-10">
						<input id="isml_api_key" name="isml_api_key" type="text" class="regular-text code"
							   value="<?= esc_attr(get_option('isml_api_key')); ?>"
						/>
					</div>

				</div>

				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<input type="button" name="test" class="button button-primary isml__test__connection"
							   value="<?php
							   _e('Check the connection', 'isml'); ?>"/>
					</div>
				</div>
			</div>
			<div class="isml__block">
				<div class="row">

					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
						<h4>
							<?php
							_e('Full sync commands', 'isml'); ?>
						</h4>
					</div>

				</div>

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<input type="button" name="test" class="button button-primary isml__sync_wp_to_imageshop"
							   value="<?php
							   _e('Sync local WP images to Imageshop clowd', 'isml'); ?>"/>
					</div>

				</div>

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-2">
						<input type="button" name="test" class="button button-primary isml__sync_imageshop_to_wp"
							   value="<?php
							   _e('Sync Imageshop clowd images to local WP ', 'isml'); ?>"/>
					</div>

				</div>
			</div>


			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<h4>
						<?php
						_e('Sync settings', 'isml'); ?>
					</h4>
				</div>

			</div>

			<div class="row">

				<div class="col-xs-1 col-sm-1 col-md-1 col-lg-1" style="width: 50px;">
					<input id="onlystorage" type="checkbox" name="isml_storage_file_only"
						   value="1" <?= checked(get_option('isml_storage_file_only'), 1); ?>
					/>
				</div>

				<div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
					<?php
					_e('Store files only in the Imageshop cloud and delete after successful upload.', 'isml'); ?>
					<?php
					_e(
						'In that case file will be removed from your server after being uploaded to cloud storage, that saves you space.',
						'isml'
					); ?>
				</div>

			</div>

			<div class="row">

				<div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
					<input id="isml_storage_file_delete" type="checkbox" name="isml_storage_file_delete"
						   value="1" <?= checked(get_option('isml_storage_file_delete'), 1); ?>
					/>
				</div>

				<div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
					<?php
					_e('Delete file from cloud storage as soon as it was removed from your library.', 'isml'); ?>
				</div>

			</div>

			<div class="row">

				<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<input type="hidden" name="action" value="update"/>
					<?php
					submit_button(); ?>
				</div>

			</div>

		</form>

	</div>

	<div class="col-xs-12 col-xs-12 col-md-4 col-lg-4">

		<p>
			This plugin syncs your WordPress library with Imageshop Container.
		</p>

	</div>

</div>
