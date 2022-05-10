const ismlLoadingIndicator = document.getElementsByClassName( 'isml__loader' )[0],
	ismlMessage = document.getElementsByClassName( 'isml__message' )[0],
	ismlTestConnection = document.getElementsByClassName( 'isml__test__connection' )[0],
	ismlSyncToImageshop = document.getElementsByClassName( 'isml__sync_wp_to_imageshop' )[0],
	ismlSyncToLocal = document.getElementsByClassName( 'isml__sync_imageshop_to_wp' )[0];

// check connection button
ismlTestConnection.addEventListener( 'click', function () {
	wp.apiFetch( {
		path: '/imageshop/v1/settings/test-connection',
		method: 'POST',
		data: {
			token: document.querySelector( 'input[name=isml_api_key]' ).value
		}
	} )
		.then( function( response ) {
			ismlMessage.innerHTML = response.message;
		} );
} );

ismlSyncToImageshop.addEventListener( 'click', function() {
	ismlLoadingIndicator.style.display = 'block';

	wp.apiFetch( { path: '/imageshop/v1/sync/remote', method: 'POST' } )
		.then( function( response ) {
			ismlLoadingIndicator.style.display = 'none';

			ismlMessage.innerHTML = response.data.message;
		} );
} );

ismlSyncToLocal.addEventListener( 'click', function() {
	ismlLoadingIndicator.style.display = 'block';

	wp.apiFetch( { path: '/imageshop/v1/sync/local', method: 'POST' } )
		.then( function( response ) {
			ismlLoadingIndicator.style.display = 'none';

			ismlMessage.innerHTML = response.data.message;
		} );
} );
