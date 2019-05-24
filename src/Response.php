<?php

declare(strict_types=1);

/**
 * Micro
 *
 * @author      Raffael Sahli <sahli@gyselroth.net>
 * @copyright   Copryright (c) 2015-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Http;

use Closure;
use SimpleXMLElement;

class Response
{
    /**
     * Possible output formats.
     */
    const OUTPUT_FORMATS = [
        'json' => 'application/json; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
        'text' => 'text/html; charset=utf-8',
    ];
    /**
     * Output format.
     *
     * @var string
     */
    protected $output_format = 'json';

    /**
     * Human readable output.
     *
     * @var bool
     */
    protected $pretty_format = false;

    /**
     * Headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Code.
     *
     * @var int
     */
    protected $code = 200;

    /**
     * Body.
     *
     * @var string
     */
    protected $body;

    /**
     * Init response.
     */
    public function __construct()
    {
        $this->setupFormats();
    }

    /**
     * Set header.
     *
     * @param string $header
     *
     * @return Response
     */
    public function setHeader(string $header, string $value): self
    {
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * Delete header.
     *
     * @param string $header
     *
     * @return Response
     */
    public function removeHeader(string $header): self
    {
        if (isset($this->headers[$header])) {
            unset($this->headers[$header]);
        }

        return $this;
    }

    /**
     * Get headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send headers.
     *
     * @return Response
     */
    public function sendHeaders(): self
    {
        foreach ($this->headers as $header => $value) {
            header($header.': '.$value);
        }

        return $this;
    }

    /**
     * Set response code.
     *
     * @param int $code
     *
     * @return Response
     */
    public function setCode(int $code): self
    {
        if (!array_key_exists($code, Http::STATUS_CODES)) {
            throw new Exception('invalid http code set');
        }

        $this->code = $code;

        return $this;
    }

    /**
     * Get response code.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Set body.
     *
     * @param mixed $body
     *
     * @return Response
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sends the actual response.
     */
    public function send(): void
    {
        $status = Http::STATUS_CODES[$this->code];
        $this->sendHeaders();
        header('HTTP/1.0 '.$this->code.' '.$status, true, $this->code);

        if (null === $this->body || 204 === $this->code) {
            return;
        }

        if ($this->body instanceof Closure) {
            $body = $this->body->call($this);

            return;
        }
        $body = $this->body;

        switch ($this->output_format) {
            case null:
            break;
            default:
            case 'json':
                echo $this->asJSON($body);

            break;
            case 'xml':
                echo $this->asXML($body);

            break;
            case 'text':
                echo $body;

            break;
        }
    }

    /**
     * Get output format.
     *
     * @return string
     */
    public function getOutputFormat(): string
    {
        return $this->output_format;
    }

    /**
     * Convert response to human readable output.
     *
     * @param bool $format
     *
     * @return Response
     */
    public function setPrettyFormat(bool $format): self
    {
        $this->pretty_format = (bool) $format;

        return $this;
    }

    /**
     * Set header Content-Length $body.
     *
     * @param string $body
     *
     * @return Response
     */
    public function setContentLength(string $body): self
    {
        header('Content-Length: '.strlen($body));

        return $this;
    }

    /**
     * Converts $body to pretty json.
     *
     * @param mixed $body
     *
     * @return string
     */
    public function asJSON($body): string
    {
        if ($this->pretty_format) {
            $result = json_encode($body, JSON_PRETTY_PRINT);
        } else {
            $result = json_encode($body);
        }

        if (false === $result) {
            return '';
        }

        $this->setContentLength($result);

        return $result;
    }

    /**
     * Converts mixed data to XML.
     *
     * @param mixed            $data
     * @param SimpleXMLElement $xml
     * @param string           $child_name
     *
     * @return string
     */
    public function toXML($data, SimpleXMLElement $xml, string $child_name): string
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    (is_int($k)) ? $this->toXML($v, $xml->addChild($child_name), $v) : $this->toXML($v, $xml->addChild(strtolower($k)), $child_name);
                } else {
                    (is_int($k)) ? $xml->addChild($child_name, $v) : $xml->addChild(strtolower($k), $v);
                }
            }
        } else {
            $xml->addChild($child_name, $data);
        }

        return $xml->asXML();
    }

    /**
     * Converts response to xml.
     *
     * @param mixed $body
     *
     * @return string
     */
    public function asXML($body): string
    {
        $root = new SimpleXMLElement('<response></response>');
        $raw = $this->toXML($body, $root, 'node');

        if ($this->pretty_format) {
            $raw = $this->prettyXml($raw);
        }

        $this->setContentLength($raw);

        return $raw;
    }

    /**
     * Pretty formatted xml.
     *
     * @param string $xml
     *
     * @return string
     */
    public function prettyXml(string $xml): string
    {
        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml);

        return $domxml->saveXML();
    }

    /**
     * Set the current output format.
     *
     * @param string $format
     *
     * @return Response
     */
    public function setOutputFormat(?string $format = null): self
    {
        if (null === $format) {
            $this->output_format = null;

            return $this;
        }

        if (!array_key_exists($format, self::OUTPUT_FORMATS)) {
            throw new Exception('invalid output format given');
        }

        $this->setHeader('Content-Type', self::OUTPUT_FORMATS[$format]);
        $this->output_format = $format;

        return $this;
    }

    /**
     * Setup formats.
     *
     * @return Response
     */
    public function setupFormats(): self
    {
        $pretty = array_key_exists('pretty', $_GET) && ('false' !== $_GET['pretty'] && '0' !== $_GET['pretty']);
        $this->setPrettyFormat($pretty);

        //through HTTP_ACCEPT
        if (isset($_SERVER['HTTP_ACCEPT']) && false === strpos($_SERVER['HTTP_ACCEPT'], '*/*')) {
            foreach (self::OUTPUT_FORMATS as $format) {
                if (false !== strpos($_SERVER['HTTP_ACCEPT'], $format)) {
                    $this->output_format = $format;

                    break;
                }
            }
        }

        $this->setOutputFormat($this->output_format);

        return $this;
    }
}
