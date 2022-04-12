const isml_loader = jQuery('.isml__loader')
const isml_message = jQuery('.isml__message')
const isml_test_connection = jQuery('.isml__test__connection')
const isml__sync_to_imageshop = jQuery('.isml__sync_wp_to_imageshop')
const isml__sync_to_local_wp = jQuery('.isml__sync_imageshop_to_wp')

function send_request(data) {
	isml_loader.hide()

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

document.addEventListener("DOMContentLoaded", function (event) {
	// check connection button
	isml_test_connection.on('click', function () {
		send_request({
				isml_api_key: document.querySelector('input[name=isml_api_key]').value,
				action: 'isml_test_connection'
			}
		)
	})
	isml__sync_to_imageshop.on('click', function () {
		send_request({action: 'isml_import_wp_to_imageshop'})
	})
	isml__sync_to_local_wp.on('click', function () {
		console.log('intrta')
		send_request({action: 'isml_import_imageshop_to_wp'})
	})
})
