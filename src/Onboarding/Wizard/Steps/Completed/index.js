import React from 'react';
import { __ } from '@wordpress/i18n';

const Completed = ( { setShowWizard, setShowNotice } ) => {
	const closeOnboarding = () => {
		setShowNotice( false );
		setShowWizard( false );
	}

	return (
		<>
			<p>
				{ __( 'Your site has now been configured to use all media via Imageshop.', 'imageshop-dam-connector' ) }
			</p>

			<div className="imageshop-modal-actions">
				<button type="button" className="button button-primary" onClick={ () => closeOnboarding() }>
					{ __( 'Finish setup', 'imageshop-dam-connector' ) }
				</button>
			</div>
		</>
	)
}

export default Completed;
