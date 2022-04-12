import React from 'react';
import { __ } from '@wordpress/i18n';

const Completed = ( { setShowWizard, setShowNotice } ) => {
	const closeOnboarding = () => {
		setShowNotice( false );
		setShowWizard( false );
	}

	return (
		<>
			Final page

			<button type="button" className="button button-primary" onClick={ () => closeOnboarding() }>
				{ __( 'Finish setup', 'imageshop' ) }
			</button>
		</>
	)
}

export default Completed;
