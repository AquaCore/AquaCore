Number.THOUSAND_STEP = ",";
Number.DECIMAL_POINT = ".";
Number.prototype.format = function(thousandSep, decPoint) {
	var decimal, str;
	thousandSep = thousandSep || Number.THOUSAND_STEP;
	decPoint = decPoint || Number.DECIMAL_POINT;
	decimal = this.toString().split(".");
	str = Math.abs(this)
		.toFixed(0)
		.toString()
		.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
	if(decimal.length === 2) {
		str+= decPoint + decimal[1].toString();
	}
	if(this < 0) {
		str = "-" + str;
	}
	return str;
};
