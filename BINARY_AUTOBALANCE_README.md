# Binary Tree Auto-Balance System

## Overview
Automatic binary placement system that places new referrals on the weaker leg to maintain tree balance.

## How It Works

### 1. **Automatic Placement Logic**

When a new user registers with a sponsor:

```php
// System automatically calculates weaker leg
$binary_position = getWeakerLeg($sponsor_id, $conn);
```

**Decision Criteria (in order):**
1. **Investment Volume** - Places on leg with lower total investment
2. **Member Count** - If volumes equal, places on leg with fewer members
3. **Default** - If both equal, defaults to LEFT

### 2. **Weaker Leg Calculation**

```
Left Leg:  5 members, $10,000 volume
Right Leg: 3 members, $8,000 volume

Result: Place on RIGHT (lower volume)
```

### 3. **Manual Override**

Users can still use referral links with specific positions:
```
https://evolentra.com/register?sponsor=john&position=left
https://evolentra.com/register?sponsor=john&position=right
```

If position is specified, it overrides auto-placement.

## Benefits

✅ **Balanced Growth** - Prevents lopsided trees
✅ **Fair Distribution** - Equal opportunity for binary commissions
✅ **Automatic** - No manual intervention needed
✅ **Smart Logic** - Considers both count and volume
✅ **Override Option** - Manual placement still available

## Features Implemented

### 1. **Auto-Balance Registration**
- `register.php` - Updated with auto-placement logic
- Calculates weaker leg before placing user
- Respects manual position if provided

### 2. **Balance Calculation Functions**
- `getWeakerLeg()` - Determines optimal placement
- `countTeamMembers()` - Counts all downline members
- `getTeamVolume()` - Calculates total investment
- `getBinaryBalance()` - Returns complete balance info

### 3. **Utility Library**
- `lib/BinaryBalance.php` - Reusable balance functions
- API endpoint for real-time balance checking

## Usage Examples

### For Sponsors

**Check Your Binary Balance:**
```php
require 'lib/BinaryBalance.php';

$balance = getBinaryBalance($user_id, $conn);

echo "Left Team: {$balance['left_count']} members, ${$balance['left_volume']}";
echo "Right Team: {$balance['right_count']} members, ${$balance['right_volume']}";
echo "Next placement will go to: {$balance['weaker_leg']} leg";
```

### For New Registrations

**Automatic Placement:**
```
User registers with sponsor "john"
System checks:
- Left: 10 members, $5,000
- Right: 8 members, $4,500
Result: Places on RIGHT (lower volume)
```

**Manual Placement:**
```
User clicks: john's LEFT referral link
System: Places on LEFT (overrides auto-balance)
```

## Dashboard Integration

Add to your dashboard to show balance:

```php
<?php
require 'lib/BinaryBalance.php';
$balance = getBinaryBalance($_SESSION['user_id'], $conn);
?>

<div class="binary-balance">
    <h3>Your Binary Tree Balance</h3>
    <div class="leg">
        <strong>Left Leg:</strong>
        <?= $balance['left_count'] ?> members
        $<?= number_format($balance['left_volume'], 2) ?>
    </div>
    <div class="leg">
        <strong>Right Leg:</strong>
        <?= $balance['right_count'] ?> members
        $<?= number_format($balance['right_volume'], 2) ?>
    </div>
    <div class="next-placement">
        Next referral will be placed on: <strong><?= $balance['weaker_leg'] == 'L' ? 'LEFT' : 'RIGHT' ?></strong>
    </div>
</div>
```

## API Usage

**Get Balance via AJAX:**
```javascript
fetch('lib/BinaryBalance.php?action=get_balance')
    .then(r => r.json())
    .then(data => {
        console.log('Left:', data.left_count);
        console.log('Right:', data.right_count);
        console.log('Weaker leg:', data.weaker_leg);
    });
```

## Testing

### Test Scenario 1: Empty Tree
```
Sponsor: John (no downline)
New user: Alice
Result: Placed on LEFT (default)
```

### Test Scenario 2: Unbalanced Tree
```
Sponsor: John
Left: 5 members
Right: 2 members
New user: Bob
Result: Placed on RIGHT (weaker)
```

### Test Scenario 3: Equal Count, Different Volume
```
Sponsor: John
Left: 3 members, $1,500
Right: 3 members, $1,000
New user: Carol
Result: Placed on RIGHT (lower volume)
```

## Performance

- **Recursive Calculation** - Counts entire downline
- **Cached Results** - Consider caching for large trees
- **Optimized Queries** - Single query per level

For very large trees (1000+ members), consider:
1. Caching balance calculations
2. Updating counts on registration
3. Using materialized views

## Security

✅ **SQL Injection Protection** - Use prepared statements
✅ **Position Validation** - Only 'L' or 'R' allowed
✅ **Sponsor Verification** - Validates sponsor exists
✅ **Session Checks** - API requires authentication

## Future Enhancements

- [ ] Real-time balance updates via WebSocket
- [ ] Balance history tracking
- [ ] Spillover notifications
- [ ] Visual tree balance indicator
- [ ] Admin override for placement

## Support

For issues or questions:
1. Check balance calculation logic
2. Verify sponsor_id and binary_position in database
3. Test with small tree first
4. Review recursive function limits

---

**Status**: ✅ Implemented and Ready
**Version**: 1.0
**Last Updated**: 2025-12-26
