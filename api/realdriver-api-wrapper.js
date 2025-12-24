/**
 * API Wrapper para Sistema Real Driver
 * 
 * Este arquivo substitui o uso de LocalStorage pela API REST
 * Mantém compatibilidade com o código existente
 */

(function() {
    'use strict';
    
    const API_URL = window.REALDRIVER_API_URL || '/api/realdriver-api.php';
    
    // Cache de dados
    let dataCache = null;
    let isLoading = false;
    
    /**
     * Função auxiliar para fazer requisições à API
     */
    async function apiRequest(action, type, data = null, id = null) {
        try {
            let url = `${API_URL}?action=${action}&type=${type}`;
            if (id) {
                url += `&id=${id}`;
            }
            
            const token = window.REALDRIVER_TOKEN || localStorage.getItem('realdriver_token');
            
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            const response = await fetch(url, options);
            
            // Se não autenticado, redirecionar para login
            if (response.status === 401) {
                const result = await response.json();
                if (result.redirect) {
                    window.location.href = result.redirect;
                    return;
                }
                window.location.href = '/api/realdriver-login.html';
                return;
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Erro na API');
            }
            
            return result.data;
        } catch (error) {
            console.error('Erro na API:', error);
            throw error;
        }
    }
    
    /**
     * Limpar cache de dados
     */
    function clearCache() {
        dataCache = null;
        console.log('🗑️ Cache limpo');
    }
    
    /**
     * Carregar todos os dados do banco
     * @param {boolean} forceRefresh - Forçar recarregamento mesmo se houver cache
     */
    async function loadAllData(forceRefresh = false) {
        if (isLoading && !forceRefresh) {
            return dataCache || { motoristas: [], veiculos: [], diarias: [], manutencoes: [], contratos: [], financeiro: [] };
        }
        
        if (dataCache && !forceRefresh) {
            return dataCache;
        }
        
        // Limpar cache se forçando refresh
        if (forceRefresh) {
            dataCache = null;
        }
        
        isLoading = true;
        try {
            // Usar apiRequest para garantir que o token seja enviado
            const token = window.REALDRIVER_TOKEN || localStorage.getItem('realdriver_token');
            
            if (!token) {
                throw new Error('Token de autenticação não encontrado');
            }
            
            // Adicionar timestamp para evitar cache do navegador
            const timestamp = new Date().getTime();
            const url = `${API_URL}?action=getAll&_t=${timestamp}`;
            
            const options = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            };
            
            const response = await fetch(url, options);
            
            // Se não autenticado, redirecionar para login
            if (response.status === 401) {
                const result = await response.json();
                if (result.redirect) {
                    window.location.href = result.redirect;
                    return {
                        motoristas: [],
                        veiculos: [],
                        diarias: [],
                        manutencoes: [],
                        contratos: [],
                        financeiro: []
                    };
                }
                window.location.href = '/api/realdriver-login.html';
                return {
                    motoristas: [],
                    veiculos: [],
                    diarias: [],
                    manutencoes: [],
                    contratos: [],
                    financeiro: []
                };
            }
            
            const result = await response.json();
            
            if (result.success) {
                dataCache = result.data;
                // Garantir que todas as propriedades existam
                dataCache.motoristas = dataCache.motoristas || [];
                dataCache.veiculos = dataCache.veiculos || [];
                dataCache.diarias = dataCache.diarias || [];
                dataCache.manutencoes = dataCache.manutencoes || [];
                dataCache.contratos = dataCache.contratos || [];
                dataCache.financeiro = dataCache.financeiro || [];
                
                console.log('✅ Dados carregados:', {
                    motoristas: dataCache.motoristas.length,
                    veiculos: dataCache.veiculos.length,
                    diarias: dataCache.diarias.length,
                    manutencoes: dataCache.manutencoes.length,
                    contratos: dataCache.contratos.length,
                    financeiro: dataCache.financeiro.length
                });
                
                return dataCache;
            } else {
                throw new Error(result.message || 'Erro ao carregar dados');
            }
        } catch (error) {
            console.error('Erro ao carregar dados da API:', error);
            // Retornar estrutura vazia em caso de erro
            return {
                motoristas: [],
                veiculos: [],
                diarias: [],
                manutencoes: [],
                contratos: [],
                financeiro: []
            };
        } finally {
            isLoading = false;
        }
    }
    
    /**
     * Salvar item específico
     */
    async function saveItem(type, item) {
        try {
            const saved = await apiRequest('save', type, item);
            // Invalidar cache
            dataCache = null;
            return saved;
        } catch (error) {
            console.error(`Erro ao salvar ${type}:`, error);
            throw error;
        }
    }
    
    /**
     * Deletar item específico
     */
    async function deleteItem(type, id) {
        try {
            await apiRequest('delete', type, null, id);
            // Invalidar cache
            dataCache = null;
            return true;
        } catch (error) {
            console.error(`Erro ao deletar ${type}:`, error);
            throw error;
        }
    }
    
    /**
     * Interceptar e substituir métodos do LocalStorage
     */
    const originalLocalStorage = {
        getItem: window.localStorage.getItem.bind(window.localStorage),
        setItem: window.localStorage.setItem.bind(window.localStorage),
        removeItem: window.localStorage.removeItem.bind(window.localStorage)
    };
    
    // Sobrescrever localStorage.getItem para 'sisRealDriverData'
    const originalGetItem = window.localStorage.getItem;
    window.localStorage.getItem = function(key) {
        if (key === 'sisRealDriverData') {
            // Retornar null para forçar o sistema a usar a API
            return null;
        }
        return originalGetItem.call(this, key);
    };
    
    // Sobrescrever localStorage.setItem para 'sisRealDriverData'
    const originalSetItem = window.localStorage.setItem;
    window.localStorage.setItem = function(key, value) {
        if (key === 'sisRealDriverData') {
            // Não salvar no localStorage, apenas invalidar cache
            dataCache = null;
            return;
        }
        return originalSetItem.call(this, key, value);
    };
    
    /**
     * Patches para a classe SisRealDriver
     */
    window.RealDriverAPI = {
        loadAllData,
        saveItem,
        deleteItem,
        clearCache
    };
    
    /**
     * Aplicar patches quando a classe SisRealDriver estiver disponível
     */
    function applyPatches() {
        if (typeof SisRealDriver === 'undefined') {
            setTimeout(applyPatches, 100);
            return;
        }
        
        // Patch loadData
        const originalLoadData = SisRealDriver.prototype.loadData;
        SisRealDriver.prototype.loadData = async function(forceRefresh = false) {
            console.log('📊 Carregando dados da API...', forceRefresh ? '(forçando refresh)' : '');
            try {
                this.data = await loadAllData(forceRefresh);
                console.log('✅ Dados carregados da API:', {
                    motoristas: this.data.motoristas?.length || 0,
                    veiculos: this.data.veiculos?.length || 0,
                    contratos: this.data.contratos?.length || 0,
                    diarias: this.data.diarias?.length || 0,
                    manutencoes: this.data.manutencoes?.length || 0,
                    financeiro: this.data.financeiro?.length || 0
                });
                
                // Garantir que todas as propriedades existam
                if (!this.data.contratos) this.data.contratos = [];
                if (!this.data.motoristas) this.data.motoristas = [];
                if (!this.data.veiculos) this.data.veiculos = [];
                if (!this.data.diarias) this.data.diarias = [];
                if (!this.data.manutencoes) this.data.manutencoes = [];
                if (!this.data.financeiro) this.data.financeiro = [];
            } catch (error) {
                console.error('Erro ao carregar dados da API, usando fallback:', error);
                originalLoadData.call(this);
            }
        };
        
        // Patch updateDashboard para forçar reload se dados vazios
        const originalUpdateDashboard = SisRealDriver.prototype.updateDashboard;
        SisRealDriver.prototype.updateDashboard = async function() {
            // Se os dados estão vazios, forçar reload
            const hasData = this.data && (
                (this.data.motoristas && this.data.motoristas.length > 0) ||
                (this.data.veiculos && this.data.veiculos.length > 0)
            );
            
            if (!hasData) {
                console.log('🔄 Dados vazios detectados, forçando reload...');
                await this.loadData(true);
            }
            
            originalUpdateDashboard.call(this);
        };
        
        // Patch saveData - apenas invalidar cache
        const originalSaveData = SisRealDriver.prototype.saveData;
        SisRealDriver.prototype.saveData = function() {
            console.log('💾 Cache invalidado - dados serão salvos via API');
            dataCache = null;
            // Manter comportamento original para backups locais
            originalSaveData.call(this);
        };
        
        // Patch saveMotorista
        if (SisRealDriver.prototype.saveMotorista) {
            const originalSaveMotorista = SisRealDriver.prototype.saveMotorista;
            SisRealDriver.prototype.saveMotorista = async function() {
                const formData = {
                    nome: document.getElementById('motorista-nome').value.trim(),
                    cpf: document.getElementById('motorista-cpf').value.trim(),
                    cnh: document.getElementById('motorista-cnh').value.trim(),
                    telefone: document.getElementById('motorista-telefone').value.trim(),
                    endereco: document.getElementById('motorista-endereco').value.trim(),
                    status: document.getElementById('motorista-status').value
                };
                
                // Validação
                if (!formData.nome || !formData.cpf || !formData.cnh || !formData.telefone) {
                    this.showMessage('Preencha todos os campos obrigatórios!', 'error');
                    return;
                }
                
                try {
                    if (this.currentEditId) {
                        formData.id = this.currentEditId;
                    }
                    
                    const saved = await saveItem('motoristas', formData);
                    dataCache = null; // Invalidar cache
                    
                    // Atualizar dados locais
                    if (this.currentEditId) {
                        const index = this.data.motoristas.findIndex(m => m.id === this.currentEditId);
                        if (index !== -1) {
                            this.data.motoristas[index] = saved;
                        }
                    } else {
                        this.data.motoristas.push(saved);
                    }
                    
                    this.renderMotoristas();
                    this.updateDashboard();
                    closeModal('motorista-modal');
                    this.showMessage('Motorista salvo com sucesso!', 'success');
                    this.currentEditId = null;
                } catch (error) {
                    console.error('Erro ao salvar motorista:', error);
                    this.showMessage('Erro ao salvar motorista: ' + error.message, 'error');
                }
            };
        }
        
        // Patch deleteMotorista
        if (SisRealDriver.prototype.deleteMotorista) {
            const originalDeleteMotorista = SisRealDriver.prototype.deleteMotorista;
            SisRealDriver.prototype.deleteMotorista = async function(id) {
                try {
                    await deleteItem('motoristas', id);
                    dataCache = null;
                    this.data.motoristas = this.data.motoristas.filter(m => m.id !== id);
                    this.renderMotoristas();
                    this.updateDashboard();
                    this.showMessage('Motorista excluído com sucesso!', 'success');
                } catch (error) {
                    console.error('Erro ao deletar motorista:', error);
                    this.showMessage('Erro ao excluir motorista: ' + error.message, 'error');
                }
            };
        }
        
        console.log('✅ Patches aplicados ao SisRealDriver');
    }
    
    // Aguardar DOM e aplicar patches
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyPatches);
    } else {
        applyPatches();
    }
    
    console.log('✅ RealDriver API Wrapper carregado');
})();

