ActivityPub
=========
![Elgg 6.1](https://img.shields.io/badge/Elgg-6.1-purple.svg?style=flat-square)

ActivityPub integration for Elgg

## About

Implements the **ActivityPub protocol** [1](https://en.wikipedia.org/wiki/ActivityPub), [2](https://activitypub.rocks), [3](https://w3c.github.io/activitypub) for your Elgg app, allowing members to communicate with each other. 
Users will be able to follow content on Mastodon and other federated platforms that support ActivityPub. 

With the ActivityPub plugin installed, your Elgg app itself function as a federated server in **Fediverse** [1](https://en.wikipedia.org/wiki/Fediverse), [2](https://www.britannica.com/technology/fediverse), along with profiles for each user and/or group. 

For instance, if your Elgg app is `app.url`, then the app-wide profile can be found at `@app.url@app.url`, and users like Jane and Bob would have their individual profiles at `@jane@app.url` and `@bob@app.url`, respectively.

Inspired by [ActivityPub](https://www.drupal.org/project/activitypub) Drupal plugin by [Kristof De Jaeger aka swentel](https://git.drupalcode.org/swentel) and [Minds ActivityPub](https://developers.minds.com/docs/decentralization/activitypub)

## Features

* Allow users to enable ActivityPub for their account
* Enable ActivityPub for Groups
* Allow or block domains from posting to inbox globally and/or per user/group
* [Outbox](https://www.w3.org/TR/activitypub/#outbox), [Inbox](https://www.w3.org/TR/activitypub/#inbox), [Followers](https://www.w3.org/TR/activitypub/#followers), [Following](https://www.w3.org/TR/activitypub/#following) and [Liked](https://www.w3.org/TR/activitypub/#liked) endpoints
* Map Activity types and properties to content types and create posts to send out to the Fediverse
* Discovery via [WebFinger](https://webfinger.net) for locating app, user and group profiles
* Signature verification for incoming activities
* Integration with the [IndieWeb plugin](https://github.com/RiverVanRain/indieweb)

**Supported Activity types**

* [Accept](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-accept)
* [Announce](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-announce)
* [Create](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-create)
* [Delete](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-delete)
* [Follow](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-follow)
* [Join](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-join)
* [Leave](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-leave)
* [Like](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-like)
* [Move](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-move)
* [Undo](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-undo)
* [Update](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-update)

... more to come

**Supported Actor types**

* [Application](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-application)
* [Group](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-group)
* [Person](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-person)

... more to come

**Supported Object types**

* [Article](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-article)
* [Audio](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-audio)
* [Document](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-document)
* [Image](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-image)
* [Note](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-note)
* [Page](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-page)
* [Video](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-video)

... more to come

**Supported Link types**

* [Mention](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mention)

The plugin has been tested with the following federated platforms:

* [Diaspora](https://diasporafoundation.org)
* [Drupal](https://www.drupal.org/project/activitypub)
* [Elgg](https://elgg.org/plugins/3330966)
* [Friendica](https://friendi.ca)
* [Lemmy](https://join-lemmy.org)
* [Mastodon](https://joinmastodon.org)
* [Misskey](https://misskey-hub.net/en/)
* [Mitra](https://mitra.social)
* [Pixelfed](https://pixelfed.org)
* [Pleroma](https://pleroma.social)
* [Smithereen](https://github.com/grishka/Smithereen)
* [WordPress](https://wordpress.org/plugins/activitypub)
* [Write as](https://write.as)

[Open an issue](https://github.com/RiverVanRain/activitypub/issues) if you have successfully interacted with another platform.

## Requirements Elgg plugins

You need to activate the following [bundled](https://learn.elgg.org/en/stable/plugins/index.html) Elgg plugins:

- **Friends**
- **Groups**: for enabling ActivityPub for Groups
- **Messages** and **Site notifications**: for sending and receiving the direct messages via Fediverse

## Suggested Elgg plugins

* [The Wire tools](https://github.com/ColdTrick/thewire_tools) to allow post and reshare via Groups


## Installation

* You must have the required Elgg version installed.

**Note**: Don't forget to [setup cron](https://learn.elgg.org/en/stable/intro/install.html#set-up-cron)!

* Update server configurations:

**For Apache**

Change this rule in .htaccess:

```
RewriteRule "^.well-known/" - [L]
```

On these rules:

```
RewriteCond %{REQUEST_URI} !^/\.well-known/(webfinger|nodeinfo|x-nodeinfo2)
RewriteCond %{REQUEST_URI} ^/\. [NC]
```

**For Nginx**

Add these rules at the end of your config:

```
location = /.well-known/webfinger {
	try_files $uri @elgg;
}
	
location = /.well-known/nodeinfo {
	try_files $uri @elgg;
}
	
location = /.well-known/x-nodeinfo2 {
	try_files $uri @elgg;
}

location ~ (^\.|/\.) {
	deny all;
}
```

See examples in the `/install/config` folder of this plugin.

* Activate `ActivityPub` plugin on the `/admin/plugins` page.


## Configure plugin

### Basic config

Go to the plugin's settings `/admin/plugin_settings/activitypub`

**Configure permissions:**

- *Allow users to enable ActivityPub for their account* for authenticated user
- *Enable ActivityPub for Groups* for groups
- *Allow users to resolve remote activities and actors* to search remote accounts and posts in Fediverse

At this point, when a user has enabled ActivityPub at `/settings/plugins/username/activitypub`, they should be discoverable. 

*Test enabled ActivityPub by user on Elgg:*

- Try searching for `@username@yourapp.url` on Mastodon.
- Use Webfinger: `http://yourapp.url/.well-known/webfinger?resource=acct:username@yourapp.url`

If you follow this user, [Follow](https://www.w3.org/TR/activitypub/#follow-activity-inbox) activity will arrive in the inbox and an [Accept](https://www.w3.org/TR/activitypub/#accept-activity-inbox) outbox activity will be created automatically.

**Configure global settings to select how to process ActivityPub activities:**

- Outbox: enable/disable *Send activities*
- Inbox: enable/disable *Process incoming*

This way you can configure your Elgg app as [Client-to-Server](https://www.w3.org/TR/activitypub/#client-to-server-interactions) or [Server-to-Server](https://www.w3.org/TR/activitypub/#server-to-server-interactions) interaction.

**Control domains:**

- Allow *Globally whitelisted domains* to send requests to your app
- Block *Globally blocked domains* from sending requests to your app

**Server parameters:**

Configure your ActivityPub instance.

Read [documentation](https://landrok.github.io/activitypub/activitypub-server-usage.html) to learn more about Server instance configuration.

### Types

On `/admin/activitypub/types` you get an overview of all ActivityPub types configuration entities. 

**Core types** are enabled when enabling the bundled Elgg plugin and have locked ActivityPub types.

**Dynamic types** are not enabled yet since this depends on your setup.


## Public and Private keys

Public and private keys are saved in `/data/1/1/activitypub/keys/`,
where `data` is your Elgg [data folder](https://learn.elgg.org/en/stable/intro/install.html#create-a-data-folder).


## Caching

For incoming or outgoing requests, images needs to be fetched to get the Inbox endpoint for example. 

This is saved in `/data/activitypub/cache/`,
where `data` is your Elgg [data folder](https://learn.elgg.org/en/stable/intro/install.html#create-a-data-folder).


## Logs

Enable logs in `Development` section at `/admin/plugin_settings/activitypub` to check how the plugin works. 

Logs are saved in `/data/activitypub/log/`,
where `data` is your Elgg [data folder](https://learn.elgg.org/en/stable/intro/install.html#create-a-data-folder).


## Inbox and outbox

Every user and group has an inbox and outbox where activities from remote users are stored and outgoing posts to your followers, unless it's an unlisted or private post.

Administrators can control all ActivityPub activities on `/admin/activitypub/activities` page.

## Support

[Frequently Asked Questions](https://github.com/RiverVanRain/activitypub/wiki/FAQ)

[Donate](https://nowpayments.io/donation/elgg)
