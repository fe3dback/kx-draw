<?php

namespace KX\Template;

use KX\Template\Builders\EngineFactory;
use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{

    /** @var  Engine */
    private $engine;

    private $tmpDir;

    public function testException()
    {
        $this->expectException(\Exception::class);

        $this->engine::halt('Test');
    }

    public function testEnabledMemCacheAndDisabledNormalCache()
    {
        $this->expectException(\Exception::class);

        (new EngineFactory())
            ->setTemplatesDirReal($this->tmpDir)
            ->setCacheDirReal($this->tmpDir)
            ->setExt('hbs')

            // should be exception:
            ->setUseMemCache(true)
            ->setUseCache(false)

            ->build();
    }

    public function testWithoutTemplatesExt()
    {
        $this->expectException(\Exception::class);

        (new EngineFactory())
            ->setTemplatesDirReal($this->tmpDir)
            ->setCacheDirReal($this->tmpDir)

            // we not set template ext
            ->setExt(false)

            ->build();
    }

    public function testUnExistDirs()
    {
        $this->expectException(\Exception::class);

        (new EngineFactory())
            ->setTemplatesDirReal(__DIR__ . '/someRandomUnExistDirectory')
            ->setCacheDirReal(__DIR__ . '/someRandomUnExistDirectory')
            ->build();
    }

    public function testMapFileName()
    {
        $this->expectException(\Exception::class);

        (new EngineFactory())
            ->setTemplatesDirReal($this->tmpDir)
            ->setCacheDirReal($this->tmpDir)
            ->setMapFileName(false)
            ->build();
    }

    protected function setUp()
    {
        $this->tmpDir = __DIR__ . DIRECTORY_SEPARATOR . '../tmp';

        $this->engine = (new EngineFactory())
            ->setTemplatesDirReal($this->tmpDir)
            ->setCacheDirReal($this->tmpDir)
            ->setExt('hbs')
            ->build();
    }
}
