CREATE DATABASE IF NOT EXISTS bcmr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bcmr;

CREATE TABLE IF NOT EXISTS empresas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  tipo ENUM('COMERCIAL','MANUTENCAO','TRANSPORTE') NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('ADMIN_GERAL','ADMIN_EMPRESA','FUNCIONARIO') NOT NULL DEFAULT 'FUNCIONARIO',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuario_empresas (
  usuario_id INT UNSIGNED NOT NULL,
  empresa_id INT UNSIGNED NOT NULL,
  PRIMARY KEY(usuario_id,empresa_id),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  slug VARCHAR(150),
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS produtos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  categoria_id INT UNSIGNED NULL,
  sku VARCHAR(100) NOT NULL UNIQUE,
  nome VARCHAR(200) NOT NULL,
  marca VARCHAR(120),
  modelo VARCHAR(120),
  preco_compra DECIMAL(12,2) NOT NULL DEFAULT 0,
  preco_venda DECIMAL(12,2) NOT NULL DEFAULT 0,
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id),
  FOREIGN KEY(categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  produto_id INT UNSIGNED NOT NULL,
  tipo ENUM('ENTRADA','VENDA','SAIDA_MANUAL','DEVOLUCAO','PERDA','AJUSTE') NOT NULL,
  quantidade INT NOT NULL,
  estoque_anterior INT NOT NULL,
  estoque_posterior INT NOT NULL,
  referencia VARCHAR(100),
  observacao TEXT,
  usuario_id INT UNSIGNED NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_estoque_produto_data(produto_id,criado_em),
  FOREIGN KEY(empresa_id) REFERENCES empresas(id),
  FOREIGN KEY(produto_id) REFERENCES produtos(id),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pedidos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  status ENUM('PENDENTE','AGUARDANDO_PAGAMENTO','PAGO','PROCESSANDO','FINALIZADO','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  estoque_reservado TINYINT(1) NOT NULL DEFAULT 0,
  reserva_expira_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS servicos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  protocolo VARCHAR(60) NOT NULL UNIQUE,
  cliente_nome VARCHAR(180) NULL,
  equipamento VARCHAR(200) NOT NULL,
  problema TEXT NULL,
  status ENUM('RECEBIDO','EM_ANALISE','AGUARDANDO_ORCAMENTO','AGUARDANDO_APROVACAO','EM_MANUTENCAO','FINALIZADO','ENTREGUE','CANCELADO') NOT NULL DEFAULT 'RECEBIDO',
  prazo DATETIME NULL,
  valor_estimado DECIMAL(12,2) DEFAULT 0,
  valor_final DECIMAL(12,2) DEFAULT 0,
  finalizado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS servicos_historico (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  servico_id BIGINT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  status_anterior VARCHAR(100),
  status_novo VARCHAR(100) NOT NULL,
  observacao TEXT,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_servico_hist(servico_id,criado_em),
  FOREIGN KEY(servico_id) REFERENCES servicos(id) ON DELETE CASCADE,
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Há apenas um motorista. Por isso o sistema não mantém cadastro nem seleção de motorista.
CREATE TABLE IF NOT EXISTS viagens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  cliente_nome VARCHAR(180) NULL,
  origem VARCHAR(255) NOT NULL,
  destino VARCHAR(255) NOT NULL,
  data_saida DATETIME NOT NULL,
  data_finalizacao DATETIME NULL,
  passageiros INT NOT NULL DEFAULT 1,
  tipo VARCHAR(80) NULL,
  observacao TEXT NULL,
  status ENUM('AGENDADA','A_CAMINHO','EMBARQUE','EM_VIAGEM','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'AGENDADA',
  valor DECIMAL(12,2) DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS viagens_historico (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  viagem_id BIGINT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  status_anterior VARCHAR(100),
  status_novo VARCHAR(100) NOT NULL,
  observacao TEXT,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_viagem_hist(viagem_id,criado_em),
  FOREIGN KEY(viagem_id) REFERENCES viagens(id) ON DELETE CASCADE,
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS eventos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  modulo VARCHAR(80) NULL,
  tipo VARCHAR(100) NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  prioridade ENUM('INFORMATIVO','ATENCAO','CRITICO') NOT NULL DEFAULT 'INFORMATIVO',
  entidade_tipo VARCHAR(100),
  entidade_id BIGINT UNSIGNED,
  chave_unica VARCHAR(190) NULL UNIQUE,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS notificacoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  evento_id BIGINT UNSIGNED NULL,
  titulo VARCHAR(255) NOT NULL,
  mensagem TEXT,
  prioridade ENUM('INFORMATIVO','ATENCAO','CRITICO') NOT NULL DEFAULT 'INFORMATIVO',
  lida TINYINT(1) NOT NULL DEFAULT 0,
  enviado_push TINYINT(1) NOT NULL DEFAULT 0,
  lida_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notificacao_usuario(usuario_id,lida,criado_em),
  FOREIGN KEY(empresa_id) REFERENCES empresas(id),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY(evento_id) REFERENCES eventos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  empresa_id INT UNSIGNED NULL,
  acao VARCHAR(120) NOT NULL,
  entidade_tipo VARCHAR(100) NULL,
  entidade_id BIGINT UNSIGNED NULL,
  descricao TEXT NULL,
  ip VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auditoria_data(criado_em),
  INDEX idx_auditoria_empresa(empresa_id),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS documentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  tipo ENUM('NFE','XML_NFE','ORCAMENTO','PROTOCOLO','COMPROVANTE','FOTO','OUTRO') NOT NULL DEFAULT 'OUTRO',
  entidade_tipo VARCHAR(100) NULL,
  entidade_id BIGINT UNSIGNED NULL,
  nome_original VARCHAR(255) NOT NULL,
  arquivo VARCHAR(500) NOT NULL,
  mime_type VARCHAR(150) NULL,
  tamanho_bytes BIGINT UNSIGNED NULL,
  sha256 CHAR(64) NULL,
  extraido_json JSON NULL,
  status_processamento ENUM('NAO_SOLICITADO','PENDENTE','PROCESSANDO','CONCLUIDO','ERRO') NOT NULL DEFAULT 'NAO_SOLICITADO',
  usuario_id INT UNSIGNED NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS integracao_sheets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NULL,
  tipo VARCHAR(100) NOT NULL,
  entidade_tipo VARCHAR(100) NULL,
  entidade_id BIGINT UNSIGNED NULL,
  status ENUM('PENDENTE','PROCESSANDO','SINCRONIZADO','ERRO') NOT NULL DEFAULT 'PENDENTE',
  tentativa INT NOT NULL DEFAULT 0,
  erro TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processado_em DATETIME NULL,
  INDEX idx_sheets_status(status,criado_em),
  FOREIGN KEY(empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
);

INSERT INTO empresas(id,nome,tipo) VALUES
(1,'Comercial Marques','COMERCIAL'),
(2,'Manutenções Marques','MANUTENCAO'),
(3,'Grupo BCMR Transportes','TRANSPORTE')
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO categorias(id,empresa_id,nome,slug) VALUES
(1,1,'Celulares','celulares'),(2,1,'Notebooks','notebooks'),(3,1,'Videogames','videogames'),
(4,1,'Computadores','computadores'),(5,1,'TVs','tvs'),(6,1,'Rádios','radios'),
(7,1,'Maquininhas','maquininhas'),(8,1,'Acessórios','acessorios')
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO produtos(empresa_id,categoria_id,sku,nome,marca,modelo,preco_venda,estoque_atual,estoque_minimo) VALUES
(1,1,'CM-CEL-0001','Samsung Galaxy A55','Samsung','A55',1999,5,2),
(1,3,'CM-GAME-0002','PlayStation 5','Sony','PS5',3899,2,2),
(1,2,'CM-NOTE-0003','Notebook Dell Inspiron','Dell','Inspiron',3499,0,1)
ON DUPLICATE KEY UPDATE nome=VALUES(nome),estoque_atual=VALUES(estoque_atual),estoque_minimo=VALUES(estoque_minimo);

INSERT IGNORE INTO pedidos(empresa_id,codigo,status,total,criado_em) VALUES
(1,'CM-000158','PAGO',3998,NOW()),(1,'CM-000157','PAGO',3899,NOW());

INSERT IGNORE INTO servicos(empresa_id,protocolo,cliente_nome,equipamento,status,prazo,valor_final) VALUES
(2,'MM-001582','Cliente Exemplo','Notebook Dell','EM_MANUTENCAO',DATE_SUB(NOW(),INTERVAL 1 DAY),0),
(2,'MM-001583','Cliente Exemplo','iPhone 14','EM_ANALISE',DATE_ADD(NOW(),INTERVAL 2 DAY),0),
(2,'MM-001584','Cliente Exemplo','PC Gamer','AGUARDANDO_APROVACAO',DATE_ADD(NOW(),INTERVAL 3 DAY),0);

INSERT IGNORE INTO viagens(empresa_id,codigo,cliente_nome,origem,destino,data_saida,passageiros,status,valor) VALUES
(3,'BCMR-2581','Cliente Exemplo','Barueri - SP','Campinas - SP',DATE_ADD(NOW(),INTERVAL 30 MINUTE),4,'AGENDADA',850),
(3,'BCMR-2579','Cliente Exemplo','Osasco - SP','Guarulhos - SP',DATE_SUB(NOW(),INTERVAL 18 MINUTE),3,'AGENDADA',620),
(3,'BCMR-2583','Cliente Exemplo','Barueri - SP','São Paulo - SP',DATE_ADD(NOW(),INTERVAL 3 HOUR),2,'AGENDADA',500);

-- V4: pagamentos, documentos seguros, push e fila de processamento assistido
CREATE TABLE IF NOT EXISTS pedido_itens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id BIGINT UNSIGNED NOT NULL,
  produto_id INT UNSIGNED NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY(pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY(produto_id) REFERENCES produtos(id)
);

CREATE TABLE IF NOT EXISTS pagamentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  pedido_id BIGINT UNSIGNED NULL,
  forma ENUM('PIX','CARTAO','DINHEIRO','LINK_PAGAMENTO','OUTRO') NOT NULL DEFAULT 'PIX',
  gateway VARCHAR(80) NOT NULL DEFAULT 'MANUAL',
  valor DECIMAL(12,2) NOT NULL,
  status ENUM('PENDENTE','APROVADO','RECUSADO','CANCELADO','ESTORNADO') NOT NULL DEFAULT 'PENDENTE',
  identificador_externo VARCHAR(190) NULL,
  idempotency_key VARCHAR(190) NULL UNIQUE,
  payload_gateway JSON NULL,
  pago_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pagamentos_status(status,criado_em),
  FOREIGN KEY(empresa_id) REFERENCES empresas(id),
  FOREIGN KEY(pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS push_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token VARCHAR(500) NOT NULL,
  plataforma ENUM('ANDROID','IOS','WEB') NOT NULL DEFAULT 'WEB',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_uso DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_push_token(token(190)),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS push_envios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  evento_id BIGINT UNSIGNED NULL,
  titulo VARCHAR(255) NOT NULL,
  mensagem TEXT NULL,
  prioridade ENUM('INFORMATIVO','ATENCAO','CRITICO') NOT NULL DEFAULT 'INFORMATIVO',
  status ENUM('PENDENTE','ENVIANDO','ENVIADO','ERRO') NOT NULL DEFAULT 'PENDENTE',
  tentativa INT NOT NULL DEFAULT 0,
  erro TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  enviado_em DATETIME NULL,
  INDEX idx_push_status(status,criado_em),
  FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY(evento_id) REFERENCES eventos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS documento_processamentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  documento_id BIGINT UNSIGNED NOT NULL,
  tipo_processamento ENUM('EXTRACAO_LOCAL','IA') NOT NULL DEFAULT 'IA',
  status ENUM('PENDENTE','PROCESSANDO','CONCLUIDO','ERRO') NOT NULL DEFAULT 'PENDENTE',
  tentativa INT NOT NULL DEFAULT 0,
  resultado_json JSON NULL,
  erro TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processado_em DATETIME NULL,
  INDEX idx_docproc_status(status,criado_em),
  FOREIGN KEY(documento_id) REFERENCES documentos(id) ON DELETE CASCADE
);

INSERT IGNORE INTO pagamentos(empresa_id,pedido_id,forma,gateway,valor,status,identificador_externo,pago_em)
SELECT 1,id,'PIX','DEMO',total,'APROVADO',CONCAT('DEMO-',codigo),criado_em FROM pedidos WHERE status='PAGO';
USE bcmr;

CREATE TABLE IF NOT EXISTS clientes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  cpf_cnpj VARCHAR(30) NULL,
  telefone VARCHAR(40) NULL,
  email VARCHAR(180) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cliente_doc(cpf_cnpj), INDEX idx_cliente_email(email)
);

ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS cliente_id BIGINT UNSIGNED NULL AFTER empresa_id;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS desconto DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal;

CREATE TABLE IF NOT EXISTS pedido_itens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id BIGINT UNSIGNED NOT NULL,
  produto_id INT UNSIGNED NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY(pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY(produto_id) REFERENCES produtos(id)
);

CREATE TABLE IF NOT EXISTS orcamentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL DEFAULT 2,
  servico_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  descricao TEXT NULL,
  valor_mao_obra DECIMAL(12,2) NOT NULL DEFAULT 0,
  valor_pecas DECIMAL(12,2) NOT NULL DEFAULT 0,
  valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('RASCUNHO','ENVIADO','APROVADO','RECUSADO','CANCELADO') NOT NULL DEFAULT 'RASCUNHO',
  token_publico CHAR(64) NULL UNIQUE,
  enviado_em DATETIME NULL,
  respondido_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(servico_id) REFERENCES servicos(id) ON DELETE CASCADE,
  FOREIGN KEY(empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS configuracoes (
  chave VARCHAR(120) PRIMARY KEY,
  valor TEXT NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
