<?php namespace Assetic\Test\Extension\Twig;

use PHPUnit\Framework\TestCase;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Factory\Resource\ResourceInterface;
use Assetic\Factory\AssetFactory;
use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigFormulaLoaderTest extends TestCase
{
    private $am;
    private $fm;
    /**
     * @var TwigFormulaLoader
     */
    private $loader;

    protected function setUp(): void
    {
        if (!class_exists('\Twig\Environment')) {
            $this->markTestSkipped('Twig is not installed.');
        }

        $this->am = $this->getMockBuilder(AssetManager::class)->getMock();
        $this->fm = $this->getMockBuilder(FilterManager::class)->getMock();

        $factory = new AssetFactory(__DIR__.'/templates');
        $factory->setAssetManager($this->am);
        $factory->setFilterManager($this->fm);

        $twig = new Environment(new ArrayLoader(array()));
        $twig->addExtension(new AsseticExtension($factory, array(
            'some_func' => array(
                'filter' => 'some_filter',
                'options' => array('output' => 'css/*.css'),
            ),
        )));

        $this->loader = new TwigFormulaLoader($twig);
    }

    protected function tearDown(): void
    {
        $this->am = null;
        $this->fm = null;
    }

    public function testMixture()
    {
        $asset = $this->getMockBuilder(AssetInterface::class)->getMock();

        $expected = array(
            'mixture' => array(
                array('foo', 'foo/*', '@foo'),
                array(),
                array(
                    'output'  => 'packed/mixture',
                    'name'    => 'mixture',
                    'debug'   => false,
                    'combine' => null,
                    'vars'    => array(),
                ),
            ),
        );

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/mixture.twig')));
        $this->am->expects($this->any())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($asset));

        $formulae = $this->loader->load($resource);
        $this->assertEquals($expected, $formulae);
    }

    public function testFunction()
    {
        $expected = array(
            'my_asset' => array(
                array('path/to/asset'),
                array('some_filter'),
                array('output' => 'css/*.css', 'name' => 'my_asset'),
            ),
        );

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/function.twig')));

        $formulae = $this->loader->load($resource);
        $this->assertEquals($expected, $formulae);
    }

    public function testUnclosedTag()
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/unclosed_tag.twig')));

        $formulae = $this->loader->load($resource);
        $this->assertEquals(array(), $formulae);
    }

    public function testEmbeddedTemplate()
    {
        $expected = array(
            'image' => array(
                array('images/foo.png'),
                array(),
                array(
                    'name'    => 'image',
                    'debug'   => true,
                    'vars'    => array(),
                    'output'  => 'images/foo.png',
                    'combine' => false,
                ),
            ),
        );

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(file_get_contents(__DIR__.'/templates/embed.twig')));

        $formulae = $this->loader->load($resource);
        $this->assertEquals($expected, $formulae);
    }
}
