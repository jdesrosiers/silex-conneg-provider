<?php

namespace JDesrosiers\Tests\Services\Cart;

use JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider;
use JDesrosiers\Silex\Provider\JmsSerializerServiceProvider;
use Silex\Application;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class CartServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app->register(new JmsSerializerServiceProvider(), array(
            "serializer.srcDir" => dirname(__DIR__) . "/vendor/jms/serializer/src",
            "serializer.cacheDir" => dirname(__DIR__) . "/cache",
            "serializer.namingStrategy" => "IdenticalProperty",
        ));
        $this->app->register(new ContentNegotiationServiceProvider(), array(
             "conneg.serializer" => $this->app["serializer"],
             "conneg.serializationFormats" => array("json", "xml", "yml"),
             "conneg.deserializationFormats" => array("json", "xml"),
             "conneg.defaultContentType" => "json",
        ));
    }

    public function testConneg()
    {
//        $client = new Client($this->app);
//        $client->request("GET", "/foo");
//        $response = $client->getResponse();
//
//        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
    }
}
