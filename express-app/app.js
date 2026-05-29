const express = require('express');
const cors = require('cors');

const catsRoutes = require('./routes/cats');

const app = express();

app.use(cors());
app.use(express.json());

app.use('/cats', catsRoutes);

const PORT = 3000;

app.listen(PORT, () => {
    console.log(`Express server running on port ${PORT}`);
});