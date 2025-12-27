<?php
/**
 * Compensation Logic Engine
 * Formal Spec Implementation (2025)
 * Updates: 
 * - 250 Day ROI Limit
 * - $5000 Daily Capping (Flash Out)
 */
class Compensation {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * ROI Tiers (Business Plans)
     * ROOT:  $50 - $5,000      -> 1.2%
     * RISE:  $5,001 - $25,000  -> 1.3%
     * TERRA: $25,001+          -> 1.5%
     */
    public function getRoiRate($amount) {
        if ($amount > 25000) return 0.015; // TERRA
        if ($amount > 5000) return 0.013;  // RISE
        if ($amount >= 50) return 0.012;   // ROOT
        return 0;
    }

    /**
     * Referral Commission Rates
     * Flat 9% for all tiers as per detailed plan (Section 3)
     */
    public function getReferralRate($amount) {
        if ($amount >= 50) return 0.09;
        return 0;
    }

    /**
     * Get Daily Earning Cap based on Investment Tier
     * ROOT: $2,000
     * RISE: $2,500
     * TERRA: $5,000
     */
    public function getDailyCap($amount) {
        if ($amount > 25000) return 5000; // TERRA
        if ($amount > 5000) return 2500;  // RISE
        return 2000;                      // ROOT
    }

    private function getDailyEarnings($user_id) {
        $today = date('Y-m-d');
        $res = $this->conn->query("SELECT SUM(amount) as total FROM mlm_transactions WHERE user_id = $user_id AND created_at LIKE '$today%' AND type IN ('ROI', 'REFERRAL', 'BINARY')");
        $row = $res->fetch_assoc();
        return floatval($row['total'] ?? 0);
    }

    private function getUserInvestment($user_id) {
        $res = $this->conn->query("SELECT investment FROM mlm_users WHERE id = $user_id");
        $row = $res->fetch_assoc();
        return floatval($row['investment'] ?? 0);
    }

    /**
     * Calculate and Pay Daily ROI for eligible users
     * Rules: 
     * - Must have active investment
     * - Max Duration: 250 Days (roi_days_paid < 250)
     */
    public function processDailyRoi() {
        echo "Processing Daily ROI...\n";
        // Filter users who haven't reached 250 days yet
        $users = $this->conn->query("SELECT id, investment, roi_days_paid FROM mlm_users WHERE investment >= 50 AND roi_days_paid < 250");
        $count = 0;

        while ($u = $users->fetch_assoc()) {
            $rate = $this->getRoiRate($u['investment']);
            $roi = $u['investment'] * $rate;
            $user_id = $u['id'];
            $cap = $this->getDailyCap($u['investment']);

            if ($roi > 0) {
                // Check Global Cap
                $current_earnings = $this->getDailyEarnings($user_id);
                if ($current_earnings >= $cap) {
                    echo "User $user_id Reached Daily Cap ($$cap). ROI Skipped.\n";
                    continue; 
                }
                
                // Adjust if ROI pushes over cap
                if ($current_earnings + $roi > $cap) {
                    $roi = $cap - $current_earnings; // Partial Pay
                }

                // Update Wallet & Counter
                $this->conn->query("UPDATE mlm_wallets SET roi_wallet = roi_wallet + $roi WHERE user_id = $user_id");
                $this->conn->query("UPDATE mlm_users SET roi_days_paid = roi_days_paid + 1 WHERE id = $user_id");
                
                // Log Transaction
                $this->conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'ROI', $roi, 'Daily ROI ($rate) - Day " . ($u['roi_days_paid'] + 1) . "/250')");
                $count++;
            }
        }
        echo "Processed ROI for $count users.\n";
    }

    /**
     * Calculate and Pay Referral Commission for a NEW investment
     * Subject to Daily Cap check
     */
    public function processReferral($newUserInvestment, $sponsorId) {
        if (!$sponsorId || $newUserInvestment < 50) return;

        $rate = $this->getReferralRate($newUserInvestment);
        $bonus = $newUserInvestment * $rate;
        $sponsorInvestment = $this->getUserInvestment($sponsorId);
        $cap = $this->getDailyCap($sponsorInvestment);

        if ($bonus > 0) {
            // Cap Check
            $current_earnings = $this->getDailyEarnings($sponsorId);
            $payable = $bonus;
            $flushed = 0;

            if ($current_earnings + $bonus > $cap) {
                $payable = max(0, $cap - $current_earnings);
                $flushed = $bonus - $payable;
            }

            if ($payable > 0) {
                $this->conn->query("UPDATE mlm_wallets SET referral_wallet = referral_wallet + $payable WHERE user_id = $sponsorId");
                $this->conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($sponsorId, 'REFERRAL', $payable, 'Referral Bonus ($rate)')");
            }

            if ($flushed > 0) {
                 $this->conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($sponsorId, 'FLUSHED', 0, 'Referral Capped (Flush out: $$flushed)')");
            }
        }
    }

    /**
     * 5.2 Binary Matching Formula (10% of Weaker Leg)
     * Rules:
     * - 10% Match
     * - Tiered Daily Cap (Flash Out)
     */
    public function processBinary() {
        echo "Processing Binary Commissions...\n";
        
        $wallets = $this->conn->query("SELECT user_id, left_vol, right_vol FROM mlm_wallets WHERE left_vol > 0 AND right_vol > 0");
        $count = 0;

        if (!$wallets) {
             echo "Binary columns not found.\n";
             return;
        }

        while ($w = $wallets->fetch_assoc()) {
            $user_id = $w['user_id'];
            $weaker = min($w['left_vol'], $w['right_vol']);
            $commission = $weaker * 0.10;
            
            $userInvestment = $this->getUserInvestment($user_id);
            $cap = $this->getDailyCap($userInvestment);

            if ($commission > 0) {
                // Check Cap
                $current_earnings = $this->getDailyEarnings($user_id);
                $payable = $commission;
                $flushed = 0;

                if ($current_earnings + $commission > $cap) {
                    $payable = max(0, $cap - $current_earnings);
                    $flushed = $commission - $payable; // This is the Flash Out
                }

                if ($payable > 0) {
                    $this->conn->query("UPDATE mlm_wallets SET binary_wallet = binary_wallet + $payable WHERE user_id = $user_id");
                    $this->conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'BINARY', $payable, 'Binary Match ($weaker vol)')");
                }

                if ($flushed > 0) {
                    $this->conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'FLUSHED', 0, 'Binary Flash Out ($$flushed)')");
                }
                
                // FLUSH VOLUMES (Reset weaker leg logic)
                $newLeft = $w['left_vol'] - $weaker;
                $newRight = $w['right_vol'] - $weaker;

                $this->conn->query("UPDATE mlm_wallets SET left_vol = $newLeft, right_vol = $newRight WHERE user_id = $user_id");

                $count++;
            }
        }
        echo "Processed Binary for $count users.\n";
    }
}
?>
