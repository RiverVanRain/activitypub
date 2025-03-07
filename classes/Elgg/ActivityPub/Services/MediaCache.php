<?php

namespace Elgg\ActivityPub\Services;

use Elgg\Traits\Di\ServiceFacade;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class MediaCache
{
    use ServiceFacade;

    /**
     * @var array
     */
    private static $cache;

    /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'activityPubMediaCache';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @return bool
     */
    public function isValidUrl($url = ''): bool
    {
        // Replace non-latin chars
        if (preg_match('#^([\w\d]+://)([^/]+)(.*)$#iu', $url, $m)) {
            $url = $m[1] . idn_to_ascii($m[2], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) . $m[3];
        }
        $url = urldecode($url);
        $url = rawurlencode($url);
        $url = str_replace(['%3A','%2F'], [':', '/'], $url);

        // based on http://php.net/manual/en/function.filter-var.php#104160
        // adapted by @mrclay in https://github.com/mrclay/Elgg-leaf/blob/62bf31c0ccdaab549a7e585a4412443e09821db3/engine/lib/output.php
        $res = filter_var($url, FILTER_VALIDATE_URL);
        if ($res) {
            return $res;
        }
        // Check if it has unicode chars.
        $l = mb_strlen($url);
        if (strlen($url) == $l) {
            return $res;
        }

        // Replace wide chars by “X”.
        $s = '';
        for ($i = 0; $i < $l; ++$i) {
            $ch = elgg_substr($url, $i, 1);
            $s .= (strlen($ch) > 1) ? 'X' : $ch;
        }
        // Re-check now.
        return filter_var($s, FILTER_VALIDATE_URL) ? $url : false;
    }

    /**
     * Check if URL exists and is reachable by making an HTTP request to retrieve header information
     *
     * @param string $url URL of the resource
     * @return boolean
     */
    public function exists($url = '')
    {
        $response = $this->request($url);
        if ($response instanceof Response) {
            return $response->getStatusCode() == 200;
        }
        return false;
    }

    /**
     * Returns head of the resource
     *
     * @param string $url URL of the resource
     * @return Response|false
     */
    public function request($url = '')
    {
        $url = str_replace(' ', '%20', $url);
        if (!$this->isValidUrl($url)) {
            return false;
        }
        if (!isset(self::$cache[$url])) {
            try {
                $client = new Client();
                $response = $client->request('GET', $url);
            } catch (\Exception $e) {
                $response = false;
            }
            self::$cache[$url] = $response;
        }

        return self::$cache[$url];
    }

    /**
     * Get contents of the page
     *
     * @param string $url URL of the resource
     * @return string
     */
    public function read($url = '')
    {
        $body = '';
        if (!$this->exists($url)) {
            return $body;
        }

        $response = $this->request($url);
        $body = (string) $response->getBody();
        return $body;
    }

    /**
     * Get mime type of the URL content
     *
     * @param string $url URL of the resource
     * @return string
     */
    public function getContentType($url = '')
    {
        $response = $this->request($url);
        if ($response instanceof Response) {
            $header = $response->getHeader('Content-Type');
            if (is_array($header) && !empty($header)) {
                $parts = explode(';', $header[0]);
                return trim($parts[0]);
            }
        }
        return '';
    }

    /**
     * Save image
     *
     * @param string $url URL of the resource
     * @param string $folder Directory to save images (e.g. 'thumbs', 'avatar')
     *
     * @return void
     */
    public function saveImageFromUrl(string $url = '', string $folder = 'thumbs')
    {
        if (!$url) {
            return;
        }

        $mime = $this->getContentType($url);
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $ext = 'jpg';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/webp':
                $ext = 'webp';
                break;
            default:
                return false;
        }

        $basename = sha1($url);
        $raw_bytes = $this->read($url);
        if (empty($raw_bytes)) {
            return;
        }

        $site = elgg_get_site_entity();
        $tmp = new \ElggFile();
        $tmp->owner_guid = $site->guid;
        $tmp->setFilename("activitypub/media_cache/tmp/$basename.$ext");
        $tmp->open('write');
        $tmp->write($raw_bytes);
        $tmp->close();
        unset($raw_bytes);

        $imagesize = getimagesize($tmp->getFilenameOnFilestore());
        if (!$imagesize) {
            $tmp->delete();
            return false;
        }

        $image = new \ElggFile();
        $image->owner_guid = $site->guid;
        $image->setFilename("activitypub/media_cache/$folder/$basename.jpg");

        $image->natural_width = $imagesize[0];
        $image->natural_height = $imagesize[1];

        $image->open('write');
        $image->close();

        $thumb = elgg_save_resized_image($tmp->getFilenameOnFilestore(), $image->getFilenameOnFilestore(), [
            'w' => 1020,
            'h' => 1020,
            'upscale' => false,
            'square' => false,
        ]);

        unset($thumb);
        $tmp->delete();

        return $image;
    }
}
