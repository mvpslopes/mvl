import { useCallback, useEffect, useState } from 'react';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { projFetch } from '../../lib/projetoApi';
import {
  diasDaSemana,
  formatSemanaLabel,
  isoWeekFromDate,
  isHoje,
  parseIsoWeek,
  shiftIsoWeek,
} from '../../lib/projetoSemana';
import type { Demanda, SemanaResponse, TipoDemanda } from '../../types/projetos';
import ProjDemandaCard from './ProjDemandaCard';
import ProjDemandaModal, { type DemandaFormState } from './ProjDemandaModal';

type Filtro = 'todas' | 'pendentes' | 'concluidas';

export default function ProjSemanaPanel() {
  const [semanaIso, setSemanaIso] = useState(isoWeekFromDate());
  const [data, setData] = useState<SemanaResponse | null>(null);
  const [tipos, setTipos] = useState<TipoDemanda[]>([]);
  const [loading, setLoading] = useState(true);
  const [filtro, setFiltro] = useState<Filtro>('pendentes');
  const [modal, setModal] = useState(false);
  const [editing, setEditing] = useState<Demanda | null>(null);
  const [diaNovo, setDiaNovo] = useState('');
  const [diaAberto, setDiaAberto] = useState<string | null>(null);

  const range = parseIsoWeek(semanaIso);
  const dias = diasDaSemana(range.de);

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const [semData, tiposData] = await Promise.all([
        projFetch<SemanaResponse & { success: boolean }>(
          `/demandas.php?semana=${semanaIso}&status=${filtro}`
        ),
        projFetch<{ tipos: TipoDemanda[] }>('/tipos.php'),
      ]);
      setData(semData);
      setTipos(tiposData.tipos);
    } finally {
      setLoading(false);
    }
  }, [semanaIso, filtro]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const abrirNovo = (dataDia: string) => {
    setEditing(null);
    setDiaNovo(dataDia);
    setModal(true);
  };

  const abrirEditar = (d: Demanda) => {
    setEditing(d);
    setDiaNovo(d.data_prevista ?? '');
    setModal(true);
  };

  const salvar = async (form: DemandaFormState) => {
    const body = {
      titulo: form.titulo.trim(),
      descricao: form.descricao.trim() || null,
      tipo_id: Number(form.tipo_id),
      data_prevista: form.data_prevista || null,
      prioridade: form.prioridade,
      checklist: form.checklist,
    };
    if (editing) {
      await projFetch('/demandas.php', { method: 'PUT', body: { id: editing.id, ...body } });
    } else {
      await projFetch('/demandas.php', { method: 'POST', body });
    }
    await carregar();
  };

  const porDia = data?.por_dia ?? {};

  return (
    <div className="max-w-6xl">
      <div className="flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3 mb-4">
        <div className="flex items-center justify-center sm:justify-start gap-2">
          <button
            type="button"
            className="panel-btn-ghost p-2"
            onClick={() => setSemanaIso(shiftIsoWeek(semanaIso, -1))}
            aria-label="Semana anterior"
          >
            <ChevronLeft size={18} />
          </button>
          <div className="text-center sm:text-left min-w-[10rem]">
            <p className="font-semibold text-sm sm:text-base">{formatSemanaLabel(range.de, range.ate)}</p>
            <p className="text-[10px] text-slate-500">{semanaIso}</p>
          </div>
          <button
            type="button"
            className="panel-btn-ghost p-2"
            onClick={() => setSemanaIso(shiftIsoWeek(semanaIso, 1))}
            aria-label="Próxima semana"
          >
            <ChevronRight size={18} />
          </button>
          <button
            type="button"
            className="panel-btn-ghost text-xs py-1.5 px-2 ml-1"
            onClick={() => setSemanaIso(isoWeekFromDate())}
          >
            Hoje
          </button>
        </div>

        <div className="flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 w-full sm:w-auto">
          {(['pendentes', 'todas', 'concluidas'] as const).map((f) => (
            <button
              key={f}
              type="button"
              onClick={() => setFiltro(f)}
              className={`flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium capitalize ${
                filtro === f ? 'bg-white shadow-sm text-[#1A1D26]' : 'text-slate-500'
              }`}
            >
              {f === 'pendentes' ? 'Pendentes' : f === 'concluidas' ? 'Concluídas' : 'Todas'}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <p className="text-slate-500 py-8 text-center">Carregando…</p>
      ) : (
        <>
          {/* Desktop: grade 7 colunas */}
          <div className="hidden lg:grid lg:grid-cols-7 gap-2">
            {dias.map((dia) => {
              const itens = porDia[dia.data] ?? [];
              return (
                <div
                  key={dia.data}
                  className={`rounded-xl border min-h-[120px] flex flex-col ${
                    isHoje(dia.data) ? 'border-violet-300 bg-violet-50/40' : 'border-slate-200 bg-slate-50/50'
                  }`}
                >
                  <div className="px-2 py-2 border-b border-slate-200/80 flex items-center justify-between">
                    <div>
                      <p className="text-[10px] font-semibold uppercase text-slate-400">{dia.nome}</p>
                      <p className="text-sm font-bold">{dia.label}</p>
                    </div>
                    <span className="text-[10px] text-slate-500">{itens.length}</span>
                  </div>
                  <div className="p-1.5 space-y-1.5 flex-1 overflow-y-auto max-h-[420px]">
                    {itens.map((d) => (
                      <ProjDemandaCard key={d.id} demanda={d} onEdit={abrirEditar} onChanged={carregar} compact />
                    ))}
                  </div>
                  <button
                    type="button"
                    className="m-1.5 panel-btn-ghost text-xs py-1.5 w-[calc(100%-12px)]"
                    onClick={() => abrirNovo(dia.data)}
                  >
                    <Plus size={14} /> Adicionar
                  </button>
                </div>
              );
            })}
          </div>

          {/* Mobile / tablet: dias empilhados */}
          <div className="lg:hidden space-y-3">
            {dias.map((dia) => {
              const itens = porDia[dia.data] ?? [];
              const aberto = diaAberto === dia.data;
              return (
                <div
                  key={dia.data}
                  className={`panel-card !p-0 overflow-hidden ${isHoje(dia.data) ? 'ring-1 ring-violet-200' : ''}`}
                >
                  <button
                    type="button"
                    className="w-full flex items-center justify-between px-4 py-3 text-left"
                    onClick={() => setDiaAberto(aberto ? null : dia.data)}
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`w-10 h-10 rounded-xl flex flex-col items-center justify-center text-xs font-bold ${
                          isHoje(dia.data) ? 'bg-violet-100 text-violet-800' : 'bg-slate-100 text-slate-700'
                        }`}
                      >
                        <span className="text-[9px] font-medium uppercase">{dia.nome}</span>
                        {dia.label}
                      </div>
                      <div>
                        <p className="font-medium text-sm">
                          {new Date(dia.data + 'T12:00:00').toLocaleDateString('pt-BR', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'short',
                          })}
                        </p>
                        <p className="text-xs text-slate-500">
                          {itens.length} {itens.length === 1 ? 'demanda' : 'demandas'}
                        </p>
                      </div>
                    </div>
                    <ChevronRight size={18} className={`text-slate-400 transition-transform ${aberto ? 'rotate-90' : ''}`} />
                  </button>
                  {aberto && (
                    <div className="px-3 pb-3 space-y-2 border-t border-slate-100 pt-3">
                      {itens.map((d) => (
                        <ProjDemandaCard key={d.id} demanda={d} onEdit={abrirEditar} onChanged={carregar} />
                      ))}
                      <button type="button" className="panel-btn-ghost text-sm w-full" onClick={() => abrirNovo(dia.data)}>
                        <Plus size={16} /> Nova demanda
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </>
      )}

      <ProjDemandaModal
        open={modal}
        editing={editing}
        tipos={tipos}
        defaultData={diaNovo}
        onClose={() => setModal(false)}
        onSave={salvar}
      />
    </div>
  );
}
