silex-conneg-provider
=====================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-conneg-provider.png?branch=master)](https://travis-ci.org/jdesrosiers/silex-conneg-provider)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/badges/quality-score.png?s=3b40bf693eeed775332adc6dce0a0d5d6d22562b)](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/)
[![Code Coverage](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/badges/coverage.png?s=236af0823e81210b6d6f75ccb2952df1a45f7fa4)](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/)

A [silex](https://github.com/fabpot/Silex) service provider that provides tools for doing
[HTTP Content Negotiation](http://www.w3.org/Protocols/rfc2616/rfc2616-sec12.html).  It allows you to declare which
formats your application can handle in the request body and the response body.  If the client requests a response in a
format your application does not support, they will get a `406 Not Acceptable` response.  If the client sends a request
body in a format your application does not support, they will get a 415 Unsupported Media Type` response.  There is also
a service to make it easy to automatically serialize responses and deserialize requests using
[JMS Serialzier](http://jmsyst.com/libs/serializer).

Installation
------------
Install the silex-conneg-provider using [composer](http://getcomposer.org/).  This project uses
[sematic versioning](http://semver.org/).

```json
{
    "require": {
        "jdesrosiers/silex-conneg-provider": "dev-master"
    }
}
```

Parameters
----------
Content Negotiation
* **conneg.responseFormats**: (array) Array of supported response formats.  Defaults to `array("html")`
* **conneg.requestFormats**: (array) Array of supported request formats.  Defaults to `array("form")`

General Serialization
* **conneg.defaultFormat**: (string) Defaults to `html`

JMS Serializer
* **conneg.serializationContext**: (JMS\Serializer\SerializationContext).  Optional.
* **conneg.deserializationContext**: (JMS\Serializer\DeserializationContext).  Optional.

Services
--------
* **conneg**: Provides a ContentNegotiation object with two methods: createResponse and deserializeRequest
 * **createResponse**: This works just like `Respose::create`, but takes a JMS Serializer annotated object instead of a string and serializes it to the format the user requested.
 * **deserializeRequest**: Pass it a class name and it will deserialize the request entity and give you back an instance of that class.

Registering
-----------
```php
$app->register(new JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider(), array(
    "conneg.responseFormats" => array("json", "xml"),
    "conneg.requestFormats" => array("json", "xml"),
    "conneg.defaultFormat" => "json",
));
```

Content Negotiation Usage
-------------------------
The middleware does all of the header validation automatically and responds appropriately when the request can not be
handled.  You can get the response format determined by the request headers using th `Request::getRequestFormat` method.

```php
$request->getRequestFormat($defaultFormat);
```

Serailizer Usage
----------------
The `conneg` service provides some helper functions for automatically serailizing responses and deserializing requests.
This functionality is available if you have the optional [JMS Serialzier](http://jmsyst.com/libs/serializer) package
installed.

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
