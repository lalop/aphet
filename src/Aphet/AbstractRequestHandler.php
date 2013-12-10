<?php

namespace Aphet;

use Assetic\Asset\AssetCollection;
use Assetic\AssetWriter;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\JSMinFilter;

abstract class AbstractRequestHandler
{
    
    protected $mime_types = array(
        '.txt' => 'text/plain',
        '.htm' => 'text/html',
        '.html' => 'text/html',
        '.php' => 'text/html',
        '.css' => 'text/css',
        '.js' => 'application/javascript',
        '.json' => 'application/json',
        '.xml' => 'application/xml',
        '.swf' => 'application/x-shockwave-flash',
        '.flv' => 'video/x-flv',
        // images
        '.png' => 'image/png',
        '.jpe' => 'image/jpeg',
        '.jpeg' => 'image/jpeg',
        '.jpg' => 'image/jpeg',
        '.gif' => 'image/gif',
        '.bmp' => 'image/bmp',
        '.ico' => 'image/vnd.microsoft.icon',
        '.tiff' => 'image/tiff',
        '.tif' => 'image/tiff',
        '.svg' => 'image/svg+xml',
        '.svgz' => 'image/svg+xml',
        //font
        '.woff' => 'application/font-woff',//'application/x-font-woff',
        '.eot' => 'application/vnd.ms-fontobject',
        '.ttf' => 'application/octet-stream',
        '.otf' => 'application/octet-stream'
    );
    
    /**
     * @var \Assets\Manager
     */
    protected $manager;
    
    public function initRequestHandler( \Assets\Manager $manager )
    {
        $this->manager = $manager;
    }   
    
    public function request($path)
    {
        $ext = strtolower( substr( $path, strrpos( $path, '.' )));
        if( !isset( $this->mime_types[$ext] ) ){
            return $this->notFound();
        }
        
        try{
            $asset = $this->manager->loader()->fromWebPath( $path );
        } catch( Exceptions\FileNotFoundException $e ) {
            // file not found => 404
            return $this->notfound();
        }
        
        if( $path !== $asset->getTargetPath() ){
            // file found but bad url => redirect
            return $this->redirectTo( $asset->getTargetPath() );
        }
        
        $this->send( $this->mime_types[$ext], $asset->dump() );
        
    }

    abstract protected function notFound();
    abstract public function urlFor( $path );
    abstract protected function redirectTo( $path );
    abstract protected function send( $content_type, $body );
}

