/**
 * Multi-Wallet Connector
 * JavaScript library for connecting and interacting with multiple Web3 wallets
 */

class MultiWalletConnector {
    constructor() {
        this.connectedWallet = null;
        this.connectedAddress = null;
        this.isTestnet = false; // Toggle for testnet
        this.network = this.isTestnet ? 'BSC_TESTNET' : 'BSC';
        this.chainId = this.isTestnet ? '0x61' : '0x38'; // BSC Testnet : Mainnet

        // Contract addresses
        this.contracts = {
            USDT_BSC: '0x55d398326f99059fF775485246999027B3197955', // Mainnet
            USDT_BSC_TESTNET: '0x337610d27c682E347C9cD60BD4b3b107C9d34dDd', // Testnet
            PLATFORM_ADDRESS: '0xYourPlatformAddressHere' // TODO: Set your platform wallet
        };

        // Network configurations
        this.networks = {
            BSC: {
                chainId: '0x38',
                chainName: 'Binance Smart Chain',
                rpcUrls: ['https://bsc-dataseed.binance.org/'],
                blockExplorerUrls: ['https://bscscan.com/'],
                nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 }
            },
            BSC_TESTNET: {
                chainId: '0x61',
                chainName: 'BSC Testnet',
                rpcUrls: ['https://data-seed-prebsc-1-s1.binance.org:8545/'],
                blockExplorerUrls: ['https://testnet.bscscan.com/'],
                nativeCurrency: { name: 'tBNB', symbol: 'tBNB', decimals: 18 }
            }
        };
    }

    /**
     * Detect available wallets
     */
    detectWallets() {
        const wallets = {
            binance: typeof window.BinanceChain !== 'undefined',
            trust: window.ethereum && window.ethereum.isTrust,
            metamask: window.ethereum && window.ethereum.isMetaMask && !window.ethereum.isTrust,
            ethereum: typeof window.ethereum !== 'undefined'
        };

        console.log('Detected wallets:', wallets);
        return wallets;
    }

    /**
     * Connect to Binance Wallet
     */
    async connectBinanceWallet() {
        try {
            // Check if Binance Chain extension is installed
            if (typeof window.BinanceChain === 'undefined') {
                throw new Error('Binance Wallet extension not detected. Please install it from https://www.binance.com/en/wallet');
            }

            console.log('Binance Wallet detected, requesting accounts...');

            // Request account access
            const accounts = await window.BinanceChain.request({
                method: 'eth_requestAccounts'
            });

            console.log('Accounts received:', accounts);

            if (!accounts || accounts.length === 0) {
                throw new Error('No accounts found. Please unlock your Binance Wallet.');
            }

            const address = accounts[0];
            console.log('Connected address:', address);

            // Switch to BSC network
            await this.switchToBSC(window.BinanceChain);

            // Sign verification message
            const message = `Connect wallet to Evolentra\nAddress: ${address}\nTimestamp: ${Date.now()}`;
            console.log('Requesting signature...');

            const signature = await window.BinanceChain.request({
                method: 'personal_sign',
                params: [message, address]
            });

            console.log('Signature received');

            // Send to backend
            const result = await this.sendConnectionToBackend('binance', address, signature, message);

            if (result.success) {
                this.connectedWallet = 'binance';
                this.connectedAddress = address;
                console.log('Binance Wallet connected successfully');
                return { success: true, address, wallet: 'binance' };
            }

            throw new Error(result.error || 'Connection failed');

        } catch (error) {
            console.error('Binance Wallet connection error:', error);

            // Provide user-friendly error messages
            let errorMessage = error.message;

            if (error.code === 4001) {
                errorMessage = 'Connection request rejected. Please approve the connection in your Binance Wallet.';
            } else if (error.code === -32002) {
                errorMessage = 'Connection request already pending. Please check your Binance Wallet extension.';
            }

            return { success: false, error: errorMessage };
        }
    }

    /**
     * Connect to Trust Wallet
     */
    async connectTrustWallet() {
        try {
            if (!window.ethereum) {
                throw new Error('Trust Wallet not detected. Please install Trust Wallet or use the mobile app.');
            }

            // Request account access
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });

            if (accounts.length === 0) {
                throw new Error('No accounts found');
            }

            const address = accounts[0];

            // Switch to BSC network
            await this.switchToBSC(window.ethereum);

            // Sign verification message
            const message = `Connect wallet to Evolentra\nAddress: ${address}\nTimestamp: ${Date.now()}`;
            const signature = await window.ethereum.request({
                method: 'personal_sign',
                params: [message, address]
            });

            // Send to backend
            const result = await this.sendConnectionToBackend('trust', address, signature, message);

            if (result.success) {
                this.connectedWallet = 'trust';
                this.connectedAddress = address;
                return { success: true, address, wallet: 'trust' };
            }

            throw new Error(result.error || 'Connection failed');

        } catch (error) {
            console.error('Trust Wallet connection error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Connect to MetaMask
     */
    async connectMetaMask() {
        try {
            if (!window.ethereum) {
                throw new Error('MetaMask not detected. Please install MetaMask extension.');
            }

            // Request account access
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });

            if (accounts.length === 0) {
                throw new Error('No accounts found');
            }

            const address = accounts[0];

            // Switch to BSC network
            await this.switchToBSC(window.ethereum);

            // Sign verification message
            const message = `Connect wallet to Evolentra\nAddress: ${address}\nTimestamp: ${Date.now()}`;
            const signature = await window.ethereum.request({
                method: 'personal_sign',
                params: [message, address]
            });

            // Send to backend
            const result = await this.sendConnectionToBackend('metamask', address, signature, message);

            if (result.success) {
                this.connectedWallet = 'metamask';
                this.connectedAddress = address;
                return { success: true, address, wallet: 'metamask' };
            }

            throw new Error(result.error || 'Connection failed');

        } catch (error) {
            console.error('MetaMask connection error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Switch to BSC network (mainnet or testnet)
     */
    async switchToBSC(provider) {
        const networkConfig = this.isTestnet ? this.networks.BSC_TESTNET : this.networks.BSC;

        try {
            await provider.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: networkConfig.chainId }],
            });
        } catch (switchError) {
            // Chain not added, add it
            if (switchError.code === 4902) {
                await provider.request({
                    method: 'wallet_addEthereumChain',
                    params: [networkConfig]
                });
            } else {
                throw switchError;
            }
        }
    }

    /**
     * Toggle between mainnet and testnet
     */
    toggleTestnet(enabled) {
        this.isTestnet = enabled;
        this.network = enabled ? 'BSC_TESTNET' : 'BSC';
        this.chainId = enabled ? '0x61' : '0x38';
    }

    /**
     * Get current USDT contract address
     */
    getUSDTContract() {
        return this.isTestnet ? this.contracts.USDT_BSC_TESTNET : this.contracts.USDT_BSC;
    }

    /**
     * Get block explorer URL
     */
    getExplorerUrl(txHash) {
        const baseUrl = this.isTestnet ? 'https://testnet.bscscan.com' : 'https://bscscan.com';
        return `${baseUrl}/tx/${txHash}`;
    }

    /**
     * Send connection to backend
     */
    async sendConnectionToBackend(walletType, address, signature, message) {
        const response = await fetch('/api/wallet/connect.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                walletType,
                walletAddress: address,
                signature,
                message,
                network: this.network
            })
        });

        return await response.json();
    }

    /**
     * Send payment
     */
    async sendPayment(amount, token = 'USDT') {
        try {
            if (!this.connectedAddress) {
                throw new Error('No wallet connected');
            }

            // Initiate payment on backend
            const initResponse = await fetch('/api/wallet/payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'initiate',
                    amount,
                    walletType: this.connectedWallet,
                    walletAddress: this.connectedAddress,
                    token,
                    network: this.network
                })
            });

            const initResult = await initResponse.json();

            if (!initResult.success) {
                throw new Error(initResult.error);
            }

            // Get provider
            const provider = this.getProvider();

            // Convert amount to wei (USDT has 18 decimals on BSC)
            const amountWei = '0x' + (BigInt(Math.floor(amount * 1e18))).toString(16);

            // Prepare transaction
            const tx = {
                from: this.connectedAddress,
                to: this.contracts.USDT_BSC,
                data: this.encodeTransferData(this.contracts.PLATFORM_ADDRESS, amountWei),
                gas: '0x186A0', // 100000
            };

            // Send transaction
            const txHash = await provider.request({
                method: 'eth_sendTransaction',
                params: [tx],
            });

            // Verify transaction on backend
            const verifyResponse = await fetch('/api/wallet/payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify',
                    txHash,
                    amount,
                    walletAddress: this.connectedAddress,
                    network: this.network
                })
            });

            const verifyResult = await verifyResponse.json();

            return {
                success: true,
                txHash,
                status: verifyResult.status
            };

        } catch (error) {
            console.error('Payment error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Encode ERC20 transfer data
     */
    encodeTransferData(toAddress, amount) {
        // ERC20 transfer function signature
        const functionSignature = '0xa9059cbb';

        // Remove 0x prefix and pad address to 32 bytes
        const paddedAddress = toAddress.substring(2).padStart(64, '0');

        // Pad amount to 32 bytes
        const paddedAmount = amount.substring(2).padStart(64, '0');

        return functionSignature + paddedAddress + paddedAmount;
    }

    /**
     * Get current provider
     */
    getProvider() {
        if (this.connectedWallet === 'binance') return window.BinanceChain;
        if (this.connectedWallet === 'trust') return window.ethereum;
        if (this.connectedWallet === 'metamask') return window.ethereum;
        return window.ethereum;
    }

    /**
     * Disconnect wallet
     */
    disconnect() {
        this.connectedWallet = null;
        this.connectedAddress = null;
    }

    /**
     * Format address for display
     */
    formatAddress(address) {
        if (!address) return '';
        return `${address.substring(0, 6)}...${address.substring(address.length - 4)}`;
    }
}

// Export for use in other scripts
window.MultiWalletConnector = MultiWalletConnector;
