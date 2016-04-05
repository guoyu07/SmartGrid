(function($) {

	var selectedCheckRows,
		methods,
		inputFocus,
		pfsXHR = {},
		gridSettings = [];

	selectedCheckRows = function(gridId) {
		
	};

	methods = {
		init: function(options) {
			var settings = $.extend({
				ajaxUpdate: [],
				ajaxVar: 'ajax',
				ajaxType: 'GET',
				// csrfTokenName: null,
				// csrfToken: null,
				loadingClass: 'pfs-loading',
				selectableRows: 1,
				enableHistory: false,
				dataLinkAjax: [
					'a[data-pfs-sg-ajax-link]'
				],
				dataFormAjax: [
					'form[data-pfs-sg-ajax-pagination]',
					'form[data-pfs-sg-ajax-filter]'
				]
					// ajaxUpdateError: function() {}
					// beforeAjaxUpdate: function(id) {},
					// afterAjaxUpdate: function(id, data) {},
					// selectionChange: function(id) {},
					///updateSelector: ['#id']
			},
			options || {});

			return this.each(function() {
				var $grid = $(this),
					id = $grid.attr('id');
				gridSettings[id] = settings;

				if (settings.ajaxUpdate.length > 0 || settings.ajaxUpdate === true) {
					var eventCb = function(e) {
						e.preventDefault();
						e.stopPropagation();

						var href = $(this).attr('href') || $(this).attr('action');
						var data = $(this).prop('tagName').toLowerCase() === 'form'
							? $(this).serialize()
							: undefined;
						$('#' + id).pfsSmartGrid('update', {
							url: href,
							data: data
						});
					};

					$grid.on('click', settings.dataLinkAjax.join(','), eventCb);
					$grid.on('submit', settings.dataFormAjax.join(','), eventCb);
					$grid.on('keydown', 'input', function(e) {
						if (e.keyCode === 13) {
							inputFocus = $(this);
						}
					});
				}
				
				if (settings.selectableRows > 0) {
					
				}
				
				/*
				$grid.on('click', '.dropdown-toggle', function(e) {
					dropDownFixPosition($('button'), $('.dropdown-menu'), $(this));
					
					function dropDownFixPosition(button, dropdown, $parent){
						
						setTimeout(function() {
							var dropDown = $parent.parent().find('.dropdown-menu');
							dropDown.each(function(index, el) {
								var $dp = $(el);
								var offset = $dp.offset();
								var dropDownWidth = $dp.width();						
								$dp.css('position', 'fixed');
								$dp.css('top', offset.top-2 +'px');
								$dp.css('left', offset.left + 'px');
								$dp.css('width', dropDownWidth);
								
							});
						}, 200);
						
						//var dropDownTop = button.offset().top + button.outerHeight();
						//dropdown.css('top', dropDownTop + "px");
						//dropdown.css('left', button.offset().left + "px");
					}
				});				*/

				$(window).on('resize', function() {
					$('#' + id).pfsSmartGrid('redrawTable');
				});
				$(window).trigger('resize');
			});
		},
		update: function(options) {
			var customError;
			if (options && options.error !== undefined) {
				customError = options.error;
				delete options.error;
			}

			return this.each(function() {
				var $form,
					$grid = $(this),
					id = $grid.attr('id'),
					settings = gridSettings[id];

				var renderUpdate = function(data) {
					if (settings.updateSelector === undefined) {
						var tableBody = $(data).find('#' + id);
						tableBody.each(function(index, el) {
							$('#' + id).html( $(el).html() );
							if (inputFocus !== undefined) {
								var tag = inputFocus.prop('tagName');
								var name = inputFocus.attr('name');
								var findFocus = $('#' + id).find(tag + '[name="' + name + '"]');
								findFocus.each(function(index, el) {
									$(el).focus();
									var elValue = $(el).val();
									$(el).val('');
									$(el).val(elValue);
								});
							}
						});
					}

					$('#' + id).pfsSmartGrid('redrawTable');
				};

				options = $.extend({
					type: settings.ajaxType,
					url: $grid,
					success: function(data) {
						renderUpdate(data);

						if (settings.afterAjaxUpdate !== undefined) {
							settings.afterAjaxUpdate(id, data);
						}
						if (settings.selectableRows > 0) {
							selectedCheckRows(id);
						}
					},
					complete: function() {
						pfsXHR[id] = null;
						// remove loading class
					},
					error: function(XHR, textStatus, errorThrown) {
						var ret,
							err;
						if (XHR.readyState === 0 || XHR.status === 0) {
							return;
						}
						if (customError !== undefined) {
							ret = customError(XHR);
							if (ret !== undefined && !ret) {
								return;
							}
						}
						switch (textStatus) {
							case 'timeout':
								err = 'The request timed out';
								break;
							case 'parsererror':
								err = 'Parser error!';
								break;
							case 'error':
								if (XHR.status && !/^\s*$/.test(XHR.status)) {
									err = 'Error ' + XHR.status;
								} else {
									err = 'Error';
								}
								if (XHR.responseText && !/^\s*$/.test(
									XHR.responseText)) {
									err = err + ': ' + XHR.responseText;
								}
								break;
						}


						if (settings.ajaxUpdateError !== undefined) {
							settings.ajaxUpdateError(XHR, textStatus,
								errorThrown, err, id);
						} else if (err) {
							alert(err);
						}
					}
				},
				options || {});
				// if (options.type === 'GET') {
				//    if (options.data !== undefined) {
				//        options.url += options.url.indexOf('?') <= -1 ? '?' : '&';
				//        options.url += options.data;
				//       options.data = {}; 
				//    }
				// }
				// if (settings.csrfTokenName && settings.csrfToken) {
				//   if (typeof options.data === 'string') {
				//		 options.data += '&' + settings.csrfTokenName + '=' + settings.csrfToken;
				//	 } else if (options.data === undefined) {
				//		 options.data = {};
				//		 options.data[settings.csrfTokenName] = settings.csrfToken;
				//	 } else {
				// 		 options.data[settings.csrfTokenName] = settings.csrfToken;
				// 	 }
				// }

				if (pfsXHR[id] != null) {
					pfsXHR[id].abort();
				}

				// show loading class
				if (settings.enableHistory && window.history) {
					var url = decodeURIComponent(options.url);
					window.history.pushState({path: url}, '', url);
				}
				if (settings.ajaxUpdate !== false) {
					/*if (settings.ajaxVar) {
					 console.log(options.data);
					 }*/
					if (settings.beforeAjaxUpdate !== undefined) {
						settings.beforeAjaxUpdate(id, options);
					}
					pfsXHR[id] = $.ajax(options);
				}
			});
		},
		redrawTable: function() {
			return this.each(function() {
				var $grid = $(this),
					id = $grid.attr('id'),
					$tableContainer = $grid.find('.pfs-sg-container'),
					$tableBody = $tableContainer.find('.body'),
					$tableHeader = $tableContainer.find('.header'),
					$tableFooter = $tableContainer.find('.footer'),
					$panelTop = $tableContainer.find('.panel-option-top'),
					$panelBottom = $tableContainer.find('.panel-option-bottom');

				var elemTableBody = $tableBody.find('table>tbody>tr:first-child>td');
				elemTableBody.each(function(index, el) {
					var width = $(this).innerWidth();
					var lastPadding = (elemTableBody.length - 1 === index
						? scrollBarWidth
						: 0);
					var lastHeader = $tableHeader.find('th[data-pfs-sg-title-position="' + (index + 1) + '"]').
						innerWidth(width + lastPadding);
					var lastFooter = $tableFooter.find('th[data-pfs-sg-title-position="' + (index + 1) + '"]').
						innerWidth(width + lastPadding);
				});

				var scrollBarWidth = function() {
					var inner = $('<p/>').addClass('fixed-table-scroll-inner');
					var outer = $('<div/>').addClass('fixed-table-scroll-outer');
					var width1,
						width2;
					outer.append(inner);
					$('body').append(outer);
					width1 = inner[0].offsetWidth;
					outer.css('overflow', 'scroll');
					width2 = inner[0].offsetWidth;
					if (width1 === width2) {
						width2 = outer[0].clientWidth;
					}
					outer.remove();
					return width1 - width2;
				};
			});
		}
	};

	$.fn.pfsSmartGrid = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(
				arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.pfsSmartGrid');
			return false;
		}
	};

})(jQuery);