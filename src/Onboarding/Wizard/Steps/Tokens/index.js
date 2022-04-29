import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import './tokens.scss'

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
				{ __( 'Enter your API token here. You can find your API token in your ImageShop account.', 'imageshop' ) }
			</p>

			{ validationNotice &&
				<>
					{ validToken &&
						<p className="valid-token">
							{ __( '✔ Your API token is valid.', 'imageshop' ) }
						</p>
					}

					{ ! validToken &&
						<>
							<p className="invalid-token">
								{ __( '❌ Your API token is invalid:', 'imageshop' ) }
							</p>

							<p className="invalid-token">
								{ validationNotice }
							</p>
						</>
					}
				</>
			}

			<input type="text" value={ token } onChange={ ( e ) => setToken( e.target.value ) } />

			<div className="imageshop-modal-actions">
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
			</div>
		</>
	)
}

export default Tokens;
