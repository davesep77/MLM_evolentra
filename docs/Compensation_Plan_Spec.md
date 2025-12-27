# Evolentra Web Application – Integrated Architecture, Compensation Logic, and Strategic Framework (2025)

## 1. Purpose of This File ("Web App")

The Evolentra Web App is the **primary operational system** for:

* Distributor onboarding and identity
* Compensation calculation and transparency
* Payment collection and commission payouts
* Genealogy, dashboards, and replicated websites
* Regulatory and financial compliance (Ethiopia + global)

This file acts as the **single source of truth** for how the web application behaves.

## 2. Integrated Compensation Plan

### 2.1 Income Streams Overview

The Evolentra platform supports **three parallel income streams**, each calculated independently and displayed transparently in the distributor dashboard:

1. **ROI Income (Capital Participation Return)**
2. **Referral Commission (Direct Sponsorship Bonus)**
3. **Binary Commission (Team Performance Bonus)**

These streams are additive and auditable.

## 3. ROI Income Logic (Digitized)

ROI is calculated daily based on the **capital amount committed by the user**, with tiered percentage returns.

### 3.1 ROI Tiers

| Capital Range (USD) | Daily ROI Rate |
| ------------------- | -------------- |
| $50 – $5,000        | 1.2%           |
| $5,001 – $20,000    | 1.3%           |
| $20,001 and above   | 1.5%           |

### 3.2 ROI Formula

```
Daily ROI = Invested Amount × ROI Rate
```

### 3.3 System Rules

* ROI accrues **daily**
* ROI is visible in real time in the dashboard
* ROI withdrawals are subject to platform withdrawal rules (see Section 6)

## 4. Referral Commission (Direct Income)

Referral commissions are earned when a distributor directly sponsors another participant.

### 4.1 Referral Commission Rates

| Referred Capital Volume | Commission Rate |
| ----------------------- | --------------- |
| $50 – $5,000            | 8%              |
| $5,001 – $20,000        | 9%              |
| $20,001 and above       | 10%             |

### 4.2 Referral Rules

* Paid **one-time** per referred participant
* Triggered immediately after successful activation
* Credited to referral wallet

## 5. Binary Commission Structure

Evolentra operates a **binary tree system** (Left / Right leg).

### 5.1 Binary Commission Rate

| Matching Volume Tier | Commission |
| -------------------- | ---------- |
| $50 – $5,000         | 10%        |
| $5,001 – $20,000     | 10%        |
| $20,001 and above    | 10%        |

> Note: The rate is flat (10%) across all tiers; volume affects caps, not percentage.

### 5.2 Binary Matching Formula

```
Binary Commission = Weaker Leg Volume × 10%
```

### 5.3 Binary Conditions

* Calculated daily
* Excess volume is carried forward
* System auto-detects weaker leg

## 6. Daily Withdrawal & Cash‑Out Rules

### 6.1 Daily Withdrawal Caps

| Capital Tier     | Maximum Daily Cash‑Out |
| ---------------- | ---------------------- |
| $50 – $5,000     | $2,000 / day           |
| $5,001 – $20,000 | $2,500 / day           |
| $20,001+         | Defined by admin rules |

### 6.2 Wallet Types

* ROI Wallet
* Referral Wallet
* Binary Wallet

Each wallet is tracked independently but unified in the dashboard UI.
