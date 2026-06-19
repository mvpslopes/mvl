import { useCallback, useEffect, useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight, Download, FileSpreadsheet, Plus, Search } from 'lucide-react';
import { finFetch } from '../../lib/financeApi';
import { exportMesPdf, exportMesXlsx } from '../../lib/financeExport';
import { formatBRL, lancamentoConcluido, mesLabel, valorRealizadoLancamento } from '../../lib/financeFormat';
import type { Categoria, Lancamento, ResumoMes, TipoLancamento } from '../../types/financeiro';
import FinMesSecao, { lancamentoKey } from './FinMesSecao';
import FinMesSemanas from './FinMesSemanas';
import FinMesVencimentos from './FinMesVencimentos';
import FinModal from './FinModal';
import FinCategoriaSelect from './FinCategoriaSelect';
import { calcularSemanas, lancamentoNaSemana, type ModoSemana } from '../../lib/financeSemanas';
import FinRecorrenciaForm, {
  emptyRecorrenciaForm,
  recorrenciaToForm,
  saveRecorrencia,
  type RecorrenciaFormState,
} from './FinRecorrenciaForm';
import type { Recorrencia } from '../../types/financeiro';

const emptyForm = () => ({
  tipo: 'despesa' as TipoLancamento,
  descricao: '',
  valor: '',
  valorRealizado: '',
  data_vencimento: '',
  data_efetivacao: '',
  categoria_id: '' as string | number,
  status: 'prevista',
});

type Classificar =
  | 'todos'
  | 'receita'
  | 'despesa'
  | 'prevista'
  | 'concluido'
  | 'cancelada'
  | 'recorrente';

type Ordenar = 'vencimento-asc' | 'vencimento-desc' | 'valor-desc' | 'valor-asc' | 'descricao-asc';

function itemConcluido(l: Lancamento): boolean {
  return l.status === 'recebida' || l.status === 'paga';
}

function filtrarLancamentos(lista: Lancamento[], classificar: Classificar): Lancamento[] {
  return lista.filter((l) => {
    switch (classificar) {
      case 'receita':
        return l.tipo === 'receita';
      case 'despesa':
        return l.tipo === 'despesa';
      case 'prevista':
        return l.status === 'prevista';
      case 'concluido':
        return itemConcluido(l);
      case 'cancelada':
        return l.status === 'cancelada';
      case 'recorrente':
        return !!l.recorrencia_id;
      default:
        return true;
    }
  });
}

function ordenarLancamentos(lista: Lancamento[], ordenar: Ordenar): Lancamento[] {
  const copia = [...lista];
  copia.sort((a, b) => {
    switch (ordenar) {
      case 'vencimento-desc':
        return b.data_vencimento.localeCompare(a.data_vencimento);
      case 'valor-desc':
        return b.valor - a.valor;
      case 'valor-asc':
        return a.valor - b.valor;
      case 'descricao-asc':
        return a.descricao.localeCompare(b.descricao, 'pt-BR');
      default:
        return a.data_vencimento.localeCompare(b.data_vencimento);
    }
  });
  return copia;
}

function saldoSelecionado(itens: Lancamento[]) {
  let receitas = 0;
  let despesas = 0;
  for (const l of itens) {
    if (l.status === 'cancelada') continue;
    const v = lancamentoConcluido(l.status) ? valorRealizadoLancamento(l) : l.valor;
    if (l.tipo === 'receita') receitas += v;
    else despesas += v;
  }
  return { receitas, despesas, saldo: receitas - despesas };
}

function filtrarBusca(lista: Lancamento[], busca: string): Lancamento[] {
  const q = busca.trim().toLowerCase();
  if (!q) return lista;
  return lista.filter((l) => l.descricao.toLowerCase().includes(q));
}

function filtrarCategoria(lista: Lancamento[], categoriaId: number | ''): Lancamento[] {
  if (!categoriaId) return lista;
  return lista.filter((l) => l.categoria_id === categoriaId);
}

