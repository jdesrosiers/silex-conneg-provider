<?php

namespace JDesrosiers\Silex;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class ContentNegotiation
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function createResponse($responseObject, $status = 200, array $headers = array())
    {
        $format = $this->app["request"]->getRequestFormat();

        // Just in case
        if (!in_array($format, $this->app["conneg.serializationFormats"])) {
            throw new NotAcceptableHttpException();
        }

        $serializedContent = $this->app["serializer"]->serialize($responseObject, $format);

        // Set validation cache headers
        $response = new Response($serializedContent, $status, $headers);
        $response->setVary(array('Accept', 'Accept-Encoding', 'Accept-Charset'));
        $response->setEtag(md5($serializedContent));

        return $response;
    }

    public function deserializeRequest($class)
    {
        $format = $this->app["request"]->getContentType();
        if (in_array($format, $this->app["conneg.deserializationFormats"])) {
            return $this->app["conneg.serializer"]->deserialize($this->app["request"]->getContent(), $class, $format);
        } else {
            throw new UnsupportedMediaTypeHttpException();
        }
    }
}
