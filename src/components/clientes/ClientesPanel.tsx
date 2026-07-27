import { useCallback, useEffect, useState } from 'react';
import { ArrowDown, ArrowUp, Download, Pencil, Plus, Trash2 } from 'lucide-react';
import { clientesAdminFetch } from '../../lib/clientesApi';
import type { ClienteAdmin } from '../../types/clientes';

const PRESET_COLORS = ['#FFFFFF', '#000000', '#F8F9FB', '#1A1D26', '#0F172A', '#E2E8F0'];

type FormState = {
  name: string;
  url: string;
  bg_color: string;
  logo: File | null;
};

const emptyForm = (): FormState => ({
  name: '',
  url: '',
  bg_color: '#FFFFFF',
  logo: null,
});

export default function ClientesPanel() {
  const [lista, setLista] = useState<ClienteAdmin[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editId, setEditId] = useState<string | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm());
  const [preview, setPreview] = useState<string | null>(null);
  const [msg, setMsg] = useState('');
  const [info, setInfo] = useState('');
  const [saving, setSaving] = useState(false);

  const carregar = useCallback(async () => {
    setLoading(true);
    setMsg('');
    try {
      const data = await clientesAdminFetch<{ clientes: ClienteAdmin[] }>('/admin.php');
      setLista(data.clientes ?? []);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao carregar');
      setLista([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    carregar();
  }, [carregar]);

  useEffect(() => {
    if (!form.logo) {
      if (!editId) setPreview(null);
      return;
    }
    const url = URL.createObjectURL(form.logo);
    setPreview(url);
    return () => URL.revokeObjectURL(url);
  }, [form.logo, editId]);

  const abrirNovo = () => {
    setEditId(null);
    setForm(emptyForm());
    setPreview(null);
    setMsg('');
    setModal(true);
  };

  const abrirEditar = (c: ClienteAdmin) => {
    setEditId(c.id);
    setForm({
      name: c.name,
      url: c.url,
      bg_color: c.bg_color || '#FFFFFF',
      logo: null,
    });
    setPreview(c.logo_url);
    setMsg('');
    setModal(true);
  };

  const salvar = async () => {
    setMsg('');
    if (!form.name.trim()) {
      setMsg('Informe o nome do cliente.');
      return;
    }
    if (!editId && !form.logo) {
      setMsg('Envie a logo do cliente.');
      return;
    }
    setSaving(true);
    try {
      const fd = new FormData();
      fd.append('name', form.name.trim());
      fd.append('url', form.url.trim());
      fd.append('bg_color', form.bg_color);
      if (editId) fd.append('id', editId);
      if (form.logo) fd.append('logo', form.logo);
      await clientesAdminFetch('/admin.php', { method: 'POST', formData: fd });
      setModal(false);
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const excluir = async (c: ClienteAdmin) => {
    if (!confirm(`Excluir "${c.name}"?`)) return;
    try {
      await clientesAdminFetch('/admin.php', { method: 'DELETE', json: { id: c.id } });
      await carregar();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Erro ao excluir');
    }
  };

  const importarPadrao = async () => {
    const force = lista.length > 0;
    if (force && !confirm('Isso substitui todas as logos atuais pelas 8 logos padrão do site. Continuar?')) {
      return;
    }
    setInfo('');
    setMsg('');
    try {
      const fd = new FormData();
      fd.append('action', 'seed');
      if (force) fd.append('force', '1');
      const data = await clientesAdminFetch<{ clientes: ClienteAdmin[]; message?: string; imported?: number }>(
        '/admin.php',
        { method: 'POST', formData: fd }
      );
      setLista(data.clientes ?? []);
      setInfo(data.message || `Importadas ${data.imported ?? 0} logos.`);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao importar');
    }
  };

  const mover = async (index: number, dir: -1 | 1) => {
    const next = index + dir;
    if (next < 0 || next >= lista.length) return;
    const ids = lista.map((c) => c.id);
    const tmp = ids[index];
    ids[index] = ids[next];
    ids[next] = tmp;
    const fd = new FormData();
    fd.append('action', 'reorder');
    fd.append('ids', JSON.stringify(ids));
    try {
      const data = await clientesAdminFetch<{ clientes: ClienteAdmin[] }>('/admin.php', {
        method: 'POST',
        formData: fd,
      });
      setLista(data.clientes ?? []);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Erro ao reordenar');
    }
  };

  return (
    <div className="max-w-4xl">
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4 sm:mb-6">
        <p className="text-sm text-slate-500 max-w-xl">
          Logos exibidas na seção Clientes do site. Escolha a cor de fundo de cada container para logos claras ou escuras.
        </p>
        <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto shrink-0">
          <button type="button" className="panel-btn-ghost w-full sm:w-auto" onClick={importarPadrao}>
            <Download size={16} /> Importar logos do site
          </button>
          <button type="button" className="panel-btn-primary w-full sm:w-auto" onClick={abrirNovo}>
            <Plus size={16} /> Nova logo
          </button>
        </div>
      </div>

      {msg && !modal && (
        <div className="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-red-700 text-sm">{msg}</div>
      )}
      {info && !modal && (
        <div className="mb-4 p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-sm">{info}</div>
      )}

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : lista.length === 0 ? (
        <div className="panel-card text-sm text-slate-500 space-y-3">
          <p>
            Nenhuma logo no painel ainda. As logos que já existem no site (Ariane, Toda Arte, etc.) podem ser importadas
            com um clique.
          </p>
          <button type="button" className="panel-btn-primary" onClick={importarPadrao}>
            <Download size={16} /> Importar logos do site
          </button>
        </div>
      ) : (
        <div className="space-y-2">
          {lista.map((c, i) => (
            <div key={c.id} className="panel-card !p-3 flex items-center gap-3">
              <div
                className="w-16 h-14 rounded-xl border border-slate-200 flex items-center justify-center shrink-0 overflow-hidden"
                style={{ backgroundColor: c.bg_color || '#fff' }}
              >
                {c.logo_url ? (
                  <img src={c.logo_url} alt={c.name} className="max-h-10 max-w-[52px] object-contain" />
                ) : (
                  <span className="text-[10px] text-slate-400">sem logo</span>
                )}
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm truncate">{c.name}</p>
                <p className="text-xs text-slate-500 truncate">
                  Fundo {c.bg_color}
                  {c.url ? ` · ${c.url}` : ''}
                </p>
              </div>
              <div className="flex gap-1 shrink-0">
                <button type="button" className="panel-btn-ghost p-2" disabled={i === 0} onClick={() => mover(i, -1)} aria-label="Subir">
                  <ArrowUp size={16} />
                </button>
                <button
                  type="button"
                  className="panel-btn-ghost p-2"
                  disabled={i === lista.length - 1}
                  onClick={() => mover(i, 1)}
                  aria-label="Descer"
                >
                  <ArrowDown size={16} />
                </button>
                <button type="button" className="panel-btn-ghost p-2" onClick={() => abrirEditar(c)}>
                  <Pencil size={16} />
                </button>
                <button type="button" className="panel-btn-ghost p-2 text-red-600" onClick={() => excluir(c)}>
                  <Trash2 size={16} />
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {modal && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40">
          <div className="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-xl max-h-[92vh] overflow-y-auto">
            <div className="p-5 border-b border-slate-100">
              <h3 className="text-lg font-semibold">{editId ? 'Editar cliente' : 'Nova logo'}</h3>
            </div>
            <div className="p-5 space-y-4">
              {msg && (
                <div className="p-3 bg-red-50 border border-red-100 rounded-xl text-red-700 text-sm">{msg}</div>
              )}

              <label className="block space-y-1.5">
                <span className="text-sm font-medium text-slate-700">Nome</span>
                <input
                  className="panel-input w-full"
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  placeholder="Ex.: Toda Arte"
                />
              </label>

              <label className="block space-y-1.5">
                <span className="text-sm font-medium text-slate-700">Site (opcional)</span>
                <input
                  className="panel-input w-full"
                  value={form.url}
                  onChange={(e) => setForm((f) => ({ ...f, url: e.target.value }))}
                  placeholder="https://..."
                />
              </label>

              <div className="space-y-2">
                <span className="text-sm font-medium text-slate-700">Cor de fundo do container</span>
                <div className="flex flex-wrap gap-2 items-center">
                  {PRESET_COLORS.map((cor) => (
                    <button
                      key={cor}
                      type="button"
                      onClick={() => setForm((f) => ({ ...f, bg_color: cor }))}
                      className={`w-9 h-9 rounded-lg border-2 ${
                        form.bg_color.toUpperCase() === cor ? 'border-violet-500' : 'border-slate-200'
                      }`}
                      style={{ backgroundColor: cor }}
                      title={cor}
                      aria-label={`Cor ${cor}`}
                    />
                  ))}
                  <label className="flex items-center gap-2 text-sm text-slate-600">
                    <input
                      type="color"
                      value={form.bg_color}
                      onChange={(e) => setForm((f) => ({ ...f, bg_color: e.target.value.toUpperCase() }))}
                      className="w-9 h-9 rounded cursor-pointer border border-slate-200"
                    />
                    {form.bg_color}
                  </label>
                </div>
              </div>

              <label className="block space-y-1.5">
                <span className="text-sm font-medium text-slate-700">
                  Logo {editId ? '(deixe em branco para manter)' : ''}
                </span>
                <input
                  type="file"
                  accept="image/png,image/jpeg,image/webp,image/svg+xml"
                  className="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium hover:file:bg-slate-200"
                  onChange={(e) => setForm((f) => ({ ...f, logo: e.target.files?.[0] ?? null }))}
                />
              </label>

              <div className="space-y-1.5">
                <span className="text-sm font-medium text-slate-700">Prévia</span>
                <div
                  className="h-28 rounded-2xl border border-slate-200 flex items-center justify-center px-4"
                  style={{ backgroundColor: form.bg_color }}
                >
                  {preview ? (
                    <img src={preview} alt="Prévia" className="max-h-16 w-auto object-contain" />
                  ) : (
                    <span className="text-sm text-slate-400">Sem imagem</span>
                  )}
                </div>
              </div>
            </div>
            <div className="p-5 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
              <button type="button" className="panel-btn-ghost" onClick={() => setModal(false)} disabled={saving}>
                Cancelar
              </button>
              <button type="button" className="panel-btn-primary" onClick={salvar} disabled={saving}>
                {saving ? 'Salvando…' : 'Salvar'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
