<?php 

namespace Aphet;

class Loader 
{
    
    private $paths = array();
    /**
     * @var Manager
     */
    private $manager;

    
    public function __construct( array $paths, Manager $manager )
    {
        $this->paths = $paths;
        $this->manager = $manager;
    }
    
    /**
     * @return File
     */
    public function fromAppPath( $path )
    {
        foreach( $this->paths as $asset_prefix => $asset_path ){
            $file_path = $path;
            if( !is_numeric($asset_prefix) ){ // prefix
                if( 0 !== strpos( $path, $asset_prefix ) ) continue; // prefix not found in path
                $file_path = substr($path, strlen( $asset_prefix ));
            }
            if( file_exists( $asset_path . '/' . $file_path) ){
                return new File( $asset_path . '/' .  $file_path );
            }
        }
        throw new Exceptions\FileNotFoundException( "File {$path} not found" );
    }
    
    /**
     * @return Asset
     */
    public function fromWebPath( $path )
    {   
        $split = explode( '/', $path );
        $file_name = preg_replace( '/^[0-9]*-(.*)/', '$1', array_pop( $split ));
        $split[] = $file_name;
        
        $asset = $this->manager->computeAsset( implode( '/', $split ) );
        
        if( $asset ) return $asset;
        
        throw new Exceptions\FileNotFoundException( "File {$path} not found" );
        
    }
}
