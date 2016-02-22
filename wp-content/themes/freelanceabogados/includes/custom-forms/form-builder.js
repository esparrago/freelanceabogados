/**
 * jQuery Form Builder Plugin
 * Copyright (c) 2009 Mike Botsko, Botsko.net LLC (http://www.botsko.net)
 * http://www.botsko.net/blog/2009/04/jquery-form-builder-plugin/
 * Originally designed for AspenMSM, a CMS product from Trellis Development
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Copyright notice and license must remain intact for legal use
 */
/**
 * Forked by AppThemes on 09/09/2011
 */
(function ($) {
	$.fn.formbuilder = function (options) {
		var frmb_id = 'frmb-' + $('ul[id^=frmb-]').length++;
		return this.each(function () {
			var ul_obj = $(this).append('<ul id="' + frmb_id + '" class="frmb"></ul>').find('ul');
			var field = '';
			var field_type = '';
			var last_id = 1;
			var help;
			// Add a unique class to the current element
			$(ul_obj).addClass(frmb_id);

			var appValidate = function(e) {
				$('label.error').remove();
				$('.frmb input[type=text]').each(function(){
					var valid = true;
					if ( $(this).val() == '' ) {
						$(this).after('<label for="'+ $(this).attr('id') +'" class="error">'+ validateL10n.required +'</label>');
						valid = false;
					}
					if ( ! valid ) {
						e.preventDefault();
					}
				});
			};

			// Create form control select box and add into the editor
			$(function() {
				var body = '';
				var box_content = '';
				var box_id = frmb_id + '-control-box';

				body += '<a href="input_text" class="button">' + l10n.text + '</a>';
				body += '<a href="textarea" class="button">' + l10n.textarea + '</a>';
				body += '<a href="checkbox" class="button">' + l10n.checkboxes + '</a>';
				body += '<a href="radio" class="button">' + l10n.radio + '</a>';
				body += '<a href="select" class="button">' + l10n.select + '</a>';
				body += '<a href="file" class="button">' + l10n.file + '</a>';

				box_content = '<div class="toolbar"><div id="' + box_id + '"  class="button-group">' + body + '</div></div>';

				$(ul_obj).before(box_content);

				$('#post').submit(function(e){
					$('#va_form').val( $(ul_obj).serializeFormList({ prepend: 'va_form' }) );
					$('.frmb input[type=text]').on('input focusout', function(e){appValidate(e);});
					appValidate(e);
				});

				$('.button-group > .button' ).click(function () {
					appendNewField($(this).attr('href'));
					$(this).val(0).blur();
					// This solves the scrollTo dependency
					$('html, body').animate({
						scrollTop: $('#frm-' + (last_id - 1) + '-item').offset().top
					}, 500);
					return false;
				});
			});

			// Wrapper for adding a new field
			var appendNewField = function (type, values, options, required, field_id) {
					field = '';
					field_type = type;
					if (typeof (values) === 'undefined') {
						values = '';
					}
					switch (type) {
					case 'input_text':
						appendTextInput(values, required, field_id);
						break;
					case 'textarea':
						appendTextarea(values, required, field_id);
						break;
					case 'file':
						appendFileUpload(values, options, required, field_id);
						break;
					case 'checkbox':
						appendCheckboxGroup(values, options, required, field_id);
						break;
					case 'radio':
						appendRadioGroup(values, options, required, field_id);
						break;
					case 'select':
						appendSelectList(values, options, required, field_id);
						break;
					}
				};
			// single line input type="text"
			var appendTextInput = function (values, required, field_id) {
					field += '<label>' + l10n.label + '</label>';
					id = last_id;
					field += '<input class="fld-title" id="title-' + id + '" type="text" value="' + values + '" data-field-id="' + field_id + '" />';
					help = '';
					appendFieldLi(l10n.text, field, required, help);
				};
			// multi-line textarea
			var appendTextarea = function (values, required, field_id) {
					field += '<label>' + l10n.label + '</label>';
					id = last_id;
					field += '<input type="text" id="title-' + id + '" value="' + values + '" data-field-id="' + field_id + '" />';
					help = '';
					appendFieldLi(l10n.textarea_field, field, required, help);
				};
			// input type="file"
			var appendFileUpload = function (values, options, required, field_id) {
					field += '<label>' + l10n.label + '</label>';
					id = last_id;
					field += '<input type="text" id="title-' + id + '" value="' + values + '" data-field-id="' + field_id + '" />';
					if (typeof (options) === 'undefined') {
						options = 'pdf';
					}
					field += '<div class="false-label">' + l10n.file_extensions + '</div>';
					field += '<div class="fields">';
					field += '<input name="file-upload-ext-' + id + '" type="text" value="' + options + '" />';
					field += '<div class="field-tip">' + l10n.file_tip; + '</div>';
					field += '</div>';
					field += '</div>';
					help = '';
					appendFieldLi(l10n.file, field, required, help);
				};
			// adds a checkbox element
			var appendCheckboxGroup = function (values, options, required, field_id) {
					var title = '';
					if (typeof (options) === 'object') {
						title = options[0];
					}
					field += '<div class="chk_group">';
					field += '<div class="frm-fld"><label>' + l10n.title + '</label>';
					id = last_id;
					field += '<input type="text" name="title" id="title-' + id + '" value="' + title + '" data-field-id="' + field_id + '" /></div>';
					field += '<div class="false-label">' + l10n.select_options + '</div>';
					field += '<div class="fields">';
					if (typeof (values) === 'object') {
						for (i = 0; i < values.length; i++) {
							field += checkboxFieldHtml(values[i]);
						}
					}
					else {
						field += checkboxFieldHtml('');
					}
					field += '<div class="add-area"><a href="#" class="add add_ck">' + l10n.add + '</a></div>';
					//field += '<div class="add-area"><a href="#" class="add add_ck button icon add"></a></div>';
					field += '</div>';
					field += '</div>';
					help = '';
					appendFieldLi(l10n.checkbox_group, field, required, help);
				};
			// Checkbox field html, since there may be multiple
			var checkboxFieldHtml = function (values) {
					var checked = false;
					var value = '';
					if (typeof (values) === 'object') {
						value = values[0];
						checked = ( values[1] === 'false' || values[1] === 'undefined' ) ? false : true;
					}
					field = '';
					field += '<div>';
					field += '<input type="checkbox"' + (checked ? ' checked="checked"' : '') + ' />';
					field += '<input type="text" value="' + value + '" />';
					field += '<a href="#" class="remove" title="' + l10n.remove_message + '">' + l10n.remove + '</a>';
					//field += '<a href="#" class="button icon remove"></a>';
					field += '</div>';
					return field;
				};
			// adds a radio element
			var appendRadioGroup = function (values, options, required, field_id) {
					var title = '';
					if (typeof (options) === 'object') {
						title = options[0];
					}
					field += '<div class="rd_group">';
					field += '<div class="frm-fld"><label>' + l10n.title + '</label>';
					field += '<input type="text" name="title" value="' + title + '" data-field-id="' + field_id + '" /></div>';
					field += '<div class="false-label">' + l10n.select_options + '</div>';
					field += '<div class="fields">';
					if (typeof (values) === 'object') {
						for (i = 0; i < values.length; i++) {
							field += radioFieldHtml(values[i], 'frm-' + last_id + '-fld');
						}
					}
					else {
						field += radioFieldHtml('', 'frm-' + last_id + '-fld');
					}
					field += '<div class="add-area"><a href="#" class="add add_rd">' + l10n.add + '</a></div>';
					field += '</div>';
					field += '</div>';
					help = '';
					appendFieldLi(l10n.radio_group, field, required, help);
				};
			// Radio field html, since there may be multiple
			var radioFieldHtml = function (values, name) {
					var checked = false;
					var value = '';
					if (typeof (values) === 'object') {
						value = values[0];
						checked = ( values[1] === 'false' || values[1] === 'undefined' ) ? false : true;
					}
					field = '';
					field += '<div>';
					field += '<input type="radio"' + (checked ? ' checked="checked"' : '') + ' name="radio_' + name + '" />';
					field += '<input type="text" value="' + value + '" />';
					field += '<a href="#" class="remove" title="' + l10n.remove_message + '">' + l10n.remove + '</a>';
					field += '</div>';
					return field;
				};
			// adds a select/option element
			var appendSelectList = function (values, options, required, field_id) {
					var multiple = false;
					var title = '';
					if (typeof (options) === 'object') {
						title = options[0];
						multiple = options[1] === 'true' ? true : false;
					}
					field += '<div class="opt_group">';
					field += '<div class="frm-fld"><label>' + l10n.title + '</label>';
					field += '<input type="text" name="title" value="' + title + '" data-field-id="' + field_id + '" /></div>';
					field += '';
					field += '<div class="false-label">' + l10n.select_options + '</div>';
					field += '<div class="fields">';
//					field += '<input type="checkbox" name="multiple"' + (multiple ? 'checked="checked"' : '') + '>';
//					field += '<label class="auto">' + l10n.selections_message + '</label>';
					if (typeof (values) === 'object') {
						for (i = 0; i < values.length; i++) {
							field += selectFieldHtml(values[i], multiple);
						}
					}
					else {
						field += selectFieldHtml('', multiple);
					}
					field += '<div class="add-area"><a href="#" class="add add_opt">' + l10n.add + '</a></div>';
					field += '</div>';
					field += '</div>';
					help = '';
					appendFieldLi(l10n.select, field, required, help);
				};
			// Select field html, since there may be multiple
			var selectFieldHtml = function (values, multiple) {
					if (multiple) {
						return checkboxFieldHtml(values);
					}
					else {
						return radioFieldHtml(values);
					}
				};
			// Appends the new field markup to the editor
			var appendFieldLi = function (title, field_html, required, help) {
					if (required) {
						required = required === 'checked' ? true : false;
					}
					var li = '';
					li += '<li id="frm-' + last_id + '-item" data-field-type="' + field_type + '">';
					li += '<div class="legend">';
					li += '<a id="frm-' + last_id + '" class="toggle-form" href="#">' + l10n.hide + '</a> ';
					li += '<a id="del_' + last_id + '" class="del-button delete-confirm" href="#" title="' + l10n.remove + '"><span>' + l10n.remove + '</span></a>';
					li += '<strong id="txt-title-' + last_id + '">' + title + '</strong></div>';
					li += '<div id="frm-' + last_id + '-fld" class="frm-holder">';
					li += '<div class="frm-elements">';
					li += '<div class="frm-fld"><label for="required-' + last_id + '">' + l10n.required + '</label>';
					li += '<input class="required" type="checkbox" value="1" name="required-' + last_id + '" id="required-' + last_id + '"' + (required ? ' checked="checked"' : '') + ' /></div>';
					li += field;
					li += '</div>';
					li += '</div>';
					li += '</li>';
					$(ul_obj).append(li);
					$('#frm-' + last_id + '-item').hide();
					$('#frm-' + last_id + '-item').animate({
						opacity: 'show',
						height: 'show'
					}, 'slow');
					last_id++;
				};
			// handle field delete links
			$('.frmb').delegate('.remove', 'click', function () {
				$(this).parent('div').animate({
					opacity: 'hide',
					height: 'hide',
					marginBottom: '0px'
				}, 'fast', function () {
					$(this).remove();
				});
				return false;
			});
			// handle field display/hide
			$('.frmb').delegate('.toggle-form', 'click', function () {
				var target = $(this).attr("id");
				if ($(this).html() === l10n.hide) {
					$(this).removeClass('open').addClass('closed').html(l10n.show);
					$('#' + target + '-fld').animate({
						opacity: 'hide',
						height: 'hide'
					}, 'slow');
					return false;
				}
				if ($(this).html() === l10n.show) {
					$(this).removeClass('closed').addClass('open').html(l10n.hide);
					$('#' + target + '-fld').animate({
						opacity: 'show',
						height: 'show'
					}, 'slow');
					return false;
				}
				return false;
			});
			// handle delete confirmation
			$('.frmb').delegate('.delete-confirm', 'click', function () {
				var delete_id = $(this).attr("id").replace(/del_/, '');
				if (confirm($(this).attr('title'))) {
					$('#frm-' + delete_id + '-item').animate({
						opacity: 'hide',
						height: 'hide',
						marginBottom: '0px'
					}, 'slow', function () {
						$(this).remove();
					});
				}
				return false;
			});
			// Attach a callback to add new checkboxes
			$('.frmb').delegate('.add_ck', 'click', function () {
				$(this).parent().before(checkboxFieldHtml());
				return false;
			});
			// Attach a callback to add new options
			$('.frmb').delegate('.add_opt', 'click', function () {
				$(this).parent().before(selectFieldHtml('', false));
				return false;
			});
			// Attach a callback to add new radio fields
			$('.frmb').delegate('.add_rd', 'click', function () {
				$(this).parent().before(radioFieldHtml(false, $(this).parents('.frm-holder').attr('id')));
				return false;
			});

			// Json parser to build the form builder
			var fromJson = function (json) {
					var values = '';
					var options = false;
					var required = false;
					// Parse json
					$(json).each(function () {
						// checkbox type
						if (this.cssClass === 'checkbox') {
							options = [this.title];
							values = [];
							$.each(this.values, function () {
								values.push([this.value, this.baseline]);
							});
						}
						// radio type
						else if (this.cssClass === 'radio') {
							options = [this.title];
							values = [];
							$.each(this.values, function () {
								values.push([this.value, this.baseline]);
							});
						}
						// select type
						else if (this.cssClass === 'select') {
							options = [this.title, this.multiple];
							values = [];
							$.each(this.values, function () {
								values.push([this.value, this.baseline]);
							});
						}
						// file type
						else if (this.cssClass === 'file') {
							options = [this.extensions];
							values = [this.values];
						}
						else {
							values = [this.values];
						}
						appendNewField(this.cssClass, values, options, this.required, this.id);
					});
				};

				fromJson( jQuery.parseJSON( $('#va_form').val() ) );
		});
	};
})(jQuery);
/**
 * jQuery Form Builder List Serialization Plugin
 * Copyright (c) 2009 Mike Botsko, Botsko.net LLC (http://www.botsko.net)
 * Originally designed for AspenMSM, a CMS product from Trellis Development
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Copyright notice and license must remain intact for legal use
 * Modified from the serialize list plugin
 * http://www.botsko.net/blog/2009/01/jquery_serialize_list_plugin/
 */
