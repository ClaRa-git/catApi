# server node
FROM node:22

# répertoire de travail
WORKDIR /app

# copie des fichiers nécessaires
COPY package*.json ./

# installation des dépendances
RUN npm install

# copie des autres fichiers
COPY . .

# exposer le port de l'application
EXPOSE 3000

# lancement du serveur node
CMD ["npm", "run", "dev"]