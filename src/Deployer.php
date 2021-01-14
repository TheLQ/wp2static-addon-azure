<?php

namespace WP2StaticAzure;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use GuzzleHttp\Client;

/**
 * Azure Deployer
 *
 * - uses Azure's digest method, doesn't need WP2Static's DeployCache
 */
class Deployer {

    public function upload_files( string $processed_site_path ) : void {
        $deployed = 0;
        $cache_skipped = 0;
        $file_hashes = [];
        $filename_path_hashes = [];

        if ( ! is_dir( $processed_site_path ) ) {
            return;
        }

        // required setings
         // $site_id = Controller::getValue( 'siteID' );
        // $access_token = \WP2Static\CoreOptions::encrypt_decrypt(
        //     'decrypt',
        //     Controller::getValue( 'accessToken' )
        // );
        

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $processed_site_path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        // Get all deployable file hashes to send to Netlify
        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );

            if ( $base_name != '.' && $base_name != '..' ) {
                $real_filepath = realpath( $filename );

                if ( ! $real_filepath ) {
                    $err = 'Trying to deploy unknown file: ' . $filename;
                    \WP2Static\WsLog::l( $err );
                    continue;
                }

                // Standardise all paths to use / (Windows support)
                $filename = str_replace( '\\', '/', $filename );

                if ( ! is_string( $filename ) ) {
                    continue;
                }

                $remote_path = str_replace( $processed_site_path, '', $filename );
                // Azure's Content-MD5 format
                $hash = base64_encode(md5_file($filename, true));
                $file_hashes[ $remote_path ] = $hash;
                $filename_path_hashes[ $hash ] = [ $filename, $remote_path ];
            }
        }

        // Required settings
        $storageAccountName = "";
        $storageContainer = '';
        $storageFolder = "";
        $sasToken = "";
        require 'creds.php';

        if ($storageFolder == null) {
            $storageFolder = "";
        }

        // Get all files in container
        $client = new Client( [ 'base_uri' => "https://$storageAccountName.blob.core.windows.net" ] );

        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Accept'        => 'application/json',
        ];

        $url = "/$storageContainer?restype=container&comp=list&$sasToken";
        $res = $client->request(
            'GET',
            $url,
            [
                'http_errors' => false,
            ],
        );
        if ($res->getStatusCode() != 200) {
            if (WP_DEBUG) {
                \WP2Static\WsLog::l(
                    "List Blobs $url failed!" . print_r($res, true) . $res->getBody()
                );
            } else {
                \WP2Static\WsLog::l(
                    "List Blobs failed!"
                );
            }
            return;
        }
        $response_body = $res->getBody();
        $response = new \SimpleXMLElement($response_body);
        if ($response->getName() != "EnumerationResults") {
            \WP2Static\WsLog::l(
                "Azure deploy failed! Cannot list container: $response_body"
            );
            return;
        }

        // Extract hash to path map
        $azure_hashes = [];
        foreach ($response->Blobs->Blob as $blob) {
            $path = (string)$blob->Name;
            $hash = (string)$blob->Properties->{'Content-MD5'};
            if ($storageFolder != null && strpos($path, $storageFolder) !== 0) {
                // unrelated content
                continue;
            }
            $azure_hashes[$hash] = $path;
        }
        
        // Determine what needs to be uploaded
        $cache_skipped = 0;
        foreach ( $filename_path_hashes as $hash => $file_info ) {
            $filename = $file_info[0];
            $remote_path = $file_info[1];

            // If Azure doesn't already have this
            if ( !array_key_exists( $hash, $azure_hashes ) ) {
                $url = "/$storageContainer/$storageFolder$remote_path";
                $azure_hash_existing = array_search("$storageFolder$remote_path", $azure_hashes);
                \WP2Static\WsLog::l(
                    "PUT $url because existing hash $azure_hash_existing new $hash "
                );
                $res = $client->request(
                    'PUT',
                    "$url?$sasToken",
                    [
                        'headers' => [
                            "Content-Length" => filesize($filename),
                            "x-ms-blob-type" => "BlockBlob",
                        ],
                        'body' => fopen( $filename, 'r' ),
                        'http_errors' => false,
                    ],
                );
                if ($res->getStatusCode() != 201) {
                    if (WP_DEBUG) {
                        \WP2Static\WsLog::l(
                            "PUT $url failed!" . print_r($res, true) . $res->getBody()
                        );
                    } else {
                        \WP2Static\WsLog::l(
                            "PUT $remote_path failed!"
                        );
                    }
                    return;
                }
                if ($azure_hash_existing !== FALSE) {
                    unset($azure_hashes[$azure_hash_existing]);
                }
                
                $deployed++;
            } else {
                unset($azure_hashes[$hash]);
                if (WP_DEBUG) {
                    \WP2Static\WsLog::l(
                        "Already exists in Azure $remote_path"
                    );
                }
                $cache_skipped++;
            }
        }

        \WP2Static\WsLog::l(
            "Azure deploy remaining." . print_r($azure_hashes, true)
        );
        

        \WP2Static\WsLog::l(
            "Azure deploy complete. $deployed deployed, $cache_skipped unchanged."
        );
    }
}
