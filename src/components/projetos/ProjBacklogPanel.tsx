import { useCallback, useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import { projFetch } from '../../lib/projetoApi';
import type { Demanda, TipoDemanda } from '../../types/projetos';
import ProjDemandaCard from './ProjDemandaCard';
import ProjDemandaModal, { type DemandaFormState } from './ProjDemandaModal';

type Filtro = 'todas' | 'pendentes' | 'concluidas';

export default function ProjBacklogPanel() {
  const [lista, setLista] = useState<Demanda[]>([]);
  const [tipos, setTipos] = useState<TipoDemanda[]>([]);
  const [loading, setLoading] = useState(true);
  const [filtro, setFiltro] = useState<Filtro>('pendentes');
  const [modal, setModal] = useState(false);
  const [editing, setEditing] = useState<Demanda | null>(null);

  const carregar = useCallback(async () => {
    setLoading(true);
    try {
      const [demData, tiposData] = await Promise.all([
        projFetch<{ demandas: Demanda[] }>(`/demandas.php?backlog=1&status=${filtro}`),
        projFetch<{ tipos: TipoDemanda[] }>('/tipos.php'),
      ]);
      setLista(demData.demandas);
      setTipos(tiposData.tipos);
    } finally {
      setLoading(false);
    }
  }, [filtro]);

  useEffect(() => {
    carregar();
  }, [carregar]);

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

  return (
    <div className="max-w-3xl">
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <p className="text-sm text-slate-500">Demandas sem data definida — organize quando quiser agendar.</p>
        <button
          type="button"
          className="panel-btn-primary w-full sm:w-auto"
          onClick={() => {
            setEditing(null);
            setModal(true);
          }}
        >
          <Plus size={16} /> Nova no backlog
        </button>
      </div>

      <div className="flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 mb-4 w-full sm:w-auto sm:inline-flex">
        {(['pendentes', 'todas', 'concluidas'] as const).map((f) => (
          <button
            key={f}
            type="button"
            onClick={() => setFiltro(f)}
            className={`flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium ${
              filtro === f ? 'bg-white shadow-sm text-[#1A1D26]' : 'text-slate-500'
            }`}
          >
            {f === 'pendentes' ? 'Pendentes' : f === 'concluidas' ? 'Concluídas' : 'Todas'}
          </button>
        ))}
      </div>

      {loading ? (
        <p className="text-slate-500">Carregando…</p>
      ) : lista.length === 0 ? (
        <div className="panel-card text-center text-slate-500 py-12">Nenhuma demanda no backlog.</div>
      ) : (
        <div className="space-y-2">
          {lista.map((d) => (
            <ProjDemandaCard
              key={d.id}
              demanda={d}
              onEdit={(item) => {
                setEditing(item);
                setModal(true);
              }}
              onChanged={carregar}
            />
          ))}
        </div>
      )}

      <ProjDemandaModal
        open={modal}
        editing={editing}
        tipos={tipos}
        allowSemData
        onClose={() => setModal(false)}
        onSave={salvar}
      />
    </div>
  );
}
