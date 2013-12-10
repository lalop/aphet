<?php

namespace Assets\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends Command
{
    
    private $assetPath;
    /**
     * @var \Assets\Manager
     */
    private $assetManager;
    
    public function __construct( \Assets\Manager $asset_manager )
    {
        $this->assetManager = $asset_manager;
        parent::__construct();
    }

    
    protected function configure()
    {
        $this->setName('asset:dump')
            ->setDescription('Dump asset in public directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assetPath = ROOTPATH .'/public/assets'; 
        $this->output = $output;
        $this->removeAssets();
        $this->compileAssets();
    }
    
    /**
     * @FIX file starting with dot no removed
     */
    private function removeAssets()
    {
        function rm($path){
            if (!in_array($path, array('/','../','..')) && file_exists($path)){
                is_file($path) ?
                    unlink($path) :
                    array_map(__FUNCTION__, glob($path.'/*')) == rmdir($path);
            }
        };
        rm($this->assetPath);
    }
    
    /**
     * We search for call to asset_... in php file and template
     * then we compile recursivly what's needed
     */
    private function compileAssets()
    {   
        foreach ($this->assetManager->settings['php_extract'] as $path) {
            $this->searchPhpFiles( $path );
        }
    }

    private function searchPhpFiles( $path )
    {
        if (!in_array($path, array('/','../','..')) && file_exists($path)){
            if(is_dir(($path))) array_map(array($this,__FUNCTION__), glob($path.'/*'));
            elseif ( '.php' ===  strtolower(substr($path,strrpos($path,"."))) ) {
                $this->output->writeln("extract in {$path}");
                $this->extractAssetMethods($path);
            }
        }
    }
    private function extractAssetMethods( $file )
    {
        $tokens = token_get_all(file_get_contents($file));
        $i = 0;
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => \Config::get('app_path')
        ));
        
        while(isset($tokens[$i])){
            $token = $tokens[$i];

            if( $token[0] === T_STRING && strpos($token[1],'assets_') === 0 ){
                $function = $token[1];
                $args = array();
                $i++; // on passe la parenthèse ouvrante
                do{
                    $i++;
                    $token = $tokens[$i];
                    if($token === ')') break;
                    elseif ($token[0]===T_ARRAY) {
                        $i++; // on passe la parenthèse ouvrante
                        $list = array();
                        do{
                            $i++;
                            $token = $tokens[$i];
                            if($token === ')') break;
                            elseif ($token[0]===T_CONSTANT_ENCAPSED_STRING) {
                                $list[] = $this->cleanString($token[1]);
                            }
                        }while(isset($tokens[$i]));
                        $args[] = $list;
                    } elseif ($token[0]===T_CONSTANT_ENCAPSED_STRING) {
                        $args[] = $this->cleanString($token[1]);
                    }
                }while(isset($tokens[$i]));
                
                $stringify = json_encode($args);
                $this->output->writeln("<info>compile: {$stringify} </info>");
                $urls = call_user_func_array(array(
                        $this->assetManager, 
                        'computeAssetsUrl'
                    ), $args);
                $stringify = json_encode($urls);
                $this->output->writeln("  ===> {$stringify}");
            }
            $i++;
        }
    }

    private function cleanString( $str ){
        return substr($str, 1, strlen($str)-2);
    }
}