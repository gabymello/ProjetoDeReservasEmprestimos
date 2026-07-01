-- =========================================================
-- Dados de exemplo para o banco `sistema_reservas`
-- (opcional — execute depois de importar sistema_reservas.sql)
-- =========================================================

USE sistema_reservas;

INSERT INTO `categoria` (`nome`, `descricao`) VALUES
('Audiovisual', 'Equipamentos de projeção, som e imagem'),
('Informática', 'Notebooks, desktops e periféricos'),
('Mobiliário', 'Mesas, cadeiras e armários');

INSERT INTO `setor` (`nome`, `responsavel`, `telefone`, `email`) VALUES
('TI', 'Carlos Mendes', '(51) 99999-0001', 'ti@instituicao.com'),
('Secretaria', 'Fernanda Alves', '(51) 99999-0002', 'secretaria@instituicao.com'),
('Coordenação Pedagógica', 'Juliana Prado', '(51) 99999-0003', 'coordenacao@instituicao.com');

INSERT INTO `recurso` (`nome`, `descricao`, `id_categoria`, `id_setor`, `status`, `localizacao`, `foto`) VALUES
('Projetor Epson PowerLite', 'Projetor multimídia full HD', 1, 1, 'Disponível', 'Sala de TI', NULL),
('Notebook Dell Latitude', 'Notebook para uso administrativo', 2, 2, 'Em uso', 'Secretaria - Mesa 3', NULL),
('Mesa redonda 6 lugares', 'Mesa para reuniões pequenas', 3, 3, 'Disponível', 'Sala de reuniões B', NULL);
