<?php
/**
 * Autogenerated by Thrift
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 */
include_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';


$GLOBALS['E_Method'] = array(
  'GET' => 0,
  'PUT' => 1,
  'POST' => 2,
  'DELETE' => 3,
  'HEAD' => 4,
  'OPTIONS' => 5,
);

final class Method {
  const GET = 0;
  const PUT = 1;
  const POST = 2;
  const DELETE = 3;
  const HEAD = 4;
  const OPTIONS = 5;
  static public $__names = array(
    0 => 'GET',
    1 => 'PUT',
    2 => 'POST',
    3 => 'DELETE',
    4 => 'HEAD',
    5 => 'OPTIONS',
  );
}

$GLOBALS['E_Status'] = array(
  'CONT' => 100,
  'SWITCHING_PROTOCOLS' => 101,
  'OK' => 200,
  'CREATED' => 201,
  'ACCEPTED' => 202,
  'NON_AUTHORITATIVE_INFORMATION' => 203,
  'NO_CONTENT' => 204,
  'RESET_CONTENT' => 205,
  'PARTIAL_CONTENT' => 206,
  'MULTI_STATUS' => 207,
  'MULTIPLE_CHOICES' => 300,
  'MOVED_PERMANENTLY' => 301,
  'FOUND' => 302,
  'SEE_OTHER' => 303,
  'NOT_MODIFIED' => 304,
  'USE_PROXY' => 305,
  'TEMPORARY_REDIRECT' => 307,
  'BAD_REQUEST' => 400,
  'UNAUTHORIZED' => 401,
  'PAYMENT_REQUIRED' => 402,
  'FORBIDDEN' => 403,
  'NOT_FOUND' => 404,
  'METHOD_NOT_ALLOWED' => 405,
  'NOT_ACCEPTABLE' => 406,
  'PROXY_AUTHENTICATION' => 407,
  'REQUEST_TIMEOUT' => 408,
  'CONFLICT' => 409,
  'GONE' => 410,
  'LENGTH_REQUIRED' => 411,
  'PRECONDITION_FAILED' => 412,
  'REQUEST_ENTITY_TOO_LARGE' => 413,
  'REQUEST_URI_TOO_LONG' => 414,
  'UNSUPPORTED_MEDIA_TYPE' => 415,
  'REQUESTED_RANGE_NOT_SATISFIED' => 416,
  'EXPECTATION_FAILED' => 417,
  'UNPROCESSABLE_ENTITY' => 422,
  'LOCKED' => 423,
  'FAILED_DEPENDENCY' => 424,
  'INTERNAL_SERVER_ERROR' => 500,
  'NOT_IMPLEMENTED' => 501,
  'BAD_GATEWAY' => 502,
  'SERVICE_UNAVAILABLE' => 503,
  'GATEWAY_TIMEOUT' => 504,
  'INSUFFICIENT_STORAGE' => 506,
);

