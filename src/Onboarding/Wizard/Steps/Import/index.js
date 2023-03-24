import React, {useReducer, useEffect, useState} from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import './import.scss';

const Import = ( { setStep } ) => {
	const [ state, setState ] = useReducer(
		( s, a ) => ( { ...s, ...a } ),
		{
			progressImported: 0,
			progressTotal: 0,
			doingImport: false,
		}
	);

	const {
		progressImported,
		progressTotal,
		doingImport
	} = state;

	let importStatusTimer;

	const setupComplete = () => {
		clearTimeout( importStatusTimer );

		apiFetch( { path: '/imageshop/v1/onboarding/completed' } )
			.then( () => {
				setStep( 5 );
			} );
	}

	const getImportStatus = () => {
		apiFetch( {
			path: '/imageshop/v1/onboarding/import',
			method: 'GET'
		} )
			.then( ( response ) => {
				setState( {
					progressImported: response.imported,
					progressTotal: response.total,
				} );

				importStatusTimer = setTimeout( getImportStatus, 5000 );
			} );
	}

	const startImports = () => {
		setState( { doingImport: true } );

		apiFetch( {
			path: '/imageshop/v1/onboarding/import',
			method: 'POST'
		} )
			.then( ( response ) => {
				getImportStatus();
			} );
	}

	useEffect( () => {
		if ( progressTotal <= 0 ) {
			return;
		}

		if ( progressTotal === progressImported ) {
			setupComplete();
		}
	}, [ progressTotal, progressImported ] );

	return (
		<>
			<p>
				{ __( 'Would you like to import your current Wordpress media library to Imageshop? This would make your media be available both in Imageshop and in the Imageshop plugin.', 'imageshop-dam-connector' ) }
			</p>

			<p>
				{ __( 'WARNING: Such an import may take quite a long time to complete, and is not required for your existing media to keep showing as usual. You should only perform an import if you know that you need existing media to be available in Imageshop as well.', 'imageshop-dam-connector' ) }
			</p>

			{ doingImport &&
				<div className="imageshop-import-progress-indicator">
					<label htmlFor="imageshop-import-progress">
						{ __( 'Import Progress', 'imageshop-dam-connector' ) }
					</label>
					<progress
						id="imageshop-import-progress"
						value={ progressImported }
						max={ progressTotal }
					/>
				</div>
			}

			<div className="imageshop-modal-actions">
				<button type="button" className="button button-primary" onClick={ () => setupComplete() } disabled={ doingImport }>
					{ __( 'Continue without importing media', 'imageshop-dam-connector' ) }
				</button>

				<button type="button" className="button button-link button-link-delete" onClick={ () => startImports() } disabled={ doingImport }>
					{ __( 'Import existing media', 'imageshop-dam-connector' ) }
				</button>
			</div>
		</>
	)
}

export default Import;
