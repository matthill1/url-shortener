<?php 

require_once './vendor/autoload.php';
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Nette\Database\Connection;
use Nette\Database\Row;

class ServerTests extends TestCase
{
    private static $shortcode = null;

    public function testShortenEndpointWithInvalidUrl(): void
    {
        $client = new Client(['http_errors' => false]);

        $shortcode = self::$shortcode;

        $result = $client->get('http://localhost:8000/shorten?url=mn;jgrklagna');

        $this->assertEquals(400, $result->getStatusCode(), 'Response should be bad request due to invalid url');
    }

    public function testShortenEndpointWithNoUrl(): void
    {
        $client = new Client(['http_errors' => false]);

        $shortcode = self::$shortcode;

        $result = $client->get('http://localhost:8000/shorten');

        $this->assertEquals(400, $result->getStatusCode(), 'Response should be bad request due to missing url');
    }

    public function testShortenEndpoint(): void
    {
        $client = new Client(['http_errors' => false]);

        $result = $client->get('http://localhost:8000/shorten?url=www.google.com');

        $this->assertEquals(200, $result->getStatusCode(), 'Response should be succesfull');


        $responseBody = json_decode((string) $result->getBody(), true);
        $shortcode = $responseBody['short_code'] ?? '';

        $this->assertNotEmpty($shortcode, 'Response should return a short code');

        self::$shortcode = $shortcode;
    }

    public function retestShortenEndpointWithSameURL(): void
    {
        $client = new Client(['http_errors' => false]);

        $result = $client->get('http://localhost:8000/shorten?url=www.google.com');

        $this->assertEquals(200, $result->getStatusCode(), 'Response should be successfull');


        $responseBody = json_decode((string) $result->getBody(), true);
        $responseShortcode = $responseBody['short_code'] ?? '';

        $this->assertNotEmpty($shortcode, 'Response should return a short code');

        $this->assertEquals(self::$shortcode, $responseShortcode, 'Response should be the same short code as this is the same URL shortened twice');
    }

    public function testStatsEndpointWithInvalidShortCode(): void
    {
        $client = new Client(['http_errors' => false]);

        $shortcode = self::$shortcode;

        $result = $client->get('http://localhost:8000/stats?short_code=invalidcode');

        $this->assertEquals(404, $result->getStatusCode(), 'Response should be not found, as this is not a short code which exists ');
    }

    public function testStatsEndpointWithMissingShortCode(): void
    {
        $client = new Client(['http_errors' => false]);

        $shortcode = self::$shortcode;

        $result = $client->get('http://localhost:8000/stats?');

        $this->assertEquals(400, $result->getStatusCode(), 'Response should be bad request due to missing short code');
    }


    public function testStatsEndpoint(): void
    {
        $client = new Client(['http_errors' => false]);

        $shortcode = self::$shortcode;

        $result = $client->get('http://localhost:8000/stats?short_code='.$shortcode);

        $this->assertEquals(200, $result->getStatusCode(), 'Response should be successfull');

        $responseBody = json_decode((string) $result->getBody(), true);

        $responseShortcode = $responseBody['short_code'] ?? '';
        $this->assertEquals($shortcode, $responseShortcode, 'Response should contain the same short code as requested');

        $originalURL = $responseBody['url'] ?? '';
        $this->assertEquals('www.google.com', $originalURL, 'Response should contain www.google.com as the url matching the short code');

        $hitCount = $responseBody['hit_counter'] ?? '';
        $this->assertEquals(1, $hitCount, 'Response should contain a hit count of 1');  
        //Unsure if this is part of spec - inital shorten initialises hit = 1
    }

    public function teseBaseURLEndpoint(): void
    {
        $client = new Client(['http_errors' => false]);

        $result = $client->get('http://localhost:8000/');

        $this->assertEquals(400, $result->getStatusCode(), 'Response should be bad request due to missing short code ');

    }


    /*
    // Issues making this testcase run.  
    // This testcase will result in a redirect to www.google.com when following in cURL
    // Not adept enough with this testing library to see where I'm going wrong.

    public function testBaseUrlRedirect(): void
    {
        $client = new Client(
            ['http_errors' => false,
            'allow_redirects' => [
                'max'             => 10,
                'strict'          => true, 
                'referer'         => true,
                'track_redirects' => true,
            ]
        ]);

        $result = $client->get('http://localhost:8000/?short_code='.$shortcode);

        $this->assertEquals(301, $result->getStatusCode(), 'Response should redirect with valid short code );

        $headers = $result->getHeaders();

        foreach ($headers as $name => $values) {
            echo $name . ': ' . implode(', ', $values) . "\n";
        }
    }
    */


}