final class Status {
  const CONT = 100;
  const SWITCHING_PROTOCOLS = 101;
  const OK = 200;
  const CREATED = 201;
  const ACCEPTED = 202;
  const NON_AUTHORITATIVE_INFORMATION = 203;
  const NO_CONTENT = 204;
  const RESET_CONTENT = 205;
  const PARTIAL_CONTENT = 206;
  const MULTI_STATUS = 207;
  const MULTIPLE_CHOICES = 300;
  const MOVED_PERMANENTLY = 301;
  const FOUND = 302;
  const SEE_OTHER = 303;
  const NOT_MODIFIED = 304;
  const USE_PROXY = 305;
  const TEMPORARY_REDIRECT = 307;
  const BAD_REQUEST = 400;
  const UNAUTHORIZED = 401;
  const PAYMENT_REQUIRED = 402;
  const FORBIDDEN = 403;
  const NOT_FOUND = 404;
  const METHOD_NOT_ALLOWED = 405;
  const NOT_ACCEPTABLE = 406;
  const PROXY_AUTHENTICATION = 407;
  const REQUEST_TIMEOUT = 408;
  const CONFLICT = 409;
  const GONE = 410;
  const LENGTH_REQUIRED = 411;
  const PRECONDITION_FAILED = 412;
  const REQUEST_ENTITY_TOO_LARGE = 413;
  const REQUEST_URI_TOO_LONG = 414;
  const UNSUPPORTED_MEDIA_TYPE = 415;
  const REQUESTED_RANGE_NOT_SATISFIED = 416;
  const EXPECTATION_FAILED = 417;
  const UNPROCESSABLE_ENTITY = 422;
  const LOCKED = 423;
  const FAILED_DEPENDENCY = 424;
  const INTERNAL_SERVER_ERROR = 500;
  const NOT_IMPLEMENTED = 501;
  const BAD_GATEWAY = 502;
  const SERVICE_UNAVAILABLE = 503;
  const GATEWAY_TIMEOUT = 504;
  const INSUFFICIENT_STORAGE = 506;
  static public $__names = array(
    100 => 'CONT',
    101 => 'SWITCHING_PROTOCOLS',
    200 => 'OK',
    201 => 'CREATED',
    202 => 'ACCEPTED',
    203 => 'NON_AUTHORITATIVE_INFORMATION',
    204 => 'NO_CONTENT',
    205 => 'RESET_CONTENT',
    206 => 'PARTIAL_CONTENT',
    207 => 'MULTI_STATUS',
    300 => 'MULTIPLE_CHOICES',
    301 => 'MOVED_PERMANENTLY',
    302 => 'FOUND',
    303 => 'SEE_OTHER',
    304 => 'NOT_MODIFIED',
    305 => 'USE_PROXY',
    307 => 'TEMPORARY_REDIRECT',
    400 => 'BAD_REQUEST',
    401 => 'UNAUTHORIZED',
    402 => 'PAYMENT_REQUIRED',
    403 => 'FORBIDDEN',
    404 => 'NOT_FOUND',
    405 => 'METHOD_NOT_ALLOWED',
    406 => 'NOT_ACCEPTABLE',
    407 => 'PROXY_AUTHENTICATION',
    408 => 'REQUEST_TIMEOUT',
    409 => 'CONFLICT',
    410 => 'GONE',
    411 => 'LENGTH_REQUIRED',
    412 => 'PRECONDITION_FAILED',
    413 => 'REQUEST_ENTITY_TOO_LARGE',
    414 => 'REQUEST_URI_TOO_LONG',
    415 => 'UNSUPPORTED_MEDIA_TYPE',
    416 => 'REQUESTED_RANGE_NOT_SATISFIED',
    417 => 'EXPECTATION_FAILED',
    422 => 'UNPROCESSABLE_ENTITY',
    423 => 'LOCKED',
    424 => 'FAILED_DEPENDENCY',
    500 => 'INTERNAL_SERVER_ERROR',
    501 => 'NOT_IMPLEMENTED',
    502 => 'BAD_GATEWAY',
    503 => 'SERVICE_UNAVAILABLE',
    504 => 'GATEWAY_TIMEOUT',
    506 => 'INSUFFICIENT_STORAGE',
  );
}

class RestRequest {
  static $_TSPEC;

