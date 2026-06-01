const express = require('express');
const router = express.Router();
const { getCats, getCatById } = require('../controllers/catsController');

router.get('/', getCats);
router.get('/:id', getCatById);

module.exports = router;