-- ============================================
-- Script SQL gerado automaticamente
-- Backup: SisRealDriver_Backup_2025-12-24_12-27-38.json
-- Data: 2025-12-24 16:34:34
-- ============================================

USE `u179630068_realdriver`;

-- Desabilitar verificações temporariamente para inserção rápida
SET FOREIGN_KEY_CHECKS = 0;
SET AUTOCOMMIT = 0;

START TRANSACTION;

-- ============================================
-- MOTORISTAS
-- ============================================

INSERT INTO motoristas (id, nome, cpf, cnh, telefone, endereco, status) VALUES (2, 'Aline Dias Sabino', '082.542.186-17', 04860362555, 31998529634, 'Rua Cristiano Otoni, 11, Primeiro de Maio, Ouro Branco/MG', 'Ativo') ON DUPLICATE KEY UPDATE nome = 'Aline Dias Sabino', cpf = '082.542.186-17', cnh = 04860362555, telefone = 31998529634, endereco = 'Rua Cristiano Otoni, 11, Primeiro de Maio, Ouro Branco/MG', status = 'Ativo';
INSERT INTO motoristas (id, nome, cpf, cnh, telefone, endereco, status) VALUES (3, 'Lucas Davidson Souza Marinho', '149.272.236-71', 074459908571, 31995999153, 'Rua Lafersa, 742, Siderurgia, Ouro Branco MG', 'Ativo') ON DUPLICATE KEY UPDATE nome = 'Lucas Davidson Souza Marinho', cpf = '149.272.236-71', cnh = 074459908571, telefone = 31995999153, endereco = 'Rua Lafersa, 742, Siderurgia, Ouro Branco MG', status = 'Ativo';
INSERT INTO motoristas (id, nome, cpf, cnh, telefone, endereco, status) VALUES (4, 'Léia Paz Gonçalves Moreira', '611.634.906-44', 04175191079, 31992826721, 'Rua Nuno José Vieira, 198, Centro, Ouro Branco/MG.', 'Ativo') ON DUPLICATE KEY UPDATE nome = 'Léia Paz Gonçalves Moreira', cpf = '611.634.906-44', cnh = 04175191079, telefone = 31992826721, endereco = 'Rua Nuno José Vieira, 198, Centro, Ouro Branco/MG.', status = 'Ativo';
INSERT INTO motoristas (id, nome, cpf, cnh, telefone, endereco, status) VALUES (5, 'Francisco José Caetano', 07358297644, 04166895311, 31981195524, 'Rua Rouxinol, 88, Serra, Ouro Branco MG', 'Ativo') ON DUPLICATE KEY UPDATE nome = 'Francisco José Caetano', cpf = 07358297644, cnh = 04166895311, telefone = 31981195524, endereco = 'Rua Rouxinol, 88, Serra, Ouro Branco MG', status = 'Ativo';

-- ============================================
-- VEÍCULOS
-- ============================================

INSERT INTO veiculos (id, modelo, marca, placa, ano, cor, motorista_id, status) VALUES (1, 'CRUZE LT 1.8 16V FlexPower 4p Aut.', 'GM - Chevrolet', 'PWI9H80', 2015, 'Prata', 5, 'Ativo') ON DUPLICATE KEY UPDATE modelo = 'CRUZE LT 1.8 16V FlexPower 4p Aut.', marca = 'GM - Chevrolet', ano = 2015, cor = 'Prata', motorista_id = 5, status = 'Ativo';
INSERT INTO veiculos (id, modelo, marca, placa, ano, cor, motorista_id, status) VALUES (2, 'VERSA S 1.6 16V Flex Fuel 4p Mec.', 'Nissan', 'NXX5G28', 2012, 'Prata', 2, 'Ativo') ON DUPLICATE KEY UPDATE modelo = 'VERSA S 1.6 16V Flex Fuel 4p Mec.', marca = 'Nissan', ano = 2012, cor = 'Prata', motorista_id = 2, status = 'Ativo';
INSERT INTO veiculos (id, modelo, marca, placa, ano, cor, motorista_id, status) VALUES (3, 'AGILE LTZ 1.4 MPFI 8V FlexPower 5p', 'GM - Chevrolet', 'LUD3A39', 2010, 'Prata', 4, 'Ativo') ON DUPLICATE KEY UPDATE modelo = 'AGILE LTZ 1.4 MPFI 8V FlexPower 5p', marca = 'GM - Chevrolet', ano = 2010, cor = 'Prata', motorista_id = 4, status = 'Ativo';
INSERT INTO veiculos (id, modelo, marca, placa, ano, cor, motorista_id, status) VALUES (4, 'VW/VOYAGE TL MA', 'VW/VOLKSWAGEN', 'PPG8A42', 2016, 'BRANCA', 3, 'Ativo') ON DUPLICATE KEY UPDATE modelo = 'VW/VOYAGE TL MA', marca = 'VW/VOLKSWAGEN', ano = 2016, cor = 'BRANCA', motorista_id = 3, status = 'Ativo';

