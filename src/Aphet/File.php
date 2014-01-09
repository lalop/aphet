<?php

namespace Aphet;

use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;


class File
{
    
    private $real_path;
    
    public function __construct( $real_path )
    {
        $this->real_path = $real_path;
    }
    
    /**
     * @param $path String target path
     * @return \Assetic\Filter\FileAsset
     */
    public function asset( $opts = array() )
    {
        $asset = new FileAsset( $this->real_path );
        $ext = strtolower( substr( $this->real_path, strrpos( $this->real_path, '.' )));
        if( in_array( $ext, array('.css','.scss') ) ){//il faut rajouter la méthode asset-url
            $scss = new  ScssFilterFix();
            if(isset($opts['compass']) && $opts['compass']) $scss->enableCompass( true );
            $scss->registerFunction('aphet_url',function($args,$scss) {
                if($args[0][0] === 'string'){
                    $url = is_array($args[0][2][0]) ? $args[0][2][0][2][0] : $args[0][2][0];
                } else {
                    throw new \Exception('je ne sais pas quoi faire là');
                }
                if(strpos($url, '?')!==false) list($url, $query) = explode('?', $url);
                else $query = null;
                if(strpos($url,'#')!==false) list($url, $hash) = explode('#', $url);
                else $hash = null;
                return 'url('. aphet_url($url) .($query? "?{$query}" : '').($hash? "?{$hash}" : '') .')';
            });
            $asset->ensureFilter($scss);
        } elseif ( $ext === '.js') {
            $filter = new \Assetic\Filter\CallablesFilter(function( $asset ) {
                $asset->setContent(preg_replace_callback('/aphet_url\((.*)\)/', function( $match ) {
                    return '\''.aphet_url(json_decode($match[1])).'\'';
                }, $asset->getContent()));
            });
            $asset->ensureFilter($filter);
        }
        return $asset;
    }
    
    public static function getExt( $file_name )
    {
        return strtolower( substr( $file_name, strrpos( $file_name, '.' )));
    }
    
}
