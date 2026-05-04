// server.js - Simple JSON Server
const express = require('express');
const fs = require('fs').promises;
const path = require('path');

const app = express();
const PORT = 3001;
const DB_FILE = path.join(__dirname, 'db.json');

// Middleware
app.use(express.json());
app.use(express.static(__dirname)); // Serve static files

// Load database
async function loadDB() {
    try {
        const data = await fs.readFile(DB_FILE, 'utf8');
        return JSON.parse(data);
    } catch (error) {
        // If file doesn't exist, create default structure
        const defaultDB = {
            products: [],
            orders: [],
            settings: {}
        };
        await saveDB(defaultDB);
        return defaultDB;
    }
}

// Save database
async function saveDB(data) {
    await fs.writeFile(DB_FILE, JSON.stringify(data, null, 2), 'utf8');
}

// Routes

// GET all data
app.get('/db', async (req, res) => {
    try {
        const db = await loadDB();
        res.json(db);
    } catch (error) {
        res.status(500).json({ error: 'Failed to load database' });
    }
});

// GET all orders
app.get('/orders', async (req, res) => {
    try {
        const db = await loadDB();
        res.json(db.orders);
    } catch (error) {
        res.status(500).json({ error: 'Failed to load orders' });
    }
});

// GET order by ID
app.get('/orders/:id', async (req, res) => {
    try {
        const db = await loadDB();
        const order = db.orders.find(o => o.id === req.params.id);
        
        if (order) {
            res.json(order);
        } else {
            res.status(404).json({ error: 'Order not found' });
        }
    } catch (error) {
        res.status(500).json({ error: 'Failed to load order' });
    }
});

// POST new order
app.post('/orders', async (req, res) => {
    try {
        const db = await loadDB();
        const newOrder = req.body;
        
        // Add ID if not provided
        if (!newOrder.id) {
            newOrder.id = 'ORD' + Date.now().toString().slice(-6);
        }
        
        // Add timestamp if not provided
        if (!newOrder.tanggal) {
            newOrder.tanggal = new Date().toLocaleString('id-ID');
        }
        
        db.orders.push(newOrder);
        await saveDB(db);
        
        res.status(201).json(newOrder);
    } catch (error) {
        res.status(500).json({ error: 'Failed to create order' });
    }
});

// UPDATE order
app.put('/orders/:id', async (req, res) => {
    try {
        const db = await loadDB();
        const index = db.orders.findIndex(o => o.id === req.params.id);
        
        if (index !== -1) {
            db.orders[index] = { ...db.orders[index], ...req.body };
            await saveDB(db);
            res.json(db.orders[index]);
        } else {
            res.status(404).json({ error: 'Order not found' });
        }
    } catch (error) {
        res.status(500).json({ error: 'Failed to update order' });
    }
});

// DELETE order
app.delete('/orders/:id', async (req, res) => {
    try {
        const db = await loadDB();
        const index = db.orders.findIndex(o => o.id === req.params.id);
        
        if (index !== -1) {
            db.orders.splice(index, 1);
            await saveDB(db);
            res.json({ message: 'Order deleted' });
        } else {
            res.status(404).json({ error: 'Order not found' });
        }
    } catch (error) {
        res.status(500).json({ error: 'Failed to delete order' });
    }
});

// GET products
app.get('/products', async (req, res) => {
    try {
        const db = await loadDB();
        res.json(db.products);
    } catch (error) {
        res.status(500).json({ error: 'Failed to load products' });
    }
});

// GET settings
app.get('/settings', async (req, res) => {
    try {
        const db = await loadDB();
        res.json(db.settings);
    } catch (error) {
        res.status(500).json({ error: 'Failed to load settings' });
    }
});

// Serve index.html for root route
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// Start server
app.listen(PORT, () => {
    console.log(`✅ Server berjalan di http://localhost:${PORT}`);
    console.log(`📁 Database file: ${DB_FILE}`);
    console.log(`📋 Halaman utama: http://localhost:${PORT}`);
    console.log(`👑 Admin password: admin123`);
    console.log(`🔄 Auto-refresh setiap 30 detik untuk admin`);
});