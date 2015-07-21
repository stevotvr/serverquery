<?php

/* 
 * The MIT License
 *
 * Copyright 2015 Steve Guidetti.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

include 'config.php';
include 'testconfig.php';
include 'lib/Gameserver.class.php';

header('Content-Type: text/plain; charset=UTF-8');

foreach(SQConfig::$servers as $server) {
    $className = SQConfig::$games[$server['game']]['class'];
    if(!class_exists($className)) {
        $fileName = 'games/';
        $fileName .= substr($className, strrpos($className, '_') + 1);
        $fileName .= '.class.php';
        require $fileName;
    }
    
    $config = array_key_exists('config', $server) ? $server['config'] : array();
    if(array_key_exists('config', SQConfig::$games[$server['game']])) {
        $config = array_merge(SQConfig::$games[$server['game']]['config'], $config);
    }
    
    $o = new $className($server['addr'], $config);
    try {
        $o->query();
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    }
    
    var_dump($o);
}