(function ($) {
	$.fn.serializeFormList = function (options) {
		// Extend the configuration options with user-provided
		var defaults = {
			prepend: 'ul',
			is_child: false,
			attributes: ['data-field-type']
		};
		var opts = $.extend(defaults, options);
		if (!opts.is_child) {
			opts.prepend = '&' + opts.prepend;
		}
		var serialStr = '';
		// Begin the core plugin
		this.each(function () {
			var ul_obj = this;
			var li_count = 0;
			var c = 1;
			$(this).children().each(function () {
				for (att = 0; att < opts.attributes.length; att++) {
					var key = (opts.attributes[att] === 'data-field-type' ? 'cssClass' : opts.attributes[att]);
					serialStr += opts.prepend + '[' + li_count + '][' + key + ']=' + encodeURIComponent($(this).attr(opts.attributes[att]));
					// append the form field values
					if (opts.attributes[att] === 'data-field-type') {
						serialStr += opts.prepend + '[' + li_count + '][id]=' + encodeURIComponent($('#' + $(this).attr('id') + ' input[type=text]').data('field-id'));
						serialStr += opts.prepend + '[' + li_count + '][required]=' + encodeURIComponent($('#' + $(this).attr('id') + ' input.required').attr('checked'));
						switch ($(this).attr(opts.attributes[att])) {
						case 'input_text':
							serialStr += opts.prepend + '[' + li_count + '][values]=' + encodeURIComponent($('#' + $(this).attr('id') + ' input[type=text]').val());
							break;
						case 'textarea':
							serialStr += opts.prepend + '[' + li_count + '][values]=' + encodeURIComponent($('#' + $(this).attr('id') + ' input[type=text]').val());
							break;
						case 'file':
							serialStr += opts.prepend + '[' + li_count + '][values]=' + encodeURIComponent($('#' + $(this).attr('id') + ' input[type=text]').val());
							serialStr += opts.prepend + '[' + li_count + '][extensions]=' + encodeURIComponent($('input[name^=file-upload-ext]', this).val());
							break;
						case 'checkbox':
							c = 1;
							$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
								if ($(this).attr('name') === 'title') {
									serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
								}
								else {
									serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
									serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][baseline]=' + $(this).prev().attr('checked');
								}
								c++;
							});
							break;
						case 'radio':
							c = 1;
							$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
								if ($(this).attr('name') === 'title') {
									serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
								}
								else {
									serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
									serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][baseline]=' + $(this).prev().attr('checked');
								}
								c++;
							});
							break;
						case 'select':
							c = 1;
							serialStr += opts.prepend + '[' + li_count + '][multiple]=' + $('#' + $(this).attr('id') + ' input[name=multiple]').attr('checked');
							$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
								if ($(this).attr('name') === 'title') {
									serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
								}
								else {
									serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
									serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][baseline]=' + $(this).prev().attr('checked');
								}
								c++;
							});
							break;
						}
					}
				}
				li_count++;
			});
		});
		return (serialStr);
	};
})(jQuery);
