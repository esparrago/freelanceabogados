(function( $ ){

	var methods = {
		init : function( options ) {
			return this.each(function(){
				var $this = $(this),
					data = $this.data('appthemes_slider');

				var _options = $.extend({}, $.fn.appthemes_slider.defaults, options );

				if ( ! data ) {
					data = {
						options : _options,
						count   : parseInt( $(this).find('.attachment').length, 10 )
					};
					$this.data( 'appthemes_slider', data );
				}

				if ( data.count <= 1 ) {
					$this.find('.left-arrow').hide();
					$this.find('.right-arrow').hide();
				}

				$this.find('.left-arrow').data( 'position', data.count - 1 );
				$this.find('.right-arrow').data( 'position', 1 );

				$this.find('.attachment').removeClass('current');
				$this.find('.attachment').eq(0).addClass('current');

				methods.hover_handlers( $this );
				methods.resize_handlers( $this );
				methods.click_handlers( $this );

			});

		},

		hover_handlers: function( that ) {
			var $this = that;

			$this.find('.attachment-caption').mouseenter(function(){
				$(this).slideUp();
			});

			$this.mouseleave(function(){
				$this.find('.attachment-caption').slideDown();
			});
		},

		resize_handlers: function( that ) {
			var $this = that,
				data  = $this.data('appthemes_slider'),
				ratio = 9/16,
				width = $this.width();

			if ( 'undefined' !== typeof data.options.height && 'undefined' !== typeof data.options.width ) {
				ratio = data.options.height / data.options.width;
				width = data.options.width;
			}

			$this.css( 'max-width', width );

			$(window)
				.resize(function() {
					$this
						.css( 'height', ratio * $this.width() + 'px' )
						.find( '.slider-video-iframe iframe' )
						.each(function(){
							$(this).attr({
								height : ratio * $this.width(),
								width  : $this.width()
							});
						});
				})
				.resize();

		},

		click_handlers: function( that ) {
			var $this       = that,
				data        = $this.data('appthemes_slider'),
				right_arrow = $this.find('.right-arrow'),
				left_arrow  = $this.find('.left-arrow');

			right_arrow.click(function(e){
				e.preventDefault();
				var _next_position = parseInt( $(this).data('position'), 10 ),
					direction      = 1,
					effect         = data.options.effect;

				methods[effect]( $this, direction, _next_position );
				methods.change_position( $this, _next_position );
			});

			left_arrow.click(function(e){
				e.preventDefault();
				var _next_position = parseInt( $(this).data('position'), 10 ),
					direction      = -1,
					effect         = data.options.effect;

				methods[effect]( $this, direction, _next_position );
				methods.change_position( $this, _next_position );
			});
		},

		change_position : function( slider, current ) {
			var data           = slider.data('appthemes_slider'),
				right_arrow    = slider.find('.right-arrow'),
				left_arrow     = slider.find('.left-arrow'),
				_prev_position = current - 1,
				_next_position = current + 1;

			if ( current >= ( data.count - 1 ) ) {
				_next_position = 0;
			} else if ( current === 0 ) {
				_prev_position = data.count - 1;
			}

			right_arrow.data( 'position', _next_position );
			left_arrow.data( 'position', _prev_position );
		},

		slide : function( slider, direction, position ) {
			var data   = slider.data('appthemes_slider'),
				next   = slider.find('.attachment_' + position ),
				curr   = slider.find('.current'),
				amount = slider.width();

			curr.animate(
				{ left     : '-=' + amount * direction },
				{ duration : data.options.duration,
					complete: function() {
						$(this)
							.removeClass('current')
							.css({ left: 0 });
					}
				}
			);

			next
				.css({ left: amount * direction })
				.addClass('current')
				.animate(
					{ left     : '-=' + amount * direction },
					{ duration : data.options.duration }
				);
		},

		fade : function( slider, direction, position ) {
			var data = slider.data('appthemes_slider'),
				next = slider.find('.attachment_' + position ),
				curr = slider.find('.current');

			next.show();

			curr.fadeOut(
				data.options.duration,
				function() {
					$(this).removeClass('current');
					next.addClass('current');
				}
			);
		}
	};

	$.fn.appthemes_slider = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.appthemes_slider' );
		}
	};

	$.fn.appthemes_slider.defaults = {
		duration : 300,
		effect   : 'slide'
	};

})( jQuery );