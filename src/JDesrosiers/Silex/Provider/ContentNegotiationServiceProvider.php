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
    protected $app;

    public function boot(Application $app)
    {
        $app->before(array($this, "setRequestFormat"), Application::EARLY_EVENT);
        $app->error(array($this, "handleNotAcceptable"), Application::EARLY_EVENT);
    }

    public function register(Application $app)
    {
        $this->app = $app;

        $app["conneg.serializationFormats"] = array("json", "xml", "yml");
        $app["conneg.deserializationFormats"] = array("json", "xml");
        $app["conneg.defaultContentType"] = "json";

        $app["conneg"] = $app->share(
            function () use ($app) {
                return new ContentNegotiation($app);
            }
        );
    }

    public function setRequestFormat(Request $request)
    {
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            $format = $contentType === "*/*" ?
                $this->app["conneg.defaultContentType"] :
                $request->getFormat($contentType);

            if (in_array($format, $this->app["conneg.serializationFormats"])) {
                $request->setRequestFormat($format);
                return;
            }
        }

        throw new NotAcceptableHttpException();
    }

    public function handleNotAcceptable(NotAcceptableHttpException $e, $code)
    {
        return new Response("", $code);
    }
}
