<?php

namespace Aphet\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends Command
{

    private $assetPath;
    /**
     * @var \Aphet\Manager
     */
    private $assetManager;

    public function __construct( \Aphet\Manager $asset_manager )
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
        $this->assetPath = $this->assetManager->settings['public_path'] .'/'. $this->assetManager->settings['web_path'];
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
            $end_path = substr($path, strrpos($path, '/'));
            if (!in_array($end_path, array('/','/.','/..')) && file_exists($path)){
                is_file($path) ?
                    unlink($path) :
                    array_map(__FUNCTION__, glob($path.'/{*,.*}',GLOB_BRACE)) == rmdir($path);
            }
        }

        if($this->assetManager->settings['cache_file']){
            rm($this->assetManager->settings['cache_file']);
        }
        rm($this->assetPath);
        $this->output->writeln("{$this->assetPath} cleaned");
    }

    /**
     * We search for call to aphet_... in php file and template
     * then we compile recursivly what's needed
     */
    private function compileAssets()
    {
        foreach ($this->assetManager->settings['extract'] as $path) {
            $this->searchPhpFiles( $path );
        }
    }

    private function searchPhpFiles( $path )
    {
        if (!in_array($path, array('/','../','..')) && file_exists($path)){
            if(is_dir(($path))){
                 //array_map(array($this,__FUNCTION__), glob($path.'/*'));
                foreach(glob($path.'/*') as $p) $this->searchPhpFiles($p);
            } elseif ( '.php' ===  strtolower(substr($path,strrpos($path,"."))) ) {
                $this->output->writeln("extract in {$path}");
                $this->extractAssetMethods($path);
            } else {
                $this->output->writeln("extract in {$path}");
                $this->twigExtractAssetMethods($path);
            }
        }
    }

    private function extractAssetMethods( $file )
    {
        $tokens = token_get_all(file_get_contents($file));
        $i = 0;

        while(isset($tokens[$i])){
            $token = $tokens[$i];

            if( $token[0] === T_STRING && strpos($token[1],'aphet_') === 0 ){
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

                if(count($args)){
                    $stringify = json_encode($args);
                    $this->output->writeln("<info>compile: {$stringify} </info>");
                    $urls = call_user_func_array(array(
                            $this->assetManager,
                            'computeAssetsUrl'
                        ), $args);
                    $stringify = json_encode($urls);
                    $this->output->writeln("  ===> {$stringify}");
                }
            }
            $i++;
        }
    }

    private function twigExtractAssetMethods( $file )
    {
        preg_replace_callback('/aphet_[url|script|link]*\((.*)\)/', function( $match ) {
            $match = str_replace("'",'"', $match[1]);
            $name = null;
            if(substr($match, 0,1) === '[' && substr($match, -1) !== ']'){
                $split = explode(']', $match);
                $match = $split[0] . ']';
                $split = explode(',', $split[1]);
                $name = json_decode(str_replace("'",'"',$split[1]));
            }
            $urls = json_decode($match);
            $this->assetManager->computeAssetsUrl($urls,$name);
        },  file_get_contents($file ) );
    }

    private function cleanString( $str ){
        return substr($str, 1, strlen($str)-2);
    }

}
