<?php
/**
 * API REST - Sistema Real Driver
 * 
 * Esta API gerencia todos os dados do sistema Real Driver no banco de dados.
 * 
 * Endpoints:
 * GET  /realdriver-api.php?action=get&type=motoristas
 * GET  /realdriver-api.php?action=get&type=veiculos
 * POST /realdriver-api.php?action=save&type=motoristas
 * POST /realdriver-api.php?action=delete&type=motoristas&id=1
 * 
 * Tipos suportados: motoristas, veiculos, diarias, manutencoes, contratos, financeiro
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];
$prefix = $config['system']['table_prefix'];

require_once __DIR__ . '/realdriver-permissions.php';

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com banco de dados']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$type = $_GET['type'] ?? $_POST['type'] ?? '';

// Para action=getAll, não precisa de type
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ação é obrigatória']);
    exit;
}

// Se não for getAll, type é obrigatório
if ($action !== 'getAll' && empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo é obrigatório para esta ação']);
    exit;
}

// Validar tipo (apenas se não for getAll)
if ($action !== 'getAll') {
    $validTypes = ['motoristas', 'veiculos', 'diarias', 'manutencoes', 'contratos', 'financeiro'];
    if (!in_array($type, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
        exit;
    }
}

// Verificar autenticação e permissões
$requiredPermission = getRequiredPermission($action, $type ?? '');
$user = requirePermission($pdo, $prefix, $requiredPermission);

switch ($action) {
    case 'get':
        handleGet($pdo, $prefix, $type);
        break;
    case 'save':
        handleSave($pdo, $prefix, $type);
        break;
    case 'delete':
        handleDelete($pdo, $prefix, $type);
        break;
    case 'getAll':
        handleGetAll($pdo, $prefix);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}

function handleGet($pdo, $prefix, $type) {
    $table = $prefix . $type;
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => formatData($type, $data)]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formatted = array_map(function($item) use ($type) {
            return formatData($type, $item);
        }, $data);
        
        echo json_encode(['success' => true, 'data' => $formatted]);
    }
}

function handleSave($pdo, $prefix, $type) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        return;
    }
    
    $table = $prefix . $type;
    $id = $data['id'] ?? null;
    
    // Preparar dados para inserção/atualização
    $fields = prepareFields($type, $data);
    
    if ($id) {
        // Atualizar
        $setClause = [];
        foreach ($fields as $key => $value) {
            if ($key !== 'id') {
                $setClause[] = "{$key} = :{$key}";
            }
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $setClause) . " WHERE id = :id";
        $fields['id'] = $id;
    } else {
        // Inserir
        $keys = array_keys($fields);
        $placeholders = array_map(function($k) { return ":{$k}"; }, $keys);
        $sql = "INSERT INTO {$table} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($fields);
        
        if (!$id) {
            $id = $pdo->lastInsertId();
        }
        
        // Retornar o registro salvo
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $saved = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => $id ? 'Registro atualizado' : 'Registro criado',
            'data' => formatData($type, $saved)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
}

function handleDelete($pdo, $prefix, $type) {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
        return;
    }
    
    $table = $prefix . $type;
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Registro excluído']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
    }
}

function handleGetAll($pdo, $prefix) {
    $result = [];
    $types = ['motoristas', 'veiculos', 'diarias', 'manutencoes', 'contratos', 'financeiro'];
    
    foreach ($types as $type) {
        $table = $prefix . $type;
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result[$type] = array_map(function($item) use ($type) {
            return formatData($type, $item);
        }, $data);
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
}

function prepareFields($type, $data) {
    $fields = [];
    
    switch ($type) {
        case 'motoristas':
            $fields = [
                'nome' => $data['nome'] ?? '',
                'cpf' => $data['cpf'] ?? '',
                'cnh' => $data['cnh'] ?? '',
                'telefone' => $data['telefone'] ?? '',
                'endereco' => $data['endereco'] ?? '',
                'status' => $data['status'] ?? 'Ativo'
            ];
            break;
        case 'veiculos':
            $fields = [
                'modelo' => $data['modelo'] ?? '',
                'marca' => $data['marca'] ?? '',
                'placa' => $data['placa'] ?? '',
                'ano' => $data['ano'] ?? null,
                'cor' => $data['cor'] ?? '',
                'motorista_id' => $data['motoristaId'] ?? null,
                'status' => $data['status'] ?? 'Ativo'
            ];
            break;
        case 'diarias':
            $fields = [
                'motorista_id' => $data['motoristaId'] ?? null,
                'veiculo_id' => $data['veiculoId'] ?? null,
                'data' => $data['data'] ?? date('Y-m-d'),
                'valor' => $data['valor'] ?? 0,
                'status' => $data['status'] ?? 'Pendente'
            ];
            break;
        case 'manutencoes':
            $fields = [
                'veiculo_id' => $data['veiculoId'] ?? null,
                'tipo' => $data['tipo'] ?? 'Preventiva',
                'data' => $data['data'] ?? date('Y-m-d'),
                'valor' => $data['valor'] ?? 0,
                'descricao' => $data['descricao'] ?? ''
            ];
            break;
        case 'contratos':
            $dataInicio = $data['dataInicio'] ?? date('Y-m-d');
            $duracaoDias = $data['duracao'] ?? 30;
            $dataVencimento = date('Y-m-d', strtotime($dataInicio . " +{$duracaoDias} days"));
            
            $fields = [
                'veiculo_id' => $data['veiculoId'] ?? null,
                'motorista_id' => $data['motoristaId'] ?? null,
                'data_inicio' => $dataInicio,
                'data_vencimento' => $dataVencimento,
                'duracao_dias' => $duracaoDias,
                'valor_mensal' => $data['valorMensal'] ?? 0,
                'observacoes' => $data['observacoes'] ?? '',
                'status' => $data['status'] ?? 'Ativo'
            ];
            break;
        case 'financeiro':
            $fields = [
                'descricao' => $data['descricao'] ?? '',
                'valor' => $data['valor'] ?? 0,
                'data' => $data['data'] ?? date('Y-m-d'),
                'tipo' => $data['tipo'] ?? 'Despesa',
                'categoria' => $data['categoria'] ?? 'Outros'
            ];
            break;
    }
    
    return $fields;
}

function formatData($type, $data) {
    // Converter campos do banco para o formato esperado pelo frontend
    switch ($type) {
        case 'motoristas':
            return [
                'id' => (int)$data['id'],
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'cnh' => $data['cnh'],
                'telefone' => $data['telefone'],
                'endereco' => $data['endereco'],
                'status' => $data['status']
            ];
        case 'veiculos':
            return [
                'id' => (int)$data['id'],
                'modelo' => $data['modelo'],
                'marca' => $data['marca'],
                'placa' => $data['placa'],
                'ano' => (int)$data['ano'],
                'cor' => $data['cor'],
                'motoristaId' => $data['motorista_id'] ? (int)$data['motorista_id'] : null,
                'status' => $data['status']
            ];
        case 'diarias':
            return [
                'id' => (int)$data['id'],
                'motoristaId' => (int)$data['motorista_id'],
                'veiculoId' => (int)$data['veiculo_id'],
                'data' => $data['data'],
                'valor' => (float)$data['valor'],
                'status' => $data['status']
            ];
        case 'manutencoes':
            return [
                'id' => (int)$data['id'],
                'veiculoId' => (int)$data['veiculo_id'],
                'tipo' => $data['tipo'],
                'data' => $data['data'],
                'valor' => (float)$data['valor'],
                'descricao' => $data['descricao']
            ];
        case 'contratos':
            return [
                'id' => (int)$data['id'],
                'veiculoId' => (int)$data['veiculo_id'],
                'motoristaId' => (int)$data['motorista_id'],
                'dataInicio' => $data['data_inicio'],
                'dataVencimento' => $data['data_vencimento'],
                'duracao' => (int)$data['duracao_dias'],
                'valorMensal' => (float)$data['valor_mensal'],
                'observacoes' => $data['observacoes'],
                'status' => $data['status']
            ];
        default:
            return $data;
    }
}

