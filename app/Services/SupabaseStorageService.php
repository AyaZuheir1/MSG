<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SupabaseStorageService
{
    protected $client;
    protected $url;
    protected $bucket;
    protected $anonKey;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL') . "/storage/v1/object";
        $this->bucket = env('SUPABASE_BUCKET');
        $this->anonKey = env('SUPABASE_ANON_KEY');

        $this->client = new Client([
            'headers' => [
                'Authorization' => "Bearer {$this->anonKey}",
                'apikey' => $this->anonKey,
            ]
        ]);
    }


    public function uploadFile($file, $path)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = "$path/$fileName";
        
        try {
            $response = $this->client->request('POST', "{$this->url}/{$this->bucket}/$filePath", [
                'headers' => [
                    'Authorization' => "Bearer {$this->anonKey}",
                    'apikey' => $this->anonKey,
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => fopen($file->getPathname(), 'r'),
            ]);
            
            return [
                'file_name' => $fileName,
                'file_url'  => env('SUPABASE_URL') . "/storage/v1/object/public/{$this->bucket}/$filePath",
            ];
        } catch (RequestException $e) {
           return  $e->getMessage();
        }
    }
    
}
