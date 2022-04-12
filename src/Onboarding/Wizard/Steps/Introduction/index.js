import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

const Introduction = ( { setStep } ) => {


	return (
		<>
			<h2>
				{ __( 'Imageshop setup', 'imageshop' ) }
			</h2>

			<p>
				{ __( 'After setup, Imageshop will become your one source of truth for all media on your site.', 'imageshop' ) }
			</p>

			<button type="button" className="button button-primary" onClick={ () => setStep( 2 ) }>
				{ __( 'Start setup', 'imageshop' ) }
			</button>
		</>
	)
}

export default Introduction;
