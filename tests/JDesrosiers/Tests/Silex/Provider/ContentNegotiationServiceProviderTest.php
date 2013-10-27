<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class CartServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app["debug"] = true;

        $this->app->register(new ContentNegotiationServiceProvider(), array(
             "conneg.responseFormats" => array("json", "xml"),
             "conneg.requestFormats" => array("json", "xml"),
             "conneg.defaultFormat" => "json",
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
            return "";
        });

        $headers = array(
            "HTTP_ACCEPT" => $accept,
            "HTTP_CONTENT_TYPE" => $contentType,
        );

        $client = new Client($this->app, $headers);
        $client->request("POST", "/foo", array(), array(), $headers, "");

        $response = $client->getResponse();

        $this->assertEquals("415", $response->getStatusCode());
        $this->assertEquals($expectedContentType, $response->headers->get("Content-Type"));
    }
}
