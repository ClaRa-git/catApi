const axios = require('axios');

const getCats = async (req, res) => {
    try {
        const response = await axios.get(
            'http://symfony:8000/cats'
        );

        res.json(response.data);

    } catch (error) {
        console.error(error.message);

        res.status(500).json({
            error: 'Unable to fetch cats'
        });
    }
};

module.exports = {
    getCats
};