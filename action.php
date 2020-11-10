<?php
/**
 * @author         Szymon Olewniczak <solewniczak@rid.pl>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_jplayer extends DokuWiki_Action_Plugin {

    /**
     * Register its handlers with the DokuWiki's event controller
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'include_dependencies', array());
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, 'jsinfo');
    }
    public function include_dependencies(Doku_Event $event, $param) {
        $skin = $this->getConf('skin');
        // Adding a stylesheet
        $event->data['link'][] = array (
            'type' => 'text/css',
            'rel' => 'stylesheet',
            'href' => DOKU_BASE .
            'lib/plugins/jplayer/vendor/happyworm/jplayer/dist/skin/'.$skin.'/css/jplayer.'.$skin.'.min.css',
        );

        // Adding a JavaScript File
        $event->data['script'][] = array (
            'type' => 'text/javascript',
            'src' => DOKU_BASE .
            'lib/plugins/jplayer/vendor/happyworm/jplayer/dist/jplayer/jquery.jplayer.min.js',
            'defer' => 'defer',
            '_data' => '',
        );

        $event->data['script'][] = array (
            'type' => 'text/javascript',
            'src' => DOKU_BASE .
            'lib/plugins/jplayer/vendor/happyworm/jplayer/dist/add-on/jplayer.playlist.min.js',
            'defer' => 'defer',
            '_data' => '',
        );
    }

    public function jsinfo(Doku_Event $event, $param) {
        global $JSINFO, $ID;

        if (!isset($JSINFO['plugin'])) $JSINFO['plugin'] = array();

        $name = $this->getPluginName();
        $JSINFO['plugin'][$name] = p_get_metadata($ID, "plugin $name");
    }
}
