/**
 * Functions for HtmlEditorFields in the back end.
 * Includes the JS for the ImageUpload forms. 
 * 
 * Relies on the jquery.form.js plugin to power the 
 * ajax / iframe submissions
 */

(function($) {

	$.entwine('ss', function($) {

		/**
		 * Inserts and edits links in an html editor, including internal/external web links,
		 * links to files on the webserver, email addresses, and anchors in the existing html content.
		 * Every variation has its own fields (e.g. a "target" attribute doesn't make sense for an email link),
		 * which are toggled through a type dropdown. Variations share fields, so there's only one "title" field in the form.
		 */
		$('form.htmleditorfield-linkform').entwine({

			redraw: function() {
				this._super();

				var linkType = this.find(':input[name=LinkType]:checked').val();

				this.addAnchorSelector();

				// Toggle field visibility depending on the link type.
				this.find('div.content .field').hide();
				this.find('.field#LinkType').show();
				this.find('.field#' + linkType).show();
				if(linkType == 'internal' || linkType == 'anchor') this.find('.field#Anchor').show();
				if(linkType !== 'email') this.find('.field#TargetBlank').show();
				if(linkType == 'anchor') this.find('.field#AnchorSelector').show();

				var linkableObjects = $('#LinkableObjects').data('map');

				for(var obj in linkableObjects) {
					if(linkType == obj) this.find('.field#' + obj + 'LinkID').show();
				}

				this.find('.field#Description').show();

			},
			/**
			 * @return Object Keys: 'href', 'target', 'title'
			 */
			getLinkAttributes: function() {
				var href, target = null, anchor = this.find(':input[name=Anchor]').val();
				
				// Determine target
				if(this.find(':input[name=TargetBlank]').is(':checked')) target = '_blank';
				
				// All other attributes
				switch(this.find(':input[name=LinkType]:checked').val()) {
					case 'internal':
						href = '[sitetree_link,id=' + this.find(':input[name=internal]').val() + ']';
						if(anchor) href += '#' + anchor;
						break;

					case 'anchor':
						href = '#' + anchor; 
						break;
					
					case 'file':
						href = '[file_link,id=' + this.find(':input[name=file]').val() + ']';
						target = '_blank';
						break;
					
					case 'email':
						href = 'mailto:' + this.find(':input[name=email]').val();
						target = null;
						break;
					default:
						href = this.find(':input[name=external]').val();
						// Prefix the URL with "http://" if no prefix is found
						if(href.indexOf('://') == -1) href = 'http://' + href;
						break;
				}

				var linkType = this.find(':input[name=LinkType]:checked').val();
				var linkableObjects = $('#LinkableObjects').data('map');

				for(var obj in linkableObjects) {
					if(linkType == obj) {
						href = '[object_link,id=' + 1 + ',type="' + obj + '"]';
					}
				}

				return {
					href : href,
					target : target,
					title : this.find(':input[name=Description]').val()
				};
			},

			/**
			 * Updates the state of the dialog inputs to match the editor selection.
			 * If selection does not contain a link, resets the fields.
			 */
			updateFromEditor: function() {
				var htmlTagPattern = /<\S[^><]*>/g, fieldName, data = this.getCurrentLink();

				if(data) {
					for(fieldName in data) {
						var el = this.find(':input[name=' + fieldName + ']'), selected = data[fieldName];
						// Remove html tags in the selected text that occurs on IE browsers
						if(typeof(selected) == 'string') selected = selected.replace(htmlTagPattern, '');

						// Set values and invoke the triggers (e.g. for TreeDropdownField).
						if(el.is(':checkbox')) {
							el.prop('checked', selected).change();
						} else if(el.is(':radio')) {
							el.val([selected]).change();
						} else if($('#' + fieldName).hasClass('dataobjectpicker')) {
							var helper = this.find(':input[name=' + fieldName + '_helper]');
							ajaxRequest = $.getJSON(
								helper.attr('rel') + '/Get',
								'request=' + selected,
								function(data){
									helper.val(data.title).change();
								}
							);
							el.val(selected).change();
						} else {
							el.val(selected).change();
						}
					}
				}
			},

			/**
			 * Return information about the currently selected link, suitable for population of the link form.
			 *
			 * Returns null if no link was currently selected.
			 */
			getCurrentLink: function() {
				var selectedEl = this.getSelection(),
					href = "", target = "", title = "", action = "insert", style_class = "";

				// We use a separate field for linkDataSource from tinyMCE.linkElement.
				// If we have selected beyond the range of an <a> element, then use use that <a> element to get the link data source,
				// but we don't use it as the destination for the link insertion
				var linkDataSource = null;
				if(selectedEl.length) {
					if(selectedEl.is('a')) {
						// Element is a link
						linkDataSource = selectedEl;
					// TODO Limit to inline elements, otherwise will also apply to e.g. paragraphs which already contain one or more links
					// } else if((selectedEl.find('a').length)) {
						// 	// Element contains a link
						// 	var firstLinkEl = selectedEl.find('a:first');
						// 	if(firstLinkEl.length) linkDataSource = firstLinkEl;
					} else {
						// Element is a child of a link
						linkDataSource = selectedEl = selectedEl.parents('a:first');
					}
				}
				if(linkDataSource && linkDataSource.length) this.modifySelection(function(ed){
					ed.selectNode(linkDataSource[0]);
				});

				// Is anchor not a link
				if (!linkDataSource.attr('href')) linkDataSource = null;

				if (linkDataSource) {
					href = linkDataSource.attr('href');
					target = linkDataSource.attr('target');
					title = linkDataSource.attr('title');
					style_class = linkDataSource.attr('class');
					href = this.getEditor().cleanLink(href, linkDataSource);
					action = "update";
				}

				if(href.match(/^mailto:(.*)$/)) {
					return {
						LinkType: 'email',
						email: RegExp.$1,
						Description: title
					};
				} else if(href.match(/^(assets\/.*)$/) || href.match(/^\[file_link\s*(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/)) {
					return {
						LinkType: 'file',
						file: RegExp.$1,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if(href.match(/^#(.*)$/)) {
					return {
						LinkType: 'anchor',
						Anchor: RegExp.$1,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if(href.match(/^\[sitetree_link(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/i)) {
					return {
						LinkType: 'internal',
						internal: RegExp.$1,
						Anchor: RegExp.$2 ? RegExp.$2.substr(1) : '',
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if(href.match(/^\[prwrapper_link(?:\s*|%20|,)?id=([0-9]+)\]$/i)) {
					return {
						LinkType: 'prlink',
						PrWrapperLinkID: RegExp.$1,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if(href.match(/^\[nemr_link(?:\s*|%20|,)?id=([0-9]+)\]$/i)) {
					return {
						LinkType: 'nemrlink',
						NemrLinkID: RegExp.$1,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if(href) {
					if(href.match(/^\[object_link(?:\s*|%20|,)?id=([0-9]+)?(?:\s*|%20|,)?type="([A-z,0-9]+)"\]/i)) {
						
						var array = {
							LinkType: RegExp.$2,
							Description: title,
							TargetBlank: target ? true : false
						}

						array[RegExp.$2 + 'LinkID'] = RegExp.$1;

						return array;
					} else {
						return {
							LinkType: 'external',
							external: href,
							Description: title,
							TargetBlank: target ? true : false
						};
					}
				} else {
					// No link/invalid link selected.
					return null;
				}
			}
		});		
	});
})(jQuery);