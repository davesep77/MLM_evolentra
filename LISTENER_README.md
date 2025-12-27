# Blockchain Event Listener Service

## Overview
This service listens to blockchain events from the Evolentra smart contract and updates the database in real-time.

## Features
- ✅ Listens for Staked events
- ✅ Listens for Unstaked events
- ✅ Listens for RewardClaimed events
- ✅ Listens for ReferralRewarded events
- ✅ Automatic database synchronization
- ✅ Block number tracking
- ✅ Error handling and retry logic

## Setup

### 1. Install Dependencies
```bash
composer require web3p/web3.php
```

### 2. Update Database Schema
```bash
mysql -u root -p mlm_system < setup_blockchain_schema.sql
```

### 3. Configure Environment
Update `.env` with:
```
CONTRACT_ADDRESS=0x...  # Your deployed contract address
BSC_RPC_URL=https://data-seed-prebsc-1-s1.binance.org:8545
```

### 4. Run the Listener

**Linux/Mac:**
```bash
php blockchain_listener.php
```

**Windows:**
```bash
php blockchain_listener.php
```

**As Background Service (Linux):**
```bash
nohup php blockchain_listener.php > listener.log 2>&1 &
```

**As Windows Service:**
Use NSSM (Non-Sucking Service Manager):
```bash
nssm install EvolentraListener "C:\xampp\php\php.exe" "D:\xampp\htdocs\MLM_Evolentra\blockchain_listener.php"
nssm start EvolentraListener
```

## How It Works

1. **Event Detection**: Listens for smart contract events every 15 seconds
2. **Event Processing**: Decodes event data and extracts relevant information
3. **Database Update**: Updates user balances and transaction history
4. **Block Tracking**: Saves last processed block to avoid duplicate processing

## Event Handlers

### Staked Event
- Records stake transaction
- Updates `staked_balance` in `mlm_wallets`
- Creates transaction record in `mlm_transactions`

### Unstaked Event
- Records unstake transaction
- Decreases `staked_balance`
- Increases `main_wallet` balance
- Creates transaction record

### RewardClaimed Event
- Records reward claim
- Updates `roi_wallet` balance
- Creates ROI transaction record

### ReferralRewarded Event
- Records referral commission
- Updates `referral_wallet` balance
- Creates commission history record
- Tracks referral level (1 or 2)

## Monitoring

### Check Listener Status
```bash
ps aux | grep blockchain_listener
```

### View Logs
```bash
tail -f listener.log
```

### Check Last Processed Block
```sql
SELECT * FROM mlm_system_settings WHERE setting_key = 'last_processed_block';
```

## Troubleshooting

### Listener Not Starting
- Check PHP version (requires 7.4+)
- Verify database connection
- Ensure Web3.php is installed

### Events Not Processing
- Verify contract address is correct
- Check RPC URL is accessible
- Ensure events are being emitted from contract

### Duplicate Transactions
- Check `last_processed_block` value
- Verify unique index on `tx_hash` column

## Security Considerations

1. **RPC Endpoint**: Use reliable RPC provider (QuickNode, Infura, or self-hosted)
2. **Database Access**: Listener should have limited database permissions
3. **Error Handling**: All errors are logged, listener continues running
4. **Transaction Verification**: Each transaction hash is unique

## Performance

- **Polling Interval**: 15 seconds (configurable)
- **Batch Processing**: Processes multiple blocks per cycle
- **Memory Usage**: ~50MB typical
- **CPU Usage**: Minimal (<1%)

## Integration with Frontend

The listener automatically syncs blockchain data with the database, so the frontend always displays accurate information:

- User balances are updated in real-time
- Transaction history is automatically populated
- Referral commissions are tracked
- Staking rewards are calculated

## Maintenance

### Restart Listener
```bash
# Find process ID
ps aux | grep blockchain_listener

# Kill process
kill <PID>

# Restart
php blockchain_listener.php &
```

### Update Contract Address
1. Stop listener
2. Update CONTRACT_ADDRESS in .env
3. Restart listener

### Reset Block Tracking
```sql
UPDATE mlm_system_settings 
SET value = '0' 
WHERE setting_key = 'last_processed_block';
```

## Support

For issues:
1. Check listener logs
2. Verify contract is deployed
3. Test RPC connection
4. Review database schema

## License
MIT License
