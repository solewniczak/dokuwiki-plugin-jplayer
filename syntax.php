<?php
/**
 * Embed a jPlayer
 *
 * @author     Szymon Olewniczak <solewniczak@rid.pl>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

require_once __DIR__ . '/vendor/autoload.php';

class syntax_plugin_jplayer extends DokuWiki_Syntax_Plugin {

    private $getID3;

    public function __construct() {
        $this->getID3 = new getID3;
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }
    /**
     * Where to sort in?
     */
    function getSort(){
        return 301;
    }
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{jPlayerPlaylist>[^}]*\}\}',$mode,'plugin_jplayer');
    }

    private function _audio($item) {
        $full_path = mediaFN($item['id']);
        $pathinfo = pathinfo($item['file']);
        $link = ml($item['id'],'',true);

        $audio = array('title' => $pathinfo['filename']);

        if ($pathinfo['extension'] === 'mp3') {
            $audio['mp3'] = $link;
            $analyze = $this->getID3->analyze($full_path);
            if (isset($analyze['tags']['id3v2']['title'])) {
                $audio['title'] = $analyze['tags']['id3v2']['title'];
            } elseif(isset($analyze['tags']['id3v1']['title'])) {
                $audio['title'] = $analyze['tags']['id3v1']['title'];
            }
        }
        if ($pathinfo['extension'] === 'ogg') {
            $audio['oga'] = $link;
        }

        //https://stackoverflow.com/questions/9066878/jquery-jplayer-enable-download-for-client
        $audio['free'] = true;

        return $audio;
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $conf;
        global $ID;
        $match = substr($match, strlen('{{jPlayerPlaylist>'), -2); //strip markup from start and end
        $data = array('unique_id' => $pos);
        $data['audio'] = array();
        // extract params
        $files_and_namespaces = preg_split('/\s+/', $match);
        //remove empty
        $files_and_namespaces = array_filter($files_and_namespaces);

        foreach ($files_and_namespaces as $file) {
            // namespace (including resolving relatives)
            resolve_mediaid(getNS($ID), $file, $exists);
            if (!$exists) {
                msg("jPlayerPlaylist: file \"$file\" doesn't exist", -1);
                continue;
            }

            $auth = auth_quickaclcheck($file);
            if ($auth < AUTH_READ) {
                msg("jPlayerPlaylist: no read permission to \"$file\"", -1);
                continue;
            }

            $dir = utf8_encodeFN(str_replace(':','/',$file));
            $full_path = $conf['mediadir'] . '/' . $dir;
            if (is_dir($full_path)) {
                $sort = 'natural';
                search($files, $conf['mediadir'], 'search_media',
                       array('showmsg' => false, 'depth' => 1), $dir, 1, $sort);

                foreach ($files as $item) {
                    $data['audio'][] = $this->_audio($item);
                }
            } else {
                $data['audio'][] = $this->_audio(array(
                    'file' => $full_path,
                    'id' => $file
                ));
            }
        }

        return $data;
    }

    private function render_metadata(Doku_Renderer $R, $data) {
        $plugin_name = $this->getPluginName();
        $unique_id = $data['unique_id'];

        if (!isset($R->meta['plugin'][$plugin_name])) $R->meta['plugin'][$plugin_name] = array();

        $ids = array(
            'JPLAYER' => 'jquery_jplayer__' . $unique_id,
            'WRAPPER' => 'jp_container__' . $unique_id
        );

        $options = array(
            'swfPath' => DOKU_BASE . 'lib/plugins/jplayer/vendor/happyworm/jplayer/dist/jplayer',
            'supplied' => 'oga, mp3',
            'wmode' => 'window',
            'useStateClassSkin' => true,
            'autoBlur' => false,
            'smoothPlayBar' => true,
            'keyEnabled' => true
        );

        $R->meta['plugin'][$plugin_name][$unique_id] = array(
            'ids' => $ids,
            'audio' => $data['audio'],
            'options' => $options
        );
    }

    private function render_xhtml(Doku_Renderer $R, $data) {
        global $ID;

        $plugin_name = $this->getPluginName();
        $unique_id = $data['unique_id'];

        $skin = $this->getConf('skin');

        $tpl_path = DOKU_PLUGIN .
            'jplayer/vendor/happyworm/jplayer/dist/skin/'.$skin.'/mustache/';
        // use .html instead of .mustache for default template extension
        $options =  array('extension' => '.html');
        $mustache = new Mustache_Engine(array(
                                            'loader' => new Mustache_Loader_FilesystemLoader($tpl_path, $options)
                                        ));
        $tpl = $mustache->loadTemplate('jplayer.'.$skin.'.audio.playlist');

        $meta = p_get_metadata($ID, "plugin $plugin_name");
        $ids = $meta[$unique_id]['ids'];
        $R->doc .= $tpl->render($ids);
    }

    /**
     * Create output
     */
    function render($mode, Doku_Renderer $R, $data) {
        $method = "render_$mode";
        if (method_exists($this, $method)) {
            call_user_func(array($this, $method), $R, $data);
            return true;
        }
        return false;
    }
}
