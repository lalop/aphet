<?php


namespace Aphet;


class Modes 
{
    
    const CONCAT = 0b001;
    const MINIFY = 0b010;
    const CACHE = 0b100;
    
    const PROD = 0b111; // all
    const DEV = 0b000;
    
}
