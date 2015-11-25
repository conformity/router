<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 13:16
 */

namespace Conformity\Router;

class CachedRouter extends Router implements RouterInterface
{
    protected $file;
    
    public function __construct($file){
        $this->file = $file;
        $cache = (is_file($file)) ? require $file : [
            'routes' => [],
            'segments_map' => [],
            'matchers' => [],
            'modifyers' => []
        ];
        $this->fromArray($cache);
    }

    public function saveCacheFile(){
        file_put_contents($this->file, "<?php return " . var_export($this->toArray(), true) . ";");
    }

}