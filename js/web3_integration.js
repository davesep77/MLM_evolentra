/**
 * Enhanced Web3 Integration with Referral Support
 */

// BSC Network Configuration
const BSC_CONFIG = {
    chainId: '0x38',
    chainName: 'Binance Smart Chain',
    nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 },
    rpcUrls: ['https://bsc-dataseed.binance.org/'],
    blockExplorerUrls: ['https://bscscan.com/']
};

const BSC_TESTNET_CONFIG = {
    chainId: '0x61',
    chainName: 'Binance Smart Chain Testnet',
    nativeCurrency: { name: 'BNB', symbol: 'tBNB', decimals: 18 },
    rpcUrls: ['https://data-seed-prebsc-1-s1.binance.org:8545/'],
    blockExplorerUrls: ['https://testnet.bscscan.com/']
};

// Enhanced Contract ABI with referral functions
const TOKEN_ABI = [
    { "inputs": [{ "internalType": "address", "name": "referrer", "type": "address" }], "name": "setReferrer", "outputs": [], "stateMutability": "nonpayable", "type": "function" },
    { "inputs": [{ "internalType": "uint256", "name": "amount", "type": "uint256" }], "name": "stake", "outputs": [], "stateMutability": "nonpayable", "type": "function" },
    { "inputs": [{ "internalType": "uint256", "name": "amount", "type": "uint256" }], "name": "unstake", "outputs": [], "stateMutability": "nonpayable", "type": "function" },
    { "inputs": [], "name": "claimRewards", "outputs": [], "stateMutability": "nonpayable", "type": "function" },
    { "inputs": [{ "internalType": "address", "name": "user", "type": "address" }], "name": "pendingRewards", "outputs": [{ "internalType": "uint256", "name": "", "type": "uint256" }], "stateMutability": "view", "type": "function" },
    { "inputs": [{ "internalType": "address", "name": "user", "type": "address" }], "name": "getStakeInfo", "outputs": [{ "internalType": "uint256", "name": "stakedAmount", "type": "uint256" }, { "internalType": "uint256", "name": "stakingTime", "type": "uint256" }, { "internalType": "uint256", "name": "pendingReward", "type": "uint256" }], "stateMutability": "view", "type": "function" },
    { "inputs": [{ "internalType": "address", "name": "user", "type": "address" }], "name": "getReferralInfo", "outputs": [{ "internalType": "address", "name": "referrer", "type": "address" }, { "internalType": "uint256", "name": "totalRewards", "type": "uint256" }, { "internalType": "uint256", "name": "referralCount", "type": "uint256" }], "stateMutability": "view", "type": "function" },
    { "inputs": [{ "internalType": "address", "name": "user", "type": "address" }], "name": "getReferralChain", "outputs": [{ "internalType": "address[5]", "name": "chain", "type": "address[5]" }], "stateMutability": "view", "type": "function" },
    { "inputs": [{ "internalType": "address", "name": "account", "type": "address" }], "name": "balanceOf", "outputs": [{ "internalType": "uint256", "name": "", "type": "uint256" }], "stateMutability": "view", "type": "function" }
];

class Web3Manager {
    constructor() {
        this.web3 = null;
        this.contract = null;
        this.account = null;
        this.contractAddress = null;
    }

    async init(useTestnet = true) {
        if (typeof window.ethereum === 'undefined') {
            throw new Error('MetaMask not installed');
        }

        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        this.account = accounts[0];

        await this.switchToBSC(useTestnet);
        this.web3 = new Web3(window.ethereum);

        // Save wallet address to backend
        await this.saveWalletAddress();

        window.ethereum.on('accountsChanged', (accounts) => {
            this.account = accounts[0];
            this.onAccountChanged(accounts[0]);
        });

        window.ethereum.on('chainChanged', () => {
            window.location.reload();
        });

        return this.account;
    }

