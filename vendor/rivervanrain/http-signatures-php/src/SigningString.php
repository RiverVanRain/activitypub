<?php

namespace HttpSignatures;

use Psr\Http\Message\RequestInterface;

class SigningString
{
    /** @var HeaderList */
    private $headerList;

    /** @var RequestInterface */
    private $message;

    /**
     * @param RequestInterface $message
     */
    public function __construct(HeaderList $headerList, $message)
    {
        $this->headerList = $headerList;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function string()
    {
        return implode("\n", $this->lines());
    }

    /**
     * @return array
     */
    private function lines()
    {
        if (is_null($this->headerList->names)) {
            return [];
        } else {
            return array_map(
              [$this, 'line'],
              $this->headerList->names
          );
        }
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws SignedHeaderNotPresentException
     */
    private function line($name)
    {
        if ('(request-target)' == $name) {
            return $this->requestTargetLine();
        } else {
            return sprintf('%s: %s', $name, $this->headerValue($name));
        }
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws SignedHeaderNotPresentException
     */
    private function headerValue($name)
    {
        if ($this->message->hasHeader($name)) {
            $header = '';
            $values = $this->message->getHeader($name);
            while (sizeof($values) > 0) {
                $header = $header.$values[0];
                array_shift($values);
                if (sizeof($values) > 0) {
                    $header = $header.', ';
                }
            }
            // $header = $this->message->getHeader($name);

            return $header;
        // return end($header);
        } else {
            throw new SignedHeaderNotPresentException("Header '$name' not in message");
        }
    }

    /**
     * @return string
     */
    private function requestTargetLine()
    {
        return sprintf(
            '(request-target): %s %s',
            strtolower($this->message->getMethod()),
            $this->message->getRequestTarget()
        );
    }
}
