<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/15
 * Time: 10:03
 */

namespace Conformity\Router\Exception;


class MethodNotAllowedException extends HttpException
{

    public function __construct(array $allow, $message = null, \Exception $previous = null, $code = 0)
    {
        $headers = array('Allow' => strtoupper(implode(', ', $allow)));
        parent::__construct(405, $message, $previous, $headers, $code);
    }

}