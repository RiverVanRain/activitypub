<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\ObjectType;

class PlaceType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Place';

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-accuracy
     */
    #[ExportProperty]
    public string $accuracy;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-altitude
     */
    #[ExportProperty]
    public string $altitude;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-latitude
     */
    #[ExportProperty]
    public string $latitude;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-longitude
     */
    #[ExportProperty]
    public string $longitude;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-radius
     */
    #[ExportProperty]
    public string $radius;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-units
     */
    #[ExportProperty]
    public string $units;

    /**
     * The address of the place.
     *
     * @see https://schema.org/PostalAddress
     * @var array|string
     */
    #[ExportProperty]
    public array|string $address;
}
