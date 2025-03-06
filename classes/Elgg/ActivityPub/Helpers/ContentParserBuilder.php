<?php
namespace Elgg\ActivityPub\Helpers;

use Elgg\ActivityPub\Common\Regex;

class ContentParserBuilder {
 
    /**
     * Returns all urls that are found in a string
     * @return string[]
     */
    public static function getMentions(string $input): array {
        preg_match_all(Regex::AT, $input, $matches);

        /** @var string[] */
        $mentions = [];

        foreach ($matches[0] as $match) {
            $mentions[] = ltrim($match, ' ');
        }

        return $mentions;
    }
}
