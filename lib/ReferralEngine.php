<?php
/**
 * Referral Engine
 * Comprehensive referral management and tracking system
 */

class ReferralEngine {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Generate unique referral code for a user
     */
    public function generateReferralCode($userId, $username) {
        // Clean username and create base code
        $clean_username = preg_replace('/[^a-zA-Z0-9]/', '', $username);
        $random = strtoupper(substr(md5($userId . time()), 0, 4));
        $code = strtoupper(substr($clean_username, 0, 6)) . $random;
        
        // Ensure uniqueness
        $attempt = 0;
        while (true) {
            $check = $this->conn->query("SELECT id FROM mlm_users WHERE referral_code='$code'");
            if ($check->num_rows == 0) break;
            
            $attempt++;
            if ($attempt > 10) {
                $code = strtoupper(bin2hex(random_bytes(5)));
                break;
            }
            $code = strtoupper(substr($clean_username, 0, 6)) . strtoupper(substr(md5($userId . time() . $attempt), 0, 4));
        }
        
        return $code;
    }
    
    /**
     * Create referral links for a user (general, left, right)
     */
    public function createReferralLinks($userId, $referralCode) {
        $types = ['general', 'left', 'right'];
        $created = 0;
        
        foreach ($types as $type) {
            $sql = "INSERT INTO mlm_referral_links (user_id, referral_code, link_type) 
                    VALUES ($userId, '$referralCode', '$type')";
            if ($this->conn->query($sql) === TRUE) {
                $created++;
            }
        }
        
        return $created;
    }
    
