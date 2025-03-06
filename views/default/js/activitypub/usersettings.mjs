import $ from 'jquery';
	
const apSettings = () => {
	if ($('#usersettings-enable-activitypub').is(':checked')) {
		$('#usersettings-activitypub').show();
	} else {
		$('#usersettings-activitypub').hide();
	}
};
	
apSettings();

$(document).ready(() => {
    $('#usersettings-enable-activitypub').change(apSettings);
});
