<?php namespace KX\Template\Builders;

use KX\Template\Engine;

class EngineFactory
{
    private $ext = "hbs";
    private $use_cache = false;
    private $use_mem_cache = false;
    private $cache_dir_real = '';
    private $templates_dir_real = '';
    private $map_file_name = 'map.json';
    private $use_benchmark = false;

    /**
     * Set templates extension
     *
     * @param string $ext
     * @return $this
     */
    public function setExt($ext)
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * KX Draw can use file cache?
     *
     * @param bool $use_cache
     * @return $this
     */
    public function setUseCache($use_cache)
    {
        $this->use_cache = $use_cache;
        return $this;
    }

    /**
     * KX Draw can use mem cache?
     * this will be ignored, if normal cache
     * disabled
     *
     * @param bool $use_mem_cache
     * @return $this
     */
    public function setUseMemCache($use_mem_cache)
    {
        $this->use_mem_cache = $use_mem_cache;
        return $this;
    }

    /**
     * Set cache directory
     * This should be real server absolutely path
     * to r/w access directory
     *
     * @param string $cache_dir_real
     * @return $this
     */
    public function setCacheDirReal($cache_dir_real)
    {
        $this->cache_dir_real = $cache_dir_real;
        return $this;
    }

    /**
     * Set templates directory
     * This should be real server absolutely path
     * to r/w access directory
     *
     * @param string $templates_dir_real
     * @return $this
     */
    public function setTemplatesDirReal(string $templates_dir_real)
    {
        $this->templates_dir_real = $templates_dir_real;
        return $this;
    }

    /**
     * Set map file name (actual not needed)
     * you can use default.
     * @param string $map_file_name
     * @return $this
     */
    public function setMapFileName($map_file_name)
    {
        $this->map_file_name = $map_file_name;
        return $this;
    }

    /**
     * @param bool $use_benchmark
     * @return $this
     */
    public function setUseBenchmark(bool $use_benchmark)
    {
        $this->use_benchmark = $use_benchmark;
        return $this;
    }

    /**
     * Build draw Engine
     *
     * @return Engine
     */
    public function build(): Engine
    {
        return new Engine(
            $this->ext,
            $this->use_cache,
            $this->use_mem_cache,
            $this->cache_dir_real,
            $this->templates_dir_real,
            $this->map_file_name,
            $this->use_benchmark
        );
    }
}