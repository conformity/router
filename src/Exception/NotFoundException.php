<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/15
 * Time: 10:16
 */

namespace Conformity\Router\Exception;


class NotFoundException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, array(), $code);
    }
}