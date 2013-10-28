<?php

namespace JDesrosiers\Silex\Provider;

use JDesrosiers\Silex\JmsSerializerContentNegotiation;
use JDesrosiers\Silex\SymfonySerializerContentNegotiation;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * This ServiceProvider provides HTTP Content Negotiation support to a silex application.
 */
class ContentNegotiationServiceProvider implements ServiceProviderInterface
{
    protected $app;

    /**
     * Add filters to check that the request and repsonse formats are supported by the application.
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        $app->before(array($this, "setRequestFormat"), Application::EARLY_EVENT);
        $app->before(array($this, "validateRequestContentType"), Application::EARLY_EVENT);
    }

    /**
     * Set defaults and build the conneg service.
     *
     * @param Application $app
     * @throws ServiceUnavailableHttpException
     */
    public function register(Application $app)
    {
        $this->app = $app;

        $app["conneg.responseFormats"] = array("html");
        $app["conneg.requestFormats"] = array("form");
        $app["conneg.defaultFormat"] = "html";

        $app["conneg"] = $app->share(
            function (Application $app) {
                if ($app->offsetExists("serializer")) {
                    if ($app["serializer"] instanceof \JMS\Serializer\Serializer) {
                        if (!$app->offsetExists("conneg.serializationContext")) {
                            $app["conneg.serializationContext"] = null;
                        }
                        if (!$app->offsetExists("conneg.deserializationContext")) {
                            $app["conneg.deserializationContext"] = null;
                        }

                        return new JmsSerializerContentNegotiation($app);
                    } elseif ($app["serializer"] instanceof \Symfony\Component\Serializer\Serializer) {
                        if (!$app->offsetExists("conneg.serializationContext")) {
                            $app["conneg.serializationContext"] = array();
                        }
                        if (!$app->offsetExists("conneg.deserializationContext")) {
                            $app["conneg.deserializationContext"] = array();
                        }

                        return new SymfonySerializerContentNegotiation($app);
                    }
                }

                throw new ServiceUnavailableHttpException(null, "No supported serializer found");
            }
        );
    }

    /**
     * This before middleware validates whether the application will be able to respond in a format that the client
     * understands.
     *
     * @param Request $request
     * @throws NotAcceptableHttpException
     */
    public function setRequestFormat(Request $request)
    {
        // If there is no Accept header, do nothing
        if (!$request->headers->get("Accept")) {
            return;
        }

        // Check the Accept header for acceptable formats
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            // If any format is acceptable, do nothing
            if ($contentType === "*/*") {
                return;
            }

            $format = $request->getFormat($contentType);
            if (in_array($format, $this->app["conneg.responseFormats"])) {
                // An acceptable format was found.  Set it as the requestFormat where it can be referenced later.
                $request->setRequestFormat($format);
                return;
            }
        }

        // No acceptable formats were found
        throw new NotAcceptableHttpException();
    }

    /**
     * This before middleware validates that the request body is in a format that the application understands.
     *
     * @param Request $request
     * @throws UnsupportedMediaTypeHttpException
     */
    public function validateRequestContentType(Request $request)
    {
        // Define the "form" format so we can use it for validation
        $request->setFormat("form", array("application/x-www-form-urlencoded", "multipart/form-data"));

        $format = $request->getContentType();
        if ($format !== null && !in_array($format, $this->app["conneg.requestFormats"])) {
            // The request has a body but it is not a supported media type
            throw new UnsupportedMediaTypeHttpException();
        }
    }
}
