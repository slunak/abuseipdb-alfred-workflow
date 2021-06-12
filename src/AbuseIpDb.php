<?php

/*
 * Class AbuseIpDb
 * Author Serhiy Lunak
 */
class AbuseIpDb
{
    /**
     * AbuseIPDB API URL and path
     */
    const API_URL = "https://api.abuseipdb.com/api/v2/check";

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct($ip, $apiKey)
    {
        $this->ip = $ip;
        $this->apiKey = $apiKey;
    }

    /**
     * Validate entered IP address.
     * Keep validating until user enters valid IP address.
     */
    public function validateIP()
    {
        if (false === filter_var($this->ip, FILTER_VALIDATE_IP)) {
            $results = array(
                "items" => array(
                    array(
                        "title" => "Enter IP address",
                        "subtitle" => "Enter public IP address to check against AbuseIPDB",
                        "valid" => false,
                    )
                )
            );
            echo json_encode($results);
            die();
        }
    }

    /**
     * cURL request to AbuseIPDB API.
     */
    public function curl()
    {
        $curlOptions = array(
            CURLOPT_URL => self::API_URL."?ipAddress=".$this->ip,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Key: ".$this->apiKey,
            ),
        );

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $curlResponse = curl_exec($ch);
        curl_close($ch);

        return json_decode($curlResponse);
    }

    public function processResponse($decodedCurlResponse)
    {
        if (false === $decodedCurlResponse->data->isPublic) {
            $results = array(
                "items" => array(
                    array(
                        "title" => "Enter IP address",
                        "subtitle" => "Entered IP address is not public",
                        "valid" => false,
                    )
                )
            );
            echo json_encode($results);
            die();
        }

        $results = array(
            "items" => array(
                array(
                    "title" => "Abuse Confidence Score: ".$decodedCurlResponse->data->abuseConfidenceScore."%",
                    "subtitle" => "Country: ".$decodedCurlResponse->data->countryCode
                        .", ISP: ".$decodedCurlResponse->data->isp
                        .", Domain: ".$decodedCurlResponse->data->domain,
                    "arg" => "IP: ".$this->ip
                        ."\nConfidence Score: ".$decodedCurlResponse->data->abuseConfidenceScore."%"
                        ."\nCountry: ".$decodedCurlResponse->data->countryCode
                        ."\nISP: ".$decodedCurlResponse->data->isp
                        ."\nDomain Name: ".$decodedCurlResponse->data->domain
                        ."\nUsage Type: ".$decodedCurlResponse->data->usageType
                        ."\nURL: https://www.abuseipdb.com/check/".$this->ip
                        ."\n"
                    ,
                )
            )
        );
        echo json_encode($results);
        die();
    }
}

/**
 * Process command line arguments for IP and API key
 */
$arguments = getopt("i:k:");

/**
 * Create new AbuseIPDB class
 */
$abuseIpDb = new AbuseIpDb($arguments['i'], $arguments['k']);

/**
 * Validate entered IP address
 */
$abuseIpDb->validateIP();

/**
 * Submit request to the API and get response
 */
$decodedCurlResponse = $abuseIpDb->curl();

/**
 * Process cURL response and output results
 */
$abuseIpDb->processResponse($decodedCurlResponse);
