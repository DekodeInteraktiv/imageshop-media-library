import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

const Interfaces = ( { setStep } ) => {
	const [ apiInterface, setApiInterface ] = useState();
	const [ availableInterfaces, setAvailableInterfaces ] = useState( [] );

	const getInterfaces = () => {
		apiFetch( { path: '/imageshop/v1/onboarding/interfaces' } )
			.then( ( response ) => {
				const interfaces = [];

				// Add non-valued interface to the option dropdown.
				interfaces.push( {
					value: '',
					label: __( 'Select an interface', 'imageshop' ),
				} );

				response.interfaces.map( ( single ) => {
					const singleInterface = {
						value: single.Id,
						label: single.Name
					}

					interfaces.push( singleInterface );
				} );

				setAvailableInterfaces( interfaces );
			} )
	}

	useEffect( () => {
		getInterfaces()
	}, [] );

	useEffect( () => {
		if ( ! apiInterface ) {
			return;
		}

		apiFetch(
			{
				path: '/imageshop/v1/onboarding/interfaces',
				method: 'POST',
				data: {
					interface: apiInterface
				}
			}
		);
	}, [ apiInterface ] );

	return (
		<>
			<p>
				{ __( 'Interfaces determine where files are stored in your Imageshop account.', 'imageshop')}
			</p>

			<SelectControl
				label={ __( 'Select which interface is used for uploaded media files', 'imageshop' ) }
				options={ availableInterfaces }
				onChange={ ( selection ) => setApiInterface( selection ) }
			/>

			<div className="imageshop-modal-actions">
				{ apiInterface &&
					<button type="button" className="button button-primary" onClick={ () => setStep( 4 ) }>
						{ __( 'Continue to imports', 'imageshop' ) }
					</button>
				}
			</div>
		</>
	)
}

export default Interfaces;
