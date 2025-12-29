/**
 * WalletConnect Integration
 * Adds support for mobile wallets via WalletConnect protocol
 */

// Add WalletConnect to MultiWalletConnector class
class WalletConnectIntegration {
    constructor() {
        this.provider = null;
        this.connector = null;
    }

    /**
     * Initialize WalletConnect
     */
    async initWalletConnect() {
        try {
            // Import WalletConnect provider
            const WalletConnectProvider = window.WalletConnectProvider.default;

            // Create provider
            this.provider = new WalletConnectProvider({
                rpc: {
                    56: 'https://bsc-dataseed.binance.org/', // BSC Mainnet
                    97: 'https://data-seed-prebsc-1-s1.binance.org:8545/' // BSC Testnet
                },
                chainId: 56,
                qrcode: true,
                qrcodeModalOptions: {
                    mobileLinks: [
                        'rainbow',
                        'metamask',
                        'argent',
                        'trust',
                        'imtoken',
                        'pillar',
                    ],
                }
            });

            return true;
        } catch (error) {
            console.error('WalletConnect initialization error:', error);
            return false;
        }
    }

    /**
     * Connect via WalletConnect
     */
    async connectWalletConnect() {
        try {
            if (!this.provider) {
                await this.initWalletConnect();
            }

            // Enable session (shows QR Code modal)
            await this.provider.enable();

            const accounts = this.provider.accounts;
            const chainId = this.provider.chainId;

            if (accounts.length === 0) {
                throw new Error('No accounts found');
            }

            const address = accounts[0];

            // Sign verification message
            const message = `Connect wallet to Evolentra\nAddress: ${address}\nTimestamp: ${Date.now()}`;
            const signature = await this.provider.request({
                method: 'personal_sign',
                params: [message, address]
            });

            // Send to backend
            const response = await fetch('/api/wallet/connect.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    walletType: 'walletconnect',
                    walletAddress: address,
                    signature,
                    message,
                    network: chainId === 56 ? 'BSC' : 'BSC_TESTNET'
                })
            });

            const result = await response.json();

            if (result.success) {
                return { success: true, address, wallet: 'walletconnect' };
            }

            throw new Error(result.error || 'Connection failed');

        } catch (error) {
            console.error('WalletConnect connection error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Send payment via WalletConnect
     */
    async sendPaymentWalletConnect(amount, contractAddress, platformAddress) {
        try {
            if (!this.provider) {
                throw new Error('WalletConnect not initialized');
            }

            const accounts = this.provider.accounts;
            const fromAddress = accounts[0];

            // Convert amount to wei
            const amountWei = '0x' + (BigInt(Math.floor(amount * 1e18))).toString(16);

            // Encode transfer data
            const functionSignature = '0xa9059cbb';
            const paddedAddress = platformAddress.substring(2).padStart(64, '0');
            const paddedAmount = amountWei.substring(2).padStart(64, '0');
            const data = functionSignature + paddedAddress + paddedAmount;

            // Send transaction
            const txHash = await this.provider.request({
                method: 'eth_sendTransaction',
                params: [{
                    from: fromAddress,
                    to: contractAddress,
                    data: data,
                    gas: '0x186A0', // 100000
                }],
            });

            return { success: true, txHash };

        } catch (error) {
            console.error('WalletConnect payment error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Disconnect WalletConnect
     */
    async disconnectWalletConnect() {
        if (this.provider) {
            await this.provider.disconnect();
            this.provider = null;
        }
    }
}

// Add to global scope
window.WalletConnectIntegration = WalletConnectIntegration;

// Extend MultiWalletConnector with WalletConnect support
if (window.MultiWalletConnector) {
    const originalClass = window.MultiWalletConnector;

    window.MultiWalletConnector = class extends originalClass {
        constructor() {
            super();
            this.walletConnect = new WalletConnectIntegration();
        }

        async connectWalletConnect() {
            const result = await this.walletConnect.connectWalletConnect();

            if (result.success) {
                this.connectedWallet = 'walletconnect';
                this.connectedAddress = result.address;
            }

            return result;
        }

        async sendPayment(amount, token = 'USDT') {
            if (this.connectedWallet === 'walletconnect') {
                // Use WalletConnect for payment
                const result = await this.walletConnect.sendPaymentWalletConnect(
                    amount,
                    this.contracts.USDT_BSC,
                    this.contracts.PLATFORM_ADDRESS
                );

                if (result.success) {
                    // Verify on backend
                    await fetch('/api/wallet/payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'verify',
                            txHash: result.txHash,
                            amount,
                            walletAddress: this.connectedAddress,
                            network: this.network
                        })
                    });
                }

                return result;
            } else {
                // Use original method for other wallets
                return super.sendPayment(amount, token);
            }
        }

        disconnect() {
            if (this.connectedWallet === 'walletconnect') {
                this.walletConnect.disconnectWalletConnect();
            }
            super.disconnect();
        }
    };
}
