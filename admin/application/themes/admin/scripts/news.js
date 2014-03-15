(function($) {
	var datePicker, schedule, archive;

	function updateDatePicker() {
		var date = $(this).datetimepicker("getDate"),
			text = $(this).closest("td").find(".ac-schedule-date");
		if(!date || (Math.floor((new Date).getTime() / 1000) * 1000) == date.getTime()) {
			text.text(AquaCore.l("application", ($(this).hasClass("ac-post-schedule") ? "now" : "none")));
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

	function filter(term) {
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

	$(".ac-post-tags").bind("keydown", function( event ) {
		if (event.keyCode === $.ui.keyCode.TAB && $(this).data("ui-autocomplete").menu.active) {
			event.preventDefault();
		}
	}).autocomplete({
		minLength: 2,
		focus: function() { return false },
		source: function(request, response) {
			var val = $.trim(request.term.split( /,\s*/).pop());
			if(!val.length) return;
			response(filter(val));
		},
		select: function(event, ui) {
			var terms = this.value.split( /,\s*/);
			terms.pop();
			terms.push(ui.item.value);
			terms.push("");
			this.value = terms.join(", ");
			return false;
		},
		position: {
			my: 'left top+1 0'
		}
	}).data("ui-autocomplete")._renderItem = function(ul, item) {
		return $("<li/>")
			.data("item.autocomplete", item)
			.append("<a>"+ item.label  + "</a>")
			.appendTo(ul);
	};

	schedule = $(".ac-post-schedule");
	archive  = $(".ac-archive-schedule");
	schedule.datetimepicker({
		showOn: "button",
		buttonText: "",
		nextText: "",
		prevText: "",
		dateFormat: "yy-mm-dd",
		timeFormat: "HH:mm:ss",
		onSelect: updateDatePicker
	}).css("display", "none");
	archive.datetimepicker({
		afterUpdate: function(e, inst) {
			var current = $(".ui-datepicker-current", inst.dpDiv);
			current.off("click")
				.text(AquaCore.l("application", "none"))
				.on("click", function() {
					$.datepicker._clearDate(e);
				});
		},
		showOn: "button",
		buttonText: "",
		nextText: "",
		prevText: "",
		dateFormat: "yy-mm-dd",
		timeFormat: "HH:mm:ss",
		onSelect: updateDatePicker
	}).css("display", "none");
	for(var k = 0; k < 2; ++k) {
		var element = (k ? schedule : archive);
		if(element.attr("timestamp")) {
			var date = new Date(new Date((parseInt(schedule.attr("timestamp")) * 1000)));
			element.datetimepicker("setDate", date);
		}
	}
	updateDatePicker.call(schedule.get(0));
	updateDatePicker.call(archive.get(0));
	$(".ac-post-delete").bind("click", function(e) {
		if(!confirm(AquaCore.l("news", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	CKEDITOR.replace("ckeditor", AquaCore.settings.CKEditorOptions);
})(jQuery);
