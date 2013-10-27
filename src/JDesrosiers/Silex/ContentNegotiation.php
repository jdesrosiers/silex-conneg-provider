<?php

namespace JDesrosiers\Silex;

/**
 * This interface defines helper methods for automatically serializing responses and deserializing requests.
 */
interface ContentNegotiation
{
    /**
     * Serialize the response in an acceptable format.
     */
    function createResponse($responseObject, $status = 200, array $headers = array());

    /**
     * Deserialize the request body.
     */
    function deserializeRequest($class);
}
