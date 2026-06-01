const axios = require('axios');
const SYMFONY_URL = process.env.SYMFONY_URL || 'http://symfony:8000';

const getHeaders = (req) => ({ 'X-Session-Id': req.sessionId });

const getCats = async (req, res) => {
  try {
    const response = await axios.get(`${SYMFONY_URL}/cats`, { headers: getHeaders(req) });
    res.json(response.data);
  } catch (error) {
    console.error(error.message);
    res.status(500).json({ error: 'Unable to fetch cats' });
  }
};

const getCatById = async (req, res) => {
  try {
    const response = await axios.get(`${SYMFONY_URL}/cats/${req.params.id}`, { headers: getHeaders(req) });
    res.json(response.data);
  } catch (error) {
    console.error(error.message);
    res.status(error.response?.status || 500).json({ error: 'Unable to fetch cat' });
  }
};

module.exports = { getCats, getCatById };