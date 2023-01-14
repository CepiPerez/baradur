<?php 

class SimpleS3
{
    private $accessKeyId;
    private $secretKey;
    private $sessionToken;
    private $region;
    private $endpoint;
    private $timeoutInSeconds = 5;

    public static function fromEnvironmentVariables($region)
    {
        return new self(
            $_SERVER['AWS_ACCESS_KEY_ID'],
            $_SERVER['AWS_SECRET_ACCESS_KEY'],
            $_SERVER['AWS_SESSION_TOKEN'],
            $region
        );
    }

    public function __construct($accessKeyId, $secretKey, $sessionToken, $region, $endpoint = null)
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;
        $this->sessionToken = $sessionToken;
        $this->region = $region;
        $this->endpoint = $endpoint;
    }

    public function setTimeout($timeoutInSeconds)
    {
        $this->timeoutInSeconds = $timeoutInSeconds;
        return $this;
    }

    /**
     * @param Array<string, string> $headers
     * @return Response
     * @throws RuntimeException If the request failed.
     */
    public function get($bucket, $key, $headers = array())
    {
        return $this->s3Request('GET', $bucket, $key, $headers);
    }

    public function getInfo($bucket, $key, $headers = array())
    {
        return $this->s3Request('GET', $bucket, $key, $headers, '', true, 'attributes');
    }

    /**
     * `get()` will throw if the object doesn't exist.
     * This method will return a 404 status and not throw instead.
     *
     * @param Array<string, string> $headers
     * @return Response
     * @throws RuntimeException If the request failed.
     */
    public function getIfExists($bucket, $key, $headers = array())
    {
        return $this->s3Request('GET', $bucket, $key, $headers, '', false);
    }

    /**
     * @param Array<string, string> $headers
     * @return Response
     * @throws RuntimeException If the request failed.
     */
    public function put($bucket, $key, $content, $headers = array())
    {
        return $this->s3Request('PUT', $bucket, $key, $headers, $content);
    }

    /**
     * @param Array<string, string> $headers
     * @return Response
     * @throws RuntimeException If the request failed.
     */
    public function delete($bucket, $key, $headers = array())
    {
        return $this->s3Request('DELETE', $bucket, $key, $headers);
    }

    /**
     * @param Array<string, string> $headers
     * @return Response
     * @throws RuntimeException If the request failed.
     */
    private function s3Request($httpVerb, $bucket, $key, $headers, $body = '', $throwOn404 = true, $queryString='')
    {
        $uriPath = str_replace('%2F', '/', rawurlencode("{$bucket}/{$key}"));
        $uriPath = '/' . ltrim($uriPath, '/'); //. ($queryString? "?$queryString" : '');
        //$queryString = $queryString ? "?$queryString" : '';
        $hostname = $this->endpoint; //$this->getHostname($bucket);
        $headers['host'] = $hostname;

        // Sign the request via headers
        $headers = $this->signRequest($httpVerb, $uriPath, ''/* $queryString */, $headers, $body);

        /* if ($this->endpoint) {
            $url = $this->endpoint;
        } else {
            $url = "https://$hostname";
        } */
        $url = "{$this->endpoint}/{$bucket}/{$key}" . ($queryString? '?'.$queryString : '');

        dump($url);

        list($status, $body, $responseHeaders) = $this->curlRequest($httpVerb, $url, $headers, $body);


        $xml   = simplexml_load_string($body);
        $array = json_decode(json_encode((array) $xml), true);
        $array = array($xml->getName() => $array);
        dump($status); dump($array); dump($responseHeaders); die();


        $shouldThrow404 = $throwOn404 && ($status === 404);
        if ($shouldThrow404 || $status < 200 || ($status >= 400 && $status !== 404)) {
            $errorMessage = '';
            if ($body) {
                //dump($body);
                $dom = new DOMDocument;
                if (! $dom->loadXML($body)) {
                    throw new RuntimeException('Could not parse the AWS S3 response: ' . $body);
                }
                if ($dom->childNodes->item(0)->nodeName === 'Error') {
                    $errorMessage = $dom->childNodes->item(0)->textContent;
                }
            }
            throw $this->httpError($status, $errorMessage);
        }

        return [$status, $body, $responseHeaders];
    }

    /**
     * @param Array<string, string> $headers
     * @return Response
     * @throws RuntimeException If the request failed.
     */
    private function curlRequest($httpVerb, $url, $headers, $body)
    {
        $curlHeaders = array();
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "$name: $value";
        }

        $ch = curl_init($url);
        if (! $ch) {
            throw $this->httpError(null, 'could not create a CURL request for an unknown reason');
        }


        $responseHeadersAsString = '';
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $httpVerb,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeoutInSeconds,
            CURLOPT_POSTFIELDS => $body,
            // So that `curl_exec` returns the response body
            CURLOPT_RETURNTRANSFER => true,
            // Retrieve the response headers
            CURLOPT_HEADERFUNCTION => function ($c, $data) use (&$responseHeadersAsString) {
                $responseHeadersAsString .= $data;
                return strlen($data);
            },
        ]);

        /* if ($httpVerb=='HEAD') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } */

        $responseBody = curl_exec($ch);
        
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $success = $status !== 0 && $status !== 100 && $status !== 500 && $status !== 502 && $status !== 503;
        if ($responseBody === false || ! $success || curl_errno($ch) > 0) {
            curl_close($ch);
            throw $this->httpError($status, curl_error($ch));
        }
        curl_close($ch);

        $responseHeaders = iconv_mime_decode_headers(
            $responseHeadersAsString,
            ICONV_MIME_DECODE_CONTINUE_ON_ERROR,
            'UTF-8'
        );

        if (!$responseHeaders) $responseHeaders = array();

        return [$status, (string) $responseBody, $responseHeaders];
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string> Modified headers
     */
    private function signRequest($httpVerb, $uriPath, $queryString, $headers, $body)
    {
        $dateAsText = gmdate('Ymd');
        $timeAsText = gmdate('Ymd\THis\Z');
        $scope = "$dateAsText/{$this->region}/s3/aws4_request";
        $bodySignature = hash('sha256', $body);

        $headers['x-amz-date'] = $timeAsText;
        $headers['x-amz-content-sha256'] = $bodySignature;
        if ($this->sessionToken) {
            $headers['x-amz-security-token'] = $this->sessionToken;
        }

        // Ensure the headers always have the same order to have a valid AWS signature
        $headers = $this->sortHeadersByName($headers);

        // https://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-header-based-auth.html
        $headerNamesAsString = implode(';', array_map('strtolower', array_keys($headers)));
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= strtolower($key) . ':' . trim($value) . "\n";
        }

        $canonicalRequest = "$httpVerb\n$uriPath\n$queryString\n$headerString\n$headerNamesAsString\n$bodySignature";

        $stringToSign = "AWS4-HMAC-SHA256\n$timeAsText\n$scope\n" . hash('sha256', $canonicalRequest);
        $signingKey = hash_hmac(
            'sha256',
            'aws4_request',
            hash_hmac(
                'sha256',
                's3',
                hash_hmac(
                    'sha256',
                    $this->region,
                    hash_hmac('sha256', $dateAsText, 'AWS4' . $this->secretKey, true),
                    true
                ),
                true
            ),
            true
        );
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $headers['authorization'] = "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/$scope,SignedHeaders=$headerNamesAsString,Signature=$signature";

        return $headers;
    }

    private function getHostname($bucketName)
    {
        if ($this->region === 'us-east-1') return "$bucketName.s3.amazonaws.com";

        return "$bucketName.s3-{$this->region}.amazonaws.com";
    }

    private function httpError($status, $message)
    {
        return new RuntimeException("AWS S3 request failed: $status $message");
    }

    /**
     * @param Array<string, string> $headers
     * @return Array<string, string>
     */
    private function sortHeadersByName($headers)
    {
        ksort($headers, SORT_STRING | SORT_FLAG_CASE);
        return $headers;
    }
}