function VariacaoLinha({ diff }: { diff: number }) {
  if (diff === 0) return <span className="text-xs text-slate-400 block mt-1">= mês anterior</span>;
  const positivo = diff > 0;
  return (
    <span className={`text-xs block mt-1 ${positivo ? 'text-emerald-600' : 'text-red-600'}`}>
      {positivo ? '↑' : '↓'} {formatBRL(Math.abs(diff))} vs mês ant.
    </span>
  );
}

function openNewForm(mes: string, tipo: TipoLancamento = 'despesa') {
  return {
    form: { ...emptyForm(), tipo, data_vencimento: `${mes}-01` },
    recorrente: false,
    semFim: true,
    dataFim: '',
  };
}

type Props = {
  mes: string;
  onMesChange: (ym: string) => void;
};

export default function FinMesPanel({ mes, onMesChange }: Props) {
  const [lancamentos, setLancamentos] = useState<Lancamento[]>([]);
  const [resumo, setResumo] = useState<ResumoMes | null>(null);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editing, setEditing] = useState<Lancamento | null>(null);
  const [form, setForm] = useState(emptyForm());
  const [recorrente, setRecorrente] = useState(false);
  const [semFim, setSemFim] = useState(true);
  const [dataFim, setDataFim] = useState('');
  const [editEscopo, setEditEscopo] = useState<'mes' | 'recorrencia'>('mes');
  const [recForm, setRecForm] = useState<RecorrenciaFormState>(emptyRecorrenciaForm);
  const [recValorStr, setRecValorStr] = useState('');
  const [recSemFim, setRecSemFim] = useState(true);
  const [carregandoRec, setCarregandoRec] = useState(false);
  const [classificar, setClassificar] = useState<Classificar>('todos');
  const [ordenar, setOrdenar] = useState<Ordenar>('vencimento-asc');
  const [selecionados, setSelecionados] = useState<Set<string>>(new Set());
  const [payTarget, setPayTarget] = useState<Lancamento | null>(null);
  const [payValorReal, setPayValorReal] = useState('');
  const [payDataEfetivacao, setPayDataEfetivacao] = useState('');
  const [saveError, setSaveError] = useState('');
  const [modoSemana, setModoSemana] = useState<ModoSemana>('previsto');
  const [busca, setBusca] = useState('');
  const [categoriaFiltro, setCategoriaFiltro] = useState<number | ''>('');
  const [semanaAtiva, setSemanaAtiva] = useState<number | null>(null);
  const [vencimentosProximos, setVencimentosProximos] = useState<Lancamento[]>([]);
  const [categorias, setCategorias] = useState<Categoria[]>([]);

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const [data, catData] = await Promise.all([
        finFetch<{ lancamentos: Lancamento[]; resumo: ResumoMes; vencimentos_proximos: Lancamento[] }>(
          `/lancamentos.php?mes=${mes}`
        ),
        finFetch<{ categorias: Categoria[] }>('/categorias.php'),
      ]);
      setLancamentos(data.lancamentos);
      setResumo(data.resumo);
      setVencimentosProximos(data.vencimentos_proximos ?? []);
      setCategorias(catData.categorias);
    } finally {
      setLoading(false);
    }
  }, [mes]);

  useEffect(() => {
    carregar();
    setSelecionados(new Set());
    setSemanaAtiva(null);
  }, [carregar]);

  const mudarMes = (delta: number) => {
    const [y, m] = mes.split('-').map(Number);
    const d = new Date(y, m - 1 + delta, 1);
    onMesChange(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
  };

  const salvar = async () => {
    setSaveError('');
    const body: Record<string, unknown> = {
      tipo: form.tipo,
      descricao: form.descricao,
      valor: parseFloat(form.valor),
      data_vencimento: form.data_vencimento,
      status: form.status,
      categoria_id: form.categoria_id ? Number(form.categoria_id) : null,
    };
    if (!Number.isFinite(body.valor as number) || (body.valor as number) <= 0) {
      setSaveError('Informe um valor válido.');
      return;
    }
    if (editing && lancamentoConcluido(editing.status) && form.valorRealizado !== '') {
      body.valor_realizado = parseFloat(form.valorRealizado);
    }
    if (editing && lancamentoConcluido(editing.status) && form.data_efetivacao) {
      body.data_efetivacao = form.data_efetivacao;
    }

    try {
      if (editEscopo === 'recorrencia' && editing?.recorrencia_id) {
        await saveRecorrencia(finFetch, recForm, recValorStr, recSemFim, editing.recorrencia_id);
      } else if (recorrente && !editing) {
        const dia = new Date(`${form.data_vencimento}T12:00:00`).getDate();
        await finFetch('/recorrencias.php', {
          method: 'POST',
          body: {
            ...body,
            dia_vencimento: dia,
            data_inicio: form.data_vencimento,
            data_fim: semFim ? null : dataFim || null,
            ativa: true,
          },
        });
      } else if (editing?.recorrencia_id && editEscopo === 'mes') {
        const { categoria_id: _cat, ...mesBody } = body;
        if (editing.id) {
          await finFetch('/lancamentos.php', { method: 'PUT', body: { id: editing.id, ...mesBody } });
        } else {
          await finFetch('/lancamentos.php', {
            method: 'PATCH',
            body: { projetado: true, recorrencia_id: editing.recorrencia_id, mes_referencia: mes, ...mesBody },
          });
        }
      } else if (editing?.id) {
        await finFetch('/lancamentos.php', { method: 'PUT', body: { id: editing.id, ...body } });
      } else {
        await finFetch('/lancamentos.php', { method: 'POST', body });
      }
      setModal(false);
      await carregar();
    } catch (err) {
      setSaveError(err instanceof Error ? err.message : 'Erro ao salvar');
    }
  };

  const abrirPagarReceber = (l: Lancamento) => {
    setPayTarget(l);
    setPayValorReal(String(l.valor));
    setPayDataEfetivacao(new Date().toISOString().slice(0, 10));
  };

  const confirmarPagamento = async () => {
    if (!payTarget) return;
    const valorReal = parseFloat(payValorReal);
    if (!Number.isFinite(valorReal) || valorReal <= 0) return;
    if (!payDataEfetivacao) return;

    const novo = payTarget.tipo === 'receita' ? 'recebida' : 'paga';
    const payload = { status: novo, valor_realizado: valorReal, data_efetivacao: payDataEfetivacao };

    if (payTarget.projetado && payTarget.recorrencia_id) {
      await finFetch('/lancamentos.php', {
        method: 'PATCH',
        body: {
          projetado: true,
          recorrencia_id: payTarget.recorrencia_id,
          mes_referencia: mes,
          ...payload,
        },
      });
    } else if (payTarget.id) {
      await finFetch('/lancamentos.php', { method: 'PUT', body: { id: payTarget.id, ...payload } });
    }

    setPayTarget(null);
    await carregar();
  };

  const reverter = async (l: Lancamento) => {
    if (!l.id) return;
    if (!confirm(`Voltar "${l.descricao}" para previsto? O valor real informado será removido.`)) return;
    await finFetch('/lancamentos.php', {
      method: 'PUT',
      body: { id: l.id, status: 'prevista', valor_realizado: null, data_efetivacao: null },
    });
    await carregar();
  };

  const cancelar = async (l: Lancamento) => {
    if (!confirm(`Cancelar "${l.descricao}"?`)) return;
    if (l.projetado && l.recorrencia_id) {
      await finFetch('/lancamentos.php', {
        method: 'PATCH',
        body: { projetado: true, recorrencia_id: l.recorrencia_id, mes_referencia: mes, status: 'cancelada' },
      });
    } else if (l.id) {
      await finFetch('/lancamentos.php', { method: 'PUT', body: { id: l.id, status: 'cancelada' } });
    }
    await carregar();
  };

  const abrirEdicao = async (item: Lancamento) => {
    setEditing(item);
    setForm({
      tipo: item.tipo,
      descricao: item.descricao,
      valor: String(item.valor),
      valorRealizado:
        item.valor_realizado != null ? String(item.valor_realizado) : String(item.valor),
      data_vencimento: item.data_vencimento,
      data_efetivacao: item.data_efetivacao ?? '',
      categoria_id: item.categoria_id ?? '',
      status: item.status,
    });
    setRecorrente(false);
    setSemFim(true);
    setDataFim('');

    if (item.recorrencia_id) {
      setEditEscopo('recorrencia');
      setCarregandoRec(true);
      try {
        const data = await finFetch<{ recorrencia: Recorrencia }>(`/recorrencias.php?id=${item.recorrencia_id}`);
        const r = data.recorrencia;
        setRecForm(recorrenciaToForm(r));
        setRecValorStr(String(r.valor));
        setRecSemFim(!r.data_fim);
      } finally {
        setCarregandoRec(false);
      }
    } else {
      setEditEscopo('mes');
      setRecForm(emptyRecorrenciaForm());
      setRecValorStr('');
      setRecSemFim(true);
    }

    setModal(true);
  };

  const handleCategoriaCriada = (categoria: Categoria) => {
    setCategorias((prev) => {
      if (prev.some((c) => c.id === categoria.id)) return prev;
      return [...prev, categoria].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
    });
  };

  const lista = useMemo(() => {
    let base = filtrarLancamentos(lancamentos, classificar);
    base = filtrarBusca(base, busca);
    base = filtrarCategoria(base, categoriaFiltro);
    if (semanaAtiva !== null) {
      base = base.filter((l) => lancamentoNaSemana(l, semanaAtiva, mes));
    }
    return ordenarLancamentos(base, ordenar);
  }, [lancamentos, classificar, ordenar, busca, categoriaFiltro, semanaAtiva, mes]);

  const listaReceitas = useMemo(() => lista.filter((l) => l.tipo === 'receita'), [lista]);
  const listaDespesas = useMemo(() => lista.filter((l) => l.tipo === 'despesa'), [lista]);
  const semanas = useMemo(
    () => calcularSemanas(lancamentos, mes, modoSemana),
    [lancamentos, mes, modoSemana]
  );
  const mostrarReceitas = classificar !== 'despesa';
  const mostrarDespesas = classificar !== 'receita';

  const itensSelecionados = useMemo(
    () => lancamentos.filter((l) => selecionados.has(lancamentoKey(l))),
    [lancamentos, selecionados]
  );

  const resumoSelecao = useMemo(() => saldoSelecionado(itensSelecionados), [itensSelecionados]);

  const toggleSelecao = (l: Lancamento) => {
    const key = lancamentoKey(l);
    setSelecionados((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

  const toggleSecao = (itens: Lancamento[]) => {
    const keys = itens.map(lancamentoKey);
    const todosMarcados = keys.length > 0 && keys.every((k) => selecionados.has(k));
    setSelecionados((prev) => {
      const next = new Set(prev);
      if (todosMarcados) keys.forEach((k) => next.delete(k));
      else keys.forEach((k) => next.add(k));
      return next;
    });
  };

  return (
    <div className="max-w-5xl">
      <div className="flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
        <div className="flex items-center justify-center sm:justify-start gap-2">
          <button type="button" onClick={() => mudarMes(-1)} className="panel-btn-ghost p-2" aria-label="Mês anterior">
            <ChevronLeft size={18} />
          </button>
          <span className="font-semibold min-w-[7rem] text-center">{mesLabel(mes)}</span>
          <button type="button" onClick={() => mudarMes(1)} className="panel-btn-ghost p-2" aria-label="Próximo mês">
            <ChevronRight size={18} />
          </button>
        </div>
        <div className="flex flex-wrap gap-2 justify-center sm:justify-end">
          {!loading && lancamentos.length > 0 && (
            <>
              <button
                type="button"
                className="panel-btn-ghost text-sm px-3"
                onClick={() => exportMesXlsx(mes, lancamentos)}
                aria-label="Exportar XLSX"
              >
                <FileSpreadsheet size={16} />
                <span className="hidden sm:inline"> XLSX</span>
              </button>
              <button
                type="button"
                className="panel-btn-ghost text-sm px-3"
                onClick={() => exportMesPdf(mes, lancamentos)}
                aria-label="Exportar PDF"
              >
                <Download size={16} />
                <span className="hidden sm:inline"> PDF</span>
              </button>
            </>
          )}
          <button
            type="button"
            className="panel-btn-ghost text-emerald-700 flex-1 sm:flex-none min-w-[calc(50%-4px)] sm:min-w-0"
            onClick={() => {
              const next = openNewForm(mes, 'receita');
              setEditing(null);
              setEditEscopo('mes');
              setForm(next.form);
              setRecorrente(next.recorrente);
              setSemFim(next.semFim);
              setDataFim(next.dataFim);
              setModal(true);
            }}
          >
            <Plus size={16} /> Receita
          </button>
          <button
            type="button"
            className="panel-btn-primary flex-1 sm:flex-none min-w-[calc(50%-4px)] sm:min-w-0"
            onClick={() => {
              const next = openNewForm(mes);
              setEditing(null);
              setEditEscopo('mes');
              setForm(next.form);
              setRecorrente(next.recorrente);
              setSemFim(next.semFim);
              setDataFim(next.dataFim);
              setModal(true);
            }}
          >
            <Plus size={16} /> Despesa
          </button>
        </div>
      </div>

      <FinMesVencimentos itens={vencimentosProximos} />

      {resumo && (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
          <div className="panel-card !p-3 sm:!p-6">
            <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Receitas realizadas</p>
            <p className="text-lg sm:text-2xl font-bold text-emerald-600">{formatBRL(resumo.receitas_realizadas)}</p>
            {resumo.variacao && <VariacaoLinha diff={resumo.variacao.receitas_realizadas} />}
          </div>
          <div className="panel-card !p-3 sm:!p-6">
            <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Despesas realizadas</p>
            <p className="text-lg sm:text-2xl font-bold text-red-600">{formatBRL(resumo.despesas_realizadas)}</p>
            {resumo.variacao && <VariacaoLinha diff={-resumo.variacao.despesas_realizadas} />}
          </div>
          <div className="panel-card !p-3 sm:!p-6 col-span-2 sm:col-span-1">
            <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Saldo realizado</p>
            <p className={`text-lg sm:text-2xl font-bold ${resumo.saldo_realizado >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
              {formatBRL(resumo.saldo_realizado)}
            </p>
            {resumo.variacao && <VariacaoLinha diff={resumo.variacao.saldo_realizado} />}
          </div>
        </div>
      )}

      {resumo && (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
          <div className="panel-card !p-3 sm:!p-6">
            <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Receitas previstas</p>
            <p className="text-lg sm:text-2xl font-bold text-emerald-600">{formatBRL(resumo.receitas_previstas)}</p>
            {resumo.variacao && <VariacaoLinha diff={resumo.variacao.receitas_previstas} />}
          </div>
          <div className="panel-card !p-3 sm:!p-6">
            <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Despesas previstas</p>
            <p className="text-lg sm:text-2xl font-bold text-red-600">{formatBRL(resumo.despesas_previstas)}</p>
            {resumo.variacao && <VariacaoLinha diff={-resumo.variacao.despesas_previstas} />}
          </div>
          <div className="panel-card !p-3 sm:!p-6 col-span-2 sm:col-span-1">
            <p className="text-[10px] sm:text-xs text-slate-500 uppercase">Saldo previsto</p>
            <p className={`text-lg sm:text-2xl font-bold ${resumo.saldo_previsto >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
              {formatBRL(resumo.saldo_previsto)}
            </p>
            {resumo.variacao && <VariacaoLinha diff={resumo.variacao.saldo_previsto} />}
            {resumo.mes_anterior_ref && (
              <p className="text-[10px] text-slate-400 mt-1">
                Comparado com {mesLabel(resumo.mes_anterior_ref)}
              </p>
            )}
          </div>
        </div>
      )}

      {!loading && lancamentos.length > 0 && (
        <FinMesSemanas
          semanas={semanas}
          modo={modoSemana}
          onModoChange={setModoSemana}
          semanaAtiva={semanaAtiva}
          onSemanaClick={setSemanaAtiva}
        />
      )}

      <div className="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3 mb-4">
        <div className="w-full sm:flex-1 sm:min-w-[200px]">
          <label className="block text-xs font-medium text-slate-500 mb-1.5">Buscar</label>
          <div className="relative">
            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              className="panel-input py-2 pl-9"
              placeholder="Descrição…"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
            />
          </div>
        </div>
        <div className="w-full sm:min-w-[160px]">
          <label className="block text-xs font-medium text-slate-500 mb-1.5">Categoria</label>
          <select
            className="panel-input py-2"
            value={categoriaFiltro}
            onChange={(e) => setCategoriaFiltro(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">Todas</option>
            {categorias.filter((c) => c.ativa).map((c) => (
              <option key={c.id} value={c.id}>
                {c.nome}
              </option>
            ))}
          </select>
        </div>
        <div className="w-full sm:min-w-[180px]">
          <label className="block text-xs font-medium text-slate-500 mb-1.5">Classificar</label>
          <select
            className="panel-input py-2"
            value={classificar}
            onChange={(e) => setClassificar(e.target.value as Classificar)}
          >
            <option value="todos">Todos</option>
            <option value="receita">Receitas</option>
            <option value="despesa">Despesas</option>
            <option value="prevista">Previstas</option>
            <option value="concluido">Concluídos</option>
            <option value="cancelada">Cancelados</option>
            <option value="recorrente">Recorrentes</option>
          </select>
        </div>
        <div className="w-full sm:min-w-[200px]">
          <label className="block text-xs font-medium text-slate-500 mb-1.5">Ordenar</label>
          <select
            className="panel-input py-2"
            value={ordenar}
            onChange={(e) => setOrdenar(e.target.value as Ordenar)}
          >
            <option value="vencimento-asc">Vencimento (mais antigo)</option>
            <option value="vencimento-desc">Vencimento (mais recente)</option>
            <option value="valor-desc">Valor (maior)</option>
            <option value="valor-asc">Valor (menor)</option>
            <option value="descricao-asc">Descrição (A–Z)</option>
          </select>
        </div>
      </div>

      {selecionados.size > 0 && (
        <div className="panel-card mb-4 flex flex-wrap items-center justify-between gap-4 border-sky-200 bg-sky-50/60">
          <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <span className="font-semibold text-sky-900">
              {selecionados.size} {selecionados.size === 1 ? 'item selecionado' : 'itens selecionados'}
            </span>
            <span className="text-emerald-700">
              Receitas: <strong>{formatBRL(resumoSelecao.receitas)}</strong>
            </span>
            <span className="text-red-600">
              Despesas: <strong>{formatBRL(resumoSelecao.despesas)}</strong>
            </span>
            <span className={resumoSelecao.saldo >= 0 ? 'text-emerald-700' : 'text-red-600'}>
              Saldo: <strong>{formatBRL(resumoSelecao.saldo)}</strong>
            </span>
          </div>
          <button type="button" className="panel-btn-ghost text-sm" onClick={() => setSelecionados(new Set())}>
            Limpar seleção
          </button>
        </div>
      )}

      {loading ? (
        <p className="text-slate-500 py-8 text-center">Carregando…</p>
      ) : lista.length === 0 ? (
        <div className="panel-card text-center text-slate-500 py-12">Nenhum lançamento neste mês.</div>
      ) : (
        <div className="space-y-6">
          {mostrarReceitas && (
            <FinMesSecao
              tipo="receita"
              itens={listaReceitas}
              selecionados={selecionados}
              onToggleSecao={toggleSecao}
              onSelect={toggleSelecao}
              onPagarReceber={abrirPagarReceber}
              onReverter={reverter}
              onCancel={cancelar}
              onEdit={abrirEdicao}
            />
          )}
          {mostrarDespesas && (
            <FinMesSecao
              tipo="despesa"
              itens={listaDespesas}
              selecionados={selecionados}
              onToggleSecao={toggleSecao}
              onSelect={toggleSelecao}
              onPagarReceber={abrirPagarReceber}
              onReverter={reverter}
              onCancel={cancelar}
              onEdit={abrirEdicao}
            />
          )}
        </div>
      )}

      <FinModal
        open={modal}
        title={
          editing
            ? editEscopo === 'recorrencia'
              ? 'Editar recorrência'
              : 'Editar lançamento'
            : 'Novo lançamento'
        }
        onClose={() => {
          setModal(false);
          setSaveError('');
        }}
      >
        <div className="space-y-4">
          {editing?.recorrencia_id && (
            <div className="flex rounded-xl border border-slate-200 p-1 bg-slate-50">
              <button
                type="button"
                className={`flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors ${
                  editEscopo === 'recorrencia' ? 'bg-white shadow-sm text-[#1A1D26]' : 'text-slate-500'
                }`}
                onClick={() => setEditEscopo('recorrencia')}
              >
                Toda a recorrência
              </button>
              <button
                type="button"
                className={`flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors ${
                  editEscopo === 'mes' ? 'bg-white shadow-sm text-[#1A1D26]' : 'text-slate-500'
                }`}
                onClick={() => setEditEscopo('mes')}
              >
                Só este mês
              </button>
            </div>
          )}
          {editing?.recorrencia_id && editEscopo === 'recorrencia' && (
            <p className="text-xs text-violet-700 bg-violet-50 border border-violet-100 rounded-lg px-3 py-2">
              A <strong>categoria</strong> definida aqui vale para <strong>todos os meses</strong> desta recorrência.
            </p>
          )}

          {editEscopo === 'recorrencia' && editing?.recorrencia_id ? (
            carregandoRec ? (
              <p className="text-slate-500 text-sm">Carregando recorrência…</p>
            ) : (
              <FinRecorrenciaForm
                form={recForm}
                valorStr={recValorStr}
                semFim={recSemFim}
                categorias={categorias}
                onCategoriaCriada={handleCategoriaCriada}
                onFormChange={setRecForm}
                onValorChange={setRecValorStr}
                onSemFimChange={setRecSemFim}
              />
            )
          ) : (
            <>
              <div>
                <label className="block text-sm font-medium mb-1.5">Tipo</label>
                <select className="panel-input" value={form.tipo} onChange={(e) => setForm({ ...form, tipo: e.target.value as TipoLancamento })}>
                  <option value="receita">Receita</option>
                  <option value="despesa">Despesa</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1.5">Descrição</label>
                <input className="panel-input" value={form.descricao} onChange={(e) => setForm({ ...form, descricao: e.target.value })} />
              </div>
              {!editing?.recorrencia_id && (
                <FinCategoriaSelect
                  categorias={categorias}
                  tipo={form.tipo}
                  value={form.categoria_id}
                  onChange={(id) => setForm({ ...form, categoria_id: id ?? '' })}
                  onCategoriaCriada={(c) => {
                    handleCategoriaCriada(c);
                    setForm((f) => ({ ...f, categoria_id: c.id }));
                  }}
                />
              )}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-sm font-medium mb-1.5">Valor previsto (R$)</label>
                  <input type="number" step="0.01" className="panel-input" value={form.valor} onChange={(e) => setForm({ ...form, valor: e.target.value })} />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1.5">Vencimento</label>
                  <input type="date" className="panel-input" value={form.data_vencimento} onChange={(e) => setForm({ ...form, data_vencimento: e.target.value })} />
                </div>
              </div>

              {editing && lancamentoConcluido(editing.status) && editEscopo === 'mes' && (
                <>
                  <div>
                    <label className="block text-sm font-medium mb-1.5">Valor real (R$)</label>
                    <input
                      type="number"
                      step="0.01"
                      className="panel-input"
                      value={form.valorRealizado}
                      onChange={(e) => setForm({ ...form, valorRealizado: e.target.value })}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1.5">Data de pagamento/recebimento</label>
                    <input
                      type="date"
                      className="panel-input"
                      value={form.data_efetivacao}
                      onChange={(e) => setForm({ ...form, data_efetivacao: e.target.value })}
                    />
                  </div>
                </>
              )}

              {!editing && (
                <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                  <label className="flex items-center gap-2 text-sm font-medium cursor-pointer">
                    <input
                      type="checkbox"
                      checked={recorrente}
                      onChange={(e) => setRecorrente(e.target.checked)}
                      className="rounded border-slate-300"
                    />
                    Repetir todo mês
                  </label>
                  {recorrente && (
                    <>
                      <p className="text-xs text-slate-500">
                        Vence todo dia{' '}
                        <strong>
                          {form.data_vencimento
                            ? new Date(`${form.data_vencimento}T12:00:00`).getDate()
                            : '—'}
                        </strong>{' '}
                        do mês, a partir de{' '}
                        {form.data_vencimento
                          ? new Date(`${form.data_vencimento}T12:00:00`).toLocaleDateString('pt-BR')
                          : '—'}
                        .
                      </p>
                      <p className="text-xs text-violet-700">
                        A categoria escolhida acima será repetida em todos os meses.
                      </p>
                      <label className="flex items-center gap-2 text-sm cursor-pointer">
                        <input
                          type="checkbox"
                          checked={semFim}
                          onChange={(e) => setSemFim(e.target.checked)}
                          className="rounded border-slate-300"
                        />
                        Sem data fim
                      </label>
                      {!semFim && (
                        <div>
                          <label className="block text-sm font-medium mb-1.5">Repetir até</label>
                          <input
                            type="date"
                            className="panel-input"
                            value={dataFim}
                            onChange={(e) => setDataFim(e.target.value)}
                          />
                        </div>
                      )}
                    </>
                  )}
                </div>
              )}

              {editing?.recorrencia_id && editEscopo === 'mes' && (
                <p className="text-xs text-violet-700 bg-violet-50 border border-violet-100 rounded-lg px-3 py-2">
                  Alteração válida apenas para <strong>{mesLabel(mes)}</strong>. Para mudar todos os meses, use{' '}
                  <strong>Toda a recorrência</strong>.
                </p>
              )}
            </>
          )}

          {saveError && <p className="text-sm text-red-600">{saveError}</p>}
          <div className="flex gap-2">
            <button type="button" className="panel-btn-ghost flex-1" onClick={() => setModal(false)}>Cancelar</button>
            <button type="button" className="panel-btn-primary flex-1" onClick={salvar}>Salvar</button>
          </div>
        </div>
      </FinModal>

      <FinModal
        open={!!payTarget}
        title={payTarget?.tipo === 'receita' ? 'Confirmar recebimento' : 'Confirmar pagamento'}
        onClose={() => setPayTarget(null)}
      >
        {payTarget && (
          <div className="space-y-4">
            <p className="text-sm text-slate-600">
              <strong>{payTarget.descricao}</strong>
            </p>
            <div className="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm">
              <span className="text-slate-500">Valor previsto: </span>
              <strong>{formatBRL(payTarget.valor)}</strong>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Valor real (R$)</label>
              <input
                type="number"
                step="0.01"
                className="panel-input"
                value={payValorReal}
                onChange={(e) => setPayValorReal(e.target.value)}
                autoFocus
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Data de pagamento/recebimento</label>
              <input
                type="date"
                className="panel-input"
                value={payDataEfetivacao}
                onChange={(e) => setPayDataEfetivacao(e.target.value)}
              />
            </div>
            <div className="flex gap-2">
              <button type="button" className="panel-btn-ghost flex-1" onClick={() => setPayTarget(null)}>
                Cancelar
              </button>
              <button type="button" className="panel-btn-primary flex-1" onClick={confirmarPagamento}>
                {payTarget.tipo === 'receita' ? 'Confirmar recebimento' : 'Confirmar pagamento'}
              </button>
            </div>
          </div>
        )}
      </FinModal>
    </div>
  );
}
