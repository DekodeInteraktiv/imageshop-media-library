import React from 'react';
import ReactDOM from 'react-dom';

import Onboarding from './Onboarding';

const onboardingContainer = document.getElementById( 'imageshop-onboarding' );

if ( onboardingContainer ) {
	ReactDOM.render(
		<React.StrictMode>
			<Onboarding/>
		</React.StrictMode>,
		onboardingContainer
	);
}