  public $method = null;
  public $uri = null;
  public $parameters = null;
  public $headers = null;
  public $body = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'method',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'uri',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'parameters',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::STRING,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::STRING,
            ),
          ),
        4 => array(
          'var' => 'headers',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::STRING,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::STRING,
            ),
          ),
        5 => array(
          'var' => 'body',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['method'])) {
        $this->method = $vals['method'];
      }
      if (isset($vals['uri'])) {
        $this->uri = $vals['uri'];
      }
      if (isset($vals['parameters'])) {
        $this->parameters = $vals['parameters'];
      }
      if (isset($vals['headers'])) {
        $this->headers = $vals['headers'];
      }
      if (isset($vals['body'])) {
        $this->body = $vals['body'];
      }
    }
  }

  public function getName() {
    return 'RestRequest';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->method);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->uri);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::MAP) {
            $this->parameters = array();
            $_size0 = 0;
            $_ktype1 = 0;
            $_vtype2 = 0;
            $xfer += $input->readMapBegin($_ktype1, $_vtype2, $_size0);
            for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
            {
              $key5 = '';
              $val6 = '';
              $xfer += $input->readString($key5);
              $xfer += $input->readString($val6);
              $this->parameters[$key5] = $val6;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::MAP) {
            $this->headers = array();
            $_size7 = 0;
            $_ktype8 = 0;
            $_vtype9 = 0;
            $xfer += $input->readMapBegin($_ktype8, $_vtype9, $_size7);
            for ($_i11 = 0; $_i11 < $_size7; ++$_i11)
            {
              $key12 = '';
              $val13 = '';
              $xfer += $input->readString($key12);
              $xfer += $input->readString($val13);
              $this->headers[$key12] = $val13;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->body);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('RestRequest');
    if ($this->method !== null) {
      $xfer += $output->writeFieldBegin('method', TType::I32, 1);
      $xfer += $output->writeI32($this->method);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->uri !== null) {
      $xfer += $output->writeFieldBegin('uri', TType::STRING, 2);
      $xfer += $output->writeString($this->uri);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->parameters !== null) {
      if (!is_array($this->parameters)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('parameters', TType::MAP, 3);
      {
        $output->writeMapBegin(TType::STRING, TType::STRING, count($this->parameters));
        {
          foreach ($this->parameters as $kiter14 => $viter15)
          {
            $xfer += $output->writeString($kiter14);
            $xfer += $output->writeString($viter15);
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->headers !== null) {
      if (!is_array($this->headers)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('headers', TType::MAP, 4);
      {
        $output->writeMapBegin(TType::STRING, TType::STRING, count($this->headers));
        {
          foreach ($this->headers as $kiter16 => $viter17)
          {
            $xfer += $output->writeString($kiter16);
            $xfer += $output->writeString($viter17);
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->body !== null) {
      $xfer += $output->writeFieldBegin('body', TType::STRING, 5);
      $xfer += $output->writeString($this->body);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class RestResponse {
  static $_TSPEC;

  public $status = null;
  public $headers = null;
  public $body = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'status',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'headers',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::STRING,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::STRING,
            ),
          ),
        3 => array(
          'var' => 'body',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['status'])) {
        $this->status = $vals['status'];
      }
      if (isset($vals['headers'])) {
        $this->headers = $vals['headers'];
      }
      if (isset($vals['body'])) {
        $this->body = $vals['body'];
      }
    }
  }

  public function getName() {
    return 'RestResponse';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->status);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::MAP) {
            $this->headers = array();
            $_size18 = 0;
            $_ktype19 = 0;
            $_vtype20 = 0;
            $xfer += $input->readMapBegin($_ktype19, $_vtype20, $_size18);
            for ($_i22 = 0; $_i22 < $_size18; ++$_i22)
            {
              $key23 = '';
              $val24 = '';
              $xfer += $input->readString($key23);
              $xfer += $input->readString($val24);
              $this->headers[$key23] = $val24;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->body);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('RestResponse');
    if ($this->status !== null) {
      $xfer += $output->writeFieldBegin('status', TType::I32, 1);
      $xfer += $output->writeI32($this->status);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->headers !== null) {
      if (!is_array($this->headers)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('headers', TType::MAP, 2);
      {
        $output->writeMapBegin(TType::STRING, TType::STRING, count($this->headers));
        {
          foreach ($this->headers as $kiter25 => $viter26)
          {
            $xfer += $output->writeString($kiter25);
            $xfer += $output->writeString($viter26);
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->body !== null) {
      $xfer += $output->writeFieldBegin('body', TType::STRING, 3);
      $xfer += $output->writeString($this->body);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

?>