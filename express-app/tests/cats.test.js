const request = require('supertest');
const express = require('express');

const app = express();

// mock route simple (on teste gateway logique sans Symfony réel)
app.get('/cats', (req, res) => {
    res.json([{ name: "Test Cat" }]);
});

describe('GET /cats', () => {
    it('should return cats list', async () => {
        const res = await request(app).get('/cats');

        expect(res.statusCode).toBe(200);
        expect(res.body.length).toBeGreaterThan(0);
        expect(res.body[0]).toHaveProperty('name');
    });
});