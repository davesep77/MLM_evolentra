class BinanceIntegration {
    constructor() {
        this.ws = null;
        this.streams = new Set();
        this.callbacks = {}; // map streamName -> callback
        this.baseStreamUrl = 'wss://stream.binance.com/stream?streams=';
        this.restUrl = 'api/binance/proxy.php';
    }

    // --- REST Methods ---

    async fetchKlines(symbol, interval, limit = 500) {
        try {
            const response = await fetch(`${this.restUrl}?action=klines&symbol=${symbol.toUpperCase()}&interval=${interval}&limit=${limit}`);
            const data = await response.json();

            if (data.error) throw new Error(data.error);

            // Convert to Lightweight Charts format
            return data.map(k => ({
                time: k[0] / 1000,
                open: parseFloat(k[1]),
                high: parseFloat(k[2]),
                low: parseFloat(k[3]),
                close: parseFloat(k[4])
            }));
        } catch (error) {
            console.error('Error fetching klines:', error);
            return [];
        }
    }

    async fetchTicker(symbol) {
        try {
            const response = await fetch(`${this.restUrl}?action=ticker&symbol=${symbol.toUpperCase()}`);
            return await response.json();
        } catch (error) {
            console.error('Error fetching ticker:', error);
            return null;
        }
    }

    async fetchTickers(symbols) {
        try {
            const symbolsParam = Array.isArray(symbols) ? symbols.join(',') : symbols;
            const response = await fetch(`${this.restUrl}?action=ticker&symbols=${symbolsParam.toUpperCase()}`);
            return await response.json();
        } catch (error) {
            console.error('Error fetching tickers:', error);
            return [];
        }
    }

    // --- WebSocket Methods ---

    /**
     * Subscribe to streams.
     * @param {string|string[]} streams - e.g. "btcusdt@kline_1m"
     * @param {function} callback - Function(data)
     */
    subscribe(streams, callback) {
        if (!Array.isArray(streams)) streams = [streams];

        streams.forEach(s => {
            const streamName = s.toLowerCase();
            this.streams.add(streamName);
            this.callbacks[streamName] = callback;
        });

        this.reconnect();
    }

    reconnect() {
        if (this.ws) {
            this.ws.close();
        }

        if (this.streams.size === 0) return;

        const combinedStreams = Array.from(this.streams).join('/');
        const url = `${this.baseStreamUrl}${combinedStreams}`;

        console.log(`Connecting to Binance WS: ${url}`);
        this.ws = new WebSocket(url);

        this.ws.onmessage = (event) => {
            const message = JSON.parse(event.data);
            // message format: { stream: "...", data: { ... } }

            if (message.stream && this.callbacks[message.stream]) {
                this.callbacks[message.stream](message.data);
            }
        };

        this.ws.onopen = () => console.log('Binance WS Connected');
        this.ws.onerror = (err) => console.error('Binance WS Error:', err);
    }

    close() {
        if (this.ws) this.ws.close();
    }
}
