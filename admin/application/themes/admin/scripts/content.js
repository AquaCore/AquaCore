(function($) {
	var container = $("#sidebar"), tags;
	function updateDatePicker() {
		var date = $(this).datetimepicker("getDate"),
			text = $(this).closest("td").find(".ac-schedule-date");
		if(!date || (Math.floor((new Date).getTime() / 1000) * 1000) == date.getTime()) {
			text.text($(this).data("noDate"));
			$(this).val("");
		} else {
			var format = moment(date.getTime());
			text.html(
				format.format("MMMM DD, YYYY") +
					"<br/><span class=\"ac-post-schedule-time\">" +
					format.format("HH:mm:ss") +
					"</span>"
			);
		}
	}
	function filterTag(term) {
		var regex = new RegExp("^" + term.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&"), "i");
		var match = [];
		for(var key in AquaCore.settings["newsTags"]) {
			if(AquaCore.settings["newsTags"].hasOwnProperty(key) && regex.test(key)) {
				key = key
					.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#39;")
					.replace(/\//g, "&#x2F;");
				match.push({
					label: key + "<div class=\"ac-tag-count\">" + AquaCore.settings["newsTags"][key] + "</div>",
					value: key
				});
			}
		}
		return match;
	}
	tags = $("input[name=\"tags\"]", container);
	if(tags.length) {
		tags.bind("keydown", function(e) {
			if(e.keyCode === $.ui.keyCode.TAB && $(this).data("ui-autocomplete").menu.active) {
				e.preventDefault();
			}
		}).autocomplete({
			minLength: 2,
			focus: function() { return false; },
			source: function(request, response) {
				var val = $.trim(request.term.split(/,\s*/).pop());
				if(!val.length) {
					return;
				}
				response(filterTag(val));
			},
			select: function(e, ui) {
				var terms = this.value.split(/,\s*/);
				terms.pop();
				terms.push(ui.item.value);
				terms.push("");
				this.value = terms.join(", ");
				return false;
			},
			position: {
				my: "left top+1 0"
			}
		}).data("ui-autocomplete")._renderItem = function(ul, itm) {
			return $("<li/>")
				.data("item.autocomplete", item)
				.append("<a>" + item.label + "</a>")
				.appendTo(ul);
		}
	}
	$(".ac-post-schedule", container).each(function() {
		var timestamp = $(this).attr("timestamp"),
			options = {
				showOn: "button",
				buttonText: "",
				nextText: "",
				prevText: "",
				dateFormat: "yy-mm-dd",
				timeFormat: "HH:mm:ss",
				onSelect: updateDatePicker
			};
		if($(this).attr("name") === "archive_date") {
			options.afterUpdate = function(e, inst) {
				var current = $(".ui-datepicker-current", inst.dpDiv);
				current.off("click")
					.text(AquaCore.l("application", "none"))
					.on("click", function() {
						$.datepicker._clearDate(e);
					});
			};
			options.minDate = new Date();
			$(this).data("noDate", AquaCore.l("application", "none"));
		} else {
			$(this).data("noDate", AquaCore.l("application", "now"));
		}
		$(this).datetimepicker(options).css("display", "none");
		if(timestamp) {
			var date = new Date(new Date((parseInt(timestamp) * 1000)));
			$(this).datetimepicker("setDate", date);
		}
		updateDatePicker.call(this);
	});
	$(".ac-post-delete", container).bind("click", function(e) {
		if(!confirm(AquaCore.l("content", "confirm-delete-s", AquaCore.settings.contentData.title))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	CKEDITOR.replace("ckeditor", AquaCore.settings.CKEditorOptions);
})(jQuery);
