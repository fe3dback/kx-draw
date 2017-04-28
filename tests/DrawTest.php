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

        file_put_contents($this->templateDirectory.DIRECTORY_SEPARATOR.'hello.hbs', $__helloTemplate);
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