    async switchToBSC(useTestnet = true) {
        const config = useTestnet ? BSC_TESTNET_CONFIG : BSC_CONFIG;

        try {
            await window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: config.chainId }]
            });
        } catch (switchError) {
            if (switchError.code === 4902) {
                await window.ethereum.request({
                    method: 'wallet_addEthereumChain',
                    params: [config]
                });
            } else {
                throw switchError;
            }
        }
    }

    initContract(contractAddress) {
        if (!this.web3) throw new Error('Web3 not initialized');
        this.contractAddress = contractAddress;
        this.contract = new this.web3.eth.Contract(TOKEN_ABI, contractAddress);
    }

    async saveWalletAddress() {
        try {
            await fetch('api/save_wallet.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ wallet_address: this.account })
            });
        } catch (error) {
            console.error('Failed to save wallet address:', error);
        }
    }

    async setReferrer(referrerAddress) {
        return await this.contract.methods.setReferrer(referrerAddress).send({
            from: this.account,
            gas: 100000
        });
    }

    async getBalance(address = null) {
        const addr = address || this.account;
        const balance = await this.contract.methods.balanceOf(addr).call();
        return this.web3.utils.fromWei(balance, 'ether');
    }

    async getBNBBalance(address = null) {
        const addr = address || this.account;
        const balance = await this.web3.eth.getBalance(addr);
        return this.web3.utils.fromWei(balance, 'ether');
    }

    async stake(amount) {
        const amountWei = this.web3.utils.toWei(amount.toString(), 'ether');
        return await this.contract.methods.stake(amountWei).send({
            from: this.account,
            gas: 300000
        });
    }

    async unstake(amount) {
        const amountWei = this.web3.utils.toWei(amount.toString(), 'ether');
        return await this.contract.methods.unstake(amountWei).send({
            from: this.account,
            gas: 200000
        });
    }

    async claimRewards() {
        return await this.contract.methods.claimRewards().send({
            from: this.account,
            gas: 150000
        });
    }

    async getPendingRewards(address = null) {
        const addr = address || this.account;
        const rewards = await this.contract.methods.pendingRewards(addr).call();
        return this.web3.utils.fromWei(rewards, 'ether');
    }

    async getStakeInfo(address = null) {
        const addr = address || this.account;
        const info = await this.contract.methods.getStakeInfo(addr).call();

        return {
            stakedAmount: this.web3.utils.fromWei(info.stakedAmount, 'ether'),
            stakingTime: new Date(parseInt(info.stakingTime) * 1000),
            pendingReward: this.web3.utils.fromWei(info.pendingReward, 'ether')
        };
    }

    async getReferralInfo(address = null) {
        const addr = address || this.account;
        const info = await this.contract.methods.getReferralInfo(addr).call();

        return {
            referrer: info.referrer,
            totalRewards: this.web3.utils.fromWei(info.totalRewards, 'ether'),
            referralCount: parseInt(info.referralCount)
        };
    }

    async getReferralChain(address = null) {
        const addr = address || this.account;
        const chain = await this.contract.methods.getReferralChain(addr).call();
        return chain.filter(addr => addr !== '0x0000000000000000000000000000000000000000');
    }

    onAccountChanged(newAccount) {
        console.log('Account changed to:', newAccount);
        this.saveWalletAddress();
        if (typeof updateDashboard === 'function') {
            updateDashboard();
        }
    }

    async getTransactionReceipt(txHash) {
        return await this.web3.eth.getTransactionReceipt(txHash);
    }

    formatAddress(address) {
        if (!address || address === '0x0000000000000000000000000000000000000000') {
            return 'None';
        }
        return address.slice(0, 6) + '...' + address.slice(-4);
    }

    async waitForTransaction(txHash, callback) {
        const checkReceipt = async () => {
            const receipt = await this.getTransactionReceipt(txHash);
            if (receipt) {
                callback(receipt);
            } else {
                setTimeout(checkReceipt, 2000);
            }
        };
        checkReceipt();
    }
}

const web3Manager = new Web3Manager();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Web3Manager, web3Manager };
}
