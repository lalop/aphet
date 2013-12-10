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
    public function asset( )
    {
        $asset = new FileAsset( $this->real_path );
        $ext = strtolower( substr( $this->real_path, strrpos( $this->real_path, '.' )));
        if( in_array( $ext, array('.css','.scss') ) ){//il faut rajouter la méthode asset-url
            $scss = new  \Assetic\Filter\ScssphpFilter();
            $scss->registerFunction('asset_url',function($args,$scss) {
                if($args[0][0] === 'string'){
                    $url = is_array($args[0][2][0]) ? $args[0][2][0][2][0] : $args[0][2][0];
                } else {
                    throw new \Exception('je ne sais pas quoi faire là');
                }
                if(strpos($url, '?')!==false) list($url, $query) = explode('?', $url);
                else $query = null;
                if(strpos($url,'#')!==false) list($url, $hash) = explode('#', $url);
                else $hash = null;
                return 'url('. assets_url($url) .($query? "?{$query}" : '').($hash? "?{$hash}" : '') .')';
            });
            $asset->ensureFilter($scss);
        } elseif ( $ext === '.js') {
            $filter = new \Assetic\Filter\CallablesFilter(function( $asset ) {
                $asset->setContent(preg_replace_callback('/asset_url\((.*)\)/', function( $match ) {
                    return '\''.assets_url(json_decode($match[1])).'\'';
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
