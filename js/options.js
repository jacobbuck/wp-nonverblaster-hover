jQuery(function ($) {
	
	$("input.color").each(function () {
		var $input = $(this),
			$picker = $(".colorpicker:first", $(this).parent());
		$picker.farbtastic($input).click(function (event) {
			event.stopPropagation();
		});
		$input.click(function (event) {
			event.stopPropagation();
			$(".colorpicker").not($picker.show()).hide();
		});
	});
	$("#wpwrap").click(function () {
		$(".colorpicker").hide();
	});
	
	
});