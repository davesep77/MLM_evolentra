// Universal Wallet Connector (for Sidebar)
class WalletConnector {
    constructor() {
        this.btn = document.getElementById('connect-wallet-btn');
        this.connected = false;
        this.provider = null;
        this.signer = null;
        this.account = null;

        // Auto-check on load
        this.checkConnection();
    }

    async checkConnection() {
        if (window.ethereum) {
            try {
                const accounts = await window.ethereum.request({ method: 'eth_accounts' });
                if (accounts.length > 0) {
                    // For auto-check, we just show UI as connected, but we don't force re-sign
                    // unless they click the button again or we want strict session checks every time.
                    // For now, jus update UI.
                    this.updateUI(accounts[0]);
                    this.connected = true;
                    this.account = accounts[0];
                }
            } catch (err) {
                console.error("Wallet check failed", err);
            }
        }
    }

    async connect() {
        if (!window.ethereum) {
            alert("No crypto wallet found! Please install Trust Wallet, MetaMask, or Binance Wallet extension.");
            window.open("https://trustwallet.com/browser-extension", "_blank");
            return;
        }

        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });

            // Force BSC Chain (Chain ID 56)
            try {
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: '0x38' }],
                });
            } catch (switchError) {
                if (switchError.code === 4902) {
                    await window.ethereum.request({
                        method: 'wallet_addEthereumChain',
                        params: [{
                            chainId: '0x38',
                            chainName: 'Binance Smart Chain',
                            nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 },
                            rpcUrls: ['https://bsc-dataseed.binance.org/'],
                            blockExplorerUrls: ['https://bscscan.com/']
                        }],
                    });
                }
            }

            // Successfully connected to wallet, now perform ownership proof
            this.performHandshake(accounts[0]);

        } catch (error) {
            console.error("Connection denied:", error);
        }
    }

    async performHandshake(address) {
        try {
            // 1. Get Nonce
            const nonceResp = await fetch('api/generate_nonce.php');
            const nonceData = await nonceResp.json();

            if (!nonceData.success) throw new Error("Could not generate auth nonce");

            const message = nonceData.message; // "Sign this unique message..."

            // 2. Request Signature
            // We use 'personal_sign' which is standard for EVM wallets
            // Params: [message, address]
            const signature = await window.ethereum.request({
                method: 'personal_sign',
                params: [message, address]
            });

            // 3. Send to Backend
            this.saveWalletToBackend(address, signature);

        } catch (err) {
            console.error("Handshake/Signature failed:", err);
            alert("Wallet connection failed: You must sign the message to verify ownership.");
        }
    }

    async saveWalletToBackend(address, signature) {
        try {
            const response = await fetch('api/save_wallet.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    wallet_address: address,
                    signature: signature
                })
            });
            const data = await response.json();
            if (data.success) {
                console.log("Wallet verified and saved.");
                this.updateUI(address);
                this.connected = true;
                this.account = address;
                alert("Wallet successfully connected and verified!");
            } else {
                console.warn("Backend verification failed:", data.error);
                alert("Verification failed: " + data.error);
            }
        } catch (err) {
            console.error("Error saving wallet to backend:", err);
        }
    }

    updateUI(account) {
        if (this.btn) {
            this.btn.classList.add('connected');
            const shortAddr = account.substring(0, 6) + '...' + account.substring(38);
            this.btn.innerHTML = `<span style="font-size:1.2rem;">🦊</span> ${shortAddr}`;
            this.btn.style.background = 'rgba(16, 185, 129, 0.2)';
            this.btn.style.color = '#34d399';
            this.btn.style.border = '1px solid rgba(16, 185, 129, 0.5)';
        }
    }

    // Deprecated direct connect, used internally by auto-check
    onConnect(account) {
        // Only for legacy/auto-load calls
        this.updateUI(account);
        this.connected = true;
        this.account = account;
    }
}

// Global Instance
let globalWallet;
document.addEventListener('DOMContentLoaded', () => {
    globalWallet = new WalletConnector();
});

// Exposed Function for Button
function connectWallet() {
    if (globalWallet) globalWallet.connect();
}
