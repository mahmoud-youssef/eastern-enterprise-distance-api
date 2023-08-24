<?php

namespace App\Service;

use League\Csv\Reader;
use League\Csv\Writer;
use App\Service\ApiService;
use App\Service\GlobalVariableService;
use Location\Coordinate;
use Location\Distance\Vincenty;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GeolocationService
{
    private HttpClientInterface $httpClient;
    private $apiService;
    private $globalVariableService;

    public function __construct(HttpClientInterface $httpClient,KernelInterface $kernel,ApiService $apiService,GlobalVariableService $globalVariableService)
    {
        $this->httpClient = $httpClient;
        $this->kernel = $kernel;
        $this->apiService = $apiService;
        $this->globalVariableService = $globalVariableService;
    }

    public function calculateDistance($latitude1,$longitude1,$latitude2,$longitude2)
    {
        // Radius of the Earth in kilometers
        $earthRadius = 6371;

        // Convert latitudes and longitudes to radians
        $lat1Rad = deg2rad($latitude1);
        $lat2Rad = deg2rad($latitude2);
        $lon1Rad = deg2rad($longitude1);
        $lon2Rad = deg2rad($longitude2);

        // Calculate the differences in latitude and longitude
        $latDiff = $lat2Rad - $lat1Rad;
        $lonDiff = $lon2Rad - $lon1Rad;

        // Calculate the central angle between the two points
        $centralAngle = 2 * asin(sqrt(sin($latDiff / 2) * sin($latDiff / 2) + cos($lat1Rad) * cos($lat2Rad) * sin($lonDiff / 2) * sin($lonDiff / 2)));

        // Calculate the distance using the Haversine formula
        $distance = $earthRadius * $centralAngle;

        return $distance;
    }

    public function writeOutPut($result, $namesArray)
    {
        // Specify the path to the CSV file
        $csvFileOutPath = $this->kernel->getProjectDir() . '/public/distances.csv';
        // Create a new CSV writer instance
        $csvWriter = Writer::createFromPath($csvFileOutPath, 'w');

        // Write the header row
        $csvWriter->insertOne(['Sortnumber', 'Distance', 'Name', 'Address']);

        if (array_key_exists('Adchieve HQ', $result)) {
            unset($result['Adchieve HQ']);
        }

        $sortnumber = 1;
        // Write the data rows
        foreach ($result as $key => $value) {
            $csvWriter->insertAll([
                [$sortnumber, strval(number_format($value, 2)) . " km", $key, $namesArray[$key]]
            ]);
            $sortnumber += 1;
        }

    }

    public function sendRequest()
    {
        // Path to the CSV file
        $csvFilePath = $this->kernel->getProjectDir() . '/public/addresses.csv';
        // Create a new CSV reader
        $csv = Reader::createFromPath($csvFilePath, 'r');
        // Read all records from the CSV file
        $data = $csv->getRecords();

        $hostUrl = $this->globalVariableService->getHostBaseUrl();
        $accessKey = $this->globalVariableService->getAccessKey();

        $distanceArray = [];
        $namesArray = [];
        $headQuartersLat = null;
        $headQuartersLng = null;

        foreach ($data as $record) {
            $string = implode("", $record);
            $array = explode(' - ', $string);
            $namesArray[$array[0]] = $array[1];
            
            $response = $this->apiService->sendRequest('GET', $hostUrl . "?access_key=".$accessKey. "&query=".$array[1]);
            $responseStatusCode = $response->getStatusCode();

            if ($responseStatusCode === 400) {
                $data = [
                    'message' => "Somthing went wrong with the positionstack please try again",
                ];

                return $data;
            }else{
                $httpResponse = $response->getContent();
                $data = json_decode($httpResponse, true); // Convert JSON to associative array
            }

            // Access values by key
            $value = $data['data'][0];
            $headQuartersLat == null ? $headQuartersLat = $value['latitude'] : $headQuartersLat;
            $headQuartersLng == null ? $headQuartersLng = $value['longitude'] : $headQuartersLng;
            // Calculate the distance using the Vincenty algorithm
            $distance = $this->calculateDistance($value['latitude'], $value['longitude'], $headQuartersLat, $headQuartersLng);

            $distanceArray[$array[0]] = $distance;
        }
        asort($distanceArray);

        $this->writeOutPut($distanceArray, $namesArray);
    }
}