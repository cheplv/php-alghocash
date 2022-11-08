<?php
require_once('../vendor/autoload.php');
require_once("../tests/config.php");

use AlgorithmicCash\PayHandler;
use AlgorithmicCash\PayHandlerResponse;
use AlgorithmicCash\PaymentType;
use AlgorithmicCash\PaymentStatus;
use AlgorithmicCash\PaymentResult;


$requestSignature = $_SERVER['HTTP_X_SIGNATURE'];
$requestData = file_get_contents('php://input');

error_log('Handler: ' . json_encode([$_SERVER, $_GET, $_POST]));
error_log('HandlerData: ' . $requestData);

$handler = new PayHandler($GLOBALS['acTestVars']['privateKey'], $GLOBALS['acTestVars']['pgKey'], $requestSignature, $requestData);

$request = $handler->handleRequest();

error_log('IsValidHandlerRequest: ' . $request->isValid());
if (!$request->isValid()) {
    die('Request is invalid or signature mismatch');
}

error_log('N Merchant: ' . $request->getMerchantId());
error_log('N TxId: ' . $request->getMerchantTxId());
error_log('N TxType: ' . $request->getTxType());
error_log('N Status: ' . $request->getStatus());
error_log('N Timestamp: ' . $request->getTimestamp());

$responseResult = PaymentResult::OK;
$responseSuccess = 1;
$responseError = "";

switch($request->getTxType()) {
    // Processing incoming transaction status
    case PaymentType::TX_IN:
        switch($request->getStatus()) {
            case PaymentStatus::ProcessingNotAvailable:
                error_log('Processing not available this time');
                break;
            case PaymentStatus::InvalidRequest:
                error_log('User Sent invalid request');
                break;
            case PaymentStatus::PaymentSuccess:
                error_log('Payment processed succesfuly');

                // Success payment additional variables
                $dataReferenceNo = $request->getReferenceNo();
                $dataCustomerHash = $request->getParam('customer_hash');
                break;
            case PaymentStatus::PaymentSettled:
                error_log('Payment settled to blockchain succesfuly');

                // Success settlement variables
                $dataReferenceNo = $request->getReferenceNo();
                $dataCustomerHash = $request->getParam('customer_hash');
                $dataAmount = $request->getParam('amount');
                $dataBlockchainAmount = $request->getParam('blockchain_request_amount');
                $dataFee = $request->getParam('fee_amount');
                $dataBlockchainFee = $request->getParam('blockchain_fee_amount');
                $dataRollingReserveAmount = $request->getParam('rolling_reserve_amount');
                $dataRollingReserveReleaseDT = $request->getParam('rolling_reserve_release_dt');
                break;
            default:
            $responseResult = PaymentResult::FAIL;
            $responseSuccess = 0;
            $responseError = 'Unknown transaction status';
            break;
        }
        break;
    
    // Processing outgoing transaction
    case PaymentType::TX_OUT:
        $responseResult = PaymentResult::FAIL;
        $responseSuccess = 0;
        $responseError = 'HANDLER FOR TX_OUT Not Implemented';
        break;
    
    default:
        $responseResult = PaymentResult::FAIL;
        $responseSuccess = 0;
        $responseError = 'Unknown transaction type';
        break;
}

$response = new PayHandlerResponse($responseResult, $responseSuccess, $responseError);
$response->send();