<?php

namespace Elgg\ActivityPub\Services;

use ActivityPhp\Server;
use Elgg\Http\Request;
use Elgg\Traits\Di\ServiceFacade;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class ActivityPubSignature
{
    use ServiceFacade;

    /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'activityPubSignature';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey(string $name = '')
    {
        $file = new \ElggFile();
        $file->owner_guid = (int) elgg_get_site_entity()->guid;
        $file->setFilename('activitypub/keys/' . $name . '/public.pem');

        return file_get_contents($file->getFilenameOnFilestore());
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey(string $name = '')
    {
        $file = new \ElggFile();
        $file->owner_guid = (int) elgg_get_site_entity()->guid;
        $file->setFilename('activitypub/keys/' . $name . '/private.pem');

        return file_get_contents($file->getFilenameOnFilestore());
    }

    /**
     * {@inheritdoc}
     */
    public function createSignature($private_key_path, $host, $path, $digest, $date = null)
    {
        if (!isset($date)) {
            $date = gmdate('D, d M Y H:i:s T', time());
        }
        try {
            $plaintext = "(request-target): post $path\nhost: $host\ndate: $date\ndigest: $digest";

            $rsa = RSA::loadPrivateKey($this->getPrivateKey($private_key_path))->withHash('sha256')->withPadding(RSA::SIGNATURE_PKCS1);

            return $rsa->sign($plaintext);
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                $this->log(elgg_echo('activitypub:keys:signature:create:error', [$e->getMessage()]));
            }

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDigest($message)
    {
        return 'SHA-256=' . base64_encode(hash('sha256', $message, true));
    }

    /**
     * {@inheritdoc}
     */
    public function generateKeys(string $name = '')
    {
        try {
            $private_key = RSA::createKey(4096);
            foreach (['public' => 'publickey', 'private' => 'privatekey'] as $filename => $rsakey) {
                $key = "{$filename}.pem";

                $data = ($rsakey === 'publickey') ? (string) $private_key->getPublicKey() : $private_key;

                $tmp = new \ElggFile();
                $tmp->owner_guid = (int) elgg_get_site_entity()->guid;
                $tmp->container_guid = (int) elgg_get_site_entity()->guid;
                $tmp->setFilename("activitypub/keys/{$name}/{$key}");
                $tmp->open('write');
                $tmp->write($data);
                $tmp->close();
            }

            return true;
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                $this->log(elgg_echo('activitypub:keys:generate:error', [$e->getMessage()]));
            }
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteKeys(string $name = '')
    {
        $dir_path = elgg_get_data_path() . '1/1/activitypub/keys/' . $name;

        return elgg_delete_directory($dir_path);
    }

    /**
     * {@inheritdoc}
     */
    public function verifySignature(Request $request, string $actor, Server $server)
    {
        $verified = false;

        try {
            $actor = $server->actor($actor);
            $publicKeyPem = $actor->getPublicKeyPem();

            $signature = '';
            $strings = [];
            $header_signature = $request->headers->get('signature');

            $sig_explode = explode(",", $header_signature ?: '');

            if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                $this->log('request: Elgg\Http\Request');
                $this->log("method:\n" . $request->getMethod());
                $this->log("path:\n" . $request->getPathInfo());
                $this->log("signature:\n" . $header_signature);
                $this->log("sig explode:\n" . print_r($sig_explode, true));
            }

            foreach ($sig_explode as $entry) {
                $entry_ex = explode("=", $entry ?: '');
                if (!empty($entry_ex[0]) && !empty($entry_ex[1])) {
                    if ($entry_ex[0] === 'headers') {
                        $ex = explode(" ", str_replace('"', '', $entry_ex[1]));

                        if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                            $this->log("header explode: \n" . print_r($ex, true));
                        }

                        foreach ($ex as $header_key) {
                            if ($header_key === '(request-target)') {
                                $parsed = parse_url($request->getRequestUri());
                                $strings[] = '(request-target): ' . strtolower($request->getMethod()) . ' ' . $parsed['path'];
                            } else {
                                $strings[] = $header_key . ': ' . $request->headers->get($header_key);
                            }
                        }
                    }

                    if ($entry_ex[0] === 'signature') {
                        $signature = str_replace(['"', 'signature='], '', $entry);
                    }
                }
            }

            if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                $this->log("strings:\n" . print_r($strings, true));
                $this->log("signature: \n" . print_r($signature, true));
            }

            $data = implode("\n", $strings);

            if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                $this->log("data:\n" . print_r($data, true));
            }

            if (!empty($data) && !empty($signature)) {
                $rsa = RSA::loadPublicKey($publicKeyPem)->withHash('sha256')->withPadding(RSA::SIGNATURE_PKCS1);
                $verified = $rsa->verify($data, base64_decode($signature, true));
            }
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                $this->log(elgg_echo('activitypub:keys:signature:verify:error', [$e->getMessage()]));
            }
        }

        return $verified;
    }

    /** Logger */
    public function log($message = '')
    {
        $log_file = elgg_get_data_path() . 'activitypub/logs/log_error_signature';

        $log = new Logger('ActivityPub');
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        // add records to the log
        return $log->warning($message);
    }
}
