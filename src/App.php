<?php

namespace Shortener;

use Nette\Database\Connection;
use Nette\Database\Row;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class App
{
    public function __construct(private Connection $db)
    {
    }

    public function migrate()
    {
        $this->db->query(
            "DROP TABLE IF EXISTS `urls`;
            CREATE TABLE `urls` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `url` varchar(1024) DEFAULT '',
                `short_code` varchar(1024) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_accessed` timestamp NULL DEFAULT NULL,
                `hits` int NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }

    // A bit rough and ready
    private function isValidURL($url) {
        $pattern = '/^(https?:\/\/)?(www\.)?[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+(\/[a-zA-Z0-9-._~%]*)*$/';
        return preg_match($pattern, $url) === 1;
    }


    private function shortenUrl($url): Response {
    
        if (!$this->isValidURL($url)) {
            return $this->response('Invalid URL', 400);
        }

        $existingUrl = $this->db->query('SELECT * FROM urls WHERE url = ?', $url)->fetch();

        if($existingUrl){
            $this->db->query(
                'UPDATE urls SET hits = hits + 1 WHERE url = ?',
                $url
            );

            return $this->response($existingUrl->short_code, 200);
        }


        $shortCode = substr(md5(time() . $url), 0, 6);
        $currentDateTime = new \DateTime();

        $query = $this->db->query(
            'INSERT INTO urls (url, short_code, created_at, hits) 
            VALUES (?, ?, ?, ?)',
            $url, 
            $shortCode, 
            $currentDateTime->format('Y-m-d H:i:s'),
            1
        );

        if ($query) {
            return $this->response($shortCode, 200);
        } else {
            return $this->response('Error inserting data', 500);
        }

        return $this->response($shortCode, 200);
    }

    private function getStatsForShortCode($shortCode) {
        $query = $this->db->query(
            'SELECT url, short_code, hits, created_at FROM urls WHERE short_code = ?',
            $shortCode
        );
    
        $stats = $query->fetch();
    
        if ($stats !== false && $stats !== null) {
            $formattedStats = [
                'url' => $stats->url,
                'short_code' => $stats->short_code,
                'hit_counter' => $stats->hits,
                'created_at' => $stats->created_at,
            ];
    
            return $formattedStats;
        } else {
            return null;
        }
    }

    private function getOriginalURLForShortCode($shortCode)
    {
        $query = $this->db->query(
            'SELECT url FROM urls WHERE short_code = ?',
            $shortCode
        );

        $result = $query->fetch();

        if ($result !== false && $result !== null) {
            return $result->url;
        } else {
            return null;
        }
    }


    private function incrementHitCounter($shortCode)
    {
        $query = $this->db->query(
            'UPDATE urls SET hits = hits + 1 WHERE short_code = ?',
            $shortCode
        );
    }

    private function handleShortURL($shortCode) {
        $originalURL = $this->getOriginalURLForShortCode($shortCode);
    
        if ($originalURL !== null) {
            $this->incrementHitCounter($shortCode);
            $response = new RedirectResponse($originalURL, 301);
            $response->send();
            return;
        } else {
            return $this->response('Short URL not found', 404);
        }
    }

    public function handle(Request $request): Response
    {   
        $pathInfo = $request->getPathInfo();
        $segments = explode('/', $pathInfo, 3);

        $action = isset($segments[1]) ? $segments[1] : '';

        $queryString = $request->getQueryString();
        $shortCode = $request->query->get('short_code');
        $url =  $request->query->get('url');

        switch ($action) {

            case "test":
                return $this->response("Test", 200);

            case "shorten":
                return $this->shortenUrl($url);

            case "stats":

                if (!$shortCode) {
                    return $this->response('Short code not provided', 400);
                }

                $stats = $this->getStatsForShortCode($shortCode);
    
                if ($stats === null) {
                    return $this->response('Short code not found', 404);
                }
            
                return $this->response(json_encode($stats), 200);

            default:
                return $this->handleShortURL($shortCode);
        }

        return $this->response(content: 'Default message...');
    }

    private function response(string $content, int $status = 200, string $type =  'text/html'): Response
    {
        $response = new Response(
            'Content',
            $status,
            ['content-type' => $type]
        );

        $response->setContent($content);

        return $response->send();
    }
}
