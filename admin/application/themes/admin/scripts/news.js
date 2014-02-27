(function($) {
	var datePicker, dateForm, updateDatePicker;

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

	updateDatePicker = function() {
		var date = datePicker.datetimepicker("getDate"),
			text = $(".ac-post-schedule-date");
		if(!date || (Math.floor((new Date).getTime() / 1000) * 1000) == date.getTime()) {
			text.html(AquaCore.l("application", "now"));
			dateForm.val("");
		} else {
			text.html($.datepicker.formatDate("MM dd, yy", date) + "<br/><span class=\"ac-post-schedule-time\">" + $.datepicker.formatTime("HH:mm:ss", {
				hour: date.getHours(),
				minute: date.getMinutes(),
				second: date.getSeconds()
			}) + "</span>");
			dateForm.val($.datepicker.formatDate("yy-mm-dd", date) + " " + $.datepicker.formatTime("HH:mm:ss", {
				hour: date.getHours(),
				minute: date.getMinutes(),
				second: date.getSeconds()
			}));
		}
	};
	datePicker = $("<input type=\"hidden\">");
	dateForm = $(".ac-post-schedule");
	dateForm.css("display", "none").before(datePicker);
	datePicker.datetimepicker({
		showOn: "button",
		buttonText: "",
		nextText: "",
		prevText: "",
		dateFormat: "yy-mm-dd",
		timeFormat: "HH:mm:ss",
		onSelect: updateDatePicker
	});
	if(dateForm.attr("timestamp")) {
		var date = new Date((parseInt(dateForm.attr("timestamp")) * 1000));
		var _date = new Date(date);
		datePicker.datetimepicker("setDate", _date);
	}
	updateDatePicker();
	$(".ac-post-delete").bind("click", function(e) {
		if(!confirm(AquaCore.l("news", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	CKEDITOR.replace("ckeditor", AquaCore.settings.CKEditorOptions);
})(jQuery);
