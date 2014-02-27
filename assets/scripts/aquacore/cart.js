var AquaCore = AquaCore || {};
(function($) {
	AquaCore.cart = function(options) {
		this.option(options);
		this.element = document.createElement("div");
		this.element.clasName = "ac-cart";
		this.itemList = document.createElement("ul");
	};
	AquaCore.cart.prototype = $.extend({
		items: [],
		priceTotal: 0,
		element: null,
		itemList: null,
		options: {
			checkoutUrl: null,
			appendTo: null
		},
		add: function(item_id, name, price, amount) {
			var item = new AquaCore.cartItem(item_id, name, price, amoune);
		},
		remove: function(item_id, name, price, amount) {

		},
		calcPrice: function() {
			var price = 0;
			for(var i = 0; i < this.items.length; ++i) {
				price += this.items[i].price * this.items[i].amount;
			}
			this.priceTotal = price;
			this.fire("calculatePrice");
			this.priceContainer.innerHTML(AquaCore.l("donation", "credit-points", price.format()));
		},
		show: function(callback) {

		},
		hide: function(callback) {

		}
	}, AquaCore.prototype);
	AquaCore.cartItem = function(itemId, itemName, price) {
		var self = this, image;
		this.element = document.createElement("div");
		this.element.className = "ac-cart-item";
		this.element.id = this.uniqueId();
		this.amountContainer = document.createElement("div");
		this.amountContainer.className = "ac-cart-item-amount";
		image = document.createElement("img");
		image.src = AquaCore.URL + "/assets/images/item/icon/" + itemId + ".png";
		this.watch("amount", function(id, oldValue, newValue) {
			self.set(newValue);
		});
	};
	AquaCore.cartItem.prototype = $.extend({
		itemId: null,
		itemName: null,
		amount: 0,
		price: 0,
		element: null,
		amountContainer: null,
		set: function(amount) {
			if(amount !== this.amount) {
				this.amount = amount;
			}
			$(".ac-item-amount", this.element).html(amount.format());
			return this;
		}
	}, AquaCore.prototype);
})(jQuery);
