const express = require('express');
const cors = require('cors');
const path = require('path');
const catsRoutes = require('./routes/cats');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'frontend')));
app.get('/breeds', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend', 'breeds.html'));
});

app.get('/breeds/:id', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend', 'breed.html')); // pour plus tard
});
app.use('/cats', catsRoutes);

const PORT = 3000;
app.listen(PORT, '0.0.0.0', () => {
  console.log(`Express server running on port ${PORT}`);
});