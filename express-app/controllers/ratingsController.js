const axios = require('axios');
const SYMFONY_URL = process.env.SYMFONY_URL || 'http://symfony:8000';

const getHeaders = (req) => ({ 'X-Session-Id': req.sessionId });

const getRating = async (req, res) => {
  try {
    const { data } = await axios.get(`${SYMFONY_URL}/ratings/${req.params.id}`, { headers: getHeaders(req) });
    res.json(data);
  } catch (e) {
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

const postRating = async (req, res) => {
  try {
    const { data } = await axios.post(
      `${SYMFONY_URL}/ratings/${req.params.id}`,
      req.body,
      { headers: getHeaders(req) }
    );
    res.json(data);
  } catch (e) {
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

module.exports = { getRating, postRating };