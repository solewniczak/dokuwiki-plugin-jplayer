<?php
/**
 *
 * @license        GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author         Szymon Olewniczak <(my first name) [at] imz [dot] re>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_jplayer extends DokuWiki_Action_Plugin {

    /**
     * Register its handlers with the DokuWiki's event controller
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'include_dependencies', array());
        
    }
    public function include_dependencies(Doku_Event $event) {
        $skin = $this->getConf('skin');
        // Adding a stylesheet 
        $event->data['link'][] = array (
            'type' => 'text/css',
            'rel' => 'stylesheet', 
            'href' => DOKU_BASE .
            'lib/plugins/jplayer/jPlayer-2.9.2/dist/skin/'.$skin.'/css/jplayer.'.$skin.'.min.css',
        );
        
        // Adding a JavaScript File
        $event->data['script'][] = array (
            'type' => 'text/javascript',
            'src' => DOKU_BASE .
            'lib/plugins/jplayer/jPlayer-2.9.2/dist/jplayer/jquery.jplayer.min.js',
            '_data' => '',
        );
            
        $event->data['script'][] = array (
            'type' => 'text/javascript',
            'src' => DOKU_BASE .
            'lib/plugins/jplayer/jPlayer-2.9.2/dist/add-on/jplayer.playlist.min.js',
            '_data' => '',
        );
    }
}