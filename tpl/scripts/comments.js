(function($) {
	var loading, reply, ckeInstance;

	function deleteReplyElements() {
		if(ckeInstance) {
			ckeInstance.destroy();
			ckeInstance = undefined;
		}
		if(reply) {
			reply.remove();
			reply = undefined;
		}
	};
	$(".ac-comment-upvote, .ac-comment-downvote").on("click", function() {
		var wrapper, id, ctype, weight, self = this;
		wrapper = $(this).closest(".ac-comment");
		if(loading || !wrapper.length) return;
		id = wrapper.attr("ac-comment-id");
		ctype = wrapper.attr("ac-comment-ctype");
		weight = $(this).is(".ac-comment-upvote") ? 1 : -1;
		if(AquaCore.settings["commentRatings"][id] === weight) weight = 0;
		loading = true;
		$.ajax({
			dataType: "json",
			cache: false,
			url: AquaCore.buildUrl({
				script: "ratecomment.php",
				query: {
					"ctype": ctype,
					"comment" : id,
					"weight": weight
				}
			}),
			success: function(data) {
				if(data.success) {
					var parent = $(self).parent();
					$(".ac-comment-upvote, .ac-comment-downvote", parent).removeClass("active");
					$(".ac-comment-points", parent).text(data.rating.format());
					AquaCore.settings["commentRatings"][id] = data.rating;
					if(data.rating !== 0) $(self).addClass("active");
				}
			},
			complete: function(x, y, z) {
				loading = false;
			}
		});
	});
	$(".ac-comment-reply").on("click", function(e) {
		deleteReplyElements();
		var parent = $(this).closest(".ac-comment"),
			cke = $("<textarea></textarea>")
				.attr("id", "comment-reply"),
			frm = $("<form></form>");
		frm.attr("method", "POST")
			.attr("action", $(this).attr("href"))
			.append(cke);
		if(AquaCore.settings.contentData.allowAnonymous) {
			frm.append(
				$("<input>")
					.attr("type", "checkbox")
					.attr("value", "1")
					.attr("name", "anonymous")
					.attr("id", "anon-reply")
				)
				.append(
					$("<label></label>")
						.attr("for", "anon-reply")
						.append(AquaCore.l("comment", "comment-anonymously"))
				)
		}
		frm.append(
				$("<button>")
					.attr("type", "type")
					.attr("class", "ac-button")
					.on("click", function(e) { deleteReplyElements() })
					.append(AquaCore.l("comment", "cancel"))
			)
			.append(
				$("<button>")
					.attr("type", "submit")
					.attr("class", "ac-button")
					.append(AquaCore.l("comment", "reply"))
			);
		reply = $("<div></div>")
			.addClass("ac-comment-reply-wrapper")
			.append(frm)
			.insertAfter($(this).closest(".ac-comment-actions"));
		e.preventDefault();
		e.stopPropagation();
		ckeInstance = CKEDITOR.replace("comment-reply", $.extend({
			toolbarCanCollapse: true,
			toolbarStartupExpanded : false
		}, AquaCore.settings.ckeComments));
		return false;
	})
	CKEDITOR.replace("cke-comment", AquaCore.settings.ckeComments);
})(jQuery);
