import { useCallback, useEffect, useState } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { finFetch } from '../../lib/financeApi';
import { formatBRL } from '../../lib/financeFormat';
import type { Categoria, Recorrencia } from '../../types/financeiro';
import FinModal from './FinModal';
import FinRecorrenciaForm, {
  emptyRecorrenciaForm,
  recorrenciaToForm,
  saveRecorrencia,
} from './FinRecorrenciaForm';

export default function FinRecorrenciasPanel() {
  const [lista, setLista] = useState<Recorrencia[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState(emptyRecorrenciaForm);
  const [valorStr, setValorStr] = useState('');
  const [semFim, setSemFim] = useState(true);
  const [filtro, setFiltro] = useState<'todos' | 'receita' | 'despesa'>('todos');
  const [msg, setMsg] = useState('');
  const [categorias, setCategorias] = useState<Categoria[]>([]);

  const handleCategoriaCriada = (categoria: Categoria) => {
    setCategorias((prev) => {
      if (prev.some((c) => c.id === categoria.id)) return prev;
      return [...prev, categoria].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
    });
  };

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const [recData, catData] = await Promise.all([
        finFetch<{ recorrencias: Recorrencia[] }>('/recorrencias.php'),
        finFetch<{ categorias: Categoria[] }>('/categorias.php'),
      ]);
      setLista(recData.recorrencias);
      setCategorias(catData.categorias);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const abrirNovo = () => {
    setEditId(null);
    setForm(emptyRecorrenciaForm());
    setValorStr('');
    setSemFim(true);
    setMsg('');
    setModal(true);
  };

  const abrirEditar = (r: Recorrencia) => {
    setEditId(r.id);
    setForm(recorrenciaToForm(r));
    setValorStr(String(r.valor));
    setSemFim(!r.data_fim);
    setMsg('');
    setModal(true);
  };

  const salvar = async () => {
    setMsg('');
    try {
      await saveRecorrencia(finFetch, form, valorStr, semFim, editId);
      setModal(false);
      await carregar();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao salvar');
    }
  };

  const listaFiltrada = lista.filter((r) => filtro === 'todos' || r.tipo === filtro);

  return (
    <div className="max-w-5xl">
      <div className="flex flex-wrap justify-between items-center gap-3 mb-4 sm:mb-6">
        <p className="text-sm text-slate-500">Receitas e despesas que repetem todo mês.</p>
        <button type="button" className="panel-btn-primary w-full sm:w-auto" onClick={abrirNovo}>
          <Plus size={16} /> Nova recorrência
        </button>
      </div>

      <div className="flex gap-2 mb-4">
        {(['todos', 'receita', 'despesa'] as const).map((f) => (
          <button
            key={f}
            type="button"
            onClick={() => setFiltro(f)}
            className={`px-3 py-1.5 rounded-lg text-sm capitalize ${
              filtro === f ? 'bg-[#1A1D26] text-white' : 'bg-white border border-slate-200'
            }`}
          >
            {f === 'todos' ? 'Todos' : f === 'receita' ? 'Receitas' : 'Despesas'}
          </button>
        ))}
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : listaFiltrada.length === 0 ? (
        <div className="panel-card text-center text-slate-500 py-12">Nenhuma recorrência.</div>
      ) : (
        <>
          <div className="md:hidden space-y-2">
            {listaFiltrada.map((r) => (
              <article
                key={r.id}
                className={`panel-card !p-3 cursor-pointer ${!r.ativa ? 'opacity-60' : ''}`}
                onClick={() => abrirEditar(r)}
              >
                <div className="flex justify-between items-start gap-2 mb-2">
                  <p className="font-medium text-sm">{r.descricao}</p>
                  <span
                    className={`inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium shrink-0 ${
                      r.ativa ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'
                    }`}
                  >
                    {r.ativa ? 'Ativa' : 'Inativa'}
                  </span>
                </div>
                <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600 mb-3">
                  <span className="capitalize">{r.tipo}</span>
                  <span className={`font-semibold ${r.tipo === 'receita' ? 'text-emerald-600' : 'text-red-600'}`}>
                    {formatBRL(r.valor)}
                  </span>
                  <span>Dia {r.dia_vencimento}</span>
                </div>
                <p className="text-[11px] text-slate-500 mb-3">
                  {new Date(r.data_inicio + 'T12:00:00').toLocaleDateString('pt-BR')}
                  {' → '}
                  {r.data_fim ? new Date(r.data_fim + 'T12:00:00').toLocaleDateString('pt-BR') : 'Sem fim'}
                </p>
                <div className="flex gap-2" onClick={(e) => e.stopPropagation()}>
                  <button type="button" className="panel-btn-ghost flex-1 text-xs py-2" onClick={() => abrirEditar(r)}>
                    <Pencil size={14} /> Editar
                  </button>
                  <button
                    type="button"
                    className="panel-btn-ghost flex-1 text-xs py-2 text-red-600"
                    onClick={async () => {
                      if (
                        !confirm(
                          'Excluir esta recorrência e TODOS os lançamentos registrados em todos os meses? Esta ação não pode ser desfeita.'
                        )
                      )
                        return;
                      const res = await finFetch<{ resultado: { lancamentos_removidos: number } }>(
                        `/recorrencias.php?id=${r.id}`,
                        { method: 'DELETE', body: { id: r.id } }
                      );
                      const n = res.resultado?.lancamentos_removidos ?? 0;
                      carregar();
                      if (n > 0) alert(`Recorrência excluída. ${n} lançamento(s) removido(s).`);
                    }}
                  >
                    <Trash2 size={14} /> Excluir
                  </button>
                </div>
              </article>
            ))}
          </div>
          <div className="hidden md:block panel-card overflow-hidden !p-0">
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
              {listaFiltrada.map((r) => (
                <tr
                  key={r.id}
                  className={`border-t border-slate-100 cursor-pointer hover:bg-slate-50/80 ${!r.ativa ? 'opacity-60' : ''}`}
                  onClick={() => abrirEditar(r)}
                >
                  <td className="px-4 py-3 font-medium">{r.descricao}</td>
                  <td className="px-4 py-3 capitalize">{r.tipo}</td>
                  <td className={`px-4 py-3 font-semibold ${r.tipo === 'receita' ? 'text-emerald-600' : 'text-red-600'}`}>
                    {formatBRL(r.valor)}
                  </td>
                  <td className="px-4 py-3">Dia {r.dia_vencimento}</td>
                  <td className="px-4 py-3 text-xs text-slate-500">
                    {new Date(r.data_inicio + 'T12:00:00').toLocaleDateString('pt-BR')}
                    {' → '}
                    {r.data_fim ? new Date(r.data_fim + 'T12:00:00').toLocaleDateString('pt-BR') : 'Sem fim'}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                        r.ativa ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'
                      }`}
                    >
                      {r.ativa ? 'Ativa' : 'Inativa'}
                    </span>
                  </td>
                  <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                    <div className="flex justify-end gap-1">
                      <button type="button" className="panel-btn-ghost p-2" onClick={() => abrirEditar(r)}>
                        <Pencil size={16} />
                      </button>
                      <button
                        type="button"
                        className="panel-btn-ghost p-2 text-red-600"
                        onClick={async () => {
                          if (
                            !confirm(
                              'Excluir esta recorrência e TODOS os lançamentos registrados em todos os meses? Esta ação não pode ser desfeita.'
                            )
                          )
                            return;
                          const res = await finFetch<{ resultado: { lancamentos_removidos: number } }>(
                            `/recorrencias.php?id=${r.id}`,
                            { method: 'DELETE', body: { id: r.id } }
                          );
                          const n = res.resultado?.lancamentos_removidos ?? 0;
                          carregar();
                          if (n > 0) alert(`Recorrência excluída. ${n} lançamento(s) removido(s).`);
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

      <FinModal
        open={modal}
        title={editId ? 'Editar recorrência' : 'Nova recorrência'}
        onClose={() => setModal(false)}
      >
        <FinRecorrenciaForm
          form={form}
          valorStr={valorStr}
          semFim={semFim}
          categorias={categorias}
          onCategoriaCriada={handleCategoriaCriada}
          onFormChange={setForm}
          onValorChange={setValorStr}
          onSemFimChange={setSemFim}
        />
        {msg && <p className="text-sm text-red-600 mt-3">{msg}</p>}
        <div className="flex gap-2 mt-4">
          <button type="button" className="panel-btn-ghost flex-1" onClick={() => setModal(false)}>
            Cancelar
          </button>
          <button type="button" className="panel-btn-primary flex-1" onClick={salvar}>
            Salvar
          </button>
        </div>
      </FinModal>
    </div>
  );
}
