<?php

namespace Core;

/**
 * Page Router - Next.js style routing for pages
 */
class PageRouter
{
    /**
     * Auto-register page routes based on file structure
     */
    public static function registerPageRoutes(Router $router): void
    {
        // Index page (/)
        $router->get('/', function ($request) {
            $page = new \Pages\IndexPage();
            return $page->render($request);
        });

        // About page (/about)
        $router->get('/about', function ($request) {
            $page = new \Pages\AboutPage();
            return $page->render($request);
        });

        // Dynamic pages can be added here
        // e.g., /blog/[slug], /user/[id], etc.
    }

    /**
     * Create API routes (like Next.js api/ folder)
     */
    public static function registerApiRoutes(Router $router): void
    {
        $router->group(['prefix' => 'api'], function ($router) {

            // GET /api/stats
            $router->get('/stats', function ($request) {
                return response()->json([
                    'visitors' => rand(1000, 5000),
                    'pages' => 12,
                    'uptime' => '99.9%',
                    'timestamp' => date('c')
                ]);
            });

            // POST /api/contact
            $router->post('/contact', function ($request) {
                $data = $request->all();

                // Validate and process contact form
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your message!',
                    'data' => $data
                ]);
            });

            // GET /api/user/[id]
            $router->get('/user/{id}', function ($request) {
                $id = $request->get('id');

                return response()->json([
                    'user' => [
                        'id' => $id,
                        'name' => 'User ' . $id,
                        'email' => 'user' . $id . '@example.com',
                        'role' => 'user'
                    ]
                ]);
            });

            // POST /api/bidding/approve - lightweight demo handler
            $router->post('/bidding/approve', function ($request) {
                $json = $request->json();
                $data = is_array($json) ? $json : $request->all();
                $biddingId = $data['biddingId'] ?? null;

                if (!$biddingId) {
                    return response()->json(['success' => false, 'error' => 'Missing biddingId'], 400);
                }

                $dummy = require base_path('config/dummy.php');
                $found = null;
                foreach ($dummy['bidding_rounds'] as $round) {
                    if (($round['id'] ?? '') === $biddingId) {
                        $found = $round;
                        break;
                    }
                }

                if (!$found) {
                    return response()->json(['success' => false, 'error' => 'Bidding round not found'], 404);
                }

                if (($found['status'] ?? '') !== 'completed') {
                    return response()->json(['success' => false, 'error' => 'Bidding round must be completed before approving'], 400);
                }

                if (empty($found['biddingCompany']) || empty($found['currentHighestBid'])) {
                    return response()->json(['success' => false, 'error' => 'No valid winning bid to approve'], 400);
                }

                // Simulate awarding the lot (no persistent DB in dummy mode)
                $found['status'] = 'awarded';
                $found['awardedCompany'] = $found['biddingCompany'];
                $found['awardedAt'] = date('c');

                return response()->json(['success' => true, 'round' => $found]);
            });

            // POST /api/bidding/reject - lightweight demo handler
            $router->post('/bidding/reject', function ($request) {
                $json = $request->json();
                $data = is_array($json) ? $json : $request->all();
                $biddingId = $data['biddingId'] ?? null;
                $reason = $data['reason'] ?? null;

                if (!$biddingId) {
                    return response()->json(['success' => false, 'error' => 'Missing biddingId'], 400);
                }

                $dummy = require base_path('config/dummy.php');
                $found = null;
                foreach ($dummy['bidding_rounds'] as $round) {
                    if (($round['id'] ?? '') === $biddingId) {
                        $found = $round;
                        break;
                    }
                }

                if (!$found) {
                    return response()->json(['success' => false, 'error' => 'Bidding round not found'], 404);
                }

                if (($found['status'] ?? '') !== 'completed') {
                    return response()->json(['success' => false, 'error' => 'Bidding round must be completed before rejecting'], 400);
                }

                // Simulate cancelling the round
                $found['status'] = 'cancelled';
                if ($reason)
                    $found['rejectionReason'] = $reason;
                $found['rejectedAt'] = date('c');

                return response()->json(['success' => true, 'round' => $found]);
            });
        });
    }
}
