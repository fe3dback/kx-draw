<?php namespace KX\Template;

class Engine
{
    // see EngineFactory for default values:
    private $ext;
    private $use_cache;
    private $use_mem_cache;
    private $use_benchmark;
    private $cache_dir_real;
    private $templates_dir_real;
    private $map_file_name;

    /** @var Storage */
    private $storage;

    function __construct(
        $ext, $use_cache, $use_mem_cache, $cache_dir_real, $templates_dir_real, $map_file_name,
        $use_benchmark
    )
    {
        $this->ext = $ext;
        $this->use_cache = $use_cache;
        $this->use_mem_cache = $use_mem_cache;
        $this->cache_dir_real = $cache_dir_real;
        $this->templates_dir_real = $templates_dir_real;
        $this->map_file_name = $map_file_name;
        $this->use_benchmark = $use_benchmark;

        $this->storage = new Storage();
        $this->checkDependencies();
    }

    private function checkDependencies()
    {
        if (!$this->ext) {
            static::halt("You should set template extension wia EngineFactory");
        }

        if (!$this->use_cache && $this->use_mem_cache) {
            static::halt("You cannot use mem cache without file cache. Please turn on both");
        }

        // test dirs
        $directories = [
            'cache' => $this->cache_dir_real,
            'templates' => $this->templates_dir_real
        ];

        foreach ($directories as $role => $path)
        {
            if (!is_dir($path)) {
                static::halt("Directory for '%s' '%s' not exist", [$role, $path]);
            }

            if (!is_writable($path)) {
                static::halt("Directory for '%s' '%s' not writable", [$role, $path]);
            }
        }

        if (!$this->map_file_name) {
            static::halt("You should provide 'map_file_name'. You can just left default.");
        }
    }

    /**
     * @param $msg
     * @param array $args
     * @throws \Exception
     */
    public static function halt($msg, $args = [])
    {
        throw new \Exception(vsprintf("(KX Draw) {$msg}", $args));
    }

    /**
     * @return string
     */
    public function getTemplatesDirectory(): string
    {
        return $this->templates_dir_real;
    }

    /**
     * @return string
     */
    public function getExt(): string
    {
        return $this->ext;
    }

    /**
     * @return string
     */
    public function getCacheDirectory(): string
    {
        return $this->cache_dir_real;
    }

    /**
     * @return bool
     */
    public function isUseCache(): bool
    {
        return $this->use_cache;
    }

    /**
     * @return bool
     */
    public function isUseMemCache(): bool
    {
        return $this->use_mem_cache;
    }

    /**
     * @return bool
     */
    public function isUseBenchmark(): bool
    {
        return $this->use_benchmark;
    }

    /**
     * @return Storage
     */
    public function getStorage(): Storage
    {
        return $this->storage;
    }
}