<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

// Begin transaction
$db->begin_transaction();

try {
    // Get posted data
    $transfer = floatval(trim($_POST['transfer']));
    $sender_acct = trim($_POST['sender_account_num']);
    $receiver_acct = trim($_POST['receiver_account_num']);
    $transferType = trim($_POST['transferType']);
    
    // Verify sender account ownership and get current balance
    $stmt = $db->prepare("SELECT a.balance, c.customerID, a.accountType 
                         FROM accounts a 
                         JOIN customer c ON a.ownerID = c.customerID 
                         WHERE a.bankAccountNumber = ? AND c.cUsername = ? AND a.status = 'approved'
                         FOR UPDATE");
    $stmt->bind_param("ss", $sender_acct, $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['transaction_failed'] = 'doesntOwnAcct';
        throw new Exception('Sender account not found or not owned by user');
    }
    
    $row = $result->fetch_assoc();
    $senderBalance = $row['balance'];
    $senderID = $row['customerID'];
    $senderAcctType = $row['accountType'];
    $stmt->close();
    
    // Check if sufficient balance
    if ($senderBalance < $transfer) {
        $_SESSION['transaction_failed'] = 'insufficentBalance';
        throw new Exception('Insufficient balance');
    }

    if ($transferType === 'internal') {
        // Verify receiver account exists and get owner info
        $stmt = $db->prepare("SELECT a.balance, c.customerID, a.accountType 
                             FROM accounts a 
                             JOIN customer c ON a.ownerID = c.customerID 
                             WHERE a.bankAccountNumber = ? AND a.status = 'approved'
                             FOR UPDATE");
        $stmt->bind_param("s", $receiver_acct);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['transaction_failed'] = 'doesntOwnAcct';
            throw new Exception('Receiver account not found');
        }
        $row = $result->fetch_assoc();
        $receiverBalance = $row['balance'];
        $receiverID = $row['customerID'];
        $receiverAcctType = $row['accountType'];
        $stmt->close();
    }
    // Update sender's balance
    $newSenderBalance = $senderBalance - $transfer;
  $stmt = $db->prepare("UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?");
    $stmt->bind_param("ds", $newSenderBalance, $sender_acct);
    $stmt->execute();
    $stmt->close();

    if ($transferType === 'internal') {
        // Update receiver's balance
        $newReceiverBalance = $receiverBalance + $transfer;
    $stmt = $db->prepare("UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?");
        $stmt->bind_param("ds", $newReceiverBalance, $receiver_acct);
        $stmt->execute();
        $stmt->close();
    }

    // Generate unique 11-digit transactionID for sender
    do {
        $senderTransactionID = strval(mt_rand(10000000000, 99999999999));
    $stmt = $db->prepare("SELECT transactionID FROM transactions WHERE transactionID = ?");
        $stmt->bind_param("s", $senderTransactionID);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0);
    $stmt->close();

  // Record sender's transaction
  $stmt = $db->prepare("INSERT INTO transactions (transactionID, dateOfTransaction, transactionType, changeInBalance, bankAccountNumber) VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?)");
    $type = $transferType . " transfer sent";
    $negativeTransfer = -$transfer;
    $stmt->bind_param("ssds", $senderTransactionID, $type, $negativeTransfer, $sender_acct);
    $stmt->execute();
    $stmt->close();

    if ($transferType === 'internal') {
        // Generate unique 11-digit transactionID for receiver
        do {
            $receiverTransactionID = strval(mt_rand(10000000000, 99999999999));
      $stmt = $db->prepare("SELECT transactionID FROM transactions WHERE transactionID = ?");
            $stmt->bind_param("s", $receiverTransactionID);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        $stmt->close();
    // Record receiver's transaction
    $stmt = $db->prepare("INSERT INTO transactions (transactionID, dateOfTransaction, transactionType, changeInBalance, bankAccountNumber) VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?)");
        $type = $transferType . " transfer received";
        $stmt->bind_param("ssds", $receiverTransactionID, $type, $transfer, $receiver_acct);
        $stmt->execute();
        $stmt->close();
    }

    // If successful, commit and redirect
    $db->commit();
    if ($transferType === 'external') {
        $_SESSION['externalTransferSuccess'] = 'successful';
    } else {
        $_SESSION['transferSuccess'] = 'successful';
    }
    header('Location: ../homepage.php');

} catch (Exception $e) {
    // If any error occurs, rollback the transaction
    $db->rollback();
    error_log("Transfer failed: " . $e->getMessage());
    header('Location: ../homepage.php');
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
    exit();
}
