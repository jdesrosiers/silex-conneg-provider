<?php

namespace JDesrosiers\Silex\Provider;

use JDesrosiers\Silex\ContentNegotiation;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class ContentNegotiationServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
        $app->before(function (Request $request) use ($app) {
            foreach ($request->getAcceptableContentTypes() as $contentType) {
                $format = $contentType === "*/*" ? $app["conneg.defaultContentType"] : $request->getFormat($contentType);

                if (in_array($format, $app["conneg.serializationFormats"])) {
                    $request->setRequestFormat($format);
                    return;
                }
            }

            throw new NotAcceptableHttpException();
        }, Application::EARLY_EVENT);

        $app->error(function (NotAcceptableHttpException $e, $code) {
            return new Response("", $code);
        }, Application::EARLY_EVENT);
    }

    public function register(Application $app)
    {
        $app["conneg.serializationFormats"] = array("json", "xml", "yml");
        $app["conneg.deserializationFormats"] = array("json", "xml");
        $app["conneg.defaultContentType"] = "json";

        $app["conneg"] = $app->share(function () use ($app) {
            return new ContentNegotiation($app["request"], $app["conneg.serializer"], $app["conneg.serializationFormats"], $app["conneg.deserializationFormats"]);
        });
    }
}
