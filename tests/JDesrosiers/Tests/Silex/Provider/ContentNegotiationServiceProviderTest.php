<?php

namespace JDesrosiers\Tests\Silex\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

AnnotationRegistry::registerAutoloadNamespace("JMS\Serializer\Annotation", __DIR__ . "/../../../../../vendor/jms/serializer/src");

class CartServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();

        $serializerBuilder = SerializerBuilder::create()
                ->setCacheDir(__DIR__ . "/../cache")
                ->setPropertyNamingStrategy(new SerializedNameAnnotationStrategy(new IdenticalPropertyNamingStrategy()));

        $this->app->register(new ContentNegotiationServiceProvider(), array(
             "conneg.serializer" => $serializerBuilder->build(),
             "conneg.serializationFormats" => array("json", "xml", "yml"),
             "conneg.deserializationFormats" => array("json", "xml"),
             "conneg.defaultContentType" => "json",
        ));
    }

    public function dataProviderNotAcceptableReturns406()
    {
        return array(
            array(""),
            array("text/html"),
            array("image/jpeg"),
            array("foo/bar"),
        );
    }

    /**
     * @dataProvider dataProviderNotAcceptableReturns406
     */
    public function testNotAcceptableReturns406($accept)
    {
        $this->app->get("/foo", function () {
            return "";
        });

        $headers = array(
            "HTTP_ACCEPT" => $accept
        );

        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("406", $response->getStatusCode());
        $this->assertEquals("", $response->getContent());
//        $this->assertFalse($response->headers->has("Content-Type"));
    }

    public function dataProviderUnsupportedMediaTypeReturns415()
    {
        return array(
            array("application/json", "image/jpeg", "application/json"),
            array("application/xml", "image/jpeg", "text/xml; charset=UTF-8"),
            array("text/xml; charset=UTF-8", "image/jpeg", "text/xml; charset=UTF-8"),
        );
    }

    /**
     * @dataProvider dataProviderUnsupportedMediaTypeReturns415
     */
    public function testUnsupportedMediaTypeReturns415($accept, $contentType, $expectedContentType)
    {
        $app = $this->app;
        $this->app->post("/foo", function () use ($app) {
            $app["conneg"]->deserializeRequest("array");
        });

        $headers = array(
            "HTTP_ACCEPT" => $accept,
            "CONTENT_TYPE" => $contentType,
        );

        $client = new Client($this->app, $headers);
        $client->request("POST", "/foo", array(), array(), $headers, "");

        $response = $client->getResponse();

        $this->assertEquals("415", $response->getStatusCode());
        $this->assertEquals($expectedContentType, $response->headers->get("Content-Type"));
    }
}
