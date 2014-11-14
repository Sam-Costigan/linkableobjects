(function($) {

	$(document).ready(function() {
		var ajaxRequest;
		var timeout;
		var clearOnClick = true;
		
		function pick(li) {
			var idbase = li.parent().attr("id").substr(0,li.parent().attr("id").length - 12);
			var parent_div = li.closest('div');

			$('li', li.parent()).removeClass('picked');
			li.addClass('picked');
			var elementidsegments = li.attr('id').split('_');
			var dataobjectid = elementidsegments[elementidsegments.length - 1];
			parent_div.find('#' + idbase).val(dataobjectid);
			parent_div.find('#' + idbase + '_helper').val(li.attr('title'));
			var pos = li.position();

			if(pos.top < 0) {
				parent_div.find('#' + idbase + '_suggestions').scrollTop(
					$('#' + idbase + '_suggestions').scrollTop() + pos.top
				);
			} else if(pos.top + li.outerHeight() > $('#' + idbase + '_suggestions').height()) {
				parent_div.find('#' + idbase + '_suggestions').scrollTop(
					parent_div.find('#' + idbase + '_suggestions').scrollTop() +
					pos.top + li.outerHeight() -
					parent_div.find('#' + idbase + '_suggestions').height()
				);
			}
		}
		
		function hide_suggestions(ul) {
			ul.slideUp('fast', function(){ul.html('');});
		}
		
		$('form .DataObjectPickerHelper').live('keydown', function(event) {
			if (event.keyCode==$.ui.keyCode.ENTER) {
				return false;
			}
		});

		$('form .DataObjectPickerHelper').live('keyup', function(event) {
			var that = this;
			
			if (ajaxRequest) {
				ajaxRequest.abort();
			}
			if (timeout) clearTimeout(timeout);
			clearOnClick = false;

			// Check for empty string
			if (jQuery.trim($(this).val())=='') return;
			
			timeout = setTimeout(function(){ // Timeout 300
				$(that).closest('div').find('.DataObjectPickerMessage').html('Searching...');

				var idbase = $(that).attr("id").substr(0,$(that).attr("id").length - 7);
				
				ajaxRequest = $.getJSON($(that).attr('rel') + '/Suggest', 'request=' + $(that).val(), function(data){
					var i=-1;
					var lis = "";
					while(data[++i]) {
						var full;
						lis += "<li";
						for(var j in data[i]) {
							if(j == 'full') {
								full = data[i][j];
								continue;
							} else if(j == 'id') {
								data[i][j] = idbase + '_suggestion_' + data[i][j];
							}
							lis += " " + j + '="' + data[i][j] + '"';
						}
						lis += ">" + full + "</li>";
					}
					
					var suggestions = $(that).closest('div').find("#" + idbase + "_suggestions");
					suggestions.html(lis).slideDown('fast', function() {
						// Install handlers
						$(this).find('li').bind('mousedown', function(event) {
							pick($(this));
							clearOnClick = true;
							$(this).closest('div').find('.DataObjectPickerMessage').html('Selected. Type to search again.');
							$(this).closest('div').find('.DataObjectPickerHelper').blur();
						}).bind('mouseover', function(event) {
							$(this).siblings('li').removeClass('picked');
							$(this).addClass('picked');
						});
					});

					$(that).closest('div').find('.DataObjectPickerMessage').html('Click to select, type to search.');
				});
				
				return false;
			}, 500);
		});
		
		$('form .DataObjectPickerHelper').live('blur', function(event) {
			var idbase = $(this).attr("id").substr(0,$(this).attr("id").length - 7);
			var that = this;
			$(this).duetoclose = setTimeout(function(){
				hide_suggestions($(that).closest('div').find("#" + idbase + "_suggestions"));
			},100);
		});

		$('form .DataObjectPickerHelper').live('click', function(event) {
			// We can clear the field, as it was auto-filled, and someone is likely to be wanting to search.
			if (clearOnClick) $(this).val('');
		});
	});

})(jQuery);
