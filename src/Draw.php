<?php namespace KX\Template;

use DOMDocument;
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
    private $templatesIterator;

    public function __construct(Engine $engine)
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
     * @param $uniqueId - if (=== false), this template will be static, and not exported to js
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function render(string $templateName, $uniqueId, array $data = []): string
    {
        if ($uniqueId !== false && strlen(trim($uniqueId)) <= 0)
        {
            Engine::halt('You should provide some unique id for template \'%s\'', [$templateName]);
        }

        if ($this->engine->isUseBenchmark())
        {
            $timeStart = microtime(true);

            // actual run
            $result = $this->realRender($templateName, $uniqueId, $data);

            $timeEnd = microtime(true);
            $time = $timeEnd - $timeStart;

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
     * @throws \Exception
     */
    public function addPartial($name)
    {
        if (!$name) {
            Engine::halt('You should provide valid partial name.');
        }

        $template = $this->getTemplate($name);
        $this->partials[$name] = $template;
    }

    /**
     * Add all templates in $relativeDirectory
     * and all sub directories to partial
     *
     * @param $relativeDirectory - directory relative to templates dir. (without slashes)
     * @throws \Exception
     */
    public function addPartialsDirectory($relativeDirectory)
    {
        if (!$relativeDirectory) {
            Engine::halt('You should provide valid partial folder name.');
        }

        $templatesPath = $this->engine->getTemplatesDirectory() . DIRECTORY_SEPARATOR;
        $templatesExt = $this->engine->getExt();
        $fullPath = $templatesPath . $relativeDirectory;

        if (!is_dir($fullPath)) {
            Engine::halt('Can\'t add directory \'%s\' to partial. Directory not exist!', [$fullPath]);
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
                $name = str_replace([$templatesPath, '.' . $templatesExt], '', $path);
                $this->addPartial($name);
            }
        }
    }

    /**
     * Return string template by name
     *
     * @param $name
     * @return string
     * @throws \Exception
     */
    public function getTemplate(string $name): string
    {
        if (!array_key_exists($name, $this->templatesCache))
        {
            $realPath = $this->engine->getTemplatesDirectory()
                . DIRECTORY_SEPARATOR . $name
                . '.' . $this->engine->getExt();

            if (!is_file($realPath)) {
                Engine::halt('Template \'%s\' not found!', [$realPath]);
            }

            $this->templatesCache[$name] = file_get_contents($realPath);
        }

        return $this->templatesCache[$name];
    }

    /**
     * Export all necessary data
     * to JS side
     *
     * This return raw html string
     * for inject into site footer (before body tag close)
     *
     * @return string - html string
     * @throws \Exception
     */
    public function exportToJS(): string
    {
        $templateIds = $this->engine->getStorage()->getUsedTemplates();
        $templates = [];
        foreach ($templateIds as $templateId)
        {
            $templates[$templateId] = $this->getTemplate($templateId);
        }

        $JS_DATA = json_encode([
            'templates' =>  $templates,
            'data' => $this->engine->getStorage()->getTemplateData(),
            'partials' => $this->partials
        ]);

        $_js = <<<HTML
<script type="text/javascript">
if ((typeof KXDrawRender === "function")) {
    window.KXDraw = new KXDrawRender({$JS_DATA});
} else {
    console.info(
        "%cCant use KXDraw (reactive render) in JS side (lib not found). Forgot include? Lib should be in /vendor/fe3dback/kx-draw/draw.js",
        "color:yellow;background-color:crimson;padding:5px;line-height:160%"
    );
}
</script>
HTML;

        return $_js;
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

        if (null === $this->templatesIterator) {
            $this->templatesIterator = new RecursiveIteratorIterator
            (
                new RecursiveDirectoryIterator($templatesPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
            );
        }

        $this->Load_disValidateOldCache();
    }

    /**
     * Check templates edit date, and drop
     * old cache
     */
    private function Load_disValidateOldCache()
    {
        // load map
        $mapFile = new \stdClass();
        $mapPath = $this->engine->getCacheDirectory() . DIRECTORY_SEPARATOR
            . $this->engine->getMapFileName();

        if (is_file($mapPath)) {
            $mapFile = json_decode(file_get_contents($mapPath));
        }

        // update map && clear old renderer cache
        $templatesExt = $this->engine->getExt();
        $templatesDir = $this->engine->getTemplatesDirectory();

        foreach ($this->templatesIterator as $path => $h)
        {
            /** @var $h \SplFileInfo */
            if ($h->isFile())
            {
                $name = str_replace([$templatesDir, '.' . $templatesExt], '', $path);
                $name = trim($name, '/');

                $modified_cache = 0;
                $modified_real = $h->getMTime();

                if (array_key_exists($name, (array)$mapFile)) {
                    $modified_cache = $mapFile->$name;
                }

                if ($this->engine->isUseCache())
                {
                    $pathToRendererCache = $this->engine->getCacheDirectory()
                        . DIRECTORY_SEPARATOR . $name
                        . '.' . static::_RENDERER_EXT;

                    if ($modified_real !== $modified_cache)
                    {
                        // remove old cache
                        if (is_file($pathToRendererCache)) {
                            unlink($pathToRendererCache);
                        }
                    }
                    else
                    {
                        // load template to mem cache
                        $this->templatesCache[$name] = file_get_contents($path);

                        // load renderer to mem cache
                        if ($this->engine->isUseMemCache() && is_file($pathToRendererCache))
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

                $mapFile->$name = $modified_real;
            }
        }

        // save map
        file_put_contents($mapPath, json_encode($mapFile));
    }

    // ==================================================================================

    /**
     * Get renderer function
     *
     * @param string $name
     * @return callable
     * @throws \Exception
     */
    private function getRenderer(string $name): callable
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
            if (array_key_exists($name, $this->rendersCache) && is_callable($this->rendersCache[$name])) {
                return $this->rendersCache[$name];
            }

            return false;
        };

        /**
         * Get renderer from file T2 cache
         *
         * @return bool|callable
         */
        $loadFromCache = function() use ($pathToFileCache) {

            $cached = false;
            if ($this->engine->isUseCache() && is_file($pathToFileCache))
            {
                /** @noinspection PhpIncludeInspection */
                $cached = include $pathToFileCache;
                if (!is_callable($cached))
                {

                    $cached = false;
                }
            }

            return $cached;
        };

        /**
         * Compile new renderer and save to file cache (if allowed)
         * @return callable
         * @throws \Exception
         */
        $compile = function () use ($name, $pathToFileCache) {

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
                $basePath = dirname($pathToFileCache);
                if (!@mkdir($basePath, 0777, true) && !is_dir($basePath)) {
                    throw new \Exception('cant create cache directory \'%s\'');
                }

                file_put_contents($pathToFileCache, '<?php ' . $render . '?>');
            }

            return eval($render);
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
            Engine::halt('
                Invalid template file. Check your syntax at \'%s\',
                if you use partials, check that they added to partials list
            ', [$name]);
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
     * @throws \Exception
     */
    private function wrapTemplate(string $raw, string $name): string
    {
        $nodeName = 'div';

        /**
         * Convert raw template text
         * to DOMDocument
         *
         * @param $raw
         * @return DOMDocument
         */
        $pipeLine_To = function($raw) use ($nodeName) {

            $html = new DOMDocument();
            $html->encoding = 'UTF-8';

            $encodedTemplate = mb_convert_encoding(vsprintf('<%s>%s</%s>', [
                $nodeName, $raw, $nodeName
            ]), 'HTML-ENTITIES', 'UTF-8');

            $html->loadHTML($encodedTemplate);

            return $html;
        };

        /**
         * Convert back special chars
         * from DOMDocument to special HTML template string
         *
         * @param $parsedHTMLString
         * @return mixed
         */
        $pipeLine_Back = function ($parsedHTMLString) {

            // decode all special chars
            $parsedHTMLString = htmlspecialchars_decode($parsedHTMLString);

            // replace variable tokens back
            return preg_replace('/%7B%7B(\S+)%7D%7D/u', '{{$1}}', $parsedHTMLString);
        };

        // =======================================================================

        $html = $pipeLine_To($raw);

        // check tag
        // -----------
        $el = $html->getElementsByTagName($nodeName)->item(0);
        if (!$el->hasChildNodes()) {
            Engine::halt('Is your template \'%s\' empty? Add some html tag to it.', [$name]);
        }

        $nodes = [];
        foreach ($el->childNodes as $node) {
            $nodes[] = $node;
        }

        if (1 !== count($nodes))
        {
            Engine::halt('Template \'%s\' should contain only one parent (like in react). 
            Wrap your elements by some html tag (div, span, etc..)', [$name]);
        }

        /** @var $firstNode \DOMElement */
        $firstNode = reset($nodes);
        if (!($firstNode instanceof \DOMElement))
        {
            Engine::halt('Template \'%s\' is invalid. Check your syntax. 
            Maybe you template without parent wrapper?', [$name]);
        }

        // add system attributes
        // -----------------------
        $firstNode->setAttribute('data-kx-draw-name', vsprintf('{{%s}}', [self::__HTML_ATTR_KX_TEMPLATE_ID]));
        $firstNode->setAttribute('data-kx-draw-id', vsprintf('{{%s}}', [self::__HTML_ATTR_KX_UNIQUE_ID]));

        // output
        // -----------------------
        $encodedHtmlString = (string)$firstNode->ownerDocument->saveHTML($firstNode);
        return $pipeLine_Back($encodedHtmlString);
    }

    /**
     * Render template and return html
     *
     * @param string $templateName
     * @param string $uniqueId
     * @param array $data
     * @return string
     * @throws \Exception
     */
    private function realRender(string $templateName, $uniqueId, array $data = []): string
    {
        // append system vars to template
        $data[self::__HTML_ATTR_KX_TEMPLATE_ID] = $templateName;
        $data[self::__HTML_ATTR_KX_UNIQUE_ID] = $uniqueId;

        // save to storage
        if ($uniqueId !== false) {
            $this->engine->getStorage()->save($templateName, $uniqueId, $data);
        }

        // render
        $renderer = $this->getRenderer($templateName);
        return $renderer($data);
    }

    // ==================================================================================

    /**
     * Not recommended to use this
     *
     * @internal
     * @return Engine
     */
    public function getEngine(): Engine
    {
        return $this->engine;
    }
}
