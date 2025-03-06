import $ from 'jquery';
import i18n from 'elgg/i18n'; 

var AP = {
	init: function () {
		$(document).on('click', '.ap-icon-plus', function (e) {
			e.preventDefault();
			var $clone = $(this).closest('.ap-policy').clone().hide();
			$clone.find('input').val('');
			$(this).closest('.ap-policy').after($clone.fadeIn());
		});

		$(document).on('click', '.ap-icon-minus', function (e) {
			e.preventDefault();
			if (!confirm(i18n.echo('question:areyousure'))) {
				return false;
			}
			$(this).closest('.ap-policy').fadeOut().remove();
		});
	},
};

AP.init();
