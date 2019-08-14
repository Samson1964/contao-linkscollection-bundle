(function($) {
	$(document).ready(function() {

		/* Link-Abfrage per Ajax */
		$("#myForm").submit(function() {

			$("#ajaxloader").html("<img src=\"system/modules/linkscollection/assets/images/laden.gif\" />");
			$.ajax({
				type: "GET",
				url: "system/modules/linkscollection/public/search.php",
				data: "s=" + $("#s").val(),
				success: function(msg)
				{
					$("#ajaxloader").html("");
					$("#searchresult").html(msg);
					$("#s").focus();
					$("#s").select();
				}
			});

			return false;

		});

	});
})(jQuery);
