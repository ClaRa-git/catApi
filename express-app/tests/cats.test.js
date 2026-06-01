const request = require('supertest');
const axios   = require('axios');
const app     = require('../app');

jest.mock('axios');

const MOCK_CATS = [
    { name: 'Abyssinian', origin: 'Egypt', temperament: 'Active', description: 'An ancient breed.' },
    { name: 'Bengal',     origin: 'USA',   temperament: 'Alert',  description: 'A wild look.' },
];

describe('GET /cats', () => {

    describe('quand Symfony répond correctement', () => {

        beforeEach(() => {
            axios.get.mockResolvedValue({ data: MOCK_CATS });
        });

        it('retourne le status 200', async () => {
            const res = await request(app).get('/cats');
            expect(res.statusCode).toBe(200);
        });

        it('retourne un tableau non vide', async () => {
            const res = await request(app).get('/cats');
            expect(Array.isArray(res.body)).toBe(true);
            expect(res.body.length).toBeGreaterThan(0);
        });

        it('chaque entrée contient les champs attendus', async () => {
            const res = await request(app).get('/cats');
            res.body.forEach(cat => {
                expect(cat).toHaveProperty('name');
                expect(cat).toHaveProperty('origin');
                expect(cat).toHaveProperty('temperament');
                expect(cat).toHaveProperty('description');
            });
        });

        it('pose un cookie session_id si absent', async () => {
            const res = await request(app).get('/cats');
            const cookies = res.headers['set-cookie'] ?? [];
            expect(cookies.some(c => c.startsWith('session_id='))).toBe(true);
        });

        it('ne repose pas de cookie si session_id déjà présent', async () => {
            const res = await request(app)
                .get('/cats')
                .set('Cookie', 'session_id=already-set');
            const cookies = res.headers['set-cookie'] ?? [];
            expect(cookies.some(c => c.startsWith('session_id='))).toBe(false);
        });
    });

    describe('quand Symfony est indisponible', () => {

        beforeEach(() => {
            axios.get.mockRejectedValue(new Error('ECONNREFUSED'));
        });

        it('retourne le status 500', async () => {
            const res = await request(app).get('/cats');
            expect(res.statusCode).toBe(500);
        });

        it('retourne un message d\'erreur JSON', async () => {
            const res = await request(app).get('/cats');
            expect(res.body).toHaveProperty('error');
            expect(typeof res.body.error).toBe('string');
        });
    });
});