/**
 * Implement filters to the media library modal.
 *
 * Courtesy of Daniel Bachhuber (danielbachhuber.com)
 */

(function(){
	let ImageshopOriginFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-origin',

		createFilters: function() {
			var filters = {};

			filters.all = {
				// Change this: use whatever default label you'd like
				text:  'Search Imageshop',
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_origin: 'imageshop'
				},
				priority: 10
			}

			filters.wp = {
				// Change this: use whatever default label you'd like
				text:  'Search WordPress library',
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_origin: 'wordpress'
				},
				priority: 10
			}

			this.filters = filters;
		}
	})

	/**
	 * Create a new MediaLibraryTaxonomyFilter we later will instantiate
	 */
	let ImageshopInterfaceFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-interface',

		createFilters: function() {
			var filters = {};
			// Formats the 'terms' we've included via wp_localize_script()
			_.each( ImageshopMediaLibrary.interfaces || {}, function( value, index ) {
				filters[ value.Id ] = {
					text: value.Name,
					props: {
						// Change this: key needs to be the WP_Query var for the taxonomy
						imageshop_interface: value.Id,
						dataTest: 'nope',
					},
					selected: ( ImageshopMediaLibrary.default_interface.toString() === value.Id.toString() ),
				};
			});
			filters.all = {
				// Change this: use whatever default label you'd like
				text:  'All interfaces',
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_interface: '0'
				},
				priority: 10
			};
			this.filters = filters;
		}
	});

	let ImageshopPostsPerPageFilter = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-posts-per-page',

		createFilters: function() {
			var filters = {};

			filters.all = {
				// Change this: use whatever default label you'd like
				text:  '10 results per page',
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					posts_per_page: 10
				},
				priority: 10
			};
			this.filters = filters;
		}
	});

	let ImageshopCategoryFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-category',

		createFilters: function() {
			var filters = {};

			if ( ImageshopMediaLibrary.categories ) {
				ImageshopMediaLibrary.categories.map( ( category ) => {
					filters[ category.CategoryID ] = {
						text: category.CategoryName,
						props: {
							// Change this: key needs to be the WP_Query var for the taxonomy
							imageshop_category: category.CategoryID,
						}
					};

					if ( category.Children ) {
						category.Children.map( ( child ) => {
							filters[ child.CategoryID ] = {
								text: ' - ' + child.CategoryName,
								props: {
									// Change this: key needs to be the WP_Query var for the taxonomy
									imageshop_category: child.CategoryID,
								}
							};
						} );
					}
				} );
			}
			filters.all = {
				// Change this: use whatever default label you'd like
				text:  'All categories',
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_category: ''
				},
				priority: 10
			};
			this.filters = filters;
		}
	});

	/**
	 * Extend and override wp.media.view.AttachmentsBrowser to include our new filter
	 */
	let AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call( this );

			this.toolbar.set( 'ImageshopOriginFiltersLabel', new wp.media.view.Label({
				value: 'Media library source origin',
				attributes: {
					'for': 'imageshop-media-library-origin'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopOriginFilters', new ImageshopOriginFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopInterfaceFiltersLabel', new wp.media.view.Label({
				value: 'Imageshop Interface',
				attributes: {
					'for': 'imageshop-media-library-interface'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopInterfaceFilters', new ImageshopInterfaceFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopCategoryFiltersLabel', new wp.media.view.Label({
				value: 'Imageshop Category',
				attributes: {
					'for': 'imageshop-media-library-category'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopCategoryFilters', new ImageshopCategoryFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopPostsPerPageFilterLabel', new wp.media.view.Label({
				value: 'Results per page',
				attributes: {
					'for': 'imageshop-posts-per-page'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopPostsPerPageFilter', new ImageshopPostsPerPageFilter({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			let ImageshopInterfaceDOM,
				ImageshopCategoryDOM,
				ImageshopSelectorTimer,
				ImageshopSelectPostsPerPageDOM;

			const setSelectors = () => {
				ImageshopInterfaceDOM = document.getElementById( 'imageshop-media-library-interface' );
				ImageshopCategoryDOM = document.getElementById( 'imageshop-media-library-category' );
				ImageshopSelectPostsPerPageDOM = document.getElementById( 'imageshop-posts-per-page' );
			}

			setSelectors()

			const getImageshopCategories = () => {
				wp.apiFetch( {
					path: '/imageshop/v1/categories/' + ImageshopInterfaceDOM.value,
					method: 'GET'
				} ).then( ( response ) => {
					ImageshopMediaLibrary.categories = response;

					this.toolbar.set( 'ImageshopCategoryFilters', new ImageshopCategoryFilters({
						controller: this.controller,
						model:      this.collection.props,
						priority: -75
					}).render() );
				} );
			}

			if ( ImageshopInterfaceDOM && ImageshopCategoryDOM ) {
				ImageshopInterfaceDOM.value = ImageshopMediaLibrary.default_interface;
				ImageshopSelectPostsPerPageDOM.dispatchEvent( new Event( 'change' ) );
			} else {
				ImageshopSelectorTimer = setInterval( () => {
					setSelectors();

					if ( ImageshopInterfaceDOM && ImageshopCategoryDOM ) {
						clearInterval( ImageshopSelectorTimer );

						ImageshopInterfaceDOM.value = ImageshopMediaLibrary.default_interface;

						ImageshopSelectPostsPerPageDOM.dispatchEvent( new Event( 'change' ) );
					}
				}, 500 );
			}
		}
	});
})()
