# Evolentra BSC Integration Guide

## Overview
This implementation follows the Binance Smart Chain Development Guide 2025, providing a complete DApp infrastructure for the Evolentra MLM platform.

## Features Implemented

### 1. Smart Contract (EvolentraToken.sol)
- ✅ ERC20 token with staking functionality
- ✅ 1.2% daily staking rewards
- ✅ Referral reward processing
- ✅ ReentrancyGuard protection
- ✅ OpenZeppelin security standards

### 2. Web3 Integration (web3_integration.js)
- ✅ MetaMask wallet connection
- ✅ BSC network switching (Testnet/Mainnet)
- ✅ Smart contract interaction methods
- ✅ Balance checking (EVOL & BNB)
- ✅ Staking/Unstaking functions
- ✅ Rewards claiming

### 3. Frontend DApp (bsc_staking.php)
- ✅ Real-time dashboard
- ✅ Wallet connection UI
- ✅ Staking interface
- ✅ Rewards display
- ✅ Transaction notifications

### 4. Development Tools
- ✅ Hardhat configuration
- ✅ Deployment scripts
- ✅ BSC Testnet/Mainnet support
- ✅ Contract verification setup

## Setup Instructions

### Prerequisites
1. Node.js (v16 or higher)
2. MetaMask browser extension
3. BNB for gas fees (Testnet: get from faucet)

### Installation

1. **Install Dependencies**
```bash
cd d:\xampp\htdocs\MLM_Evolentra
npm install
```

2. **Configure Environment**
```bash
cp .env.example .env
# Edit .env and add your private key and BscScan API key
```

3. **Compile Smart Contract**
```bash
npm run compile
```

4. **Deploy to BSC Testnet**
```bash
npm run deploy:testnet
```

5. **Update Contract Address**
After deployment, update the contract address in:
- `js/web3_integration.js` (line 157)
- `bsc_staking.php` (line 285)

### Testing on BSC Testnet

1. **Get Testnet BNB**
   - Visit: https://testnet.binance.org/faucet-smart
   - Enter your wallet address
   - Receive test BNB

2. **Add BSC Testnet to MetaMask**
   - Network Name: BSC Testnet
   - RPC URL: https://data-seed-prebsc-1-s1.binance.org:8545
   - Chain ID: 97
   - Symbol: tBNB
   - Explorer: https://testnet.bscscan.com

3. **Test the DApp**
   - Navigate to: http://localhost/MLM_Evolentra/bsc_staking.php
   - Click "Connect Wallet"
   - Approve network switch in MetaMask
   - Test staking functions

## Smart Contract Functions

### For Users
- `stake(amount)` - Stake EVOL tokens
- `unstake(amount)` - Unstake tokens and claim rewards
- `claimRewards()` - Claim pending rewards
- `pendingRewards(address)` - View pending rewards
- `getStakeInfo(address)` - Get staking details

### For Admin (Owner)
- `processReferralReward(referrer, referee, amount)` - Process MLM rewards

## Security Features

1. **ReentrancyGuard** - Prevents reentrancy attacks
2. **OpenZeppelin Contracts** - Industry-standard security
3. **Access Control** - Owner-only functions
4. **Input Validation** - Minimum stake requirements
5. **Safe Math** - Overflow protection (Solidity 0.8+)

## Gas Optimization

- Optimized compiler settings (200 runs)
- Efficient storage patterns
- Batch operations where possible
- Minimal external calls

## Deployment Checklist

### Before Mainnet Deployment
- [ ] Audit smart contract code
- [ ] Test all functions on testnet
- [ ] Verify contract on BscScan
- [ ] Test with small amounts first
- [ ] Set up monitoring
- [ ] Prepare emergency pause mechanism
- [ ] Document all admin functions
- [ ] Set up multi-sig wallet for owner

### After Deployment
- [ ] Verify contract on BscScan
- [ ] Update frontend with contract address
- [ ] Test all user flows
- [ ] Monitor gas costs
- [ ] Set up event listeners
- [ ] Implement transaction history
- [ ] Add liquidity (if needed)
- [ ] Announce to users

## Integration with Existing MLM System

### Database Schema
The smart contract integrates with existing MLM tables:
- `mlm_users` - User accounts
- `mlm_wallets` - Wallet balances
- `mlm_transactions` - Transaction history

### Workflow
1. User stakes tokens via Web3
2. Smart contract emits events
3. Backend listens for events
4. Database updated with transaction
5. MLM commissions calculated
6. Referral rewards processed on-chain

## Troubleshooting

### Common Issues

**MetaMask not connecting**
- Ensure MetaMask is installed
- Check network is BSC Testnet/Mainnet
- Refresh page and try again

**Transaction failing**
- Check BNB balance for gas
- Verify contract address is correct
- Check minimum stake amount (100 EVOL)

**Contract not found**
- Ensure contract is deployed
- Verify contract address in code
- Check network (Testnet vs Mainnet)

## Resources

- [BSC Documentation](https://docs.bnbchain.org/)
- [Hardhat Docs](https://hardhat.org/docs)
- [OpenZeppelin](https://docs.openzeppelin.com/)
- [Web3.js](https://web3js.readthedocs.io/)
- [BscScan](https://bscscan.com/)

## Support

For issues or questions:
1. Check this documentation
2. Review contract code comments
3. Test on BSC Testnet first
4. Contact development team

## License
MIT License - See LICENSE file for details
