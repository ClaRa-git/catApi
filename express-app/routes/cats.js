const express = require('express');
const router = express.Router();

const {
    getCats
} = require('../controllers/catsController');

router.get('/', getCats);

module.exports = router;