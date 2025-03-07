<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

return [
    // WEBFINGER
    'activitypub:webfinger:resource:empty' => 'Invalid resource',
    'activitypub:webfinger:resource:no_match' => "Requested host - %s does not match actual host - %s",
    'activitypub:webfinger:resource:no_user' => 'Access denied for requested account',

    // GENERAL
    'admin:activitypub' => 'ActivityPub',
    'settings:activitypub' => 'ActivityPub',

    'admin:activitypub:settings' => 'Basic Config',
    'settings:activitypub:settings' => 'Basic Config',

    'settings:activitypub:permissions' => 'Permissions',
    'settings:activitypub:permissions:enable_activitypub' => 'Allow users to enable ActivityPub for their account',
    'settings:activitypub:permissions:enable_group' => 'Enable ActivityPub for Groups',
    'settings:activitypub:permissions:resolve_remote' => 'Allow users to resolve remote activities and actors',
    'settings:activitypub:permissions:resolve_remote:help' => 'If not enabled, the remote lookup in search form will not be possible.',

    'admin:activitypub:types' => 'Types',
    'settings:activitypub:types:core' => 'Core types',
    'settings:activitypub:types:dynamic' => 'Dynamic types',
    'settings:activitypub:types:select' => '- Select -',

    'activitypub:object:aptype' => 'ActivityPub object: %s',

    'settings:activitypub:types:dynamic:subtype' => 'Elgg object',
    'settings:activitypub:types:dynamic:aptype' => 'ActivityPub object',

    'admin:activitypub:activities' => 'Activities',
    'activitypub:activities:guid' => 'GUID',
    'activitypub:activities:created' => 'Created',
    'activitypub:activities:collection' => 'Collection',
    'activitypub:activities:activity_type' => 'Type',
    'activitypub:activities:actor' => 'Actor',
    'activitypub:activities:activity_object' => 'Object',
    'activitypub:activities:queued' => 'Queued',
    'activitypub:activities:processed' => 'Processed',
    'activitypub:activities:status' => 'Status',
    'activitypub:activities:actions' => 'Actions',

    'activitypub:activities:published' => 'Published',
    'activitypub:activities:unpublished' => 'Unpublished',
    'activitypub:activities:none' => 'No found ActivityPub activities',
    'activitypub:activities:friendlytime' => 'd/m/Y - H:i',

    'settings:activitypub:global' => 'Control domains',
    'settings:activitypub:global:help' => 'Whitelisted domains have priority: 
	<div>- if both fields are filled in, the whitelisted one will be preferred but blocked ones will be ignored;</div>
	- if the white list is filled only, then only domains from this list will be allowed globally.',
    'settings:activitypub:global:whitelisted_domains' => 'Globally whitelisted domains',
    'settings:activitypub:global:whitelisted_domains:help' => 'Allow domains to send requests to your App. Enter domains line per line.',
    'settings:activitypub:global:blocked_domains' => 'Globally blocked domains',
    'settings:activitypub:global:blocked_domains:help' => 'Block domains from sending requests to your App. Enter domains line per line.',

    'settings:activitypub:content' => 'Content',
    'settings:activitypub:content:remote_user_searchable' => 'Remote users searchable',
    'settings:activitypub:content:remote_user_searchable:help' => 'If enabled local users can search the remote users on Elgg app.',
    'settings:activitypub:content:remote_group_searchable' => 'Remote groups searchable',
    'settings:activitypub:content:remote_group_searchable:help' => 'If enabled local users can search the remote groups on Elgg app.',
    'settings:activitypub:content:remote_object_searchable' => 'Remote objects searchable',
    'settings:activitypub:content:remote_object_searchable:help' => 'If enabled local users can search the remote objects on Elgg app.',
    'settings:activitypub:content:objects_slugs' => 'Add objects URLs slugs',
    'settings:activitypub:content:objects_slugs:help' => 'For activities, we will check the presence of subtypes names in the links. Typically, this name is equivalent to the subtype, for example, in "domain.com/blog/view/123/title-of-the-post/", "blog" means the subtype "blog". But sometimes links can have other names, for example in "domain.com/stories/view/123/title-of-the-post/" "stories" are not equivalent to the subtype "story". In these cases, specify such URLs slugs here. Separate words with commas.',

    'settings:activitypub:outbox' => 'Outbox',
    'settings:activitypub:outbox:process_outbox_handler' => 'Send activities',
    'settings:activitypub:outbox:process_outbox_handler:help' => 'Activities in the Outbox are not send immediately, but are prepared and stored in a queue.',
    'settings:activitypub:outbox:remove_outbox_activities' => 'Remove unpublished activities',
    'settings:activitypub:outbox:remove_outbox_activities:help' => 'Removes queued, unprocessed and unpublished activities in Outbox after one day when something goes wrong with them. Leave disabled to keep them all.',

    'settings:activitypub:inbox' => 'Inbox',
    'settings:activitypub:inbox:process_inbox_handler' => 'Process incoming',
    'settings:activitypub:inbox:process_inbox_handler:help' => 'Activities in the Inbox that need to be processed (e.g. Create, with or without a reply) are not processed immediately, but are stored in a queue.',
    'settings:activitypub:inbox:remove_inbox_activities' => 'Remove unpublished activities',
    'settings:activitypub:inbox:remove_inbox_activities:help' => 'Removes queued, unprocessed and unpublished activities in Inbox after one day when something goes wrong with them. Leave disabled to keep them all.',
    'settings:activitypub:inbox:import_inbox' => 'Interval to import inboxes',
    'settings:activitypub:inbox:import_inbox:help' => "<div>If your inbox does not receive activity from the remote user, you can import it forcibly. Set the interval when the cron will check all outboxes of all remote users who followed your local users. By default, import is disabled.</div>
Note: This may increase the load on your server.",

    'settings:activitypub:server' => 'Server',
    'settings:activitypub:server:instance' => 'Instance parameters',
    'settings:activitypub:server:instance:host' => 'host',
    'settings:activitypub:server:instance:host:help' => 'The default hostname is localhost. If you want to be reachable from a network you may pass a custom hostname.',
    'settings:activitypub:server:instance:port' => 'port',
    'settings:activitypub:server:instance:port:help' => 'The default port is 443. If you want to customize it, you may pass a port parameter.',
    'settings:activitypub:server:instance:types' => 'types',
    'settings:activitypub:server:instance:types:help' => "This option tells the instance which behaviour when an unknown property or an undefined type is encountered. 
<div>By default, an instance is configured in 'strict' mode.</div>
<div>When a non- standard type is encountered, if it's not defined as a dialect, it throws an exception.</div>
<div>It can be blocking if you're working with many kinds of federations.</div>
<div>So, you may configure your instance with a less strict requirement in two ways:</div>
<div>- ignore : non standard types and properties are ignored</div>
- include: non standard types and properties are set and created on the fly.
",
    'settings:activitypub:server:instance:types:strict' => 'strict',
    'settings:activitypub:server:instance:types:ignore' => 'ignore',
    'settings:activitypub:server:instance:types:include' => 'include',
    'settings:activitypub:server:http' => 'HTTP parameters',
    'settings:activitypub:server:http:timeout' => 'timeout (in seconds)',
    'settings:activitypub:server:http:timeout:help' => 'The default timeout for HTTP requests is 10s.',
    'settings:activitypub:server:http:retries' => 'retries',
    'settings:activitypub:server:http:sleep' => 'sleep (in seconds)',
    'settings:activitypub:server:http:sleep:help' => "Other federated servers might have some problems and responds with HTTP errors (5xx).
<div>The server instance may retry to reach another instance. </div>
<div>By default, it will make 2 more attempts ('retries') with 5 seconds between ('sleep') each before failing.</div>
Setting to 0 would make it never retry to transmit its message.",
    'settings:activitypub:server:cache' => 'Cache parameters',
    'settings:activitypub:server:cache:enable' => 'Enable cache',
    'settings:activitypub:server:cache:enable:help' => "The default type of cache is 'filesystem'. Cache is actived by default.
<div>You can disable caching objects with 'enabled' parameter.</div>",
    'settings:activitypub:server:cache:ttl' => 'ttl (in seconds)',
    'settings:activitypub:server:cache:ttl:help' => "The Time To Live (TTL) of an item is the amount of time in seconds between when that item is stored and it is considered stale.
<div>The default value is 3600.</div>",

    'settings:activitypub:development' => 'Development',
    'settings:activitypub:development:server_logger' => 'Server log output',
    'settings:activitypub:development:server_logger:help' => 'By default, the driver is Monolog\Logger. Sometimes, for testing purpose, it may be suitable to enable log output.',
    'settings:activitypub:development:log_general_inbox_error' => 'Log general error requests',
    'settings:activitypub:development:log_general_inbox_error:help' => 'Logs the error when something completely goes wrong in the inbox, interactions request.',
    'settings:activitypub:development:log_error_signature' => 'Log error signature verification',
    'settings:activitypub:development:log_error_signature:help' => 'Logs the error when the signature can not be verified for incoming requests.',
    'settings:activitypub:development:log_activity_error' => 'Log activity error',
    'settings:activitypub:development:log_activity_error:help' => 'Logs errors related to the ActivityPub activity',

    // SERVICES
    'activitypub:keys:generate:entity:error' => 'Error while creating keys: This may be caused by a problem with file or directory permissions. Check the logs or ask an administrator.',
    'activitypub:keys:generate:error' => 'Error generating keys: %s',
    'activitypub:keys:generate:move:error' => "The specified file '%s' could not be moved to '%s'.",
    'activitypub:keys:generate:copy:error' => "The source file '%s' could not be unlinked after copying to '%s'.",
    'activitypub:keys:signature:create:error' => 'Error creating the signature: %s',
    'activitypub:keys:signature:verify:error' => 'Signature verifying exception: %s',
    'activitypub:process:collection:error' => 'ProcessCollectionService - expected array, got $item',

    // USERS
    'settings:activitypub:user:enable_activitypub' => 'Enable ActivityPub',
    'settings:activitypub:user:enable_activitypub:help' => 'You will be able to follow content on Mastodon and other federated platforms that support <a href="https://activitypub.rocks/">ActivityPub</a>.',
    'settings:activitypub:user:enable_discoverable' => 'Feature profile and posts in discovery algorithms',
    'settings:activitypub:user:enable_discoverable:help' => 'Your public posts and profile may be featured or recommended on federated platforms and your profile may be suggested to other users.',
    'settings:activitypub:user:domains' => 'Control domains',
    'settings:activitypub:user:domains:help' => '<p>Whitelisted domains have priority: if both fields are filled in, the whitelisted one will be preferred but blocked ones will be ignored.</p>
<div>Global settings have priority over user settings: </div>
<div>- if domain is globally blocked, your whitelist with this domain will be ignored; </div>
- if domain is globally allowed, your blacklist with this domain will be ignored.',
    'settings:activitypub:user:whitelisted_domains' => 'Whitelisted domains',
    'settings:activitypub:user:whitelisted_domains:help' => 'Allow domains to send requests to your Inbox. Enter domains line per line. <div>%s</div>',
    'settings:activitypub:user:blocked_domains' => 'Blocked domains',
    'settings:activitypub:user:blocked_domains:help' => 'Block domains from sending requests to your Inbox. Enter domains line per line. <div>%s</div>',
    'activitypub:global_whitelisted_domains' => 'Globally whitelisted domains',
    'activitypub:global_blocked_domains' => 'Globally blocked domains',

    'activitypub:user:is_actor:header' => 'Your Fediverse identifier for this app is <strong>%s</strong>. Try entering <em>%s</em> in Mastodon or other federated platforms search.',
    'activitypub:user:disable' => 'ActivityPub is not enabled for your account. <a href="%s">Enable ActivityPub</a>',
    'activitypub:user' => 'ActivityPub',
    'activitypub:user:settings' => 'ActivityPub tool',

    'activitypub:user:follow' => 'Follow',
    'activitypub:user:follow:title' => 'ActivityPub Follow @%s',
    'activitypub:user:follow:label' => 'Handle',
    'activitypub:user:follow:help' => 'You can follow user from Mastodon or any other similar app that uses ActivityPub. Search for profile (%s) from your instance, or enter your username here and hit follow button.',
    'activitypub:user:follow:submit' => 'Proceed to follow',
    'activitypub:user:follow:endpoint' => 'Endpoint - %s',
    'activitypub:user:follow:fail' => 'Could not find your subscription endpoint.',
    'activitypub:user:follow:error:handle' => 'Your username is required',
    'activitypub:user:follow:error:local_actor' => 'Not found local actor',
    'activitypub:user:follow:error:object' => 'Not found local actor or object',
    'activitypub:user:follow:error:endpoint' => 'Error getting subscribe endpoint: %s',
    'activitypub:user:follow:error:domain' => 'The remote instance is blocked',

    'activitypub:user:unfollow' => 'Unfollow',
    'activitypub:user:pending' => 'Pending',

    'activitypub:follow:subject' => 'New follower',
    'activitypub:follow:body' => "%s followed you on %s

To view the follower, tap here:
%s",

    'activitypub:user:remove:remote_friend:failure' => 'Error deleting remote_friend relashioship with local user %s',

    // GROUPS
    'groups:tool:activitypub' => 'Enable ActivityPub',
    'groups:tool:activitypub:description' => 'Allow ActivityPub to connect and interact with this group.',

    'groups:tool:activitypub:settings:enable_activitypub' => 'Enable ActivityPub',
    'groups:tool:activitypub:settings:enable_activitypub:help' => 'You will be able to follow content on Mastodon and other federated platforms that support <a href="https://activitypub.rocks/">ActivityPub</a>.',
    'groups:tool:activitypub:settings:enable_discoverable' => 'Feature profile and posts in discovery algorithms',
    'groups:tool:activitypub:settings:enable_discoverable:help' => 'Group public posts and profile may be featured or recommended on federated platforms and your Group profile may be suggested to other groups and users.',

    'groups:tool:activitypub:settings:domains' => 'Control domains',
    'groups:tool:activitypub:settings:domains:help' => '<p>Whitelisted domains have priority: if both fields are filled in, the whitelisted one will be preferred but blocked ones will be ignored.</p>
<div>Global settings have priority over group settings: </div>
<div>- if domain is globally blocked, your whitelist with this domain will be ignored;</div>
- if domain is globally allowed, your blacklist with this domain will be ignored.',
    'groups:tool:activitypub:settings:whitelisted_domains' => 'Whitelisted domains',
    'groups:tool:activitypub:settings:whitelisted_domains:help' => 'Allow domains to send requests to your Inbox. Enter domains line per line. <div>%s</div>',

    'groups:tool:activitypub:settings:blocked_domains' => 'Blocked domains',
    'groups:tool:activitypub:settings:blocked_domains:help' => 'Block domains from sending requests to your Inbox. Enter domains line per line. <div>%s</div>',

    'activitypub:group:settings' => 'ActivityPub tool',
    'activitypub:group:settings:group' => '"%s" group ActivityPub tool',
    'activitypub:group:settings:save' => 'Group settings saved.',
    'activitypub:group:settings:error' => 'Error saving.',
    'activitypub:group:disable' => 'ActivityPub tool disabled. Enable tool in <a href="%s">Group settings</a>',

    'activitypub:group:join' => 'Join group',
    'activitypub:group:pending' => 'Pending',
    'activitypub:group:join:title' => 'Join "%s" group',
    'activitypub:group:join:label' => 'Handle',
    'activitypub:group:join:help' => 'You can join group from Mastodon or any other similar app that uses ActivityPub. Search for group profile (%s) from your instance, or enter your username here and hit Join button.',
    'activitypub:group:join:submit' => 'Proceed to join',
    'activitypub:group:join:endpoint' => 'Endpoint - %s',
    'activitypub:group:join:fail' => 'Could not find your subscription endpoint.',
    'activitypub:group:join:error:handle' => 'Your username is required',
    'activitypub:group:join:error:local_actor' => 'Not found local actor',
    'activitypub:group:join:error:endpoint' => 'Error getting subscribe endpoint: %s',
    'activitypub:group:join:error:object' => 'Not found local actor or object',
    'activitypub:group:join:error:domain' => 'The remote instance is blocked',

    'activitypub:group:join:subject' => 'New group member',
    'activitypub:group:join:body' => "%s on %s joined the group %s

To view the group member, tap here:
%s",

    'activitypub:group:request:subject' => 'New request to join the group',
    'activitypub:group:request:body' => "%s on %s has requested to join the group %s

To view the profile, tap here:
%s",

    'activitypub:group:remove:remote_member:failure' => 'Error deleting remote_member relashioship with local group %s',

    // INBOX
    'activitypub:inbox:signature:exception' => 'Inbox signature/followee exception: %s',
    'activitypub:inbox:unsaved' => 'Some activities are not saved - Payload: %s',
    'activitypub:inbox:update:error' => 'Error updating existing activity: %s',
    'activitypub:inbox:general:error' => 'Inbox general error: %s - %s',
    'activitypub:inbox:general:exception' => 'Inbox general exception in %s line %s for %s: %s',
    'activitypub:inbox:general:payload' => 'Inbox payload: %s',
    'activitypub:inbox:general:blocked' => 'Blocked in Inbox: %s',
    'activitypub:inbox:general:payload:empty' => 'Payload must be provided. JSON: %s',
    'activitypub:inbox:general:payload:wrong_type' => 'Wrong activity type in payload. Must be: %s Return: %s',
    'activitypub:inbox:general:payload:empty:actor' => 'No actor or id in payload: %s',

    'activitypub:inbox:post_save:accept:error' => 'Error post saving Accept activity on this activity: %s',
    'activitypub:inbox:post_save:move:error' => 'Error post saving Move activity on this activity: %s',

    // OUTBOX
    'activitypub:outbox:follow:success' => 'Follow request has been sent',
    'activitypub:outbox:follow:error' => 'Error saving Follow activity on the create friend relationship: Local user - %s, Federated user - %s',
    'activitypub:outbox:join:success' => 'Join request has been sent',
    'activitypub:outbox:join:error' => 'Error saving Join activity on the create membership: Local user - %s, Federated group - %s',
    'activitypub:outbox:undo:error' => 'Error saving Undo activity on the delete friend relationship: Local user - %s, Federated user - %s',
    'activitypub:outbox:post_proccess:announce:error' => 'Error post proccessing Announce activity on this activity: %s',
    'activitypub:outbox:import_feed:created' => '%s FederatedObject have been created from URL: %s',
    'activitypub:outbox:import_feed:error:outbox_url' => 'ActivityPub could not find outbox page. URL: %s',
    'activitypub:outbox:import_feed:error:first_page' => 'ActivityPub could not find outbox first page. URL: %s',

    // ACTIVITY
    'item:object:activitypub_activity' => 'ActivityPub activity',
    'collection:object:activitypub_activity' => 'ActivityPub activities',
    'activitypub_activity:none' => 'No found ActivityPub activities.',
    'activitypub_activity:count' => 'Items in queue: %s',

    'activitypub:activitypub_activity' => 'ActivityPub activity %s',
    'activitypub:activitypub_activity:save:error' => 'Cannot save ActivityPub activity on %s',

    'activitypub:activitypub_activity:edit' => 'Edit activity',
    'activitypub:activitypub_activity:edit:success' => 'ActivityPub activity saved.',
    'activitypub:activitypub_activity:edit:error' => 'Cannot save ActivityPub activity.',

    'activitypub:reply:by' => 'Reply by %s',
    'activitypub:reply:on' => 'Reply on %s',
    'activitypub:reply:on:this' => 'This is a reply on %s',

    'activitypub:interactions:error' => 'Could not find your subscription endpoint.',
    'activitypub:interactions:no_object' => 'Could not find object.',
    'activitypub:interactions:no_valid' => 'Actor identifier is not valid.',
    'activitypub:interactions:domain' => 'The remote instance is blocked.',
    'activitypub:interactions:no_type' => 'Could not find the type of object.',
    'activitypub:interactions:activity' => 'Cannot save ActivityPub activity. Please try again.',
    'activitypub:interactions:success' => 'You have successfully sent a request to %s. Please wait for a response. You will be notified about it.',

    'activitypub:resolve:service:domain_blocked' => 'Domain %s is blocked globally.',
    'activitypub:resolve:service:invalid_url' => 'URL %s is not valid.',
    'activitypub:resolve:service:bad_response' => 'Bad response from URL %s',

    'activitypub:private:subject' => 'New private mention from Fediverse',
    'activitypub:private:body' => "%s wrote:

%s

<p>%s</p>

To view the original, tap here:
%s",

    'activitypub:attachment' => 'Attachment',

    'activitypub:events:activity:delete:error' => 'Error creating Delete outbox activity on this GUID: %s',

    // Collections
    'item:user:federated' => 'Remote user',
    'collection:user:federated' => 'Remote users',

    'item:group:federated' => 'Remote group',
    'collection:group:federated' => 'Remote groups',

    'item:object:federated' => 'Remote object',
    'collection:object:federated' => 'Remote objects',

    'edit:user:federated' => 'Edit Profile',

    'activitypub:post:federated' => 'Post by %s',
    'activitypub:post:federated:on' => 'on %s',

    'activitypub:object:save:error' => 'Error object saving on this: %s',

    'activitypub:search' => 'Search in Fediverse',
    'activitypub:search:label' => 'Search or paste URL',
    'activitypub:search:help' => 'Search remote activities and actors. 
ActivityPub search looks for remote users as @name@example.com, name@example.com or https://example.com/path_to/user.
It also looks for remote activities giving the identifier url of it.',
    'activitypub:search:no_results' => 'No results.',
    'activitypub:search:error:query' => 'Please provide a valid search query',
    'process:searching' => 'Searching',
    'activitypub:search:remote_url' => 'View on remote URL',

    // river
    'river:object:federated:create' => '%s published a %s',
    'river:object:federated:comment' => '%s commented on %s',

    //members
    'collection:user:user:local' => 'Local',
    'members:title:local' => 'Local members',
    'collection:user:user:remote' => 'Remote',
    'members:title:remote' => 'Remote members',
];
