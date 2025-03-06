import $ from 'jquery';
	
const apSettings = () => {
	if ($('#groupsettings-enable-activitypub').is(':checked')) {
		$('#groupsettings-activitypub').show();
	} else {
		$('#groupsettings-activitypub').hide();
	}
};
	
apSettings();

$(document).ready(() => {
    $('#groupsettings-enable-activitypub').change(apSettings);
});
