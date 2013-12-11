<?php

namespace Aphet\Adapter;

use Aphet\AbstractRequestHandler;

class Slim extends AbstractRequestHandler
{
    
    /**
     * @var \Slim\Slim
     */
    private $app;
    
    public function __construct( \Slim\Slim $app )
    {
        $this->app = $app;
    }
    
    public function initRequestHandler( \Aphet\Manager $manager )
    {
        parent::initRequestHandler( $manager );
        $this->app->get("/{$this->manager->settings['web_path']}/:path", array($this, 'request'))
            ->name('assets')
            ->conditions(array('path' => '.+'));
    }
    
    protected function notfound()
    {
        $this->app->notFound();
    }
    
    public function urlFor( $path )
    {
        return $this->app->urlFor( 'assets', array(
            'path' => $path
        ));
    }
    
    protected function redirectTo( $path )
    {
        $this->app->redirect( $this->urlFor( $path ) );
    }
    
    protected function send( $content_type, $body )
    {
        $res = $this->app->response();

        $res['Content-type'] = $content_type;

        $res->body( $body );
    }
}
