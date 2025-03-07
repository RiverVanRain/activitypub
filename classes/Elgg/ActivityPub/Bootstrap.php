<?php

/**
 * Elgg ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub;

use Elgg\Includer;
use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap
{
    /**
     * Get plugin root
     * @return string
     */
    protected function getRoot()
    {
        return $this->plugin->getPath();
    }

    /**
     * Executed during 'plugin_boot:before', 'system' event
     *
     * Allows the plugin to require additional files, as well as configure services prior to booting the plugin
     *
     * @return void
     */
    public function load()
    {
        Includer::requireFileOnce($this->getRoot() . '/lib/functions.php');
    }

    /**
     * Executed during 'plugin_boot:before', 'system' event
     *
     * Allows the plugin to register handlers for 'plugin_boot', 'system' and 'init', 'system' events,
     * as well as implement boot time logic
     *
     * @return void
     */
    public function boot()
    {
        $events = $this->elgg()->events;

        $events->registerHandler('route:config', 'all', '\Elgg\ActivityPub\Router\PageHandler::alterRoute');
    }

    /**
     * Executed during 'init', 'system' event
     *
     * Allows the plugin to implement business logic and register all other handlers
     *
     * @return void
     */
    public function init()
    {
        if (!elgg_is_active_plugin('indieweb')) {
            elgg_register_external_file('css', 'openwebicons', elgg_get_simplecache_url('openwebicons/css/openwebicons.min.css'));
            elgg_load_external_file('css', 'openwebicons');
        }

        //import inbox
        $interval = (string) elgg_get_plugin_setting('import_inbox', 'activitypub');
        if ((bool) elgg_get_plugin_setting('process_inbox_handler', 'activitypub') && $interval !== 'disable') {
            elgg_register_event_handler('cron', $interval, '\Elgg\ActivityPub\Cron::importFollowers');
        }
    }

    /**
     * Executed during 'ready', 'system' event
     *
     * Allows the plugin to implement logic after all plugins are initialized
     *
     * @return void
     */
    public function ready()
    {
    }

    /**
     * Executed during 'shutdown', 'system' event
     *
     * Allows the plugin to implement logic during shutdown
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * Executed when plugin is activated, after 'activate', 'plugin' event and before activate.php is included
     *
     * @return void
     */
    public function activate()
    {
        //Cache
        $cache_dir = elgg_get_data_path() . 'activitypub/cache/';

        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        //Logs
        $logs_dir = elgg_get_data_path() . 'activitypub/logs/';

        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }

        //Keys
        elgg()->activityPubSignature->generateKeys((string) elgg_get_site_entity()->getDomain());
    }

    /**
     * Executed when plugin is deactivated, after 'deactivate', 'plugin' event and before deactivate.php is included
     *
     * @return void
     */
    public function deactivate()
    {
    }

    /**
     * Registered as handler for 'upgrade', 'system' event
     *
     * Allows the plugin to implement logic during system upgrade
     *
     * @return void
     */
    public function upgrade()
    {
    }
}
