<?php namespace KX\Template;

use LightnCandy\LightnCandy;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Draw
{
    const _RENDERER_EXT = 'renderer';

    /** @var Engine */
    private $engine;

    /** @var array - After first disk read, we keep string in cache */
    private $templatesCache = [];

    /** @var array - Partial templates (name => raw_str) */
    private $partials = [];

    function __construct(Engine $engine)
    {
        $this->engine = $engine;
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
        if (!$uniqueId)
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

    // ==================================================================================

    /**
     * Return string template by name
     *
     * @param $name
     * @return string
     */
    private function getTemplate(string $name)
    {
        if (!in_array($name, $this->templatesCache))
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

    /**
     * Get renderer function
     *
     * @param $name
     * @return callable
     */
    private function getRenderer(string $name)
    {
        $cached = false;

        $path = $this->engine->getCacheDirectory()
            . DIRECTORY_SEPARATOR . $name
            . '.' . static::_RENDERER_EXT;

        if ($this->engine->isUseCache())
        {
            if (is_file($path))
            {
                /** @noinspection PhpIncludeInspection */
                $cached = include $path;
                if (!is_callable($cached))
                {
                    $cached = false;
                }
            }
        }

        if ($cached)
        {
            return $cached;
        }

        // render
        $compileSettings = [
            'flags' => LightnCandy::FLAG_HANDLEBARSJS,
            'partials' => $this->partials
        ];

        $template = $this->getTemplate($name);
        $render = LightnCandy::compile($template, $compileSettings);

        // save renderer to cache
        if ($this->engine->isUseCache())
        {
            file_put_contents($path, '<?php ' . $render . '?>');
        }

        $renderer = eval($render);
        if (!is_callable($renderer))
        {
            Engine::halt("
                Invalid template file. Check your syntax at '%s',
                if you use partials, check that they added to partials list
            ", [$name]);
        }

        return $renderer;
    }

    /**
     * Wrap prepared template with tag and id
     * This we can use in js later
     *
     * @param $template
     * @param $name
     * @param $id
     * @return string
     */
    private function wrapTemplate(string $template, string $name, string $id)
    {
        return "<div data-kxdraw-name='{$name}' data-kxdraw-id='{$id}'>{$template}</div>";
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
        $this->engine->getStorage()->save($templateName, $uniqueId, $data);

        $renderer = $this->getRenderer($templateName);
        $raw = $renderer($data);

        // wrap
        $template = $this->wrapTemplate($raw, $templateName, $uniqueId);
        return $template;
    }
}