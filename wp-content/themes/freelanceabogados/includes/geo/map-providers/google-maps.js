(function( $ ){

	var methods = {
		init : function( options ) {

			return this.each(function(){
				var $this = $(this),
					data = $this.data('appthemes_map');

				var _options = $.extend({}, $.fn.appthemes_map.defaults, options );

				if ( typeof appthemes_map_vars != 'undefined' ) {
					var _options = $.extend({}, _options, appthemes_map_vars );
				}

				if ( typeof appthemes_map_icon != 'undefined' ) {
					var _options = $.extend({}, _options, appthemes_map_icon );
				}

				var mapOptions = {
					zoom: _options.zoom,
					center: new google.maps.LatLng(_options.center_lat , _options.center_lng),
					mapTypeId: google.maps.MapTypeId.ROADMAP
				};

				_options.map = new google.maps.Map( document.getElementById( $this.attr('id') ), mapOptions);

				if ( ! data ) {
					$(this).data('appthemes_map', {
						options: _options,
						markers: [],
						marker_coords: []
					});

					var data = $(this).data('appthemes_map');
				}

				if ( options.directions ) {
					data.directionsDisplay = new google.maps.DirectionsRenderer();
					data.directionsService = new google.maps.DirectionsService();

					jQuery('#' + options.get_directions_btn ).click( function(){
						var start = jQuery('#' + options.directions_from).val(); 
						var end = options.end_address; // This is the address for the listing/event
						var request = {
							origin: start,
							destination: end,
							travelMode: google.maps.TravelMode.DRIVING
						};
						
						data.directionsService.route(request, function(result, status) {
							if (status == google.maps.DirectionsStatus.OK) {
								data.directionsDisplay.setDirections(result);
								data.markers[0].setVisible(false);
								jQuery('#' + options.print_directions_btn ).slideDown('fast');
							}
						});
						
						data.directionsDisplay.setPanel(document.getElementById(options.directions_panel));
						data.directionsDisplay.setMap( _options.map );
					});

				}

				if ( options.markers ) {
					for ( x in options.markers  ) {
						methods._add_marker( $this,  options.markers[x] );
					}
					
					if( typeof options.auto_zoom == 'undefined' || options.auto_zoom !== false ) {
						methods._auto_zoom( $this );
					}
				}

			});
		},

		_auto_zoom : function ( that ) {
			var $this = that,
				data = $this.data('appthemes_map');

			if ( data.marker_coords.length < 1 ) 
				return;

			var markerBounds = new google.maps.LatLngBounds();
			for (var i = 0; i < data.marker_coords.length; i++) {
				markerBounds.extend( data.marker_coords[i] );
			}

			data.options.map.fitBounds(markerBounds);

		},

		auto_zoom : function() {
			return this.each(function(){
				return methods._auto_zoom( $(this) );
			});
		},

		_add_marker : function( that, marker_opts ) {
			
			var $this = that,
				data = $this.data('appthemes_map');

			if ( typeof data.options.use_app_icon != 'undefined' ) {

				if ( typeof marker_opts.icon_color != 'undefined' ) {
					var _icon_url = data.options.app_icon_base_url;
					_icon_url = _icon_url + '/map-pin-' + marker_opts.icon_color;
					if ( typeof marker_opts.icon_shape != 'undefined' && marker_opts.icon_shape == 'round' ) {
						_icon_url = _icon_url + '-' + marker_opts.icon_shape;
					}
					_icon_url = _icon_url + '.png';
				} else {
					var _icon_url = data.options.app_icon_url;
				}

				var marker_icon = new google.maps.MarkerImage( _icon_url );

				var marker_icon_shadow = new google.maps.MarkerImage(data.options.app_icon_shadow_url,
					new google.maps.Size(data.options.app_icon_shadow_width, data.options.app_icon_shadow_height),
					new google.maps.Point(0,0) );

				var marker_icon_shape = {
					coord: data.options.app_icon_click_coords,
					type: "rect"
				};
			}

			var marker = new google.maps.Marker({
				position: new google.maps.LatLng( marker_opts.lat , marker_opts.lng ),
				map: data.options.map,
				draggable: ( marker_opts.draggable ? true : false ),
				title: ( typeof marker_opts.marker_text != 'undefined'  ? marker_opts.marker_text : '' ),
				icon: ( typeof marker_icon != 'undefined' ? marker_icon : '' ),
				shadow: ( typeof marker_icon_shadow != 'undefined' ? marker_icon_shadow: '' ),
				shape: ( typeof marker_icon_shape != 'undefined' ? marker_icon_shape: '' )
			});

			marker.key = ( data.markers.length );

			if ( typeof marker_opts.popup_content != 'undefined' ) {
				var marker_popup = new google.maps.InfoWindow({
					content: marker_opts.popup_content
				});
				
				google.maps.event.addListener(marker, "click", function(e) {
					marker_popup.open( data.options.map, marker );
				});
			}

			if ( typeof marker_opts.anchor != 'undefined' ) {
				google.maps.event.addListener( marker, "click", function(e) {
					location = marker_opts.anchor;
				});
			}


			if ( typeof marker_opts.draggable != 'undefined' ) {
				google.maps.event.addListener(marker, "dragend", function() {
					var drag_position = marker.getPosition();

					data.options.marker_drag_end( marker.key, drag_position.lat(), drag_position.lng() );
				});
			}

			data.markers.push( marker );
			data.marker_coords.push( marker.getPosition() );
		},

		add_marker : function( marker_opts ) { 
			return this.each(function(){
				return methods._add_marker( $(this), marker_opts );
			});
		},

		update_marker_position : function( updated_pos ) { 
			return this.each(function(){
				var $this = $(this),
					data = $this.data('appthemes_map');

				var marker_key = updated_pos.marker_key ? updated_pos.marker_key : 0;
				marker = data.markers[ marker_key ];

				var updated_position = new google.maps.LatLng( updated_pos.lat, updated_pos.lng );
				data.options.map.setCenter( updated_position );
				marker.setPosition( updated_position );
			});
		}
	};

	$.fn.appthemes_map = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.appthemes_map' );
		}
	};

	$.fn.appthemes_map.defaults = {
		zoom: 14,
		center_lat: 0,
		center_lng: 0,
		map: null,
		marker_drag_end: function(){}
	};

})( jQuery );
