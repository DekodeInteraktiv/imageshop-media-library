import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';

import './wizard.scss';
import Introduction from "./Steps/Introduction";
import Tokens from "./Steps/Tokens";
import Interfaces from "./Steps/Interfaces";
import Import from "./Steps/Import";
import Completed from "./Steps/Completed";

const Wizard = ( { setShowWizard, setShowNotice } ) => {
	const [ step, setStep ] = useState( 1 );

	return (
		<>
			<div className="imageshop-modal-overlay">
				<div className="imageshop-wizard-wrapper">
					<dialog className="imageshop-wizard">
						<div className="imageshop-modal-header">
							<h2>
								{ __( 'Imageshop setup', 'imageshop' ) }
							</h2>

							<button type="button" className="imageshop-modal-close" onClick={ () => setShowWizard( false ) }>
								<span className={ 'dashicons dashicons-no' } />
								<span className="screen-reader-text">{ __( 'Close Imageshop setup modal', 'imageshop' ) }</span>
							</button>
						</div>

						<div className="imageshop-modal-body">
							{ 1 === step &&
								<Introduction setStep={ setStep } />
							}
							{ 2 === step &&
								<Tokens setStep={ setStep } />
							}
							{ 3 === step &&
								<Interfaces setStep={ setStep } />
							}
							{ 4 === step &&
								<Import setStep={ setStep } />
							}
							{ 5 === step &&
								<Completed setShowNotice={ setShowNotice } setShowWizard={ setShowWizard } />
							}
						</div>
					</dialog>
				</div>
			</div>
		</>
	)
}

export default Wizard;
