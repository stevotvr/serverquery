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

namespace SQ;

require __DIR__ . '/Gameserver.class.php';
require __DIR__ . '/Cache.class.php';

/**
 * Main application class
 *
 * @author Steve Guidetti
 */
class ServerQuery {

    /**
     * The cache
     *
     * @var \SQ\Cache
     */
    private $cache = null;

    /**
     * Cron mode is active
     *
     * @var bool
     */
    private $cronMode = false;

    /**
     * Array of prepared Gameserver objects
     *
     * @var \SQ\Gameserver[]
     */
    private $servers = array();

    /**
     * Constructor
     */
    public function __construct() {
        if(Config::CACHE_ENABLE) {
            $this->cache = new Cache();
            $this->cronMode = Config::CRON_MODE;
        }
    }

    /**
     * Get the list of Gameserver objects
     *
     * @return \SQ\Gameserver[]
     */
    public function getServers() {
        return $this->servers;
    }

    /**
     * Execute main application logic
     */
    public function exec() {
        $update = !$this->cronMode;
        foreach(Config::$servers as $server) {
            $this->servers[] = $this->getServerObject($server, $update);
        }
    }

    /**
     * Execute cron tasks
     *
     * @param int $timeLimit Maximum execution time in seconds
     */
    public function cron($timeLimit = 60) {
        if(Config::CACHE_ENABLE) {
            shuffle(Config::$servers);

            $startTime = time();
            foreach(Config::$servers as $server) {
                if(time() - $startTime >= $timeLimit) {
                    return;
                }

                $this->getServerObject($server);
            }
        }
    }

    /**
     * Get a Gameserver object based on a server config
     *
     * @param mixed[] $server Element from the servers config
     * @param bool $update Query server for updated status
     * @return \SQ\Gameserver
     */
    private function getServerObject(array $server, $update = true) {
        $gs = self::initServerObject($server);
        if(Config::CACHE_ENABLE && $this->cache->get($gs)) {
            if(time() - $gs->getQueryTime() < Config::CACHE_TIME) {
                return $gs;
            }
        }

        if($update) {
            $gs->update();

            if(Config::CACHE_ENABLE) {
                $this->cache->put($gs);
            }
        }

        return $gs;
    }

    /**
     * Initialize a Gameserver object based on a server config
     *
     * @param mixed[] $server Element from the servers config
     * @return \SQ\Gameserver
     */
    private static function initServerObject(array $server) {
        $className = '\\SQ\\Game\\' . Config::$games[$server['game']]['class'];
        if(!class_exists($className)) {
            $fileName = __DIR__ . '/games/';
            $fileName .= substr($className, strrpos($className, '\\') + 1);
            $fileName .= '.class.php';
            require $fileName;
        }

        return new $className($server['game'], $server['addr'], self::getServerConfig($server));
    }

    /**
     * Get the combined server config array
     *
     * @param mixed[] $server Element from the servers config
     * @return mixed[]
     */
    private static function getServerConfig(array $server) {
        $config = array_key_exists('config', $server) ? $server['config'] : array();

        if(array_key_exists('config', Config::$games[$server['game']])) {
            $config = array_merge(Config::$games[$server['game']]['config'], $config);
        }

        return $config;
    }

}
