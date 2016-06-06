<?php

namespace JDesrosiers\Silex\Provider\ContentNegotiation;

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
        $request = $this->app["request_stack"]->getCurrentRequest();
        $format = $request->getRequestFormat($this->app["conneg.defaultFormat"]);

        $serializedContent = $this->app["serializer"]->serialize(
            $responseObject,
            $format,
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
        $request = $this->app["request_stack"]->getCurrentRequest();

        return $this->app["serializer"]->deserialize(
            $request->getContent(),
            $className,
            $request->getContentType(),
            $this->app["conneg.deserializationContext"]
        );
    }
}
