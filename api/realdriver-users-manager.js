/**
 * Gerenciador de Usuários - Sistema Real Driver
 * 
 * Componente para gestão de usuários (apenas para perfil root)
 */

class RealDriverUsersManager {
    constructor() {
        this.apiUrl = window.REALDRIVER_USERS_URL || '/api/realdriver-users.php';
        this.authUrl = window.REALDRIVER_AUTH_URL || '/api/realdriver-auth.php';
        this.token = window.REALDRIVER_TOKEN || localStorage.getItem('realdriver_token');
        this.user = window.REALDRIVER_USER || JSON.parse(localStorage.getItem('realdriver_user') || '{}');
        
        // Verificar se é root (verificar tanto 'perfil' quanto 'perfil_nome')
        const userPerfil = this.user.perfil || this.user.perfil_nome;
        if (userPerfil !== 'root') {
            console.log('⚠️ Apenas usuários root podem gerenciar usuários. Perfil atual:', userPerfil);
            return; // Não mostrar interface de gestão
        }
        
        console.log('✅ Gerenciador de usuários inicializado para root');
        this.init();
    }
    
    async init() {
        // Adicionar aba de usuários na navegação
        this.addUsersTab();
        
        // Carregar perfis
        await this.loadPerfis();
    }
    
    addUsersTab() {
        const navTabs = document.querySelector('.nav-tabs');
        if (!navTabs) {
            console.error('❌ .nav-tabs não encontrado, tentando novamente...');
            setTimeout(() => this.addUsersTab(), 500);
            return;
        }
        
        // Verificar se já existe
        if (document.querySelector('[data-tab="usuarios"]')) {
            return;
        }
        
        const usersTab = document.createElement('button');
        usersTab.className = 'nav-tab';
        usersTab.setAttribute('data-tab', 'usuarios');
        usersTab.innerHTML = '<i class="fas fa-users"></i> Usuários';
        usersTab.addEventListener('click', (e) => {
            e.preventDefault();
            this.showUsersTab();
        });
        navTabs.appendChild(usersTab);
        
        // Adicionar seção de usuários
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            const usersSection = this.createUsersSection();
            mainContent.appendChild(usersSection);
        } else {
            console.error('❌ .main-content não encontrado');
        }
    }
    
    createUsersSection() {
        const section = document.createElement('section');
        section.id = 'usuarios';
        section.className = 'tab-content';
        section.innerHTML = `
            <div class="section-header">
                <h2>Gestão de Usuários</h2>
                <button class="btn btn-primary" id="btn-new-user">
                    <i class="fas fa-plus"></i> Novo Usuário
                </button>
            </div>
            
            <div class="table-container">
                <table class="data-table" id="usuarios-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Último Acesso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        `;
        
        // Adicionar evento ao botão de novo usuário após inserir no DOM
        setTimeout(() => {
            const btnNew = section.querySelector('#btn-new-user');
            if (btnNew) {
                btnNew.addEventListener('click', () => {
                    console.log('➕ Criando novo usuário');
                    this.openCreateModal();
                });
            }
        }, 100);
        
        return section;
    }
    
    async showUsersTab() {
        try {
            // Ativar aba
            document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));
            const usuariosTab = document.querySelector('[data-tab="usuarios"]');
            if (usuariosTab) {
                usuariosTab.classList.add('active');
            } else {
                console.error('❌ Aba de usuários não encontrada');
                return;
            }
            
            // Mostrar conteúdo
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            const usuariosSection = document.getElementById('usuarios');
            if (usuariosSection) {
                usuariosSection.classList.add('active');
            } else {
                console.error('❌ Seção de usuários não encontrada');
                return;
            }
            
            // Carregar usuários
            await this.loadUsers();
        } catch (error) {
            console.error('Erro ao mostrar aba de usuários:', error);
            alert('Erro ao carregar página de usuários');
        }
    }
    
    async loadPerfis() {
        try {
            const response = await fetch(`${this.apiUrl}?action=getPerfis`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            const result = await response.json();
            this.perfis = result.data || [];
        } catch (error) {
            console.error('Erro ao carregar perfis:', error);
            this.perfis = [];
        }
    }
    
    async loadUsers() {
        try {
            const response = await fetch(`${this.apiUrl}?action=list`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            const result = await response.json();
            
            if (result.success) {
                this.renderUsers(result.data);
            }
        } catch (error) {
            console.error('Erro ao carregar usuários:', error);
            alert('Erro ao carregar usuários');
        }
    }
    
    renderUsers(usuarios) {
        const tbody = document.querySelector('#usuarios-table tbody');
        if (!tbody) {
            console.error('❌ Tbody da tabela de usuários não encontrado');
            return;
        }
        
        tbody.innerHTML = usuarios.map(user => `
            <tr data-user-id="${user.id}">
                <td>${this.escapeHtml(user.nome)}</td>
                <td>${this.escapeHtml(user.email)}</td>
                <td><span class="badge">${this.escapeHtml(user.perfil_nome)}</span></td>
                <td>${user.ativo ? '<span style="color: green;">Ativo</span>' : '<span style="color: red;">Inativo</span>'}</td>
                <td>${user.ultimo_acesso || 'Nunca'}</td>
                <td>
                    <button class="btn btn-sm btn-secondary btn-edit-user" data-user-id="${user.id}" title="Editar usuário">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${user.id != this.user.id ? `
                        <button class="btn btn-sm btn-danger btn-delete-user" data-user-id="${user.id}" title="Excluir usuário">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : '<span style="color: #999; font-size: 0.85rem;">Você</span>'}
                </td>
            </tr>
        `).join('');
        
        // Adicionar event listeners aos botões
        tbody.querySelectorAll('.btn-edit-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const userId = parseInt(btn.getAttribute('data-user-id'));
                console.log('🔧 Editando usuário ID:', userId);
                this.openEditModal(userId);
            });
        });
        
        tbody.querySelectorAll('.btn-delete-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const userId = parseInt(btn.getAttribute('data-user-id'));
                console.log('🗑️ Deletando usuário ID:', userId);
                this.deleteUser(userId);
            });
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    async openCreateModal() {
        try {
            // Garantir que perfis estão carregados
            if (!this.perfis || this.perfis.length === 0) {
                await this.loadPerfis();
            }
            
            // Criar modal
            this.showUserModal(null);
        } catch (error) {
            console.error('Erro ao abrir modal de criação:', error);
            alert('Erro ao carregar dados');
        }
    }
    
    async openEditModal(userId) {
        console.log('🔧 openEditModal chamado com userId:', userId);
        try {
            if (!userId) {
                alert('ID do usuário não fornecido');
                return;
            }
            
            // Garantir que perfis estão carregados
            if (!this.perfis || this.perfis.length === 0) {
                console.log('📋 Carregando perfis...');
                await this.loadPerfis();
            }
            
            // Buscar usuário e mostrar modal
            console.log('📡 Buscando dados do usuário...');
            const response = await fetch(`${this.apiUrl}?action=list`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            const result = await response.json();
            
            console.log('📥 Resposta da API:', result);
            
            if (result.success) {
                const user = result.data.find(u => u.id == userId || u.id == parseInt(userId));
                console.log('👤 Usuário encontrado:', user);
                if (user) {
                    this.showUserModal(user);
                } else {
                    alert('Usuário não encontrado. ID: ' + userId);
                }
            } else {
                alert('Erro: ' + (result.message || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('❌ Erro ao abrir modal de edição:', error);
            alert('Erro ao carregar dados do usuário: ' + error.message);
        }
    }
    
    showUserModal(user) {
        const isEdit = user !== null;
        const modalHtml = `
            <div id="usuario-modal" class="modal" style="display: block;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>${isEdit ? 'Editar Usuário' : 'Novo Usuário'}</h3>
                        <button class="close-btn" onclick="this.closest('.modal').remove()">&times;</button>
                    </div>
                    <form id="usuario-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nome *</label>
                                <input type="text" id="usuario-nome" value="${user?.nome || ''}" required>
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" id="usuario-email" value="${user?.email || ''}" required>
                            </div>
                            <div class="form-group">
                                <label>Senha ${isEdit ? '(deixe em branco para não alterar)' : '*'}</label>
                                <input type="password" id="usuario-senha" ${isEdit ? '' : 'required'}>
                            </div>
                            <div class="form-group">
                                <label>Perfil *</label>
                                <select id="usuario-perfil" required>
                                    ${(this.perfis || []).map(p => `
                                        <option value="${p.id}" ${user?.perfil_id == p.id ? 'selected' : ''}>
                                            ${p.nome}${p.descricao ? ' - ' + p.descricao : ''}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="usuario-ativo" ${user?.ativo !== false ? 'checked' : ''}>
                                    Ativo
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('usuario-modal').remove()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        // Remover modal existente
        const existing = document.getElementById('usuario-modal');
        if (existing) existing.remove();
        
        // Adicionar novo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Adicionar evento de submit
        document.getElementById('usuario-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveUser(user?.id);
        });
    }
    
    async saveUser(userId) {
        const data = {
            nome: document.getElementById('usuario-nome').value,
            email: document.getElementById('usuario-email').value,
            perfil_id: parseInt(document.getElementById('usuario-perfil').value),
            ativo: document.getElementById('usuario-ativo').checked
        };
        
        const senha = document.getElementById('usuario-senha').value;
        if (senha) {
            data.senha = senha;
        }
        
        try {
            const url = userId 
                ? `${this.apiUrl}?action=update&id=${userId}`
                : `${this.apiUrl}?action=create`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Usuário salvo com sucesso!');
                document.getElementById('usuario-modal').remove();
                await this.loadUsers();
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error('Erro ao salvar usuário:', error);
            alert('Erro ao salvar usuário');
        }
    }
    
    async deleteUser(userId) {
        if (!confirm('Tem certeza que deseja excluir este usuário?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=delete&id=${userId}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Usuário excluído com sucesso!');
                await this.loadUsers();
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error('Erro ao deletar usuário:', error);
            alert('Erro ao deletar usuário');
        }
    }
}

// Inicializar quando o DOM estiver pronto e variáveis estiverem disponíveis
function initUsersManager() {
    // Aguardar variáveis estarem disponíveis
    if (!window.REALDRIVER_USER) {
        setTimeout(initUsersManager, 100);
        return;
    }
    
    // Verificar se é root
    const userPerfil = window.REALDRIVER_USER?.perfil || window.REALDRIVER_USER?.perfil_nome;
    if (userPerfil === 'root') {
        console.log('✅ Inicializando gerenciador de usuários (root)');
        window.usersManager = new RealDriverUsersManager();
    } else {
        console.log('ℹ️ Gerenciador de usuários não disponível para perfil:', userPerfil);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUsersManager);
} else {
    // Aguardar um pouco para garantir que scripts foram carregados
    setTimeout(initUsersManager, 200);
}

