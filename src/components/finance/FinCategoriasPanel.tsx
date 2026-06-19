import { useCallback, useEffect, useState } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { finFetch } from '../../lib/financeApi';
import type { Categoria } from '../../types/financeiro';
import FinModal from './FinModal';
import { CORES_CATEGORIA } from './FinCategoriaSelect';

const CORES = CORES_CATEGORIA;

export default function FinCategoriasPanel() {
  const [lista, setLista] = useState<Categoria[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [nome, setNome] = useState('');
  const [tipo, setTipo] = useState<'receita' | 'despesa' | 'ambos'>('ambos');
  const [cor, setCor] = useState(CORES[0]);
  const [msg, setMsg] = useState('');

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const data = await finFetch<{ categorias: Categoria[] }>('/categorias.php');
      setLista(data.categorias);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const abrirNovo = () => {
    setEditId(null);
    setNome('');
    setTipo('ambos');
    setCor(CORES[0]);
    setMsg('');
    setModal(true);
  };

  const abrirEditar = (c: Categoria) => {
    setEditId(c.id);
    setNome(c.nome);
    setTipo(c.tipo);
    setCor(c.cor);
    setMsg('');
    setModal(true);
  };

  const salvar = async () => {
    setMsg('');
    try {
      const body = { nome, tipo, cor, ativa: true };
      if (editId) {
        await finFetch('/categorias.php', { method: 'PUT', body: { id: editId, ...body } });
      } else {
        await finFetch('/categorias.php', { method: 'POST', body });
      }
      setModal(false);
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro');
    }
  };

  return (
    <div className="max-w-3xl">
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 sm:mb-6">
        <p className="text-sm text-slate-500">Organize receitas e despesas por categoria.</p>
        <button type="button" className="panel-btn-primary w-full sm:w-auto" onClick={abrirNovo}>
          <Plus size={16} /> Nova categoria
        </button>
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : (
        <>
          <div className="md:hidden space-y-2">
            {lista.map((c) => (
              <div key={c.id} className="panel-card !p-3 flex items-center gap-3">
                <span className="inline-block w-4 h-4 rounded-full shrink-0" style={{ backgroundColor: c.cor }} />
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm truncate">{c.nome}</p>
                  <p className="text-xs text-slate-500 capitalize">{c.tipo}</p>
                </div>
                <div className="flex gap-1 shrink-0">
                  <button type="button" className="panel-btn-ghost p-2" onClick={() => abrirEditar(c)}>
                    <Pencil size={16} />
                  </button>
                  <button
                    type="button"
                    className="panel-btn-ghost p-2 text-red-600"
                    onClick={async () => {
                      if (!confirm(`Excluir "${c.nome}"?`)) return;
                      await finFetch(`/categorias.php?id=${c.id}`, { method: 'DELETE', body: { id: c.id } });
                      carregar();
                    }}
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
            ))}
          </div>
          <div className="hidden md:block panel-card overflow-hidden !p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-slate-50 text-left">
                <th className="px-4 py-3">Cor</th>
                <th className="px-4 py-3">Nome</th>
                <th className="px-4 py-3">Tipo</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {lista.map((c) => (
                <tr key={c.id} className="border-t border-slate-100">
                  <td className="px-4 py-3">
                    <span className="inline-block w-4 h-4 rounded-full" style={{ backgroundColor: c.cor }} />
                  </td>
                  <td className="px-4 py-3 font-medium">{c.nome}</td>
                  <td className="px-4 py-3 capitalize text-slate-600">{c.tipo}</td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-1">
                      <button type="button" className="panel-btn-ghost p-2" onClick={() => abrirEditar(c)}>
                        <Pencil size={16} />
                      </button>
                      <button
                        type="button"
                        className="panel-btn-ghost p-2 text-red-600"
                        onClick={async () => {
                          if (!confirm(`Excluir "${c.nome}"?`)) return;
                          await finFetch(`/categorias.php?id=${c.id}`, { method: 'DELETE', body: { id: c.id } });
                          carregar();
                        }}
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
        </>
      )}

      <FinModal open={modal} title={editId ? 'Editar categoria' : 'Nova categoria'} onClose={() => setModal(false)}>
        <div className="space-y-4">
          <input className="panel-input" placeholder="Nome" value={nome} onChange={(e) => setNome(e.target.value)} />
          <select className="panel-input" value={tipo} onChange={(e) => setTipo(e.target.value as typeof tipo)}>
            <option value="ambos">Receita e despesa</option>
            <option value="receita">Só receita</option>
            <option value="despesa">Só despesa</option>
          </select>
          <div>
            <p className="text-sm font-medium mb-2">Cor</p>
            <div className="flex flex-wrap gap-2">
              {CORES.map((c) => (
                <button
                  key={c}
                  type="button"
                  onClick={() => setCor(c)}
                  className={`w-8 h-8 rounded-full border-2 ${cor === c ? 'border-[#1A1D26] scale-110' : 'border-transparent'}`}
                  style={{ backgroundColor: c }}
                />
              ))}
            </div>
          </div>
          {msg && <p className="text-sm text-red-600">{msg}</p>}
          <button type="button" className="panel-btn-primary w-full" onClick={salvar}>
            Salvar
          </button>
        </div>
      </FinModal>
    </div>
  );
}
