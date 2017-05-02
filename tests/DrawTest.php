<?php
namespace KX\Template;

use KX\Template\Builders\EngineFactory;
use PHPUnit\Framework\TestCase;

class DrawTest extends TestCase
{
    /** @var Draw */
    private $draw;

    private $tmpDirectory;
    private $cacheDirectory;
    private $templateDirectory;

    // ================================================

    /**
     * @param $withCache
     * @param $withMemCache
     * @param $withBenchmark
     * @return Draw
     */
    private function buildDraw($withCache, $withMemCache, $withBenchmark)
    {
        $draw = new Draw
        (
            (new EngineFactory())
                ->setExt('hbs')
                ->setCacheDirReal($this->cacheDirectory)
                ->setTemplatesDirReal($this->templateDirectory)
                ->setUseCache($withCache)
                ->setUseMemCache($withMemCache)
                ->setUseBenchmark($withBenchmark)
                ->build()
        );

        $draw->addPartialsDirectory('shared');
        return $draw;
    }

    // ================================================

    public function testEngine()
    {
        $this->assertInstanceOf(Engine::class, $this->draw->__getEngine());
    }

    public function testRender()
    {
        $uniqId = 100;
        $data = [
            'name' => 'world',
            'random' => 134,
            'test' => [123,123]
        ];

        $expected = "<b data-kx-draw-name=\"hello\" data-kx-draw-id=\"{$uniqId}\">Hello <i>world</i>!</b>";

        /**
         * @param Draw $draw
         */
        $testDraw = function(Draw $draw) use ($expected, $uniqId, $data) {

            $actual = $draw->render('hello', $uniqId, $data);
            // first time test file cache
            $this->assertEquals($expected, $actual);
            // second time test mem cache
            $this->assertEquals($expected, $actual);
        };

        // normal work
        // --------------------------------
        $testDraw($this->draw);

        // and turn off cache
        // --------------------------------
        $testDraw($this->buildDraw(false, false, false));

        // and with already exist renderer cache
        // --------------------------------
        $testDraw($this->buildDraw(true, true, false));

        // and with benchmarking
        // --------------------------------
        $testDraw($this->buildDraw(true, true, true));
    }

    public function testRenderWithoutName()
    {
        $this->expectException(\Exception::class);
        $this->draw->render(false, "");
    }

    public function testTemplates()
    {

        $__id = 100;
        $__varKey = 'name';
        $__varValue = 'unit';
        $__template = 'hello';

        // ---

        $draw = $this->buildDraw(false, false, false);
        $draw->render($__template, $__id, [$__varKey => $__varValue]);

        $storage = $draw->__getEngine()->getStorage();

        $templates = $storage->getUsedTemplates();
        $tempData = $storage->getTemplateData();

        $this->assertEquals(in_array($__template, $templates), true);
        $this->assertCount(1, $templates);

        $this->assertArrayHasKey($__template, $tempData);
        $this->assertCount(1, $tempData);

        $name = $tempData[$__template][$__id][$__varKey];
        $this->assertEquals($name, $__varValue);

        $this->assertEquals($tempData[$__template][$__id]['_kx_draw_template_name'], $__template);
        $this->assertEquals($tempData[$__template][$__id]['_kx_draw_unique_id'], $__id);

    }

    public function testBenchmark()
    {
        $benchDraw = $this->buildDraw(true, true, true);

        $testsCount = (int)round(random_int(10, 100));

        for ($i=0; $i<$testsCount; $i++) {
            $benchDraw->render('hello', $i, []);
        }

        $bench = $benchDraw->getDrawTime();

        $this->assertArrayHasKey('hello', $bench);
        $this->assertCount(1, $bench);

        // no benchmark
        $benchDraw = $this->buildDraw(true, true, false);
        $shouldBeFalse = $benchDraw->getDrawTime();
        $this->assertEquals(false, $shouldBeFalse);
    }

    public function testInvalidPartial()
    {
        // partial withpout name
        $this->expectException(\Exception::class);
        $this->draw->addPartial(false);
    }

    public function testInvalidPartialFolder()
    {
        // partial folder without name
        $this->expectException(\Exception::class);
        $this->draw->addPartialsDirectory(false);
    }

    public function testNotExistPartialFolder()
    {
        $this->expectException(\Exception::class);
        $this->draw->addPartialsDirectory(__DIR__ . '/some_random_not_exist_dir');
    }

