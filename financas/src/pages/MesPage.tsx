import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import AppShell from '../components/layout/AppShell';
import { LancamentoRow } from '../components/LancamentoRow';
import Modal from '../components/ui/Modal';
import { apiFetch } from '../lib/api';
import { formatBRL, mesLabel } from '../lib/format';
import type { Lancamento, ResumoMes, TipoLancamento } from '../types/financeiro';

const emptyForm = () => ({
  tipo: 'despesa' as TipoLancamento,
  descricao: '',
  valor: '',
  data_vencimento: '',
  status: 'prevista',
});

export default function MesPage() {
  const navigate = useNavigate();
  const params = useParams();
  const mes = params.mes ?? new Date().toISOString().slice(0, 7);

  const [lancamentos, setLancamentos] = useState<Lancamento[]>([]);
  const [resumo, setResumo] = useState<ResumoMes | null>(null);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editing, setEditing] = useState<Lancamento | null>(null);
  const [form, setForm] = useState(emptyForm());
  const [filtro, setFiltro] = useState<'todos' | 'receita' | 'despesa'>('todos');

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiFetch<{
        lancamentos: Lancamento[];
        resumo: ResumoMes;
      }>(`/lancamentos.php?mes=${mes}`);
      setLancamentos(data.lancamentos);
      setResumo(data.resumo);
    } finally {
      setLoading(false);
    }
  }, [mes]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const mudarMes = (delta: number) => {
    const [y, m] = mes.split('-').map(Number);
    const d = new Date(y, m - 1 + delta, 1);
    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    navigate(`/mes/${ym}`);
  };

  const abrirNovo = (tipo: TipoLancamento) => {
    setEditing(null);
    setForm({
      ...emptyForm(),
      tipo,
      data_vencimento: `${mes}-01`,
    });
    setModal(true);
  };

  const abrirEditar = (l: Lancamento) => {
    setEditing(l);
    setForm({
      tipo: l.tipo,
      descricao: l.descricao,
      valor: String(l.valor),
      data_vencimento: l.data_vencimento,
      status: l.status,
    });
    setModal(true);
  };

  const salvar = async () => {
    const body = {
      tipo: form.tipo,
      descricao: form.descricao,
      valor: parseFloat(form.valor),
      data_vencimento: form.data_vencimento,
      status: form.status,
    };

    if (editing?.projetado && editing.recorrencia_id) {
      await apiFetch('/lancamentos.php', {
        method: 'PATCH',
        body: {
          projetado: true,
          recorrencia_id: editing.recorrencia_id,
          mes_referencia: mes,
          ...body,
        },
      });
    } else if (editing?.id) {
      await apiFetch('/lancamentos.php', { method: 'PUT', body: { id: editing.id, ...body } });
    } else {
      await apiFetch('/lancamentos.php', { method: 'POST', body });
    }
    setModal(false);
    await carregar();
  };

  const toggleStatus = async (l: Lancamento) => {
    const novo = l.tipo === 'receita' ? 'recebida' : 'paga';
    if (l.projetado && l.recorrencia_id) {
      await apiFetch('/lancamentos.php', {
        method: 'PATCH',
        body: {
          projetado: true,
          recorrencia_id: l.recorrencia_id,
          mes_referencia: mes,
          status: novo,
        },
      });
    } else if (l.id) {
      await apiFetch('/lancamentos.php', { method: 'PUT', body: { id: l.id, status: novo } });
    }
    await carregar();
  };

  const cancelar = async (l: Lancamento) => {
    if (!confirm(`Cancelar "${l.descricao}"?`)) return;
    if (l.projetado && l.recorrencia_id) {
      await apiFetch('/lancamentos.php', {
        method: 'PATCH',
        body: {
          projetado: true,
          recorrencia_id: l.recorrencia_id,
          mes_referencia: mes,
          status: 'cancelada',
        },
      });
    } else if (l.id) {
      await apiFetch('/lancamentos.php', { method: 'PUT', body: { id: l.id, status: 'cancelada' } });
    }
    await carregar();
  };

  const lista = lancamentos.filter((l) => filtro === 'todos' || l.tipo === filtro);

  return (
    <AppShell title={mesLabel(mes)}>
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div className="flex items-center gap-2">
          <button type="button" onClick={() => mudarMes(-1)} className="fin-btn-ghost p-2">
            <ChevronLeft size={18} />
          </button>
          <button type="button" onClick={() => mudarMes(1)} className="fin-btn-ghost p-2">
            <ChevronRight size={18} />
          </button>
          <Link to="/" className="text-sm text-violet-600 hover:underline ml-2">
            Ver ano
          </Link>
        </div>
        <div className="flex gap-2">
          <button type="button" className="fin-btn-ghost text-emerald-700" onClick={() => abrirNovo('receita')}>
            <Plus size={16} /> Receita
          </button>
          <button type="button" className="fin-btn" onClick={() => abrirNovo('despesa')}>
            <Plus size={16} /> Despesa
          </button>
        </div>
      </div>

      {resumo && (
        <div className="grid sm:grid-cols-3 gap-4 mb-6">
          <div className="fin-card">
            <p className="text-xs text-slate-500 uppercase tracking-wide">Receitas previstas</p>
            <p className="text-2xl font-bold text-emerald-600 mt-1">{formatBRL(resumo.receitas_previstas)}</p>
            <p className="text-xs text-slate-400 mt-1">Realizado: {formatBRL(resumo.receitas_realizadas)}</p>
          </div>
          <div className="fin-card">
            <p className="text-xs text-slate-500 uppercase tracking-wide">Despesas previstas</p>
            <p className="text-2xl font-bold text-red-600 mt-1">{formatBRL(resumo.despesas_previstas)}</p>
            <p className="text-xs text-slate-400 mt-1">Realizado: {formatBRL(resumo.despesas_realizadas)}</p>
          </div>
          <div className="fin-card">
            <p className="text-xs text-slate-500 uppercase tracking-wide">Saldo previsto</p>
            <p
              className={`text-2xl font-bold mt-1 ${resumo.saldo_previsto >= 0 ? 'text-emerald-600' : 'text-red-600'}`}
            >
              {formatBRL(resumo.saldo_previsto)}
            </p>
            <p className="text-xs text-slate-400 mt-1">Realizado: {formatBRL(resumo.saldo_realizado)}</p>
          </div>
        </div>
      )}

      <div className="flex gap-2 mb-4">
        {(['todos', 'receita', 'despesa'] as const).map((f) => (
          <button
            key={f}
            type="button"
            onClick={() => setFiltro(f)}
            className={`px-3 py-1.5 rounded-lg text-sm capitalize ${
              filtro === f ? 'bg-ink text-white' : 'bg-white border border-slate-200'
            }`}
          >
            {f === 'todos' ? 'Todos' : f === 'receita' ? 'Receitas' : 'Despesas'}
          </button>
        ))}
      </div>

      <div className="fin-card overflow-hidden !p-0">
        {loading ? (
          <p className="p-6 text-slate-500">Carregando…</p>
        ) : lista.length === 0 ? (
          <p className="p-6 text-slate-500">Nenhum lançamento neste mês.</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-slate-50 text-left">
                <th className="px-4 py-3">Descrição</th>
                <th className="px-4 py-3">Vencimento</th>
                <th className="px-4 py-3">Valor</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {lista.map((l, i) => (
                <LancamentoRow
                  key={l.id ?? `p-${l.recorrencia_id}-${i}`}
                  lancamento={l}
                  onToggleStatus={toggleStatus}
                  onCancel={cancelar}
                  onEdit={abrirEditar}
                />
              ))}
            </tbody>
          </table>
        )}
      </div>

      <Modal open={modal} title={editing ? 'Editar lançamento' : 'Novo lançamento'} onClose={() => setModal(false)}>
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
                min="0"
                className="fin-input"
                value={form.valor}
                onChange={(e) => setForm({ ...form, valor: e.target.value })}
              />
            </div>
            <div>
              <label className="fin-label">Vencimento</label>
              <input
                type="date"
                className="fin-input"
                value={form.data_vencimento}
                onChange={(e) => setForm({ ...form, data_vencimento: e.target.value })}
              />
            </div>
          </div>
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
