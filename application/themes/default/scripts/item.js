(function($) {
	$(".ac-mob-experience-wrapper").each(function() {
		AquaCore.experienceSlider(this, {
			format: AquaCore.l("experience-slider", "format"),
			baseLevel: AquaCore.settings["mobLevel"],
			baseExperience: $(this).html()
		});
	});
})(jQuery);
