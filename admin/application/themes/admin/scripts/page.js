(function($) {
	var datePicker, dateForm, updateDatePicker;

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
		console.log(date);
		datePicker.datetimepicker("setDate", date);
	}
	updateDatePicker();
	$(".ac-post-delete").bind("click", function(e) {
		if(!confirm(AquaCore.l("page", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	CKEDITOR.replace("ckeditor", AquaCore.settings.CKEditorOptions);
})(jQuery);
