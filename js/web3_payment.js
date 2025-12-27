class Web3Payment {
    constructor(config) {
        this.masterAddress = config.masterAddress;
        this.usdtAddress = config.usdtContract || '0x55d398326f99059fF775485246999027B3197955'; // Default BSC USDT
        this.abi = [
            "function transfer(address to, uint amount) returns (boolean)",
            "function decimals() view returns (uint8)",
            "function balanceOf(address account) view returns (uint256)"
        ];
        this.provider = null;
        this.signer = null;
        this.isConnected = false;

        // Delay init slightly to ensure libraries load
        setTimeout(() => this.init(), 500);
    }

    init() {
        if (typeof ethers === 'undefined') {
            console.error("Ethers.js library not loaded!");
            // Retry once after 1 second if meaningful
            setTimeout(() => {
                if (typeof ethers === 'undefined') {
                    // alert("System Error: Web3 Library (Ethers.js) failed to load. Please check internet connection.");
                } else {
                    this.init();
                }
            }, 1000);
            return;
        }

        if (window.ethereum) {
            this.provider = new ethers.providers.Web3Provider(window.ethereum);
        } else {
            console.warn("No Web3 Provider found (window.ethereum is undefined)");
        }
    }

    async connect() {
        // Double check provider
        if (!this.provider) this.init();

        if (!window.ethereum) {
            // Mobile Redirect Logic for Trust Wallet
            const currentUrl = encodeURIComponent(window.location.href);
            const trustWalletLink = `https://link.trustwallet.com/open_url?coin_id=20000714&url=${currentUrl}`;

            if (confirm("Wallet not found. Open in TRUST WALLET App?\n\nClick OK to redirect to Trust Wallet,\nor Cancel to install browser extension.")) {
                window.location.href = trustWalletLink;
                return false;
            } else {
                window.open("https://trustwallet.com/browser-extension", "_blank");
                return false;
            }
        }

        try {
            await this.provider.send("eth_requestAccounts", []);
            this.signer = this.provider.getSigner();
            const address = await this.signer.getAddress();

            // ---------------------------------------------------------
            // SECURITY CHECK: Exchange / Hot Wallet Detection (API)
            // ---------------------------------------------------------
            const walletCheck = await this.detectWalletType(address);
            if (walletCheck.isBlocked) {
                alert(`Security Warning:\n\nDetected ${walletCheck.type} (${walletCheck.label}).\n\nPlease connect a personal wallet (MetaMask, Trust Wallet) instead of an exchange hot wallet for safe verification.`);
                return false; // Stop connection
            }
            // ---------------------------------------------------------

            this.userAddress = address;
            this.isConnected = true;

            // Check Network (Force BSC)
            const network = await this.provider.getNetwork();
            if (network.chainId !== 56) {
                try {
                    await window.ethereum.request({
                        method: 'wallet_switchEthereumChain',
                        params: [{ chainId: '0x38' }], // 56 in hex
                    });
                } catch (switchError) {
                    if (switchError.code === 4902) {
                        try {
                            await window.ethereum.request({
                                method: 'wallet_addEthereumChain',
                                params: [{
                                    chainId: '0x38',
                                    chainName: 'Binance Smart Chain',
                                    nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 },
                                    rpcUrls: ['https://bsc-dataseed.binance.org/'],
                                    blockExplorerUrls: ['https://bscscan.com/'],
                                }],
                            });
                        } catch (addError) { console.error(addError); }
                    }
                }
            }

            this.updateUI();
            return true;
        } catch (error) {
            console.error("Connection Error:", error);
            // alert("Connection failed: " + error.message);
            return false;
        }
    }

    async detectWalletType(walletAddress) {
        // API Check Logic (Etherscan V2 / BscScan)
        // Note: For full functionality, replace 'YourApiKeyToken' with a valid BscScan Pro API Key.
        const API_KEY = 'YourApiKeyToken';
        const url = `https://api.etherscan.io/v2/api?chainid=56&module=account&action=metadata&address=${walletAddress}&apikey=${API_KEY}`;

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.status === "1" && data.result) {
                const label = (data.result.nameTag || "").toLowerCase();
                const isBinance = label.includes("binance");
                const isExchange = label.includes("exchange") || label.includes("hot wallet");

                if (isBinance || isExchange) {
                    return {
                        isBlocked: true,
                        label: data.result.nameTag,
                        type: isBinance ? "Binance Exchange Account" : "Exchange Hot Wallet"
                    };
                }
            }
            return { isBlocked: false, label: "Private Wallet" };
        } catch (error) {
            console.warn("Wallet Detection Skipped (API Error)", error);
            return { isBlocked: false, error: true };
        }
    }

    updateUI() {
        const btn = document.getElementById('btn-connect-wallet');
        if (btn) {
            btn.innerHTML = `<i class="fas fa-check-circle"></i> ${this.userAddress.substring(0, 6)}...${this.userAddress.substring(38)}`;
            btn.classList.add('connected');
            btn.classList.remove('btn-outline');
            btn.classList.add('btn-primary');
        }

        const manualSection = document.getElementById('manual-tx-input-section');
        const web3Section = document.getElementById('web3-payment-section');

        if (manualSection) manualSection.style.display = 'none';
        if (web3Section) web3Section.style.display = 'block';
    }

    async pay(amount) {
        if (!this.isConnected) {
            const res = await this.connect();
            if (!res) return;
        }

        try {
            const contract = new ethers.Contract(this.usdtAddress, this.abi, this.signer);
            const amountWei = ethers.utils.parseUnits(amount.toString(), 18);

            const tx = await contract.transfer(this.masterAddress, amountWei);

            const statusEl = document.getElementById('payment-status');
            if (statusEl) statusEl.innerHTML = `<div class="status-pending" style="color:#f0b90b"><i class="fas fa-spinner fa-spin"></i> Transaction Pending...</div>`;

            await tx.wait();

            if (statusEl) statusEl.innerHTML = `<div class="status-success" style="color:#10b981"><i class="fas fa-check"></i> Transaction Confirmed!</div>`;

            const txInput = document.querySelector('input[name="txid"]');
            if (txInput) {
                txInput.value = tx.hash;
                alert("Payment Successful! Transaction Hash copied to form. Please click Submit.");
            }

            return tx.hash;

        } catch (error) {
            console.error("Payment Error:", error);
            alert("Payment Failed: " + (error.reason || error.message));
        }
    }
}
