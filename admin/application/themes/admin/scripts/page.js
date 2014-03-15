(function($) {
	var schedule;

	function updateDatePicker() {
		var date = $(this).datetimepicker("getDate"),
			text = $(this).closest("td").find(".ac-schedule-date");
		if(!date || (Math.floor((new Date).getTime() / 1000) * 1000) == date.getTime()) {
			text.html(AquaCore.l("application", "now"));
			schedule.val("");
		} else {
			var format = moment(date.getTime());
			text.html(
				format.format("MMMM DD, YYYY") +
				"<br/><span class=\"ac-post-schedule-time\">" +
				format.format("HH:mm:ss") +
				"</span>"
			);
		}
	};
	schedule = $(".ac-post-schedule");
	schedule.datetimepicker({
		showOn: "button",
		buttonText: "",
		nextText: "",
		prevText: "",
		dateFormat: "yy-mm-dd",
		timeFormat: "HH:mm:ss",
		onSelect: updateDatePicker
	}).css("display", "none");
	if(schedule.attr("timestamp")) {
		var date = new Date((parseInt(schedule.attr("timestamp")) * 1000));
		schedule.datetimepicker("setDate", date);
	}
	updateDatePicker.apply(schedule.get(0));
	$(".ac-post-delete").bind("click", function(e) {
		if(!confirm(AquaCore.l("page", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	CKEDITOR.replace("ckeditor", AquaCore.settings.CKEditorOptions);
})(jQuery);
