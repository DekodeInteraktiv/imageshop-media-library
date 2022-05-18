import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';

import Wizard from '.././Wizard';

const Notice = () => {
	const [ showNotice, setShowNotice ] = useState( true );
	const [ showWizard, setShowWizard ] = useState( false );

	useEffect( () => {
		window.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				setShowWizard( false );
			}
		} );
	}, [] );

	useEffect( () => {
		const bodyTag = document.querySelector( 'body' );

		if ( showWizard ) {
			bodyTag.classList.add( 'disable-scroll' );
		} else {
			bodyTag.classList.remove( 'disable-scroll' );
		}
	}, [ showWizard ] );

	if ( ! showNotice ) {
		return (
			<></>
		)
	}

	return (
		<>
			<div className="notice notice-warning inline">
				<h2>
					{ __( 'Imageshop setup', 'imageshop' ) }
				</h2>

				<p>
					{ __( 'The Imageshop integration is almost ready to use, please complete the setup steps to start using Imageshop directly from your media library.', 'imageshop' ) }
				</p>

				<p>
					<button type="button" className="button button-primary" onClick={ () => setShowWizard( true ) }>
						{ __( 'Complete setup', 'imageshop' ) }
					</button>
				</p>
			</div>

			{ showWizard && (
				<Wizard setShowWizard={ setShowWizard } setShowNotice={ setShowNotice } />
			) }
		</>
	)
}

export default Notice;
