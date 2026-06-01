const app = require('./app');

const PORT = process.env.PORT || 3000;
app.listen(PORT, '0.0.0.0', () => {
    console.log(`Express server running on port ${PORT}`);
});