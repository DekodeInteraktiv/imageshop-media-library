import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const Tokens = ( { setStep } ) => {
	const [ token, setToken ] = useState( '' );
	const [ validToken, setValidToken ] = useState( false );
	const [ validationNotice, setValidationNotice ] = useState( '' );

	const testToken = () => {
		apiFetch( {
			path: '/imageshop/v1/onboarding/token',
			method: 'POST',
			data: {
				token
			}
		} )
			.then( ( response ) => {
				setValidToken( response.valid );
				setValidationNotice( response.message );
			} )
			.catch( ( response ) => {
				setValidToken( response.valid );
				setValidationNotice( response.message );
			} );
	}

	return (
		<>
			<p>
				Setup tokens
			</p>

			<input type="text" value={ token } onChange={ ( e ) => setToken( e.target.value ) } />

			{ validationNotice &&
				<>
					<p>
						{ __( 'Validation response:', 'imageshop' ) }
					</p>

					<p>
						{ validationNotice }
					</p>
				</>
			}

			{ ! validToken &&
				<button type="button" className="button button-primary" onClick={ () => testToken() }>
					{ __( 'Test API token', 'imageshop' ) }
				</button>
			}

			{ validToken &&
				<button type="button" className="button button-primary" onClick={ () => setStep( 3 ) }>
					{ __( 'Continue to upload settings', 'imageshop' ) }
				</button>
			}
		</>
	)
}

export default Tokens;
