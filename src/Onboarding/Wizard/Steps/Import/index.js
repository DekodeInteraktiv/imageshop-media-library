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
			Do imports

			<button type="button" className="button button-primary" onClick={ () => startImports() }>
				{ __( 'Import existing media', 'imageshop' ) }
			</button>

			<button type="button" className="button button-secondary" onClick={ () => setupComplete() }>
				{ __( 'Continue without importing media', 'imageshop' ) }
			</button>
		</>
	)
}

export default Import;
