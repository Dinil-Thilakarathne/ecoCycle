<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Services\WasteCategoryEventService;

class WasteCategoryUpdatesController extends BaseController
{
    private WasteCategoryEventService $eventService;

    public function __construct()
    {
        $this->eventService = new WasteCategoryEventService();
    }

    /**
     * Poll for waste category updates
     * GET /api/waste-categories/updates
     *
     * Query params:
     * - since: timestamp of last update (returns newer events)
     * - limit: number of events to return (default: 50)
     */
    public function getUpdates(Request $request): Response
    {
        $since = $request->get('since', 0);
        $limit = (int)$request->get('limit', 50);

        if ($limit > 100) {
            $limit = 100;
        }

        try {
            $events = $this->eventService->getRecentEvents($limit);

            // Filter events based on since parameter
            if ($since > 0) {
                $sinceTimestamp = (int)$since;
                $events = array_filter($events, static function ($event) use ($sinceTimestamp) {
                    $eventTime = strtotime($event['created_at'] ?? '');
                    return $eventTime > $sinceTimestamp;
                });
            }

            return Response::json([
                'events' => array_values($events),
                'timestamp' => time()
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch updates', 500, [
                'detail' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get current timestamp for client synchronization
     * GET /api/waste-categories/server-time
     */
    public function getServerTime(Request $request): Response
    {
        return Response::json([
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s')
        ]);
    }
}
