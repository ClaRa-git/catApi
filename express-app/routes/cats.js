const express = require('express');
const router = express.Router();
const { getCats, getCatById } = require('../controllers/catsController');
const { incrementView, getView } = require('../controllers/viewsController');

router.post('/:id/views', incrementView);
router.get('/:id/views', getView);
router.get('/', getCats);
router.get('/:id', getCatById);

module.exports = router;