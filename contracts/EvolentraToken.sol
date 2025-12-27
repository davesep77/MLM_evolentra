// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";

/**
 * @title EvolentraToken
 * @dev MLM Platform Token with staking and automated referral rewards
 */
contract EvolentraToken is ERC20, Ownable, ReentrancyGuard {
    
    // Staking structure
    struct Stake {
        uint256 amount;
        uint256 timestamp;
        uint256 rewardDebt;
    }
    
    // Referral structure
    struct Referral {
        address referrer;
        uint256 totalRewards;
        uint256 referralCount;
    }
    
    // User stakes mapping
    mapping(address => Stake) public stakes;
    
    // Referral mappings
    mapping(address => Referral) public referrals;
    mapping(address => address) public userReferrer; // user => their referrer
    
    // Staking parameters
    uint256 public constant REWARD_RATE = 12; // 1.2% daily = 12/1000
    uint256 public constant REWARD_PRECISION = 1000;
    uint256 public constant MIN_STAKE = 100 * 10**18; // 100 tokens minimum
    
    // Referral parameters
    uint256 public constant DIRECT_REFERRAL_RATE = 100; // 10% = 100/1000
    uint256 public constant INDIRECT_REFERRAL_RATE = 50; // 5% = 50/1000
    uint256 public constant REFERRAL_PRECISION = 1000;
    
    // Total staked tracking
    uint256 public totalStaked;
    uint256 public totalReferralRewards;
    
    // Events
    event Staked(address indexed user, uint256 amount, address indexed referrer);
    event Unstaked(address indexed user, uint256 amount);
    event RewardClaimed(address indexed user, uint256 reward);
    event ReferralRewarded(address indexed referrer, address indexed referee, uint256 amount, uint8 level);
    event ReferrerSet(address indexed user, address indexed referrer);
    
    constructor() ERC20("Evolentra Token", "EVOL") {
        // Mint initial supply to contract owner
        _mint(msg.sender, 1000000000 * 10**decimals()); // 1 billion tokens
    }
    
    /**
     * @dev Set referrer for a user (can only be set once)
     */
    function setReferrer(address referrer) external {
        require(userReferrer[msg.sender] == address(0), "Referrer already set");
        require(referrer != address(0), "Invalid referrer");
        require(referrer != msg.sender, "Cannot refer yourself");
        
        userReferrer[msg.sender] = referrer;
        referrals[referrer].referralCount++;
        
        emit ReferrerSet(msg.sender, referrer);
    }
    
    /**
     * @dev Stake tokens to earn rewards (with automated referral rewards)
     */
    function stake(uint256 amount) external nonReentrant {
        require(amount >= MIN_STAKE, "Amount below minimum stake");
        require(balanceOf(msg.sender) >= amount, "Insufficient balance");
        
        // Claim pending rewards first
        if (stakes[msg.sender].amount > 0) {
            _claimRewards();
        }
        
        // Transfer tokens to contract
        _transfer(msg.sender, address(this), amount);
        
        // Update stake
        stakes[msg.sender].amount += amount;
        stakes[msg.sender].timestamp = block.timestamp;
        totalStaked += amount;
        
        // Process automated referral rewards
        address referrer = userReferrer[msg.sender];
        if (referrer != address(0)) {
            _processReferralRewards(msg.sender, referrer, amount);
        }
        
        emit Staked(msg.sender, amount, referrer);
    }
    
    /**
     * @dev Internal function to process referral rewards automatically
     */
    function _processReferralRewards(address user, address directReferrer, uint256 amount) internal {
        // Level 1: Direct referrer (10%)
        uint256 directReward = (amount * DIRECT_REFERRAL_RATE) / REFERRAL_PRECISION;
        _mint(directReferrer, directReward);
        referrals[directReferrer].totalRewards += directReward;
        totalReferralRewards += directReward;
        
        emit ReferralRewarded(directReferrer, user, directReward, 1);
        
        // Level 2: Indirect referrer (5%)
        address indirectReferrer = userReferrer[directReferrer];
        if (indirectReferrer != address(0)) {
            uint256 indirectReward = (amount * INDIRECT_REFERRAL_RATE) / REFERRAL_PRECISION;
            _mint(indirectReferrer, indirectReward);
            referrals[indirectReferrer].totalRewards += indirectReward;
            totalReferralRewards += indirectReward;
            
            emit ReferralRewarded(indirectReferrer, user, indirectReward, 2);
        }
    }
    
    /**
     * @dev Unstake tokens and claim rewards
     */
    function unstake(uint256 amount) external nonReentrant {
        require(stakes[msg.sender].amount >= amount, "Insufficient staked amount");
        
        // Claim rewards first
        _claimRewards();
        
        // Update stake
        stakes[msg.sender].amount -= amount;
        totalStaked -= amount;
        
        // Transfer tokens back to user
        _transfer(address(this), msg.sender, amount);
        
        emit Unstaked(msg.sender, amount);
    }
    
    /**
     * @dev Calculate pending rewards for a user
     */
    function pendingRewards(address user) public view returns (uint256) {
        Stake memory userStake = stakes[user];
        if (userStake.amount == 0) return 0;
        
        uint256 stakingDuration = block.timestamp - userStake.timestamp;
        uint256 dailyReward = (userStake.amount * REWARD_RATE) / REWARD_PRECISION;
        uint256 reward = (dailyReward * stakingDuration) / 1 days;
        
        return reward - userStake.rewardDebt;
    }
    
    /**
     * @dev Claim staking rewards
     */
    function claimRewards() external nonReentrant {
        _claimRewards();
    }
    
    /**
     * @dev Internal function to claim rewards
     */
    function _claimRewards() internal {
        uint256 reward = pendingRewards(msg.sender);
        if (reward > 0) {
            stakes[msg.sender].rewardDebt += reward;
            _mint(msg.sender, reward);
            emit RewardClaimed(msg.sender, reward);
        }
    }
    
    /**
     * @dev Get user stake info
     */
    function getStakeInfo(address user) external view returns (
        uint256 stakedAmount,
        uint256 stakingTime,
        uint256 pendingReward
    ) {
        Stake memory userStake = stakes[user];
        return (
            userStake.amount,
            userStake.timestamp,
            pendingRewards(user)
        );
    }
    
    /**
     * @dev Get referral info
     */
    function getReferralInfo(address user) external view returns (
        address referrer,
        uint256 totalRewards,
        uint256 referralCount
    ) {
        return (
            userReferrer[user],
            referrals[user].totalRewards,
            referrals[user].referralCount
        );
    }
    
    /**
     * @dev Get referral chain (up to 5 levels)
     */
    function getReferralChain(address user) external view returns (address[5] memory chain) {
        address current = user;
        for (uint8 i = 0; i < 5; i++) {
            current = userReferrer[current];
            chain[i] = current;
            if (current == address(0)) break;
        }
        return chain;
    }
    
    /**
     * @dev Emergency withdraw (owner only)
     */
    function emergencyWithdraw(address token, uint256 amount) external onlyOwner {
        if (token == address(0)) {
            payable(owner()).transfer(amount);
        } else {
            IERC20(token).transfer(owner(), amount);
        }
    }
}