    public function testNotExistTemplate()
    {
        $this->expectException(\Exception::class);
        $this->draw->getTemplate('some/random/not_exist_template');
    }

    public function testBrokenRender_Empty()
    {
        // we special use broken template
        // and wait for exception
        $this->expectException(\Exception::class);
        $this->draw->render('broken_empty', 1, []);
    }

    public function testBrokenRender_ManyParents()
    {
        // we special use broken template
        // and wait for exception
        $this->expectException(\Exception::class);
        $this->draw->render('broken_many_parent', 1, []);
    }

    public function testBrokenRender_OneParent()
    {
        // we special use broken template
        // and wait for exception
        $this->expectException(\Exception::class);
        $this->draw->render('broken_parent', 1, []);
    }

    public function testBrokenRender_NoParent()
    {
        // we special use broken template
        // and wait for exception
        $this->expectException(\Exception::class);
        $this->draw->render('broken_no_parent', 1, []);
    }

    public function testJSExport()
    {
        $this->draw->render('hello', 1, [
            'test_js_data' => "HEsl_9241QQE"
        ]);

        $htmlOutput = $this->draw->exportToJS();

        $check = [
            "test_js_data",
            "HEsl_9241QQE",
            "text/javascript",
            "window.__kxrender_data",
            "window.__kxrender_templates",
            "hello",
        ];

        foreach ($check as $token) {
            $this->assertTrue(
                substr_count($htmlOutput, $token) >= 1,
                    vsprintf("%s not exist in JS output", [$token])
            );
        }


    }


    // ================================================

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    protected function setUp()
    {
        // Make Example directories and templates (only for tests)
        $tmpDir = __DIR__ . DIRECTORY_SEPARATOR . '../tmp';
        $this->tmpDirectory = $tmpDir . DIRECTORY_SEPARATOR . 'tests';
        $this->templateDirectory = $this->tmpDirectory . DIRECTORY_SEPARATOR . 'templates';
        $this->cacheDirectory = $this->tmpDirectory . DIRECTORY_SEPARATOR . 'cache';

        $partialsDir = $this->templateDirectory . DIRECTORY_SEPARATOR . 'shared';
        $buildExampleStruct = [$tmpDir, $this->tmpDirectory, $this->cacheDirectory, $this->templateDirectory, $partialsDir];

        foreach ($buildExampleStruct as $dir)
        {
            if (!is_dir($dir))
            {
                mkdir($dir);
            }
        }

        // Create tmp templates
        $__helloTemplate = "<b>Hello {{> shared/name}}!</b>";
        $__partTemplate = "<i>{{name}}</i>";
        $__brokenTemplate_no_parent = "{{> for_example_try/to_render/not_exist_partial}}This should be broken!";
        $__brokenTemplate_parent = "<p>{{> for_example_try/to_render/not_exist_partial}}This should be broken!</p>";
        $__brokenTemplate_multiple_parents = "<p>parent 1</p><div>parent 2</div>";
        $__brokenTemplate_empty = "";

        file_put_contents($this->templateDirectory.DIRECTORY_SEPARATOR.'hello.hbs', $__helloTemplate);
        file_put_contents($this->templateDirectory.DIRECTORY_SEPARATOR.'broken_no_parent.hbs', $__brokenTemplate_no_parent);
        file_put_contents($this->templateDirectory.DIRECTORY_SEPARATOR.'broken_parent.hbs', $__brokenTemplate_parent);
        file_put_contents($this->templateDirectory.DIRECTORY_SEPARATOR.'broken_many_parent.hbs', $__brokenTemplate_multiple_parents);
        file_put_contents($this->templateDirectory.DIRECTORY_SEPARATOR.'broken_empty.hbs', $__brokenTemplate_empty);
        file_put_contents($partialsDir.DIRECTORY_SEPARATOR.'name.hbs', $__partTemplate);

        // -------------

        $this->draw = new Draw
        (
            (new EngineFactory())
                ->setExt('hbs')
                ->setCacheDirReal($this->cacheDirectory)
                ->setTemplatesDirReal($this->templateDirectory)
                ->setUseCache(true)
                ->setUseMemCache(true)
                ->build()
        );

        $this->draw->addPartialsDirectory('shared');
    }

    protected function tearDown()
    {
        self::delTree($this->tmpDirectory);
    }
}
