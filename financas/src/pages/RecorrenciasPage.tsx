import { useCallback, useEffect, useState } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import AppShell from '../components/layout/AppShell';
import Modal from '../components/ui/Modal';
import { apiFetch } from '../lib/api';
import { formatBRL } from '../lib/format';
import type { Recorrencia, TipoLancamento } from '../types/financeiro';

const empty = (): Omit<Recorrencia, 'id'> => ({
  tipo: 'despesa',
  descricao: '',
  valor: 0,
  dia_vencimento: 1,
  data_inicio: new Date().toISOString().slice(0, 10),
  data_fim: null,
  ativa: true,
});

export default function RecorrenciasPage() {
  const [lista, setLista] = useState<Recorrencia[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState(empty());
  const [valorStr, setValorStr] = useState('');
  const [semFim, setSemFim] = useState(true);

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiFetch<{ recorrencias: Recorrencia[] }>('/recorrencias.php');
      setLista(data.recorrencias);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const abrirNovo = () => {
    setEditId(null);
    setForm(empty());
    setValorStr('');
    setSemFim(true);
    setModal(true);
  };

  const abrirEditar = (r: Recorrencia) => {
    setEditId(r.id);
    setForm({ ...r });
    setValorStr(String(r.valor));
    setSemFim(!r.data_fim);
    setModal(true);
  };

  const salvar = async () => {
    const body = {
      ...form,
      valor: parseFloat(valorStr),
      data_fim: semFim ? null : form.data_fim,
    };
    if (editId) {
      await apiFetch('/recorrencias.php', { method: 'PUT', body: { id: editId, ...body } });
    } else {
      await apiFetch('/recorrencias.php', { method: 'POST', body });
    }
    setModal(false);
    await carregar();
  };

  const excluir = async (r: Recorrencia) => {
    if (!confirm(`Excluir recorrência "${r.descricao}"?`)) return;
    await apiFetch(`/recorrencias.php?id=${r.id}`, { method: 'DELETE', body: { id: r.id } });
    await carregar();
  };

  return (
    <AppShell title="Recorrências">
      <div className="flex justify-between items-center mb-6">
        <p className="text-sm text-slate-500 max-w-xl">
          Cadastre receitas e despesas que se repetem todo mês na mesma data. Defina até quando valem.
        </p>
        <button type="button" className="fin-btn" onClick={abrirNovo}>
          <Plus size={16} /> Nova
        </button>
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : lista.length === 0 ? (
        <div className="fin-card text-center text-slate-500 py-12">Nenhuma recorrência cadastrada.</div>
      ) : (
        <div className="fin-card overflow-hidden !p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-slate-50 text-left">
                <th className="px-4 py-3">Descrição</th>
                <th className="px-4 py-3">Tipo</th>
                <th className="px-4 py-3">Valor</th>
                <th className="px-4 py-3">Dia</th>
                <th className="px-4 py-3">Período</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {lista.map((r) => (
                <tr key={r.id} className="border-t border-slate-100">
                  <td className="px-4 py-3 font-medium">{r.descricao}</td>
                  <td className="px-4 py-3 capitalize">{r.tipo}</td>
                  <td
                    className={`px-4 py-3 font-semibold ${r.tipo === 'receita' ? 'text-emerald-600' : 'text-red-600'}`}
                  >
                    {formatBRL(r.valor)}
                  </td>
                  <td className="px-4 py-3">Dia {r.dia_vencimento}</td>
                  <td className="px-4 py-3 text-slate-600 text-xs">
                    {new Date(r.data_inicio + 'T12:00:00').toLocaleDateString('pt-BR')}
                    {' → '}
                    {r.data_fim
                      ? new Date(r.data_fim + 'T12:00:00').toLocaleDateString('pt-BR')
                      : 'Sem fim'}
                  </td>
                  <td className="px-4 py-3">
                    <span className={r.ativa ? 'badge-ok' : 'badge-prevista'}>
                      {r.ativa ? 'Ativa' : 'Inativa'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-1">
                      <button type="button" className="fin-btn-ghost p-2" onClick={() => abrirEditar(r)}>
                        <Pencil size={16} />
                      </button>
                      <button type="button" className="fin-btn-ghost p-2 text-red-600" onClick={() => excluir(r)}>
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

      <Modal open={modal} title={editId ? 'Editar recorrência' : 'Nova recorrência'} onClose={() => setModal(false)}>
        <div className="space-y-4">
          <div>
            <label className="fin-label">Tipo</label>
            <select
              className="fin-input"
              value={form.tipo}
              onChange={(e) => setForm({ ...form, tipo: e.target.value as TipoLancamento })}
            >
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
          <div>
            <label className="fin-label">Descrição</label>
            <input
              className="fin-input"
              value={form.descricao}
              onChange={(e) => setForm({ ...form, descricao: e.target.value })}
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="fin-label">Valor (R$)</label>
              <input
                type="number"
                step="0.01"
                className="fin-input"
                value={valorStr}
                onChange={(e) => setValorStr(e.target.value)}
              />
            </div>
            <div>
              <label className="fin-label">Dia do vencimento</label>
              <input
                type="number"
                min={1}
                max={31}
                className="fin-input"
                value={form.dia_vencimento}
                onChange={(e) => setForm({ ...form, dia_vencimento: Number(e.target.value) })}
              />
            </div>
          </div>
          <div>
            <label className="fin-label">Início</label>
            <input
              type="date"
              className="fin-input"
              value={form.data_inicio}
              onChange={(e) => setForm({ ...form, data_inicio: e.target.value })}
            />
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={semFim} onChange={(e) => setSemFim(e.target.checked)} />
            Repetir sem data fim
          </label>
          {!semFim && (
            <div>
              <label className="fin-label">Repetir até</label>
              <input
                type="date"
                className="fin-input"
                value={form.data_fim ?? ''}
                onChange={(e) => setForm({ ...form, data_fim: e.target.value })}
              />
            </div>
          )}
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.ativa}
              onChange={(e) => setForm({ ...form, ativa: e.target.checked })}
            />
            Recorrência ativa
          </label>
          <div className="flex gap-2 pt-2">
            <button type="button" className="fin-btn-ghost flex-1" onClick={() => setModal(false)}>
              Cancelar
            </button>
            <button type="button" className="fin-btn flex-1" onClick={salvar}>
              Salvar
            </button>
          </div>
        </div>
      </Modal>
    </AppShell>
  );
}
