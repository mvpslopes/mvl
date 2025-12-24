/**
 * Menu do Usuário - Sistema Real Driver
 * 
 * Adiciona menu com opções de trocar senha e logout
 */

class RealDriverUserMenu {
    constructor() {
        this.authUrl = window.REALDRIVER_AUTH_URL || '/api/realdriver-auth.php';
        this.token = window.REALDRIVER_TOKEN || localStorage.getItem('realdriver_token');
        this.user = window.REALDRIVER_USER || JSON.parse(localStorage.getItem('realdriver_user') || '{}');
        
        this.init();
    }
    
    init() {
        // Adicionar menu no header
        this.addUserMenu();
    }
    
    addUserMenu() {
        const headerActions = document.querySelector('.header-actions');
        if (!headerActions) return;
        
        // Criar menu do usuário
        const userMenu = document.createElement('div');
        userMenu.className = 'user-menu';
        userMenu.style.cssText = 'position: relative; margin-left: 1rem;';
        userMenu.innerHTML = `
            <button class="btn btn-secondary" id="user-menu-btn" style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-user"></i>
                <span>${this.user.nome || 'Usuário'}</span>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
            </button>
            <div class="user-menu-dropdown" id="user-menu-dropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 0.5rem; min-width: 200px; z-index: 1000;">
                <div style="padding: 1rem; border-bottom: 1px solid #eee;">
                    <div style="font-weight: 600;">${this.user.nome || 'Usuário'}</div>
                    <div style="font-size: 0.85rem; color: #666;">${this.user.email || ''}</div>
                    <div style="font-size: 0.75rem; color: #999; margin-top: 0.25rem;">Perfil: ${this.user.perfil || ''}</div>
                </div>
                ${(this.user.perfil === 'root' || this.user.perfil === 'admin') ? `
                    <button class="user-menu-item" onclick="userMenu.showChangePasswordModal()" style="width: 100%; padding: 0.75rem 1rem; border: none; background: none; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-key"></i>
                        <span>Trocar Senha</span>
                    </button>
                ` : ''}
                <button class="user-menu-item" onclick="userMenu.logout()" style="width: 100%; padding: 0.75rem 1rem; border: none; background: none; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; color: #c33;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </button>
            </div>
        `;
        
        headerActions.appendChild(userMenu);
        
        // Adicionar evento de toggle
        document.getElementById('user-menu-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            const dropdown = document.getElementById('user-menu-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                document.getElementById('user-menu-dropdown').style.display = 'none';
            }
        });
    }
    
    showChangePasswordModal() {
        const modalHtml = `
            <div id="change-password-modal" class="modal" style="display: block;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Trocar Senha</h3>
                        <button class="close-btn" onclick="document.getElementById('change-password-modal').remove()">&times;</button>
                    </div>
                    <form id="change-password-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Senha Atual *</label>
                                <input type="password" id="senha-atual" required>
                            </div>
                            <div class="form-group">
                                <label>Nova Senha *</label>
                                <input type="password" id="senha-nova" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Confirmar Nova Senha *</label>
                                <input type="password" id="senha-confirm" required minlength="6">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('change-password-modal').remove()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Alterar Senha</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        // Remover modal existente
        const existing = document.getElementById('change-password-modal');
        if (existing) existing.remove();
        
        // Adicionar novo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Fechar dropdown
        document.getElementById('user-menu-dropdown').style.display = 'none';
        
        // Adicionar evento de submit
        document.getElementById('change-password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const senhaAtual = document.getElementById('senha-atual').value;
            const senhaNova = document.getElementById('senha-nova').value;
            const senhaConfirm = document.getElementById('senha-confirm').value;
            
            if (senhaNova !== senhaConfirm) {
                alert('As senhas não coincidem!');
                return;
            }
            
            if (senhaNova.length < 6) {
                alert('A senha deve ter pelo menos 6 caracteres!');
                return;
            }
            
            try {
                const response = await fetch(`${this.authUrl}?action=changePassword`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.token}`
                    },
                    body: JSON.stringify({
                        senha_atual: senhaAtual,
                        senha_nova: senhaNova
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Senha alterada com sucesso!');
                    document.getElementById('change-password-modal').remove();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro ao alterar senha:', error);
                alert('Erro ao alterar senha');
            }
        });
    }
    
    async logout() {
        if (!confirm('Deseja realmente sair?')) {
            return;
        }
        
        try {
            await fetch(`${this.authUrl}?action=logout&token=${this.token}`, {
                method: 'POST'
            });
        } catch (error) {
            console.error('Erro ao fazer logout:', error);
        }
        
        // Limpar dados locais
        localStorage.removeItem('realdriver_token');
        localStorage.removeItem('realdriver_user');
        
        // Redirecionar para login
        window.location.href = '/api/realdriver-login.html';
    }
}

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.userMenu = new RealDriverUserMenu();
    });
} else {
    window.userMenu = new RealDriverUserMenu();
}

