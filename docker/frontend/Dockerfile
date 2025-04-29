FROM node:20-alpine

WORKDIR /app

# Instalar dependências necessárias para o Angular
RUN npm install -g @angular/cli

# Copiar os arquivos de configuração para instalar dependências
COPY package.json package-lock.json* ./

# Instalar dependências
RUN npm install

# Copiar o código-fonte
COPY . .

# Expor a porta
EXPOSE 4200

# Iniciar o servidor de desenvolvimento
CMD ["ng", "serve", "--host", "0.0.0.0", "--disable-host-check"]