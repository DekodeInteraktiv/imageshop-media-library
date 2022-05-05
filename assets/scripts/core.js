function send_request(data) {
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: data,
		dataType: 'html'
	}).done(function (res) {
		isml_message.show()
		isml_message.html('<br/>' + res)
		isml_loader.hide()
		jQuery('html,body').animate({scrollTop: 0}, 1000)
	})
}

const ismlLoadingIndicator = document.getElementsByClassName( '.isml__loader' )[0],
	ismlMessage = document.getElementsByClassName( '.isml__message' )[0],
	ismlTestConnection = document.getElementsByClassName( '.isml__test__connection' )[0],
	ismlSyncToImageshop = document.getElementsByClassName( '.isml__sync_wp_to_imageshop' )[0],
	ismlSyncToLocal = document.getElementsByClassName( '.isml__sync_imageshop_to_wp' )[0];

// check connection button
ismlTestConnection.on('click', function () {
	send_request({
			isml_api_key: document.querySelector('input[name=isml_api_key]').value,
			action: 'isml_test_connection'
		}
	)
})

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

ismlSyncToImageshop.on('click', function () {
	send_request({action: 'isml_import_wp_to_imageshop'})
} );

ismlSyncToLocal.on('click', function () {
	console.log('intrta')
	send_request({action: 'isml_import_imageshop_to_wp'})
} );
