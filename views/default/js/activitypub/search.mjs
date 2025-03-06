import $ from 'jquery';
import elgg from 'elgg'; 
import Ajax from 'elgg/Ajax'; 
import i18n from 'elgg/i18n'; 

var AS = {
	init: function() {
		$(document).on('submit', '.elgg-form-activitypub-search, #activitypub-search-form', AS.formSubmit);
	},
		
	formSubmit: function (event) {
		event.preventDefault();
		var $form = $(this);
			
		var ajax = new Ajax();

		ajax.action($form.attr('action'), {
			data: ajax.objectify($form),
			beforeSend: function () {
				$form.find('[type="submit"]').addClass('elgg-state-disabled').text(i18n.echo('process:searching')).prop('disabled', true);
			}
		}).done(function (response) {
			ajax.view('activitypub/search/results', {
				data: {response},
			}).done(function (output, statusText, jqXHR) {
				$form.find('[type="submit"]').removeClass('elgg-state-disabled').text(i18n.echo('search')).prop('disabled', false);
					
				$form.next().html(output);
			});
		}).fail(function () {
			$form.find('[type="submit"]').removeClass('elgg-state-disabled').text(i18n.echo('search')).prop('disabled', false);
		});
	},
};

AS.init();

