<?php

namespace App\Services\PermissionRequest;

use App\Models\PermissionRequest;
use App\Models\User;
use App\Services\NotificationPermissionService;
use App\Services\ViolationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionRequestService
{
    protected $creationService;
    protected $validationService;
    protected $updateService;
    protected $statusService;
    protected $queryService;
    protected $statisticsService;

    public function __construct(
        RequestCreationService $creationService,
        RequestValidationService $validationService,
        RequestUpdateService $updateService,
        RequestStatusService $statusService,
        RequestQueryService $queryService,
        RequestStatisticsService $statisticsService
    ) {
        $this->creationService = $creationService;
        $this->validationService = $validationService;
        $this->updateService = $updateService;
        $this->statusService = $statusService;
        $this->queryService = $queryService;
        $this->statisticsService = $statisticsService;
    }

    public function getAllRequests($filters = []): LengthAwarePaginator
    {
        return $this->queryService->getAllRequests($filters);
    }

    public function createRequest(array $data): array
    {
        return $this->creationService->createRequest($data);
    }

    public function createRequestForUser(int $userId, array $data): array
    {
        return $this->creationService->createRequestForUser($userId, $data);
    }

    public function updateRequest(PermissionRequest $request, array $data): array
    {
        return $this->updateService->updateRequest($request, $data);
    }

    public function updateStatus(PermissionRequest $request, array $data): array
    {
        return $this->statusService->updateStatus($request, $data);
    }

    public function resetStatus(PermissionRequest $request, string $responseType)
    {
        return $this->statusService->resetStatus($request, $responseType);
    }

    public function modifyResponse(PermissionRequest $request, array $data): array
    {
        return $this->statusService->modifyResponse($request, $data);
    }

    public function updateReturnStatus(PermissionRequest $request, int $returnStatus): array
    {
        return $this->statusService->updateReturnStatus($request, $returnStatus);
    }

    public function getRemainingMinutes(int $userId): int
    {
        return $this->validationService->getRemainingMinutes($userId);
    }

    public function getUsedMinutes(int $userId): int
    {
        return $this->validationService->getUsedMinutes($userId);
    }

    public function canRespond($user = null)
    {
        return $this->statusService->canRespond($user);
    }

    public function deleteRequest(PermissionRequest $request)
    {
        return $this->creationService->deleteRequest($request);
    }

    public function getUserRequests(int $userId): LengthAwarePaginator
    {
        return $this->queryService->getUserRequests($userId);
    }

    public function getAllowedUsers($user)
    {
        return $this->queryService->getAllowedUsers($user);
    }

    public function getStatistics($user, $dateStart, $dateEnd)
    {
        return $this->statisticsService->getStatistics($user, $dateStart, $dateEnd);
    }
}
