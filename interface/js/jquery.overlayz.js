/**

==================
 Overlay-Z 1.0
==================

A simple, yet customizable jQuery overlay.

 License
---------

The MIT License (MIT)

Copyright � 2013 Joshua D. Burns
- JDBurnZ: https://github.com/JDBurnZ
- Message In Action: https://www.messageinaction.com

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

(function($) {
	$.overlayz = function(arg) {
		var arg_datatype = typeof arg;
		if(arg_datatype == 'string') {
			// If a string is passed, return an overlay with a
			// corresponding alias. If no overlay is found, returns
			// undefined.
			return $.overlayz._alias.lookup(arg);
		} else if(arg_datatype == 'object') {
			// If an object is passed, instantiate and return a new
			// overlay.
			return $.overlayz._new(arg);
		}
	};

	// Mechanism for handling and storing overlays with aliases.
	$.overlayz._alias = {
		'overlays': {
			// Aliased overlays are stored within this object. Key is the
			// alias of the overlay, value is a reference to the instance
			// of the overlay itself.
		},
		'lookup': function(alias) {
			if(alias in $.overlayz._alias.overlays) {
				return $.overlayz._alias.overlays[alias];
			}
		},
		'add': function(alias, $overlay) {
			$.overlayz._alias.overlays[alias] = $overlay;
		},
		'remove': function(alias) {
			if(alias in this.overlays) {
				delete this.overlays[alias];
			}
		}
	};

	$.overlayz._new = function(options) {
		var $overlayz = $('<div class="overlayz">').css($.overlayz._css.main).hide();
		var $overlayz_container = $('<div class="overlayz-container">').css($.overlayz._css.container);
		var $overlayz_cell      = $('<div class="overlayz-cell">').css($.overlayz._css.cell);
		var $overlayz_body      = $('<div class="overlayz-body">').css($.overlayz._css.body);
		var $overlayz_close     = $('<div class="overlayz-close">').css($.overlayz._css.close).html('&times;');

		$overlayz.appendTo(document.body);
		$overlayz.append($overlayz_container);
		$overlayz_container.append($overlayz_cell);

		if(options['close-actions'] !== false) {
			$overlayz_cell.append($overlayz_body, $overlayz_close);
		}

		$overlayz.overlayz = {
			'body': $overlayz_body,
			'css': function(css) {
				if('main' in css) {
					$overlayz.css(css.main);
				}
				if('container' in css) {
					$overlayz_container.css(css.container);
				}
				if('cell' in css) {
					$overlayz_cell.css(css.cell);
				}
				if('close' in css) {
					$overlayz_close.css(css.close)
				}
				if('body' in css) {
					$overlayz_body.css(css.body);
				}
				return $overlayz;
			},
			'html': function(body) {
				$overlayz_body.html(body);
				return $overlayz;
			},
			'close': function(speed, callback) {
				//if(typeof animation === 'undefined') {
				//	animation = 'fadeOut';
				//} else if(animation === false) {
				//	animation = 0;
				//}
				if(typeof speed === 'undefined') {
					speed = 'fast';
				}
				if(typeof callback === 'function') {
					$overlayz.fadeOut(speed, callback);
				} else {
					$overlayz.fadeOut(speed, function() {
						$overlayz.remove();
					});
				}
				return $overlayz;
			},
			'remove': function() {
				if(this.alias != undefined) {
					$.overlayz._alias.remove(this.alias);
				}
				$overlayz.remove();
			}
		};
		
		$overlayz.close = $overlayz.overlayz.close;

		// If CSS parameters have been specified, apply them to the overlay.
		if('css' in options) {
			$overlayz.overlayz.css(options.css);
		}

		// If HTML parameters have been specified, apply them to the
		// overlay.
		if('html' in options) {
			$overlayz.overlayz.html(options.html);
		}

		// If the overlay has an alias specified, add it to our lookup
		// table.
		if('alias' in options) {
			$overlayz.overlayz.alias = options.alias;
			$.overlayz._alias.add(options.alias, $overlayz);
		}

		if(!('close-actions' in options) || options['close-actions'] == true) {
			// When the escape key is pressed, close the overlay if it is displayed.
			$(document.body).on('keydown.overlayz-close', function(event) {
				if(event.keyCode == 27 && $overlayz.is(':visible')) {
					$overlayz.overlayz.close();
				}
			});

			// When a user clicks the background (outside the body of the overlay,
			// close the overlay. We implement this in a two-step down and up
			// trigger to ensure the user both clicks down and up within the outer
			// area to prevent scenarios where the user may click inside the body
			// and release on the side, which would unexpectedly close the overlay.
			var close_triggered_bg = false;
			var close_triggered_icon = false;
			$overlayz.on('mousedown.overlayz-close-trigger, touchstart.overlayz-close-trigger', function(event) {
				close_triggered_bg = false;
				close_triggered_icon = false;
				if($overlayz.is(':visible')) {
					var $target = $(event.target);
					if($target.hasClass('overlayz-cell')) {
						close_triggered_bg = true;
					} else if($target.hasClass('overlayz-close')) {
						close_triggered_icon = true;
					}
				}
			});
			$overlayz.on('mouseup.overlayz-close-trigger, touchend.overlayz-close-trigger', function(event) {
				if($overlayz.is(':visible')) {
					var $target = $(event.target);
					if(
						(close_triggered_bg && $target.hasClass('overlayz-cell'))
						||
						(close_triggered_icon && $target.hasClass('overlayz-close'))
					) {
						$overlayz.overlayz.close();
					}
				}
				close_triggered_bg = false;
				close_triggered_icon = false;
			});
		}

		return $overlayz;
	};

	// Defines the default formatting of the overlays.
	$.overlayz._css = {
		'main': {},
		'container': {},
		'cell': {},
		'close': {},
		'body': {}
	};
})(jQuery);
