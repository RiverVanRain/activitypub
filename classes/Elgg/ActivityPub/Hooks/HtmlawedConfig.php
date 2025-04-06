<?php

namespace Elgg\ActivityPub\Hooks;

class HtmlawedConfig
{
    public function __invoke(\Elgg\Event $event)
    {
        $var = $event->getValue();

        if ((!is_string($var) && !is_array($var)) || empty($var) || isset($var['params']['head_extend']) || isset($var['params']['footer_extend'])) {
            return $var;
        }

        $config = [
            // seems to handle about everything we need.
            'safe' => true,

            // remove comments/CDATA instead of converting to text
            'comment' => 1,
            'cdata' => 1,

            // do not check for unique ids as the full input stack could be checked multiple times
            // @see https://github.com/Elgg/Elgg/issues/12934
            'unique_ids' => 0,
            'elements' => '*-applet-button-form-input-textarea-script-style-embed-object+iframe+audio+video+(tiny-math-inline)+(tiny-math-block)+maction+math+maligngroup+malignmark+menclose+merror+mfenced+mfrac+mi+mmultiscripts+mn+mo+mover+mpadded+mphantom+mprescripts+mroot+mrow+ms+mspace+msqrt+mstyle+msub+msubsup+msup+mtable+mtd+mtext+mtr+munder+munderover+semantics+annotation+(annotation-xml)+mscarries+mscarry+msgroup+msline+msrow',
            'deny_attribute' => "on*, formaction",
            'hook_tag' => '_elgg_htmlawed_tag_post_processor',
            'schemes' => '*:http,https,ftp,news,mailto,rtsp,teamspeak,gopher,mms,callto,git,svn,rtmp,steam,nntp,sftp,ssh,tel,telnet,magnet,bitcoin,data',
        ];

        // add nofollow to all links on output
        if (!elgg_in_context('input')) {
            $config['anti_link_spam'] = ['/./', ''];
        }

        $config = elgg_trigger_event_results('config', 'htmlawed', [], $config);
        $spec = elgg_trigger_event_results('spec', 'htmlawed', [], '');

        if (!is_array($var)) {
            return \Htmlawed::filter($var, $config, $spec);
        } else {
            $callback = function (&$v, $k, $config_spec) {
                if (!is_string($v) || empty($v)) {
                    return;
                }

                list ($config, $spec) = $config_spec;
                $v = \Htmlawed::filter($v, $config, $spec);
            };

            array_walk_recursive($var, $callback, [$config, $spec]);

            return $var;
        }
    }
}
