const express = require('express');
const router = express.Router();
const { getCats, getCatById } = require('../controllers/catsController');
const { incrementView, getView } = require('../controllers/viewsController');
const { toggleFavorite, getFavorites, getFavoriteStatus } = require('../controllers/favoritesController');

router.post('/:id/favorites', toggleFavorite);
router.get('/favorites', getFavorites);
router.get('/:id/favorites', getFavoriteStatus);
router.post('/:id/views', incrementView);
router.get('/:id/views', getView);
router.get('/', getCats);
router.get('/:id', getCatById);

module.exports = router;