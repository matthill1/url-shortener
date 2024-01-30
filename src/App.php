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

    public function test()
    {
        $this->migrate();
        $result = $this->db->query('SELECT * FROM urls');
        $output = [];
    
        foreach ($result as $row) {
            $output[] = ['id' => $row->id, 'name' => $row->name];
        }
        
        return json_encode($output);
    }

    public function checkTables()
    {
        $this->migrate();

        $tables = $this->db->query('SHOW TABLES')->fetchAll();

        if (count($tables) > 0) {
            return $this->response('Tables exist in the database.', 200);
        } else {
            return $this->response('No tables found in the database.', 404);
        }
    }



    private function shortenUrl($url): Response {
    
        /* Add some validation here -- both if the URL already exists +
            if (!$this->isValidURL($url)) {
                return $this->response('Invalid URL', 400);
            }
        */

        $shortCode = substr(md5(time() . $url), 0, 6);
        $currentDateTime = new \DateTime();

        $query = $this->db->query(
            'INSERT INTO urls (url, short_code, created_at) 
            VALUES (?, ?, ?)', 
            $url, 
            $shortCode, 
            $currentDateTime->format('Y-m-d H:i:s')
        );

        if ($query) {
            return $this->response($shortCode, 200);
        } else {
            // Handle the error if the query fails
            return $this->response('Error inserting data', 500);
        }

        return $this->response($shortCode, 200);
    }

    public function handle(Request $request): Response
    {   
        $pathInfo = $request->getPathInfo();
        $segments = explode('/', $pathInfo, 3);

        $action = isset($segments[1]) ? $segments[1] : '';
        $url = isset($segments[2]) ? $segments[2] : '';

        switch ($action) {

            case "test":
                $testResult = $this->test();
                return $this->response($testResult, 200);

            case "shorten":
                // Store a url in the database and return a shortened url...
                return $this->shortenUrl($url);
            case "stats":
                // Get stats about a given url and return a response...

            case "check-tables":
                // Check if any tables exist in the database
                return $this->checkTables();

            default:
                // Fetch a short url, update the hit counter, and return appropriate response (e.g. a Redirect or 404 Not Found)
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
