<?php

namespace JDesrosiers\Silex;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class provides helper methods for serializing responses and deserializing requests using JMS Serializer.
 */
class JmsSerializerContentNegotiation implements ContentNegotiation
{
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * This method can be used in place of Response::create to automatically serialize your response into the requested
     * format.
     *
     * @param object $responseObject
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function createResponse($responseObject, $status = 200, array $headers = array())
    {
        $serializedContent = $this->app["serializer"]->serialize(
            $responseObject,
            $this->app["request"]->getRequestFormat($this->app["conneg.defaultFormat"]),
            $this->app["conneg.serializationContext"]
        );

        return new Response($serializedContent, $status, $headers);
    }

    /**
     * This method retrieves the request body in any supported format and deserializes it.
     *
     * @param string $className
     * @return object
     */
    public function deserializeRequest($className)
    {
        return $this->app["serializer"]->deserialize(
            $this->app["request"]->getContent(),
            $className,
            $this->app["request"]->getContentType(),
            $this->app["conneg.deserializationContext"]
        );
    }
}
