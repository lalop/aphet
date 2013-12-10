<?php

$assets_manager;

function asset_init( \Assets\Manager $manager)
{
    global $assets_manager;
    $assets_manager = $manager;
}

function assets_url( $app_path, $name = null )
{
    global $assets_manager;
    return $assets_manager->computeAssetsUrl( $app_path, $name );
}

function assets_htmlHelper($app_path, $html, $name = null )
{
    if(!is_array($app_path)) $app_path = array($app_path);
    $assets_url = assets_url( $app_path, $name );
    
    return implode('',array_map(function($path) use($html){
        return sprintf($html,$path);
    },$assets_url));
    
}

function assets_link( $asset_url, $name = null )
{
    return assets_htmlHelper( $asset_url, "<link rel='stylesheet' href='%s' >\n", $name );
}
   
function assets_script($asset_url, $name = null )
{
    return assets_htmlHelper($asset_url, "<script src='%s'></script>\n", $name);
}   