-- ============================================
-- DIÁRIAS
-- ============================================

INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (6, 4, 3, '2025-10-06', 57.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-06', valor = 57.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (7, 4, 3, '2025-10-06', 35.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-06', valor = 35.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (8, 4, 3, '2025-10-08', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-08', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (9, 4, 3, '2025-10-08', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-08', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (10, 4, 3, '2025-10-09', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-09', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (11, 4, 3, '2025-10-09', 15.30, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-09', valor = 15.30, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (12, 4, 3, '2025-10-10', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-10', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (13, 4, 3, '2025-10-11', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-11', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (14, 4, 3, '2025-10-12', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-12', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (15, 4, 3, '2025-10-13', 50.00, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-13', valor = 50.00, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (17, 4, 3, '2025-10-14', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-14', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (19, 4, 3, '2025-10-14', 57.14, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-14', valor = 57.14, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (20, 4, 3, '2025-10-16', 50.00, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-16', valor = 50.00, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (26, 2, 2, '2025-10-19', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-19', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (27, 2, 2, '2025-10-20', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-20', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (30, 4, 3, '2025-10-20', 63.27, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-20', valor = 63.27, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (31, 4, 3, '2025-10-20', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-20', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (32, 5, 1, '2025-09-30', 778.79, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-09-30', valor = 778.79, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (35, 5, 1, '2025-09-30', 1771.72, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-09-30', valor = 1771.72, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (37, 4, 3, '2025-10-21', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-21', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (38, 2, 2, '2025-10-21', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-21', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (39, 2, 2, '2025-10-22', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-22', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (40, 4, 3, '2025-10-22', 36.00, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-22', valor = 36.00, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (41, 3, 4, '2025-10-21', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-21', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (42, 4, 3, '2025-10-25', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-25', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (43, 4, 3, '2025-10-26', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-26', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (44, 4, 3, '2025-10-23', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-23', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (45, 4, 3, '2025-10-25', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-25', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (46, 3, 4, '2025-10-23', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-23', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (47, 3, 4, '2025-10-24', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-24', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (48, 3, 4, '2025-10-25', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-25', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (49, 2, 2, '2025-10-23', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-23', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (50, 2, 2, '2025-10-24', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-24', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (51, 2, 2, '2025-10-25', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-25', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (52, 2, 2, '2025-10-26', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-26', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (53, 3, 4, '2025-10-26', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-26', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (54, 4, 3, '2025-10-27', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-27', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (55, 4, 3, '2025-10-28', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-28', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (56, 4, 3, '2025-10-29', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-29', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (57, 4, 3, '2025-10-30', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-30', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (58, 4, 3, '2025-10-31', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-31', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (59, 4, 3, '2025-11-01', 35.72, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-01', valor = 35.72, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (60, 4, 3, '2025-11-02', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-02', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (61, 4, 3, '2025-10-26', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-10-26', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (62, 2, 2, '2025-10-27', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-27', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (63, 2, 2, '2025-10-28', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-28', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (64, 2, 2, '2025-10-29', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-29', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (65, 2, 2, '2025-10-30', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-30', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (66, 2, 2, '2025-10-31', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-10-31', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (67, 2, 2, '2025-11-01', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-01', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (68, 2, 2, '2025-11-02', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-02', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (69, 3, 4, '2025-10-27', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-27', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (70, 3, 4, '2025-10-28', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-28', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (71, 3, 4, '2025-10-29', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-29', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (72, 3, 4, '2025-10-30', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-30', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (73, 3, 4, '2025-10-31', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-10-31', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (74, 3, 4, '2025-11-01', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-01', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (75, 5, 1, '2025-10-31', 2510.30, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-10-31', valor = 2510.30, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (76, 5, 1, '2025-10-31', 2308.19, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-10-31', valor = 2308.19, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (77, 5, 1, '2025-10-31', 584.40, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-10-31', valor = 584.40, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (78, 2, 2, '2025-11-03', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-03', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (79, 2, 2, '2025-11-04', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-04', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (80, 2, 2, '2025-11-05', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-05', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (81, 2, 2, '2025-11-06', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-06', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (82, 4, 3, '2025-11-03', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-03', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (83, 4, 3, '2025-11-04', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-04', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (84, 4, 3, '2025-11-05', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-05', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (85, 3, 4, '2025-11-02', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-02', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (86, 3, 4, '2025-11-03', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-03', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (87, 3, 4, '2025-11-04', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-04', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (88, 3, 4, '2025-11-05', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-05', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (90, 3, 4, '2025-11-07', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-07', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (91, 3, 4, '2025-11-08', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-08', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (92, 3, 4, '2025-11-09', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-09', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (93, 3, 4, '2025-11-10', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-10', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (94, 4, 3, '2025-11-06', 46.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-06', valor = 46.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (95, 4, 3, '2025-11-07', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-07', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (96, 4, 3, '2025-11-12', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-12', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (97, 2, 2, '2025-11-07', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-07', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (98, 2, 2, '2025-11-08', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-08', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (99, 2, 2, '2025-11-09', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-09', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (100, 2, 2, '2025-11-10', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-10', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (101, 2, 2, '2025-11-11', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-11', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (102, 2, 2, '2025-11-12', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-12', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (103, 2, 2, '2025-11-13', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-13', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (104, 2, 2, '2025-11-14', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-14', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (105, 2, 2, '2025-11-15', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-15', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (106, 2, 2, '2025-11-16', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-16', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (107, 2, 2, '2025-11-17', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-17', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (108, 4, 3, '2025-11-13', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-13', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (109, 4, 3, '2025-11-14', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-14', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (110, 4, 3, '2025-11-15', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-15', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (111, 4, 3, '2025-11-16', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-16', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (112, 4, 3, '2025-11-17', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-17', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (113, 3, 4, '2025-11-06', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-06', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (114, 3, 4, '2025-11-12', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-12', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (115, 3, 4, '2025-11-13', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-13', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (116, 3, 4, '2025-11-14', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-14', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (117, 3, 4, '2025-11-15', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-15', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (118, 3, 4, '2025-11-16', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-16', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (119, 3, 4, '2025-11-17', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-17', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (120, 4, 3, '2025-11-19', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-19', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (121, 4, 3, '2025-11-20', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-20', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (122, 4, 3, '2025-11-21', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-21', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (123, 4, 3, '2025-11-22', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-22', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (124, 4, 3, '2025-11-23', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-23', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (125, 4, 3, '2025-11-24', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-24', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (126, 4, 3, '2025-11-25', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-25', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (127, 2, 2, '2025-11-20', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-20', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (128, 2, 2, '2025-11-20', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-20', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (129, 2, 2, '2025-11-21', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 2, veiculo_id = 2, data = '2025-11-21', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (130, 3, 4, '2025-11-18', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-18', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (131, 3, 4, '2025-11-19', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-19', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (132, 3, 4, '2025-11-20', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-20', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (133, 3, 4, '2025-11-21', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-21', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (134, 3, 4, '2025-11-22', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-22', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (135, 4, 3, '2025-11-26', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-26', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (136, 4, 3, '2025-11-27', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-27', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (137, 4, 3, '2025-11-28', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-28', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (138, 4, 3, '2025-11-29', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-29', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (139, 4, 3, '2025-11-30', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-30', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (140, 4, 3, '2025-12-01', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-01', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (141, 4, 3, '2025-12-02', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-02', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (142, 4, 3, '2025-12-03', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-03', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (143, 4, 3, '2025-12-04', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-04', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (144, 3, 4, '2025-11-25', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-25', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (145, 3, 4, '2025-11-26', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-26', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (146, 3, 4, '2025-11-27', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-27', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (147, 3, 4, '2025-11-28', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-28', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (148, 3, 4, '2025-11-29', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-29', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (149, 3, 4, '2025-12-01', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-01', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (150, 3, 4, '2025-12-02', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-02', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (151, 3, 4, '2025-12-04', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-04', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (152, 5, 1, '2025-11-30', 563.98, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-11-30', valor = 563.98, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (153, 5, 1, '2025-11-30', 40.59, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-11-30', valor = 40.59, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (154, 5, 1, '2025-11-30', 2000.00, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 5, veiculo_id = 1, data = '2025-11-30', valor = 2000.00, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (155, 4, 3, '2025-11-05', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-05', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (156, 4, 3, '2025-11-06', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-06', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (158, 4, 3, '2025-11-07', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-07', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (159, 4, 3, '2025-11-08', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-08', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (160, 4, 3, '2025-11-09', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-11-09', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (161, 3, 4, '2025-11-23', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-23', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (162, 3, 4, '2025-11-24', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-24', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (163, 3, 4, '2025-12-07', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-07', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (164, 3, 4, '2025-11-30', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-11-30', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (165, 3, 4, '2025-12-03', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-03', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (166, 3, 4, '2025-12-05', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-05', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (167, 3, 4, '2025-12-06', 85.71, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 3, veiculo_id = 4, data = '2025-12-06', valor = 85.71, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (168, 4, 3, '2025-12-10', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-10', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (169, 4, 3, '2025-12-11', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-11', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (170, 4, 3, '2025-12-12', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-12', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (171, 4, 3, '2025-12-13', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-13', valor = 71.43, status = 'Pago';
INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES (172, 4, 3, '2025-12-14', 71.43, 'Pago') ON DUPLICATE KEY UPDATE motorista_id = 4, veiculo_id = 3, data = '2025-12-14', valor = 71.43, status = 'Pago';


-- Finalizar transação
COMMIT;

-- Reabilitar verificações
SET FOREIGN_KEY_CHECKS = 1;
SET AUTOCOMMIT = 1;

-- ============================================
-- FIM DO SCRIPT
-- ============================================
