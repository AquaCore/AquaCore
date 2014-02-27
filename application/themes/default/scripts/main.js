$(document).ready(function(){
	$(".ac-cart-items").slideUp(0);
	$(".ac-cart-link").bind("click", function(e) {
		$(this).parent().find(".ac-cart-items").stop(true, false).slideDown({
			duration: 300,
			easing: "easeInOutCirc"
		});
		e.stopPropagation();
		e.preventDefault();
		return false;
	});
	$(this).bind("mouseup", function(e) {
		var cart = $(".ac-cart");
		if(!cart.is(e.target) && cart.has(e.target).length === 0) {
			cart.find(".ac-cart-items").stop(true, false).slideUp({
				duration: 300,
				easing: "easeInOutCirc"
			});
		}
	});
});
