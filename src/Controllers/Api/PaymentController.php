<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\Payment;
use Services\Payment\PaymentService;

class PaymentController extends BaseController
{
    private Payment $payments;
    private PaymentService $service;

    public function __construct()
    {
        $this->payments = new Payment();
        $this->service = new PaymentService($this->payments);
    }

    public function index(Request $request): Response
    {
        $limit = $request->query('limit') ? (int) $request->query('limit') : 50;
        $records = $this->payments->listRecent($limit);

        return Response::json([
            'data' => $records,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->mergeJsonBody($request);

        $payload = $this->validateStorePayload($request);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        try {
            $record = $this->service->createManualPayment($payload['data']);
        } catch (\InvalidArgumentException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to record payment', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Payment recorded',
            'data' => $record,
        ], 201);
    }

    public function update(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Payment id is required', 400);
        }

        $this->mergeJsonBody($request);
        $data = $request->all();

        // Extract updateable fields
        $updateData = [];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['type'])) $updateData['type'] = $data['type'];
        if (isset($data['amount'])) $updateData['amount'] = $data['amount'];
        if (isset($data['recipientId'])) $updateData['recipientId'] = $data['recipientId'];
        if (isset($data['txnId'])) $updateData['txnId'] = $data['txnId'];
        if (isset($data['gatewayResponse'])) $updateData['gatewayResponse'] = $data['gatewayResponse'];

        try {
            $record = $this->service->updatePayment($id, $updateData);
        } catch (\InvalidArgumentException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update payment', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Payment updated',
            'data' => $record,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Payment id is required', 400);
        }

        $record = $this->payments->findById($id);
        if (!$record) {
            return Response::errorJson('Payment not found', 404);
        }

        return Response::json(['data' => $record]);
    }

    public function customerPayments(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $status = $request->query('status') ?: null;
        $records = $this->payments->listCustomerPayments((int) $user['id'], 50, $status);

        return Response::json([
            'data' => $records,
        ]);
    }

    public function companyInvoices(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $status = $request->query('status') ?: null;
        $records = $this->payments->listCompanyInvoices((int) $user['id'], 50, $status);

        return Response::json([
            'data' => $records,
        ]);
    }

    private function validateStorePayload(Request $request): array
    {
        $data = $request->all();
        $errors = [];

        $recipientId = isset($data['recipientId']) ? (int) $data['recipientId'] : 0;
        if ($recipientId <= 0) {
            $errors['recipientId'] = 'Recipient id is required.';
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than zero.';
        }

        $type = isset($data['type']) ? strtolower((string) $data['type']) : 'payout';
        $allowedTypes = ['payment', 'payout', 'refund'];
        if (!in_array($type, $allowedTypes, true)) {
            $errors['type'] = 'Type must be one of: ' . implode(', ', $allowedTypes);
        }

        $status = isset($data['status']) ? strtolower((string) $data['status']) : 'completed';
        $allowedStatuses = ['pending', 'processing', 'completed', 'failed'];
        if (!in_array($status, $allowedStatuses, true)) {
            $errors['status'] = 'Status must be one of: ' . implode(', ', $allowedStatuses);
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return ['data' => [
            'recipientId' => $recipientId,
            'amount' => round($amount, 2),
            'type' => $type,
            'status' => $status,
            'txnId' => $data['txnId'] ?? $data['txn_id'] ?? null,
            'date' => $data['date'] ?? null,
            'gatewayResponse' => $data['gatewayResponse'] ?? $data['gateway_response'] ?? null,
        ]];
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (is_array($json) && method_exists($request, 'mergeBody')) {
            $request->mergeBody($json);
        }
    }

    private function resolveRouteId(Request $request): ?string
    {
        $id = $request->route('id');
        if ($id === null || $id === '') {
            $id = $request->get('id');
        }

        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }
}
