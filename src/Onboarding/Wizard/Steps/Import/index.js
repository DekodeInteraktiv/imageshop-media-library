import React from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const Import = ( { setStep } ) => {

	const setupComplete = () => {
		apiFetch( { path: '/imageshop/v1/onboarding/completed' } )
			.then( () => {
				setStep( 5 );
			} );
	}

	const startImports = () => {
		apiFetch( { path: '/imageshop/v1/onboarding/import' } )
			.then( () => {
				setupComplete();
			} );
	}

	return (
		<>
			<p>
				{ __( 'Would you like to import your current media library to Imageshop?', 'imageshop' ) }
			</p>

			<p>
				{ __( 'By importing your media library, you ensure that it will remain available to insert into new posts or apges, and also makes it available to the rest of your organization via the normal Imageshop interfaces and integrations', 'imageshop' ) }
			</p>

			<div className="imageshop-modal-actions">
				<button type="button" className="button button-primary" onClick={ () => startImports() }>
					{ __( 'Import existing media', 'imageshop' ) }
				</button>

				<button type="button" className="button button-secondary" onClick={ () => setupComplete() }>
					{ __( 'Continue without importing media', 'imageshop' ) }
				</button>
			</div>
		</>
	)
}

export default Import;
