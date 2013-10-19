silex-conneg-provider
=====================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-conneg-provider.png?branch=master)](https://travis-ci.org/jdesrosiers/silex-conneg-provider)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/badges/quality-score.png?s=3b40bf693eeed775332adc6dce0a0d5d6d22562b)](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/)
[![Code Coverage](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/badges/coverage.png?s=236af0823e81210b6d6f75ccb2952df1a45f7fa4)](https://scrutinizer-ci.com/g/jdesrosiers/silex-conneg-provider/)

A [silex](https://github.com/fabpot/Silex) service provider that provides tools for doing HTTP content negotiation
using [jms/serializer](https://github.com/schmittjoh/serializer).

Installation
------------
Install the silex-conneg-provider using [composer](http://getcomposer.org/).  This project uses [sematic versioning](http://semver.org/).

```json
{
    "require": {
        "jdesrosiers/silex-conneg-provider": "~0.1"
    }
}
```

Parameters
----------
* **conneg.serializer**: (JMS\Serializer\Serializer) A JMS Serializer instance
* **conneg.serializationFormats**: (array) Array of supported serialization formats.  Defaults to `array("json", "xml", "yml")`
* **conneg.deserializationFormats**: (array) Array of supported deserialization formats.  Defaults to `array("json", "xml")`
* **conneg.defaultContentType**: (string) Defaults to `json`

Services
--------
* **conneg**: Provides a ContentNegotiation object with two methods: createResponse and deserializeRequest
* **createResponse**: This works just like instatiating a new Respose, but takes a JMS Serializer annotated object instead of a string and serializes it to the format the user requested.
* **deserializeRequest**: Pass it a class name and it will deserialize the request entity and give you back an instance of that class.

Registering
-----------
```php
$app->register(new JDesrosiers\Silex\Provider\ContentNegotiationServiceProvider(), array(
    "conneg.serializer" => $app["serializer"],
));
```

Usage
-----
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
