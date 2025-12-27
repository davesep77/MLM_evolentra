function payReferral($newUserInvestment, $sponsor_id, $conn) {
    if (!$sponsor_id) return;

    $rate = 0.08;
    if ($newUserInvestment > 5000) $rate = 0.09;
    if ($newUserInvestment > 20000) $rate = 0.10;

    $bonus = $newUserInvestment * $rate;

    $conn->query("
    UPDATE wallets 
    SET referral_wallet = referral_wallet + $bonus 
    WHERE user_id = $sponsor_id
    ");

    $conn->query("
    INSERT INTO transactions (user_id,type,amount)
    VALUES ($sponsor_id,'REFERRAL',$bonus)
    ");
}
