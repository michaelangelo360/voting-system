<?php
/**
 * PaymentController.php
 * Handles payment-related API requests
 */
namespace Controllers;

use Models\Nominee;
use Models\Payment;
use Services\PaystackService;
use Utils\Response;
use Utils\Validator;

class PaymentController {
    private $nomineeModel;
    private $paymentModel;
    private $paystackService;

    public function __construct() {
        $this->nomineeModel = new Nominee();
        $this->paymentModel = new Payment();
        $this->paystackService = new PaystackService();
    }

    /**
     * Process a payment
     * 
     * @param array $request
     * @return void
     */
    public function processPayment($request) {
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'nominee_id' => 'required|integer',
            'votes' => 'required|integer|min:1',
            'email' => 'required|email',
            'phone' => 'required|string'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        $nomineeId = $request['body']['nominee_id'];
        $votes = $request['body']['votes'];
        $email = $request['body']['email'];
        $phone = $request['body']['phone'];
        
        // Check if nominee exists
        $nominee = $this->nomineeModel->getById($nomineeId);
        if (!$nominee) {
            Response::notFound('Nominee not found');
        }
        
        // Calculate amount
        $amount = $nominee['cost'] * $votes;
        
        // Initiate payment
        try {
            $payment = $this->paystackService->initiatePayment([
                'email' => $email,
                'amount' => $amount * 100, // Convert to kobo (Paystack uses the smallest currency unit)
                'phone' => $phone,
                'callback_url' => APP_URL . '/payment/verify',
                'metadata' => [
                    'nominee_id' => $nomineeId,
                    'votes' => $votes,
                    'phone' => $phone
                ]
            ]);
            
            // Save payment reference
            $this->paymentModel->create([
                'reference' => $payment['reference'],
                'nominee_id' => $nomineeId,
                'msisdn' => $phone,
                'votes' => $votes,
                'status' => 0
            ]);
            
            Response::success([
                'authorization_url' => $payment['authorization_url'],
                'reference' => $payment['reference']
            ], 'Payment initiated');
        } catch (\Exception $e) {
            Response::error('Failed to initiate payment: ' . $e->getMessage());
        }
    }

    /**
     * Verify a transaction
     * 
     * @param array $request
     * @return void
     */
    public function verifyTransaction($request) {
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'reference' => 'required|string'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        $reference = $request['body']['reference'];
        
        // Check if reference exists
        $payment = $this->paymentModel->getByReference($reference);
        if (!$payment) {
            Response::notFound('Payment reference not found');
        }
        
        // If payment is already verified, return success
        if ($payment['status'] == 1) {
            Response::success(['status' => true], 'Payment already verified');
        }
        
        // Check transaction status
        try {
            $transaction = $this->paystackService->verifyTransaction($reference);
            
            if ($transaction['status'] == 'success') {
                // Start transaction
                $db = \Config\Database::getInstance();
                $db->beginTransaction();
                
                try {
                    // Update payment status
                    $this->paymentModel->updateStatus($payment['id'], 1);
                    
                    // Update nominee votes
                    $this->nomineeModel->updateVotes($payment['nominee_id'], $payment['votes']);
                    
                    // Record vote
                    $voteModel = new \Models\Vote();
                    $voteData = [
                        'nominee_id' => $payment['nominee_id'],
                        'vote_count' => $payment['votes'],
                        'phone_number' => $payment['msisdn'],
                        'transaction_reference' => $reference
                    ];
                    
                    $voteModel->create($voteData);
                    
                    // Commit transaction
                    $db->commit();
                    
                    Response::success(['status' => true], 'Payment verified successfully');
                } catch (\Exception $e) {
                    // Rollback transaction
                    $db->rollBack();
                    
                    Response::error('Failed to update vote: ' . $e->getMessage());
                }
            } else {
                Response::success([
                    'status' => false,
                    'message' => $transaction['message']
                ], 'Payment not successful');
            }
        } catch (\Exception $e) {
            Response::error('Failed to verify transaction: ' . $e->getMessage());
        }
    }
}