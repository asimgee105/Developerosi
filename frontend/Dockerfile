FROM node:22-alpine

WORKDIR /app

# Copy package configuration
COPY package.json ./

# Install dependencies ignoring scripts to prevent race conditions during postinstall
RUN npm install --ignore-scripts

# Copy the rest of the application files
COPY . .

# Run nuxt prepare explicitly after all dependencies are fully unpacked
RUN npx nuxt prepare

EXPOSE 3000

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
