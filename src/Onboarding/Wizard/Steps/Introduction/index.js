import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

const Introduction = ( { setStep } ) => {


	return (
		<>
			<p>
				{ __( 'After setup, Imageshop will become your one source of truth for all media on your site.', 'imageshop' ) }
			</p>

			<div className="imageshop-modal-actions">
				<button type="button" className="button button-primary" onClick={ () => setStep( 2 ) }>
					{ __( 'Start setup', 'imageshop' ) }
				</button>

				<a href="https://imageshop.no" className="pull-right" target="_blank" rel="noopener noreferrer">
					{ __( 'Create an account', 'imageshop' )}
				</a>
			</div>
		</>
	)
}

export default Introduction;
