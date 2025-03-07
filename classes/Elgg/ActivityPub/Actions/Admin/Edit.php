<?php

namespace Elgg\ActivityPub\Actions\Admin;

class Edit
{
    public function __invoke(\Elgg\Request $request)
    {
        $guid = (int) $request->getParam('guid');

        $entity = get_entity($guid);

        if (!$entity instanceof \Elgg\ActivityPub\Entity\ActivityPubActivity) {
            throw new \Elgg\Exceptions\Http\EntityNotFoundException();
        }

        $entity->queued = (bool) $request->getParam('queued');
        $entity->processed = (bool) $request->getParam('processed');
        $entity->status = (bool) $request->getParam('status');

        if (!$entity->save()) {
            return elgg_error_response(elgg_echo('activitypub:activitypub_activity:edit:error'));
        }

        return elgg_ok_response('', elgg_echo('activitypub:activitypub_activity:edit:success'));
    }
}
