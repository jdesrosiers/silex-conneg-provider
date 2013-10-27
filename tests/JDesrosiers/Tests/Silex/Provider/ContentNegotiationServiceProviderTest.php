<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider;
use JDesrosiers\Silex\Provider\JmsSerializerServiceProvider;
use Silex\Application;
use Silex\Provider\SerializerServiceProvider;
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

        $this->app->register(new JmsSerializerServiceProvider(), array(
            "serializer.srcDir" => __DIR__ . "/../../../../../vendor/jms/serializer/src",
        ));
    }

    public function dataProviderNotAcceptableReturns406()
    {
        return array(
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
        $this->app->get("/foo", function (Application $app) {
            return $app->json(array("foo" => "bar"));
        });

        $headers = array(
            "HTTP_ACCEPT" => $accept,
        );

        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("406", $response->getStatusCode());
        $this->assertEquals("text/html; charset=UTF-8", $response->headers->get("Content-Type"));
    }

    public function testNoAcceptHeaders()
    {
        $this->app->get("/foo", function (Application $app) {
            return $app->json(array("foo" => "bar"));
        });

        $headers = array(
            "HTTP_ACCEPT" => null,
            "HTTP_ACCEPT_CHARSET" => null,
            "HTTP_ACCEPT_LANGUAGE" => null,
        );

        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals($response->headers->get("Content-Type"), "application/json");
    }

    public function testAcceptAny()
    {
        $this->app->get("/foo", function (Application $app) {
            return $app->json(array("foo" => "bar"));
        });

        $headers = array(
            "HTTP_ACCEPT" => "*/*",
        );

        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals($response->headers->get("Content-Type"), "application/json");
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
        $this->app->post("/foo", function (Application $app) {
            return $app->json(array("foo" => "bar"));
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

    public function dataProviderCreateResponse()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<result>
  <entry><![CDATA[bar]]></entry>
</result>

XML;

        return array(
            array("application/json", "application/json", '{"foo":"bar"}'),
            array("text/xml", "text/xml; charset=UTF-8", $xml),
            array("application/xml", "text/xml; charset=UTF-8", $xml),
        );
    }

    /**
     * @dataProvider dataProviderCreateResponse
     */
    public function testCreateResponse($accept, $expectedContentType, $expectedContent)
    {
        $this->app->get("/foo", function (Application $app) {
            return $app["conneg"]->createResponse(array("foo" => "bar"));
        });

        $headers = array(
            "HTTP_ACCEPT" => $accept,
        );

        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertEquals($expectedContentType, $response->headers->get("Content-Type"));
        $this->assertEquals($expectedContent, $response->getContent());
    }

    public function dataProviderDeserializeRequest()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request><![CDATA[foo]]></request>
XML;

        return array(
            array("application/json", '"foo"'),
            array("text/xml; charset=UTF-8", $xml),
            array("application/xml", $xml),
        );
    }

    /**
     * @dataProvider dataProviderDeserializeRequest
     */
    public function testDeserializeRequest($contentType, $content)
    {
        $expectedContent = "foo";

        $this->app->post("/foo", function (Application $app) {
            return print_r($app["conneg"]->deserializeRequest("string"), true);
        });

        $headers = array(
            "CONTENT_TYPE" => $contentType,
        );

        $client = new Client($this->app, $headers);
        $client->request("POST", "/foo", array(), array(), $headers, $content);

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertEquals($expectedContent, $response->getContent());
    }
}
