<?php

$entity = elgg_extract('entity', $vars);

//Permissions
echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:permissions'),
	'fields' => [
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:permissions:enable_activitypub'),
			'name' => 'params[enable_activitypub]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->enable_activitypub,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:permissions:enable_group'),
			'name' => 'params[enable_group]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->enable_group,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:permissions:resolve_remote'),
			'#help' => elgg_echo('settings:activitypub:permissions:resolve_remote:help'),
			'name' => 'params[resolve_remote]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->resolve_remote,
			'switch' => true,
		],
	],
]);

//Outbox
echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:outbox'),
	'fields' => [
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:outbox:process_outbox_handler'),
			'#help' => elgg_echo('settings:activitypub:outbox:process_outbox_handler:help'),
			'name' => 'params[process_outbox_handler]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->process_outbox_handler,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:outbox:remove_outbox_activities'),
			'#help' => elgg_echo('settings:activitypub:outbox:remove_outbox_activities:help'),
			'name' => 'params[remove_outbox_activities]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->remove_outbox_activities,
			'switch' => true,
		],
	],
]);

//Inbox
echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:inbox'),
	'fields' => [
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:inbox:process_inbox_handler'),
			'#help' => elgg_echo('settings:activitypub:inbox:process_inbox_handler:help'),
			'name' => 'params[process_inbox_handler]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->process_inbox_handler,
			'switch' => true,
		],
		[
			'#type' => 'select',
			'#label' => elgg_echo('settings:activitypub:inbox:import_inbox'),
			'#help' => elgg_echo('settings:activitypub:inbox:import_inbox:help'),
			'name' => 'params[import_inbox]',
			'value' => (string) $entity->import_inbox ?? 'disable',
			'options_values' => [
				'disable' => elgg_echo('disable'),
				'minute' => elgg_echo('interval:minute'),
				'fiveminute' => elgg_echo('interval:fiveminute'),
				'fifteenmin' => elgg_echo('interval:fifteenmin'),
				'halfhour' => elgg_echo('interval:halfhour'),
				'hourly' => elgg_echo('interval:hourly'),
				'daily' => elgg_echo('interval:daily'),
			],
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:inbox:remove_inbox_activities'),
			'#help' => elgg_echo('settings:activitypub:inbox:remove_inbox_activities:help'),
			'name' => 'params[remove_inbox_activities]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->remove_inbox_activities,
			'switch' => true,
		],
	],
]);

//Domains

//Global Whitelisted domains
$whitelisted_domains = (string) $entity->activitypub_global_whitelisted_domains;
$whitelisted_domains = preg_split('/\\r\\n?|\\n/', $whitelisted_domains);
$whitelisted_domains = array_filter($whitelisted_domains);

//Global Blocked domains
$blocked_domains = (string) $entity->activitypub_global_blocked_domains;
$blocked_domains = preg_split('/\\r\\n?|\\n/', $blocked_domains);
$blocked_domains = array_filter($blocked_domains);

echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:global'),
	'fields' => [
		[
			'#html' => elgg_format_element('div', ['class' => 'elgg-field-help elgg-text-help'], elgg_echo('settings:activitypub:global:help')),
		],
		[
			'#type' => 'plaintext',
			'#label' => elgg_echo('settings:activitypub:global:whitelisted_domains'),
			'#help' => elgg_echo('settings:activitypub:global:whitelisted_domains:help'),
			'name' => 'params[activitypub_global_whitelisted_domains]',
			'value' => !empty($whitelisted_domains) ? implode("\r\n", $whitelisted_domains) : '',
		],
		[
			'#type' => 'plaintext',
			'#label' => elgg_echo('settings:activitypub:global:blocked_domains'),
			'#help' => elgg_echo('settings:activitypub:global:blocked_domains:help'),
			'name' => 'params[activitypub_global_blocked_domains]',
			'value' => !empty($blocked_domains) ? implode("\r\n", $blocked_domains) : '',
		],
	],
]);

//Content
echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:content'),
	'fields' => [
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:content:remote_user_searchable'),
			'#help' => elgg_echo('settings:activitypub:content:remote_user_searchable:help'),
			'name' => 'params[remote_user_searchable]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->remote_user_searchable,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:content:remote_group_searchable'),
			'#help' => elgg_echo('settings:activitypub:content:remote_group_searchable:help'),
			'name' => 'params[remote_group_searchable]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->remote_group_searchable,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:content:remote_object_searchable'),
			'#help' => elgg_echo('settings:activitypub:content:remote_object_searchable:help'),
			'name' => 'params[remote_object_searchable]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->remote_object_searchable,
			'switch' => true,
		],
		[
			'#type' => 'text',
			'#label' => elgg_echo('settings:activitypub:content:objects_slugs'),
			'#help' => elgg_echo('settings:activitypub:content:objects_slugs:help'),
			'name' => 'params[objects_slugs]',
			'value' => $entity->objects_slugs ?: '',
		],
	],
]);

