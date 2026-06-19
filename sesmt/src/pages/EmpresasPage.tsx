import { useCallback, useEffect, useState } from 'react';
import { Building2, Pencil, Plus, Trash2 } from 'lucide-react';
import AppShell from '../components/layout/AppShell';
import Modal from '../components/ui/Modal';
import { apiFetch, apiFetchBlob, apiUploadForm } from '../lib/api';

export type Empresa = {
  id: number;
  nome: string;
  logo_url: string | null;
};

type FormState = {
  id?: number;
  nome: string;
  logoFile: File | null;
  logoPreview: string | null;
};

const emptyForm = (): FormState => ({
  nome: '',
  logoFile: null,
  logoPreview: null,
});

export default function EmpresasPage() {
  const [empresas, setEmpresas] = useState<Empresa[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [modal, setModal] = useState<'create' | 'edit' | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm);
  const [saving, setSaving] = useState(false);

  const loadEmpresas = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await apiFetch<{ empresas: Empresa[] }>('/empresas.php');
      setEmpresas(data.empresas);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar empresas.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadEmpresas();
  }, [loadEmpresas]);

  const loadLogoPreview = async (empresa: Empresa) => {
    if (!empresa.logo_url) {
      setForm((f) => ({ ...f, logoPreview: null }));
      return;
    }
    const blob = await apiFetchBlob(`/empresas.php?id=${empresa.id}&logo=1`);
    setForm((f) => ({ ...f, logoPreview: URL.createObjectURL(blob) }));
  };

  const openCreate = () => {
    setForm(emptyForm());
    setModal('create');
  };

  const openEdit = async (empresa: Empresa) => {
    setForm({ id: empresa.id, nome: empresa.nome, logoFile: null, logoPreview: null });
    setModal('edit');
    try {
      await loadLogoPreview(empresa);
    } catch {
      /* sem logo */
    }
  };

  const closeModal = () => {
    if (form.logoPreview?.startsWith('blob:')) {
      URL.revokeObjectURL(form.logoPreview);
    }
    setModal(null);
    setForm(emptyForm());
  };

  const handleSave = async () => {
    if (!form.nome.trim()) {
      setError('Informe o nome da empresa.');
      return;
    }
    setSaving(true);
    setError('');
    try {
      const fd = new FormData();
      fd.append('nome', form.nome.trim());
      if (form.id) fd.append('id', String(form.id));
      if (form.logoFile) fd.append('logo', form.logoFile);
      await apiUploadForm('/empresas.php', fd);
      closeModal();
      await loadEmpresas();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao salvar empresa.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (empresa: Empresa) => {
    if (!confirm(`Excluir a empresa "${empresa.nome}"?`)) return;
    setError('');
    try {
      await apiFetch(`/empresas.php?id=${empresa.id}`, { method: 'DELETE' });
      await loadEmpresas();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao excluir.');
    }
  };

  return (
    <AppShell title="Empresas">
      <div className="space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-sesmt-forest/70 max-w-xl">
            Cadastre empresas com nome e logo para reutilizar na emissão de certificados.
          </p>
          <button type="button" onClick={openCreate} className="sesmt-btn-primary inline-flex items-center gap-2">
            <Plus size={18} />
            Nova empresa
          </button>
        </div>

        {error && (
          <p className="text-sm text-sesmt-forest bg-amber-50 border border-amber-200/80 rounded-[10px] px-3 py-2">
            {error}
          </p>
        )}

        {loading ? (
          <p className="text-sesmt-forest/60">Carregando…</p>
        ) : empresas.length === 0 ? (
          <div className="sesmt-card text-center py-12 text-sesmt-forest/60">
            <Building2 className="mx-auto mb-3 opacity-40" size={40} />
            <p>Nenhuma empresa cadastrada.</p>
          </div>
        ) : (
          <div className="sesmt-card overflow-hidden p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-sesmt-forest/10 text-left text-sesmt-forest/60">
                  <th className="px-4 py-3 font-medium">Logo</th>
                  <th className="px-4 py-3 font-medium">Nome</th>
                  <th className="px-4 py-3 font-medium w-28">Ações</th>
                </tr>
              </thead>
              <tbody>
                {empresas.map((e) => (
                  <EmpresaRow key={e.id} empresa={e} onEdit={() => openEdit(e)} onDelete={() => handleDelete(e)} />
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <Modal
        open={modal !== null}
        title={modal === 'edit' ? 'Editar empresa' : 'Nova empresa'}
        onClose={closeModal}
      >
        <div className="space-y-4">
          <div>
            <label className="sesmt-label">Nome da empresa</label>
            <input
              className="sesmt-input"
              value={form.nome}
              onChange={(ev) => setForm((f) => ({ ...f, nome: ev.target.value }))}
            />
          </div>
          <div>
            <label className="sesmt-label">Logo (PNG/JPG)</label>
            <input
              type="file"
              accept="image/png,image/jpeg"
              className="sesmt-input py-2"
              onChange={(ev) => {
                const file = ev.target.files?.[0];
                if (!file) return;
                if (form.logoPreview?.startsWith('blob:')) {
                  URL.revokeObjectURL(form.logoPreview);
                }
                setForm((f) => ({
                  ...f,
                  logoFile: file,
                  logoPreview: URL.createObjectURL(file),
                }));
              }}
            />
            {form.logoPreview && (
              <img src={form.logoPreview} alt="Logo" className="mt-2 h-14 object-contain" />
            )}
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <button type="button" className="sesmt-btn-ghost" onClick={closeModal}>
              Cancelar
            </button>
            <button type="button" className="sesmt-btn-primary" disabled={saving} onClick={handleSave}>
              {saving ? 'Salvando…' : 'Salvar'}
            </button>
          </div>
        </div>
      </Modal>
    </AppShell>
  );
}

function EmpresaRow({
  empresa,
  onEdit,
  onDelete,
}: {
  empresa: Empresa;
  onEdit: () => void;
  onDelete: () => void;
}) {
  const [thumb, setThumb] = useState<string | null>(null);

  useEffect(() => {
    if (!empresa.logo_url) return;
    let url: string | null = null;
    (async () => {
      try {
        const blob = await apiFetchBlob(`/empresas.php?id=${empresa.id}&logo=1`);
        url = URL.createObjectURL(blob);
        setThumb(url);
      } catch {
        /* sem logo */
      }
    })();
    return () => {
      if (url) URL.revokeObjectURL(url);
    };
  }, [empresa.id, empresa.logo_url]);

  return (
    <tr className="border-b border-sesmt-forest/5 last:border-0 hover:bg-sesmt-forest/[0.02]">
      <td className="px-4 py-3">
        {thumb ? (
          <img src={thumb} alt="" className="h-10 w-20 object-contain object-left" />
        ) : (
          <span className="text-sesmt-forest/30 text-xs">—</span>
        )}
      </td>
      <td className="px-4 py-3 font-medium text-sesmt-forest">{empresa.nome}</td>
      <td className="px-4 py-3">
        <div className="flex gap-1">
          <button type="button" className="sesmt-btn-ghost p-2" title="Editar" onClick={onEdit}>
            <Pencil size={16} />
          </button>
          <button type="button" className="sesmt-btn-ghost p-2 text-red-700" title="Excluir" onClick={onDelete}>
            <Trash2 size={16} />
          </button>
        </div>
      </td>
    </tr>
  );
}
