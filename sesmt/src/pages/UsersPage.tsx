import { useCallback, useEffect, useState } from 'react';
import { KeyRound, Pencil, Plus, Trash2, UserCheck, UserX } from 'lucide-react';
import AppShell from '../components/layout/AppShell';
import Modal from '../components/ui/Modal';
import { apiFetch } from '../lib/api';
import type { UserRole } from '../lib/auth';

export type SesmtUser = {
  id: number;
  nome: string;
  username: string;
  ativo: boolean;
  perfis: string[];
  role: UserRole;
};

type UsersResponse = { success: boolean; users: SesmtUser[] };

type FormState = {
  id?: number;
  nome: string;
  username: string;
  password: string;
  role: UserRole;
  ativo: boolean;
};

const emptyForm = (): FormState => ({
  nome: '',
  username: '',
  password: '',
  role: 'admin',
  ativo: true,
});

export default function UsersPage() {
  const [users, setUsers] = useState<SesmtUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [modal, setModal] = useState<'create' | 'edit' | 'password' | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm);
  const [saving, setSaving] = useState(false);

  const loadUsers = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await apiFetch<UsersResponse>('/users.php');
      setUsers(data.users);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar usuários.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  const openCreate = () => {
    setForm(emptyForm());
    setModal('create');
  };

  const openEdit = (user: SesmtUser) => {
    setForm({
      id: user.id,
      nome: user.nome,
      username: user.username,
      password: '',
      role: user.role,
      ativo: user.ativo,
    });
    setModal('edit');
  };

  const openPassword = (user: SesmtUser) => {
    setForm({ ...emptyForm(), id: user.id, nome: user.nome });
    setModal('password');
  };

  const closeModal = () => {
    setModal(null);
    setForm(emptyForm());
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      if (modal === 'create') {
        await apiFetch('/users.php', {
          method: 'POST',
          body: {
            nome: form.nome,
            username: form.username,
            password: form.password,
            role: form.role,
            ativo: form.ativo,
          },
        });
      } else if (modal === 'edit' && form.id) {
        await apiFetch('/users.php', {
          method: 'PUT',
          body: {
            id: form.id,
            nome: form.nome,
            username: form.username,
            role: form.role,
            ativo: form.ativo,
          },
        });
      } else if (modal === 'password' && form.id) {
        await apiFetch('/users.php', {
          method: 'PATCH',
          body: { id: form.id, password: form.password },
        });
      }

      closeModal();
      await loadUsers();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao salvar.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (user: SesmtUser) => {
    if (!confirm(`Excluir o usuário "${user.nome}"?`)) return;

    setError('');
    try {
      await apiFetch('/users.php', { method: 'DELETE', body: { id: user.id } });
      await loadUsers();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao excluir.');
    }
  };

  return (
    <AppShell title="Usuários">
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <p className="text-sm text-sesmt-forest/70">
          Apenas perfil <strong>Root</strong> pode criar, editar, excluir e alterar senhas.
        </p>
        <button type="button" onClick={openCreate} className="sesmt-btn-primary">
          <Plus size={18} />
          Novo usuário
        </button>
      </div>

      {error && (
        <p className="mb-4 text-sm text-sesmt-forest bg-amber-50 border border-amber-200/80 rounded-[10px] px-3 py-2">
          {error}
        </p>
      )}

      <div className="sesmt-card overflow-hidden p-0">
        {loading ? (
          <p className="p-6 text-sm text-sesmt-forest/60">Carregando…</p>
        ) : users.length === 0 ? (
          <p className="p-6 text-sm text-sesmt-forest/60">Nenhum usuário cadastrado.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-sesmt-page text-left">
                  <th className="px-4 py-3 font-semibold text-sesmt-forest">Nome</th>
                  <th className="px-4 py-3 font-semibold text-sesmt-forest">Usuário</th>
                  <th className="px-4 py-3 font-semibold text-sesmt-forest">Perfil</th>
                  <th className="px-4 py-3 font-semibold text-sesmt-forest">Status</th>
                  <th className="px-4 py-3 font-semibold text-sesmt-forest text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => (
                  <tr key={user.id} className="border-t border-sesmt-forest/8 hover:bg-sesmt-forest/[0.03]">
                    <td className="px-4 py-3 font-medium">{user.nome}</td>
                    <td className="px-4 py-3 text-sesmt-forest/80">{user.username}</td>
                    <td className="px-4 py-3">
                      <span
                        className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                          user.role === 'root'
                            ? 'bg-sesmt-accent-muted text-sesmt-accent'
                            : 'bg-sesmt-forest-muted text-sesmt-forest'
                        }`}
                      >
                        {user.role === 'root' ? 'Root' : 'Admin'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      {user.ativo ? (
                        <span className="inline-flex items-center gap-1 text-sesmt-forest">
                          <UserCheck size={14} /> Ativo
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-sesmt-forest/50">
                          <UserX size={14} /> Inativo
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-1">
                        <button
                          type="button"
                          onClick={() => openEdit(user)}
                          className="p-2 rounded-lg hover:bg-sesmt-forest/5 text-sesmt-forest"
                          title="Editar"
                        >
                          <Pencil size={16} />
                        </button>
                        <button
                          type="button"
                          onClick={() => openPassword(user)}
                          className="p-2 rounded-lg hover:bg-sesmt-forest/5 text-sesmt-forest"
                          title="Alterar senha"
                        >
                          <KeyRound size={16} />
                        </button>
                        <button
                          type="button"
                          onClick={() => handleDelete(user)}
                          className="p-2 rounded-lg hover:bg-sesmt-forest/10 text-sesmt-forest/70"
                          title="Excluir"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <Modal
        open={modal !== null}
        title={
          modal === 'create'
            ? 'Novo usuário'
            : modal === 'edit'
              ? 'Editar usuário'
              : 'Alterar senha'
        }
        onClose={closeModal}
      >
        <form onSubmit={handleSave} className="space-y-4">
          {modal !== 'password' && (
            <>
              <div>
                <label className="sesmt-label">Nome completo</label>
                <input
                  className="sesmt-input"
                  value={form.nome}
                  onChange={(e) => setForm({ ...form, nome: e.target.value })}
                  required
                />
              </div>
              <div>
                <label className="sesmt-label">Usuário (login)</label>
                <input
                  className="sesmt-input"
                  value={form.username}
                  onChange={(e) => setForm({ ...form, username: e.target.value })}
                  required
                  autoComplete="off"
                />
              </div>
              <div>
                <label className="sesmt-label">Perfil</label>
                <select
                  className="sesmt-input"
                  value={form.role}
                  onChange={(e) => setForm({ ...form, role: e.target.value as UserRole })}
                >
                  <option value="admin">Admin</option>
                  <option value="root">Root</option>
                </select>
              </div>
              <label className="flex items-center gap-2 text-sm text-sesmt-forest cursor-pointer">
                <input
                  type="checkbox"
                  checked={form.ativo}
                  onChange={(e) => setForm({ ...form, ativo: e.target.checked })}
                  className="rounded border-sesmt-forest/30"
                />
                Usuário ativo
              </label>
            </>
          )}

          {(modal === 'create' || modal === 'password') && (
            <div>
              <label className="sesmt-label">
                {modal === 'password' ? `Nova senha — ${form.nome}` : 'Senha inicial'}
              </label>
              <input
                type="password"
                className="sesmt-input"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                required
                minLength={8}
                autoComplete="new-password"
              />
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button type="button" onClick={closeModal} className="sesmt-btn-ghost flex-1">
              Cancelar
            </button>
            <button type="submit" disabled={saving} className="sesmt-btn-primary flex-1">
              {saving ? 'Salvando…' : 'Salvar'}
            </button>
          </div>
        </form>
      </Modal>
    </AppShell>
  );
}
