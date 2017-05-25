<?php
/**
 * Embed a jPlayer
 *
 * @author     Szymon Olewniczak <(my first name) [at] imz [dot] re>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

require DOKU_PLUGIN . 'jplayer/mustache.php-2.11.1/src/Mustache/Autoloader.php';
Mustache_Autoloader::register();

class syntax_plugin_jplayer extends DokuWiki_Syntax_Plugin {
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
        $pathinfo = pathinfo($item['file']);
        $link = ml($item['id'],'',true);

        $audio = array('title' => $pathinfo['filename']);

        if ($pathinfo['extension'] === 'mp3') {
            $audio['mp3'] = $link;
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
        $data = array();
        $data['audio'] = array();
        // extract params
        $files_and_namespaces = preg_split('/\s+/', $match);
            
        foreach ($files_and_namespaces as $file) {
            // namespace (including resolving relatives)
            resolve_mediaid(getNS($ID), $file, $exists);
            //$auth = auth_quickaclcheck("$ns:*");
            $auth = auth_quickaclcheck($file);
            if ($auth < AUTH_READ) {
                // FIXME: print permission warning here instead?
            } else {
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
        }
    
        return $data;
    }
    
    private function _script_on_document_ready($script) {
        $output = '<script type="text/javascript">'."\n";
        $output .= "/*<![CDATA[*/\n";
        $output .= 'jQuery(document).ready(function(){'."\n";
        $output .= $script;
        $output .=  '});'."\n";
        $output .= "/*!]]>*/\n";
        $output .= '</script>';
        return $output;
    }
    /**
     * Create output
     */
    function render($mode, Doku_Renderer $R, $data) {
        static $instance = 0;
        if ($mode != 'xhtml') return false;
        
        $instance += 1;
        $ids = array(
            'JPLAYER' => 'jquery_jplayer_'.$instance,
            'WRAPPER' => 'jp_container_'.$instance
        );
        
        $skin = $this->getConf('skin');
        
        $tpl_path = DOKU_PLUGIN .
                'jplayer/jPlayer-2.9.2/dist/skin/'.$skin.'/mustache/';
        // use .html instead of .mustache for default template extension
        $options =  array('extension' => '.html');
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($tpl_path, $options)
        ));
        $tpl = $mustache->loadTemplate('jplayer.'.$skin.'.audio.playlist');
        $R->doc .= $tpl->render($ids);
                
        $json = new JSON();
        $selectors = $json->encode(array(
            'jPlayer' => '#'.$ids['JPLAYER'],
            'cssSelectorAncestor' => '#'.$ids['WRAPPER']
        ));
        $audio = $json->encode($data['audio']);
        $opitons = $json->encode(array(
            'swfPath' => DOKU_BASE . 'lib/plugins/jplayer/jPlayer-2.9.2/dist/jplayer',
            'supplied' => 'oga, mp3',
            'wmode' => 'window',
            'useStateClassSkin' => true,
		    'autoBlur' => false,
		    'smoothPlayBar' => true,
		    'keyEnabled' => true
        ));
        
        $R->doc .= $this->_script_on_document_ready(
            'new jPlayerPlaylist('.$selectors.', '.$audio.', '.$options.');');
        
        return true;
    }
}
