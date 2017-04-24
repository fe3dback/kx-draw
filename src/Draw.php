<?php namespace KX\Template;

use LightnCandy\LightnCandy;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Draw
{
    const _RENDERER_EXT = 'renderer';

    const __HTML_ATTR_KX_TEMPLATE_ID = '_kx_draw_template_name';
    const __HTML_ATTR_KX_UNIQUE_ID = '_kx_draw_unique_id';

    /** @var Engine */
    private $engine;

    /** @var array - After first disk read, we keep string in cache */
    private $templatesCache = [];

    /** @var array - After compile renders, we keep it in memory (very fast loops) */
    private $rendersCache = [];

    /** @var array - Partial templates (name => raw_str) */
    private $partials = [];

    /** @var RecursiveIteratorIterator */
    private $templatesIterator = null;

    function __construct(Engine $engine)
    {
        $this->engine = $engine;

        $this->loadTemplates();
    }

    /**
     * Render template by templateName
     * and store data.
     *
     * In js you can getData/reRender it by uniqueId
     *
     * example:
     * render('catalog/card', 100, [id=>100, title=>"Shoes", ..]
     *
     * Good idea to use EntityId as uniqueId
     *
     * @param $templateName
     * @param $uniqueId
     * @param $data
     * @return string
     */
    public function render(string $templateName, string $uniqueId, array $data = [])
    {
        if (is_null($uniqueId))
        {
            Engine::halt("You should provide some unique id for template '%s'", [$templateName]);
        }

        if ($this->engine->isUseBenchmark())
        {
            $bench = new \Ubench();

            // actual run
            $result = $bench->run(function() use ($templateName, $uniqueId, $data)
            {
                return $this->realRender($templateName, $uniqueId, $data);
            });

            $time = $bench->getTime(true);
            $this->engine->getStorage()->addToDrawTime($templateName, $time);
        }
        else
        {
            // actual run
            $result = $this->realRender($templateName, $uniqueId, $data);
        }

        return $result;
    }

    /**
     * Get total draw time of all templates
     * if benchmark is not used, return false
     *
     * @return array|bool
     */
    public function getDrawTime()
    {
        if (!$this->engine->isUseBenchmark()) {
            return false;
        }

        return $this->engine->getStorage()->getDrawTime();
    }

    /**
     * Add some template to partials
     * @param $name
     */
    public function addPartial($name)
    {
        if (!$name) {
            Engine::halt("You should provide valid partial name.");
        }

        $template = $this->getTemplate($name);
        $this->partials[$name] = $template;
    }

    /**
     * Add all templates in $relativeDirectory
     * and all sub directories to partial
     *
     * @param $relativeDirectory - directory relative to templates dir. (without slashes)
     */
    public function addPartialsDirectory($relativeDirectory)
    {
        if (!$relativeDirectory) {
            Engine::halt("You should provide valid partial folder name.");
        }

        $templatesPath = $this->engine->getTemplatesDirectory() . DIRECTORY_SEPARATOR;
        $templatesExt = $this->engine->getExt();
        $fullPath = $templatesPath . $relativeDirectory;

        if (!is_dir($fullPath)) {
            Engine::halt("Can't add directory '%s' to partial. Directory not exist!", [$fullPath]);
        }

        $iterator = new RecursiveIteratorIterator
        (
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        foreach ($iterator as $path => $dir)
        {
            if ($dir->isFile())
            {
                $name = str_replace($templatesPath, '', $path);
                $name = str_replace('.' . $templatesExt, '', $name);

                $this->addPartial($name);
            }
        }
    }

    /**
     * Return string template by name
     *
     * @param $name
     * @return string
     */
    public function getTemplate(string $name)
    {
        if (!in_array($name, array_keys($this->templatesCache)))
        {
            $realPath = $this->engine->getTemplatesDirectory()
                . DIRECTORY_SEPARATOR . $name
                . '.' . $this->engine->getExt();

            if (!is_file($realPath)) {
                Engine::halt("Template '%s' not found!", [$realPath]);
            }

            $this->templatesCache[$name] = file_get_contents($realPath);
        }

        return $this->templatesCache[$name];
    }

    // ==================================================================================

    /**
     * Load all templates to mem cache
     * only first time when draw created.
     *
     * Also we check modified date, and clear
     * all old templates cache
     */
    private function loadTemplates()
    {
        $templatesPath = $this->engine->getTemplatesDirectory() . DIRECTORY_SEPARATOR;

        if (is_null($this->templatesIterator)) {
            $this->templatesIterator = new RecursiveIteratorIterator
            (
                new RecursiveDirectoryIterator($templatesPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
            );
        }

        $this->__Load_disValidateOldCache();
    }

    private function __Load_disValidateOldCache()
    {
        // load map
        $mapFile = [];
        $mapPath = $this->engine->getCacheDirectory() . DIRECTORY_SEPARATOR
            . $this->engine->getMapFileName();

        if (is_file($mapPath)) {
            $mapFile = unserialize(file_get_contents($mapPath));
        }

        // update map && clear old renderer cache
        $templatesExt = $this->engine->getExt();
        $templatesDir = $this->engine->getTemplatesDirectory();

        foreach ($this->templatesIterator as $path => $h)
        {
            /** @var $h \SplFileInfo */
            if ($h->isFile())
            {
                $name = str_replace($templatesDir, '', $path);
                $name = str_replace('.' . $templatesExt, '', $name);
                $name = trim($name, '/');

                $modified_cache = 0;
                $modified_real = $h->getMTime();

                if (in_array($name, array_keys($mapFile))) {
                    $modified_cache = $mapFile[$name];
                }

                if ($this->engine->isUseCache())
                {
                    $pathToRendererCache = $this->engine->getCacheDirectory()
                        . DIRECTORY_SEPARATOR . $name
                        . '.' . static::_RENDERER_EXT;

                    if ($modified_real != $modified_cache)
                    {
                        // remove old cache
                        unlink($pathToRendererCache);
                    }
                    else
                    {
                        // load template to mem cache
                        $this->templatesCache[$name] = file_get_contents($path);

                        // load renderer to mem cache
                        if ($this->engine->isUseMemCache())
                        {
                            /** @noinspection PhpIncludeInspection */
                            $renderer = include $pathToRendererCache;

                            if ($renderer && is_callable($renderer))
                            {
                                $this->rendersCache[$name] = $renderer;
                            }
                        }
                    }
                }

                $mapFile[$name] = $modified_real;
            }
        }

        // save map
        file_put_contents($mapPath, serialize($mapFile));
    }

    // ==================================================================================

    /**
     * Get renderer function
     *
     * @param string $name
     * @param string $uniqueId
     * @return callable
     */
    private function getRenderer(string $name, string $uniqueId)
    {
        $pathToFileCache = $this->engine->getCacheDirectory()
            . DIRECTORY_SEPARATOR . $name
            . '.' . static::_RENDERER_EXT;


        /**
         * Get renderer from memory T1 Cache
         *
         * @return bool|callable
         */
        $loadFromMemory = function() use ($name)
        {
            if (in_array($name, array_keys($this->rendersCache)) && is_callable($this->rendersCache[$name])) {
                return $this->rendersCache[$name];
            }

            return false;
        };

        /**
         * Get renderer from file T2 cache
         *
         * @return bool|callable
         */
        $loadFromCache = function() use ($name, $pathToFileCache) {

            $cached = false;

            if ($this->engine->isUseCache())
            {
                if (is_file($pathToFileCache))
                {
                    /** @noinspection PhpIncludeInspection */
                    $cached = include $pathToFileCache;
                    if (!is_callable($cached))
                    {
                        $cached = false;
                    }
                }
            }

            return $cached;
        };

        /**
         * Compile new renderer and save to file cache (if allowed)
         * @return callable
         */
        $compile = function () use ($name, $pathToFileCache, $uniqueId) {

            $compileSettings = [
                'flags' => LightnCandy::FLAG_HANDLEBARSJS,
                'partials' => $this->partials
            ];

            $raw = $this->getTemplate($name);
            $template = $this->wrapTemplate($raw, $name);

            $render = LightnCandy::compile($template, $compileSettings);

            // save renderer to file T2 cache
            if ($this->engine->isUseCache())
            {
                file_put_contents($pathToFileCache, '<?php ' . $render . '?>');
            }

            $renderer = eval($render);
            return $renderer;
        };

        // ---------------------------------------------------------

        // fast cache

        $t1 = $loadFromMemory();
        if ($t1) {
            return $t1;
        }

        // Rebuild cache

        $renderer = $loadFromCache();
        if (!$renderer)
        {
            $renderer = $compile();
        }

        if (!is_callable($renderer))
        {
            Engine::halt("
                Invalid template file. Check your syntax at '%s',
                if you use partials, check that they added to partials list
            ", [$name]);
        }

        // save to t1 cache
        if ($this->engine->isUseMemCache()) {
            $this->rendersCache[$name] = $renderer;
        }

        return $renderer;
    }

    /**
     * Add template and id to first root element
     * if raw string
     *
     * @param string $raw
     * @param string $name
     * @return string
     */
    private function wrapTemplate(string $raw, string $name)
    {
        $html = new \DOMDocument();
        $html->encoding = 'utf-8';
        $html->loadHTML('<kxparent>'.$raw.'</kxparent>');

        $el = $html->getElementsByTagName('kxparent')->item(0);
        if (!$el->hasChildNodes()) {
            Engine::halt("Is your template '%s' empty? Add some html tag to it.", [$name]);
        }

        $nodes = [];
        foreach ($el->childNodes as $node) {
            $nodes[] = $node;
        }

        if (count($nodes) >= 2)
        {
            Engine::halt("Template '%s' should contain only one parent (like in react). 
            Wrap your elements by some html tag (div, span, etc..)", [$name]);
        }

        /** @var $firstNode \DOMElement */
        $firstNode = reset($nodes);

        $firstNode->setAttribute('data-kx-draw-name', vsprintf("{{%s}}", [self::__HTML_ATTR_KX_TEMPLATE_ID]));
        $firstNode->setAttribute('data-kx-draw-id', vsprintf("{{%s}}", [self::__HTML_ATTR_KX_UNIQUE_ID]));

        $encodedHtmlString = (string)$firstNode->ownerDocument->saveHTML($firstNode);

        return htmlspecialchars_decode($encodedHtmlString);
    }

    /**
     * Render template and return html
     *
     * @param string $templateName
     * @param string $uniqueId
     * @param array $data
     * @return string
     */
    private function realRender(string $templateName, string $uniqueId, array $data = [])
    {
        // append system vars to template
        $data[self::__HTML_ATTR_KX_TEMPLATE_ID] = $templateName;
        $data[self::__HTML_ATTR_KX_UNIQUE_ID] = $uniqueId;

        // save to storage
        $this->engine->getStorage()->save($templateName, $uniqueId, $data);

        // render
        $renderer = $this->getRenderer($templateName, $uniqueId);
        $raw = $renderer($data);

        return $raw;
    }

    // ==================================================================================

    /**
     * Not recommended to use this
     *
     * @internal
     * @return Engine
     */
    public function __getEngine(): Engine
    {
        return $this->engine;
    }
}