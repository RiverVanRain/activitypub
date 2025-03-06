<?php
/**
 * All exportable entities/items should use this
 */
namespace Elgg\ActivityPub\Helpers;

interface ExportableInterface {
    /**
     * @param array $extras
     * @return array
     * */
    public function export(array $extras = []): array;
}
