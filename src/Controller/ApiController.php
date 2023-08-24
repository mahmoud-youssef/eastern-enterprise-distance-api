<?php

namespace App\Controller;

use App\Service\ApiService;
use App\Service\GlobalVariableService;
use App\Service\GeolocationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    private $apiService;
    private $globalVariableService;

    public function __construct(ApiService $apiService,GlobalVariableService $globalVariableService,GeolocationService $geolocationService)
    {
        $this->apiService = $apiService;
        $this->globalVariableService = $globalVariableService;
        $this->geolocationService = $geolocationService;
    }

    /**
     * @Route("/api/calculateDistance", name="calculate_distance", methods={"GET"})
     */
    public function calculateDistance(): JsonResponse
    {
        
        $this->globalVariableService->setHostBaseUrl($_SERVER['HOST_BASE_URL']);
        $this->globalVariableService->setAccessKey($_SERVER['ACCESS_KEY']);
        $this->geolocationService->sendRequest();
        $data = [
            'message' => "Result saved on distances.csv in location /public/distances.csv",
        ];

        return $this->json($data);
    }
}
