<?php

namespace Aphet;

use Assetic\Filter\ScssphpFilter;

class ScssFilterFix implements ScssphpFilter
{

    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $this->resetScssCompiler();
        if( null !== $loadPath ) $this->scssCompiler->addImportPath( $loadPath );

        $this->compile( $content );

        $children = array();

        $files = $this->scssCompiler->getParsedFiles();
        foreach($files as $file){
            $coll = $factory->createAsset( $file, array(), array('root' => $loadPath) );
            foreach($coll as $leaf) {
                $leaf->ensureFilter($this);
                $children[] = $leaf;
            }
        }
        return $children;
    }
    
}
