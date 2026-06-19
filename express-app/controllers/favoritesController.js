const axios = require('axios');
const SYMFONY_URL = process.env.SYMFONY_URL || 'http://symfony:8000';

const getHeaders = (req) => ({ 'X-Session-Id': req.sessionId });

const toggleFavorite = async (req, res) => {
  try {
    const { data } = await axios.post(`${SYMFONY_URL}/favorites/${req.params.id}`, {}, { headers: getHeaders(req) });
    res.json(data);
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

const getFavorites = async (req, res) => {
  try {
    const { data } = await axios.get(`${SYMFONY_URL}/favorites`, { headers: getHeaders(req) });
    res.json(data);
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

const getFavoriteStatus = async (req, res) => {
  try {
    const { data } = await axios.get(`${SYMFONY_URL}/favorites/${req.params.id}`, { headers: getHeaders(req) });
    res.json(data);
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

module.exports = { toggleFavorite, getFavorites, getFavoriteStatus };