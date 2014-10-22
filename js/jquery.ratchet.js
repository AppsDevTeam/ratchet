/**
 *
 * @copyright Copyright (c) 2013 Ledvinka Vít
 * @author Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
(function($){
	$.extend({
		websocketSettings: {
			open: function(){},
			close: function(){},
			message: function(){},
			handler: {},
			options: {}
		},

		websocket: function(url, s) {

			var ws = WebSocket ? new WebSocket( url ) : {
				send: function(m){ return false },
				close: function(){}
			};

			ws._settings = $.extend($.websocketSettings, s);

			$(ws)
				.bind('open', ws._settings.open)
				.bind('close', ws._settings.close)
				.bind('message', function(e){
					var data = e.originalEvent.data;

					try {
						var parsed = JSON.parse(data);
						if (parsed.type !== undefined) {

							// Call type
							if (parsed.type == 'call') {
								if (ws._settings.handler[parsed.name]) {
									ws._settings.handler[parsed.name](parsed.data);
								} else {
									console.log('Missing handler named: ' + parsed.name);
								}
							}

						}
					} catch (e) {
					}

					ws._settings.message(data, e.originalEvent);
				});

			ws._send = ws.send;

			ws.send = function(path, data) {
				var m = { path : path, data : data};
				return this._send( JSON.stringify(m) );
			}

			$(window).unload(function(){ ws.close(); ws = null });

			return ws;
		}

	});
})(jQuery);