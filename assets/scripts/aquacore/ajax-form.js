var AquaCore = AquaCore || {};
(function($) {
	AquaCore.AjaxForm = function(form, options) {
		var self = this;
		this.option(options);
		this.submitting = false;
		this.button = null;
		this.data = null;
		this.form = form;
		$("[type=submit]", this.form).bind("click", function() { self.button = this; });
		$(this.form).bind("submit", function(e) { self.submit(e); });
	};
	AquaCore.AjaxForm.prototype = $.extend({}, AquaCore.prototype, {
		options: {
			cache: false,
			async: false,
			beforeSend: null,
			complete: null,
			success: null,
			error: null
		},
		hasFiles: function() {
			return Boolean($("input[type=file]", this.form).val());
		},
		submit: function(e) {
			var self = this,
				data = [],
				options = {},
				fields, i;
			if(this.submitting) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
			if(this.hasFiles()) {
				if(typeof window.FormData !== "undefined") {
					data = new FormData;
				} else {
					return true;
				}
			}
			e.preventDefault();
			e.stopPropagation();
			this.submitting = true;
			fields = $("input, select, button, textarea", this.form);
			if($(this.form).attr("id")) {
				$.merge(fields, $("[form=\"" + $(this.form).attr("id") + "\"]"));
			}
			for(i = 0; i < fields.length; ++i) {
				if( !fields.eq(i).attr("name")
					|| fields.eq(i).attr("type") === "submit"
					|| ((fields.eq(i).attr("type") === "checkbox" || fields.eq(i).attr("type") === "radio") && !fields.eq(i).is(":checked"))
					) {
					continue;
				}
				if($.isArray(data)) {
					data.push({
						"name": fields.eq(i).attr("name"),
						"value": fields.eq(i).val()
					});
				} else {
					if(fields.eq(i).attr("type") === "file") {
						if(fields[i].files.length === 1) {
							data.append(fields.eq(i).attr("name"), fields[i].files[0]);
						} else for(var j = 0; j < fields[i].files.length; ++j) {
							data.append(fields.eq(i).attr("name") + "[" + j + "]", fields[i].files[j]);
						}
					} else {
						data.append(fields.eq(i).attr("name"), fields.eq(i).val());
					}
				}
			}
			if(this.button && $(this.button).attr("name")) {
				if($.isArray(data)) {
					data.push({
						"name": $(this.button).attr("name"),
						"value": $(this.button).val()
					});
				} else {
					data.append($(this.button).attr("name"), $(this.button).val());
				}
			}
			options.data = data;
			if($(this.form).attr("method")) {
				options.type = $(this.form).attr("method");
			}
			if($(this.form).attr("action")) {
				options.url = $(this.form).attr("action");
			}
			if(!$.isArray(data)) {
				options.processData = false;
				options.contentType = false;
				options.mimeType    = "multipart/form-data";
			}
			$.ajax($.extend(options, this.options, {
					beforeSend: function(jqXHR, settings) {
						if(self.options.beforeSend) {
							return self.options.beforeSend.call(self, jqXHR, settings);
						}
					},
					complete: function(jqXHR, status) {
						self.button = null;
						self.submitting = false;
						if(self.options.complete) {
							return self.options.complete.call(self, jqXHR, status);
						}
					},
					success: function(data, status, jqXHR) {
						if(self.options.success) {
							return self.options.success.call(self, data, status, jqXHR);
						}
					},
					error: function(jqXHR, status, error) {
						if(self.options.error) {
							return self.options.error.call(self, jqXHR, status, error);
						}
					}
				}));
			return false;
		}
	});
}(jQuery));
