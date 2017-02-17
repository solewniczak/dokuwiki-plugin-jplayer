<?php
/**
 * Embed a jPlayer
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
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
    
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $conf;
        global $ID;
        $match = substr($match, strlen('{{jPlayerPlaylist>'), -2); //strip markup from start and end
        $data = array();
        // extract params
        list($ns,$params) = explode(' ',$match,2);
        $ns = trim($ns);
        // namespace (including resolving relatives)
        $ns = resolve_id(getNS($ID),$ns);
        $auth = auth_quickaclcheck("$ns:*");
        if ($auth < AUTH_READ) {
            // FIXME: print permission warning here instead?
        } else {
            $dir = utf8_encodeFN(str_replace(':','/',$ns));
            $sort = 'natural';
            search($data, $conf['mediadir'], 'search_media', 
                   array('showmsg' => false, 'depth' => 1), $dir, 1, $sort);

            $data['audio'] = array();
            foreach ($data as $item) {
                $pathinfo = pathinfo($item['file']);
                $link = ml($item['id'],'',true);
                
                $audio = array('title' => $pathinfo['filename']);
                
                if ($pathinfo['extension'] === 'mp3') {
                    $audio['mp3'] = $link;
                }
                if ($pathinfo['extension'] === 'ogg') {
                    $audio['oga'] = $link;
                }
                $data['audio'][] = $audio;
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