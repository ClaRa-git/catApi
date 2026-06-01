const express = require('express');
const cors = require('cors');
const path = require('path');
const cookieParser = require('cookie-parser');
const { v4: uuidv4 } = require('uuid');
const catsRoutes = require('./routes/cats');

const app = express();

app.use(cors());
app.use(express.json());
app.use(cookieParser());
app.use(express.static(path.join(__dirname, 'frontend')));

// middleware session cookie
app.use((req, res, next) => {
  if (!req.cookies?.session_id) {
    const sessionId = uuidv4();
    res.cookie('session_id', sessionId, { maxAge: 365 * 24 * 60 * 60 * 1000, httpOnly: true });
    req.sessionId = sessionId;
  } else {
    req.sessionId = req.cookies.session_id;
  }
  next();
});

app.get('/breeds', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend', 'breeds.html'));
});

app.get('/breeds/:id', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend', 'breed.html'));
});

app.use('/cats', catsRoutes);

const PORT = 3000;
app.listen(PORT, '0.0.0.0', () => {
  console.log(`Express server running on port ${PORT}`);
});