//Server
echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:server'),
	'fields' => [
		// Instance parameters
		[
			'#html' => elgg_format_element('h3', ['class' => 'mtm mbm'], elgg_echo('settings:activitypub:server:instance')),
		],
		[
			'#type' => 'text',
			'#label' => elgg_echo('settings:activitypub:server:instance:host'),
			'#help' => elgg_echo('settings:activitypub:server:instance:host:help'),
			'name' => 'params[instance_host]',
			'value' => (string) $entity->instance_host ?? 'localhost',
		],
		[
			'#type' => 'number',
			'#label' => elgg_echo('settings:activitypub:server:instance:port'),
			'#help' => elgg_echo('settings:activitypub:server:instance:port:help'),
			'name' => 'params[instance_port]',
			'value' => (int) $entity->instance_port ?? 443,
			'min' => 0,
			'max' => 65536,
		],
		[
			'#type' => 'select',
			'#label' => elgg_echo('settings:activitypub:server:instance:types'),
			'#help' => elgg_echo('settings:activitypub:server:instance:types:help'),
			'name' => 'params[instance_types]',
			'value' => (string) $entity->instance_types ?? 'strict',
			'options_values' => [
				'strict' => elgg_echo('settings:activitypub:server:instance:types:strict'),
				'ignore' => elgg_echo('settings:activitypub:server:instance:types:ignore'),
				'include' => elgg_echo('settings:activitypub:server:instance:types:include'),
			],
		],
		// HTTP parameters
		[
			'#html' => elgg_format_element('h3', ['class' => 'mtm mbm'], elgg_echo('settings:activitypub:server:http')),
		],
		[
			'#type' => 'number',
			'#label' => elgg_echo('settings:activitypub:server:http:timeout'),
			'#help' => elgg_echo('settings:activitypub:server:http:timeout:help'),
			'name' => 'params[http_timeout]',
			'value' => (int) $entity->http_timeout ?? 10,
			'min' => 0,
		],
		[
			'#type' => 'number',
			'#label' => elgg_echo('settings:activitypub:server:http:retries'),
			'name' => 'params[http_retries]',
			'value' => (int) $entity->http_retries ?? 2,
			'min' => 0,
		],
		[
			'#type' => 'number',
			'#label' => elgg_echo('settings:activitypub:server:http:sleep'),
			'#help' => elgg_echo('settings:activitypub:server:http:sleep:help'),
			'name' => 'params[http_sleep]',
			'value' => (int) $entity->http_sleep ?? 5,
			'min' => 0,
		],
		// Cache parameters
		[
			'#html' => elgg_format_element('h3', ['class' => 'mtm mbm'], elgg_echo('settings:activitypub:server:cache')),
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:server:cache:enable'),
			'#help' => elgg_echo('settings:activitypub:server:cache:enable:help'),
			'name' => 'params[cache_enable]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->cache_enable,
			'switch' => true,
		],
		[
			'#type' => 'number',
			'#label' => elgg_echo('settings:activitypub:server:cache:ttl'),
			'#help' => elgg_echo('settings:activitypub:server:cache:ttl:help'),
			'name' => 'params[cache_ttl]',
			'value' => (int) $entity->cache_ttl ?? 3600,
			'min' => 0,
		],
	],
]);

//Development
echo elgg_view_field([
	'#type' => 'fieldset',
	'legend' => elgg_echo('settings:activitypub:development'),
	'fields' => [
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:development:server_logger'),
			'#help' => elgg_echo('settings:activitypub:development:server_logger:help'),
			'name' => 'params[server_logger]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->server_logger,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:development:log_general_inbox_error'),
			'#help' => elgg_echo('settings:activitypub:development:log_general_inbox_error:help'),
			'name' => 'params[log_general_inbox_error]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->log_general_inbox_error,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:development:log_error_signature'),
			'#help' => elgg_echo('settings:activitypub:development:log_error_signature:help'),
			'name' => 'params[log_error_signature]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->log_error_signature,
			'switch' => true,
		],
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('settings:activitypub:development:log_activity_error'),
			'#help' => elgg_echo('settings:activitypub:development:log_activity_error:help'),
			'name' => 'params[log_activity_error]',
			'value' => 1,
			'default' => 0,
			'checked' => (bool) $entity->log_activity_error,
			'switch' => true,
		],
	],
]);
