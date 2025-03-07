<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

use Elgg\Values;

$options = [
    'type' => 'object',
    'subtype' => \Elgg\ActivityPub\Entity\ActivityPubActivity::SUBTYPE,
    'count' => true,
    'limit' => (int) max(get_input('limit', max(25, _elgg_services()->config->default_limit)), 0),
    'offset' => (int) max(get_input('offset', 0), 0),
];

$count = elgg_get_entities($options);

if (!empty($count)) {
    echo elgg_view('navigation/pagination', [
        'base_url' => elgg_normalize_url('admin/activitypub/activities'),
        'count' => $count,
        'limit' => (int) max(get_input('limit', max(25, _elgg_services()->config->default_limit)), 0),
        'offset' => (int) max(get_input('offset', 0), 0),
    ]);

    $rows = [];

    $options['count'] = false;
    $entities = elgg_get_entities($options);

    /* @var $entity ElggEntity */
    foreach ($entities as $entity) {
        $row = [];

        // guid
        $row[] = elgg_format_element('td', ['width' => '5%'], elgg_view('output/url', [
            'text' => (int) $entity->guid,
            'href' => (string) $entity->getURL(),
        ]));

        // created
        $timestamp = (int) $entity->time_created;
        $date = Values::normalizeTime($timestamp);
        $row[] = elgg_format_element('td', ['width' => '10%'], $date->formatLocale(elgg_echo('activitypub:activities:friendlytime')));

        // collection
        $row[] = elgg_format_element('td', ['width' => '10%'], (string) $entity->collection);

        // type
        $row[] = elgg_format_element('td', ['width' => '10%'], (string) $entity->activity_type);

        // actor
        $actor = (string) $entity->actor;
        if (filter_var($actor, FILTER_VALIDATE_URL)) {
            $actor = elgg_view('output/url', [
                'href' => $actor,
                'text' => $actor,
            ]);
        }

        $row[] = elgg_format_element('td', ['width' => '15%'], $actor);

        // object
        $object = (string) $entity->activity_object;
        if (filter_var($object, FILTER_VALIDATE_URL)) {
            $object = elgg_view('output/url', [
                'href' => $object,
                'text' => $object,
            ]);
        } elseif (!empty((string) $entity->entity_subtype) && !empty((int) $entity->entity_guid)) {
            $local_entity = get_entity((int) $entity->entity_guid);
            if ($local_entity instanceof \ElggEntity) {
                $object = elgg_view('output/url', [
                    'href' => (string) $local_entity->getURL(),
                    'text' => (string) $local_entity->getURL(),
                ]);
            }
        }

        $row[] = elgg_format_element('td', ['width' => '15%'], $object);

        // queued
        $row[] = elgg_format_element('td', ['width' => '5%'], (bool) $entity->queued ? elgg_echo('option:yes') : elgg_echo('option:no'));

        // processed
        $row[] = elgg_format_element('td', ['width' => '5%'], (bool) $entity->processed ? elgg_echo('option:yes') : elgg_echo('option:no'));

        // status
        $row[] = elgg_format_element('td', ['width' => '10%'], (bool) $entity->status ? elgg_echo('activitypub:activities:published') : elgg_echo('activitypub:activities:unpublished'));

        // actions
        $row[] = elgg_format_element('td', ['width' => '5%'], elgg_view_menu('entity', [
            'entity' => $entity,
            'handler' => (string) elgg_extract('handler', $vars),
            'prepare_dropdown' => true,
        ]));

        $rows[] = elgg_format_element('tr', [], implode('', $row));
    }

    $header_row = [
        elgg_format_element('th', ['width' => '5%'], elgg_echo('activitypub:activities:guid')),
        elgg_format_element('th', ['width' => '15%'], elgg_echo('activitypub:activities:created')),
        elgg_format_element('th', ['width' => '10%'], elgg_echo('activitypub:activities:collection')),
        elgg_format_element('th', ['width' => '10%'], elgg_echo('activitypub:activities:activity_type')),
        elgg_format_element('th', ['width' => '15%'], elgg_echo('activitypub:activities:actor')),
        elgg_format_element('th', ['width' => '15%'], elgg_echo('activitypub:activities:activity_object')),
        elgg_format_element('th', ['width' => '5%'], elgg_echo('activitypub:activities:queued')),
        elgg_format_element('th', ['width' => '5%'], elgg_echo('activitypub:activities:processed')),
        elgg_format_element('th', ['width' => '10%'], elgg_echo('activitypub:activities:status')),
        elgg_format_element('th', ['width' => '5%'], elgg_echo('activitypub:activities:actions')), //85
    ];
    $header = elgg_format_element('tr', [], implode('', $header_row));

    $table_content = elgg_format_element('thead', [], $header);
    $table_content .= elgg_format_element('tbody', [], implode('', $rows));

    echo elgg_format_element('table', ['class' => 'elgg-table'], $table_content);
} else {
    echo elgg_format_element('div', [], elgg_echo('activitypub:activities:none'));
}
