silex-conneg-provider
=====================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-conneg-provider.png?branch=master)](https://travis-ci.org/jdesrosiers/silex-conneg-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/?branch=master)

The silex-conneg-provider is a [silex](https://github.com/fabpot/Silex) service provider that provides tools for doing
[HTTP Content Negotiation](http://www.w3.org/Protocols/rfc2616/rfc2616-sec12.html) in your application.  It allows you
to declare which request and response formats your application can handle.  If the client requests a response in a
format your application does not support, they will get a `406 Not Acceptable` response.  If the client sends a request
body in a format your application does not support, they will get a `415 Unsupported Media Type` response.  There is also
a service to make it easy to automatically serialize responses and deserialize requests using
[JMS Serialzier](http://jmsyst.com/libs/serializer) or
[Symfony Serializer](http://symfony.com/doc/current/components/serializer.html).

Installation
------------
Install the silex-conneg-provider using [composer](http://getcomposer.org/).  This project uses
[sematic versioning](http://semver.org/).

```json
{
    "require": {
        "jdesrosiers/silex-conneg-provider": "~1.0"
    }
}
```

Parameters
----------
Content Negotiation
* **conneg.responseFormats**: (array) Array of supported response formats.  Defaults to `array("html")`
* **conneg.requestFormats**: (array) Array of supported request formats.  Defaults to `array("form")`

Serialization
* **conneg.defaultFormat**: (string) Defaults to `html`
* **conneg.serializationContext**: (JMS\Serializer\SerializationContext or array).  Optional.
* **conneg.deserializationContext**: (JMS\Serializer\DeserializationContext or array).  Optional.

Services
--------
* **conneg**: Provides an object with two methods: createResponse and deserializeRequest.  This service is only
  available if you have a serializer service installed.
 * **createResponse**: This works just like `Respose::create`, but takes an object instead of a string and serializes it
   to the desired format.  The format is determined by the middleware this service provider includes.
 * **deserializeRequest**: Pass it a class name and it will deserialize the request entity and give you back an instance
   of that class.

Registering
-----------
```php
$app->register(new JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider(), array(
    "conneg.responseFormats" => array("json", "xml"),
    "conneg.requestFormats" => array("json", "xml"),
    "conneg.defaultFormat" => "json",
));
```

Usage
-----
The service provider adds middleware that does all of the content negotiation header validation automatically and
responds appropriately when the request can not be handled.  You can get the response format determined by the
middleware using the `Request::getRequestFormat` method.

```php
$request->getRequestFormat($defaultFormat);
```

Serailizer Usage
----------------
The `conneg` service provides some helper functions for automatically serailizing responses and deserializing requests.
This functionality is available if you have an instance of either the [JMS Serialzier](http://jmsyst.com/libs/serializer)
or the [Symfony Serializer](http://symfony.com/doc/current/components/serializer.html) accessible at
`$app["serializer"]`.  You can get the JMS Serializer from the
[jdesrosiers/silex-jms-serializer-provider](https://github.com/jdesrosiers/silex-jms-serializer-provider) or the Symfony
Serializer from the silex built-in `SerializerServiceProvider`.

```php
$app->post("/foo", function (Request $request) use ($app) {
    // deserializeRequest takes the class name of a JMS Serializer annotated class and will deserialize
    // the request entity and give you back an instance of that class.
    $requestData = $app["conneg"]->deserializeRequest("FooRequest");

    $response = Foo::create($requestData);

    // createResponse works just like Respose::create, but takes a JMS Serializer annotated object
    // instead of a string and serializes it to the format the user requested.
    return $app["conneg"]->createResponse($response, 201, array(
        "Location" => "/foo/1234",
    ));
});
```
