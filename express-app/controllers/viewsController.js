const axios = require('axios');
const SYMFONY_URL = process.env.SYMFONY_URL || 'http://symfony:8000';

const incrementView = async (req, res) => {
  try {
    const { data } = await axios.post(`${SYMFONY_URL}/views/${req.params.id}`);
    res.json(data);
  } catch (e) {
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

const getView = async (req, res) => {
  try {
    const { data } = await axios.get(`${SYMFONY_URL}/views/${req.params.id}`);
    res.json(data);
  } catch (e) {
    res.status(500).json({ error: 'Erreur serveur' });
  }
};

module.exports = { incrementView, getView };