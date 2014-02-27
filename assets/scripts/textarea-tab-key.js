(function($) {
	$(document).on("keydown", "textarea", function(e) {
		if((e.which && e.which === $.ui.keyCode.TAB) || (e.keyCode && e.keyCode === $.ui.keyCode.TAB)) {
			var $this = $(this),
				start = this.selectionStart,
				end = this.selectionEnd,
				content = $this.val();
			$this.val(content.substring(0, start) + "\t" + content.substring(end, content.length));
			e.preventDefault();
		}
	});
})(jQuery);
