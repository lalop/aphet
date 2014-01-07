<?php

namespace Aphet;

use Assetic\AssetWriter;
use Assetic\Factory\LazyAssetManager;
use Assetic\Factory\AssetFactory;
use Assetic\Asset\AssetCollection;

class Manager
{

    private $cache = null;
    private $cache_updated = false;
    private $loader;
    public $settings = array();
    
    
    private function __construct(array $settings)
    {
        
        if( !isset($settings['public_path']) || !file_exists($settings['public_path']) ) {
            throw new Exceptions\PublicPathNotFoundException("public path {$settings['public_path']} not found");
        }
        if( !is_writable($settings['public_path']) ) {
            throw new Exceptions\PublicPathNotWritableException("Can't write in public path {$settings['public_path']}");
        }
        
        $this->settings = array_merge(array(
            'modes' => Modes::DEV,
            'assets_paths' => array(),
            'web_path' => 'assets',
        ), $settings );
        
        if( !isset($this->settings['cache_file']) ) {
            $this->settings['cache_file'] = implode( DIRECTORY_SEPARATOR, array(
                $this->settings['public_path'],
                $this->settings['web_path'],
                'cache.php'
            ));
        }
        
        $this->loader = new Loader($this->settings['assets_paths'], $this );
        
        if(isset($settings['request_handler']) && $settings['request_handler'] instanceof AbstractRequestHandler){
            $req = $settings['request_handler'];
            $req->initRequestHandler( $this );
        }
        
        include_once __DIR__ . '/../functions.php';
        aphet_init( $this );
        
    }
    
    public static function init(array $settings)
    {
        return new self( $settings );
    }


    /**
     * @params $relative_paths array|string
     * @params $name obligatoire si count($relative_paths) > 1  ou $relative_paths est un string
     * @params $widget (optionnel)
     */
    public function computeAssetsUrl($relative_path, $name = null)
    {
        if( $this->settings['modes'] & Modes::CONCAT && $name ) {
               
            // $name has an extension ?
            $name_ext = $name;
            if( !in_array(File::getExt( $name ), array('.js','.css')) ) {
                $path = is_array($relative_path)? $relative_path[0] : $relative_path;
                $name_ext .= str_replace( 'scss', 'css', File::getExt( $path ) );
            }
            
            // in cache
            if(null === $this->getCache( $name_ext ) ){
                // compile
                $this->setCache( $name_ext, $this->computeAsset( $relative_path, $name_ext )->getTargetPath( ) );
            }
            
            $urls = array( $this->getCache( $name_ext ));
            
        } else {
            
            $paths = is_array( $relative_path )? $relative_path : array( $relative_path );
            foreach( $paths as $path ) {
                
                //in cache
                if(null === $this->getCache( $path ) ){
                    // compile
                    $this->setCache( $path, $this->computeAsset( $path )->getTargetPath( ) );
                }
                
                $urls[] = $this->getCache( $path );

            }
        }
        
        $manager = $this;
        $urls = array_map( function( $url ) use( $manager ){
            return $manager->settings['request_handler']->urlFor( $url );
        }, $urls );
        
        return is_string($relative_path) ? current($urls) : $urls;
    }


    public function computeAsset( $relative_path, $name = null)
    {
        $paths = is_array($relative_path)? $relative_path : array( $relative_path );
        
        if( count($paths)>1 && null === $name ) {
            throw new Exception('You have to define a name for asset collection');
        }
        
        $urls = array();
        $am = new LazyAssetManager( new AssetFactory('') );
        $assets = array();
        $asset_collection = new AssetCollection();
        if( $this->settings['modes'] & Modes::CONCAT ) {
            $assets[] = $asset_collection;
        }
        foreach( $paths as $p ) {
            $file = $this->loader->fromAppPath( $p );
            $asset = $file->asset();
            $ext = str_replace(array('sass','scss'), 'css', File::getExt($p) );
            $filename = substr( $p, 0, strrpos( $p, '.' ) );

            if( $this->settings['modes'] & Modes::CONCAT ) {
                $asset_collection->add( $asset );
                if( null === $name ) $name = $filename. $ext;
                $asset_collection->setTargetPath( $name );
            } else {
                $asset->setTargetPath( $filename . $ext );
                $assets[] = $asset;
            }
        }

        foreach( $assets as $asset ) {
            // add the timestamp
            $target_path = explode( '/', $asset->getTargetPath() );
            $file_name = array_pop( $target_path );
            $target_path[] = $am->getLastModified( $asset ) .'-'. $file_name;
            $web_path = implode( '/', $target_path ) ;

            $asset->setTargetPath( $web_path );
            if( !file_exists( $asset->getTargetPath() ) ){
                
                if( $this->settings['modes'] & Modes::MINIFY ) {
                    switch ( $ext ) {
                    case '.css':
                        $asset->ensureFilter( new \Assetic\Filter\CssMinFilter() );    
                        break;
                    case '.js' :
                        $asset->ensureFilter( new \Assetic\Filter\JSMinFilter() );
                        break;
                    }
                }
                $writer = new AssetWriter( $this->settings['public_path'] . $this->settings['web_path'] );
                $writer->writeAsset( $asset );
            }
        }

        return $assets[0];
    }
    
    public function loader()
    {
        return $this->loader;
    }
    
    private function getCache( $name = null )
    {
        if( null === $this->cache ){
            if( $this->settings['modes'] & Modes::CACHE && file_exists( $this->settings['cache_file'] ) ){
                try{
                    $this->cache = require( $this->settings['cache_file'] );
                    if( !is_array($this->cache) ) $this->cache = array();
                } catch(\Exception $e){
                    $this->cache = array();
                }
            }else{
                $this->cache = array();
            }
        }
        if( null === $name ) return $this->cache;
        return isset($this->cache[$name]) ? $this->cache[$name] : null;
    }
    
    private function setCache($name, $url)
    {
        $this->cache[$name] = $url;
        $this->cache_updated = true;
    }

    public function __destruct()
    {
        if( $this->settings['modes'] & Modes::CACHE && $this->cache_updated ){
            $this->cache_updated = false;
            file_put_contents($this->settings['cache_file'], 
                '<?php return '.var_export( $this->cache, true ) .';' );
        }
    }
}