    /**
     * Track a referral link click
     */
    public function trackClick($referralCode, $ipAddress = null, $userAgent = null) {
        // Get link ID
        $link_query = $this->conn->query("SELECT id FROM mlm_referral_links 
                                          WHERE referral_code='$referralCode' AND link_type='general' LIMIT 1");
        
        if ($link_query->num_rows == 0) {
            return false;
        }
        
        $link = $link_query->fetch_assoc();
        $linkId = $link['id'];
        
        // Sanitize inputs
        $ipAddress = $ipAddress ? $this->conn->real_escape_string($ipAddress) : null;
        $userAgent = $userAgent ? $this->conn->real_escape_string(substr($userAgent, 0, 500)) : null;
        
        // Insert click record
        $sql = "INSERT INTO mlm_referral_clicks (link_id, referral_code, ip_address, user_agent) 
                VALUES ($linkId, '$referralCode', " . 
                ($ipAddress ? "'$ipAddress'" : "NULL") . ", " . 
                ($userAgent ? "'$userAgent'" : "NULL") . ")";
        
        if ($this->conn->query($sql) === TRUE) {
            // Increment click counter
            $this->conn->query("UPDATE mlm_referral_links SET clicks = clicks + 1, last_used_at = NOW() 
                               WHERE referral_code='$referralCode'");
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mark a referral as converted (user registered and/or invested)
     */
    public function markConversion($referralCode, $newUserId) {
        // Update all links for this referral code
        $this->conn->query("UPDATE mlm_referral_links SET conversions = conversions + 1 
                           WHERE referral_code='$referralCode'");
        
        // Update click record if exists (mark most recent unconverted click)
        $this->conn->query("UPDATE mlm_referral_clicks 
                           SET converted = 1, converted_user_id = $newUserId 
                           WHERE referral_code='$referralCode' AND converted = 0 
                           ORDER BY clicked_at DESC LIMIT 1");
        
        return true;
    }
    
    /**
     * Log a referral earning
     */
    public function logEarning($referrerId, $referredUserId, $investmentAmount, $commissionRate, $commissionAmount, $level = 1, $status = 'paid') {
        $sql = "INSERT INTO mlm_referral_earnings 
                (referrer_id, referred_user_id, investment_amount, commission_rate, commission_amount, level, status)
                VALUES ($referrerId, $referredUserId, $investmentAmount, $commissionRate, $commissionAmount, $level, '$status')";
        
        if ($this->conn->query($sql) === TRUE) {
            // Update total_earned in referral_links
            $this->conn->query("UPDATE mlm_referral_links 
                               SET total_earned = total_earned + $commissionAmount 
                               WHERE user_id = $referrerId");
            return true;
        }
        
        return false;
    }
    
    /**
     * Get comprehensive referral statistics for a user
     */
    public function getReferralStats($userId) {
        $stats = [];
        
        // Total referrals (conversions)
        $total_query = $this->conn->query("SELECT SUM(conversions) as total FROM mlm_referral_links WHERE user_id=$userId");
        $stats['total_referrals'] = $total_query->fetch_assoc()['total'] ?? 0;
        
        // Active referrals (those who have invested)
        $active_query = $this->conn->query("SELECT COUNT(*) as count FROM mlm_users 
                                           WHERE sponsor_id=$userId AND investment > 0");
        $stats['active_referrals'] = $active_query->fetch_assoc()['count'];
        
        // Total clicks
        $clicks_query = $this->conn->query("SELECT SUM(clicks) as total FROM mlm_referral_links WHERE user_id=$userId");
        $stats['total_clicks'] = $clicks_query->fetch_assoc()['total'] ?? 0;
        
        // Conversion rate
        $stats['conversion_rate'] = $stats['total_clicks'] > 0 
            ? round(($stats['total_referrals'] / $stats['total_clicks']) * 100, 2) 
            : 0;
        
        // Total earned
        $earned_query = $this->conn->query("SELECT SUM(total_earned) as total FROM mlm_referral_links WHERE user_id=$userId");
        $stats['total_earned'] = $earned_query->fetch_assoc()['total'] ?? 0;
        
        // This month earned
        $month_start = date('Y-m-01');
        $month_query = $this->conn->query("SELECT SUM(commission_amount) as total FROM mlm_referral_earnings 
                                          WHERE referrer_id=$userId AND earned_at >= '$month_start'");
        $stats['this_month_earned'] = $month_query->fetch_assoc()['total'] ?? 0;
        
        // Recent conversions (last 10)
        $recent_query = $this->conn->query("SELECT u.username, u.investment, re.commission_amount, re.earned_at 
                                           FROM mlm_referral_earnings re
                                           JOIN mlm_users u ON u.id = re.referred_user_id
                                           WHERE re.referrer_id = $userId
                                           ORDER BY re.earned_at DESC LIMIT 10");
        $stats['recent_conversions'] = [];
        while ($row = $recent_query->fetch_assoc()) {
            $stats['recent_conversions'][] = $row;
        }
        
        // Top performers (highest earning referrals)
        $top_query = $this->conn->query("SELECT u.username, SUM(re.commission_amount) as total_commission
                                        FROM mlm_referral_earnings re
                                        JOIN mlm_users u ON u.id = re.referred_user_id
                                        WHERE re.referrer_id = $userId
                                        GROUP BY re.referred_user_id
                                        ORDER BY total_commission DESC LIMIT 5");
        $stats['top_performers'] = [];
        while ($row = $top_query->fetch_assoc()) {
            $stats['top_performers'][] = $row;
        }
        
        // Get individual link stats
        $links_query = $this->conn->query("SELECT link_type, clicks, conversions, total_earned 
                                          FROM mlm_referral_links WHERE user_id=$userId");
        $stats['links'] = [];
        while ($link = $links_query->fetch_assoc()) {
            $stats['links'][$link['link_type']] = $link;
        }
        
        return $stats;
    }
    
    /**
     * Get top referrers leaderboard
     */
    public function getTopReferrers($limit = 10) {
        $query = $this->conn->query("SELECT u.id, u.username, rl.conversions, rl.total_earned
                                    FROM mlm_users u
                                    JOIN mlm_referral_links rl ON rl.user_id = u.id
                                    WHERE rl.link_type = 'general'
                                    ORDER BY rl.total_earned DESC, rl.conversions DESC
                                    LIMIT $limit");
        
        $leaderboard = [];
        while ($row = $query->fetch_assoc()) {
            $leaderboard[] = $row;
        }
        
        return $leaderboard;
    }
    
    /**
     * Get referral link URLs for a user
     */
    public function getReferralLinks($userId, $baseUrl) {
        $user_query = $this->conn->query("SELECT referral_code FROM mlm_users WHERE id=$userId");
        if ($user_query->num_rows == 0) return null;
        
        $refCode = $user_query->fetch_assoc()['referral_code'];
        
        return [
            'code' => $refCode,
            'general' => $baseUrl . "?ref=" . $refCode,
            'left' => $baseUrl . "?ref=" . $refCode . "&position=left",
            'right' => $baseUrl . "?ref=" . $refCode . "&position=right"
        ];
    }
    
    /**
     * Get user ID from referral code
     */
    public function getUserIdFromCode($referralCode) {
        $query = $this->conn->query("SELECT id FROM mlm_users WHERE referral_code='$referralCode'");
        if ($query->num_rows > 0) {
            return $query->fetch_assoc()['id'];
        }
        return null;
    }
    
    /**
     * Validate referral code
     */
    public function validateCode($referralCode) {
        $query = $this->conn->query("SELECT id FROM mlm_users WHERE referral_code='$referralCode'");
        return $query->num_rows > 0;
    }
}
?>
