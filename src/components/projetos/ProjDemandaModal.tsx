import { useEffect, useState } from 'react';
import { Plus, X } from 'lucide-react';
import type { Demanda, TipoDemanda } from '../../types/projetos';
import FinModal from '../finance/FinModal';

export type DemandaFormState = {
  titulo: string;
  descricao: string;
  tipo_id: number | '';
  data_prevista: string;
  prioridade: 'baixa' | 'media' | 'alta';
  checklist: Array<{ texto: string; concluido?: boolean }>;
};

type Props = {
  open: boolean;
  editing: Demanda | null;
  tipos: TipoDemanda[];
  defaultData?: string;
  allowSemData?: boolean;
  onClose: () => void;
  onSave: (form: DemandaFormState) => Promise<void>;
};

export function emptyDemandaForm(defaultData = '', tipoId: number | '' = ''): DemandaFormState {
  return {
    titulo: '',
    descricao: '',
    tipo_id: tipoId,
    data_prevista: defaultData,
    prioridade: 'media',
    checklist: [],
  };
}

export default function ProjDemandaModal({
  open,
  editing,
  tipos,
  defaultData = '',
  allowSemData = false,
  onClose,
  onSave,
}: Props) {
  const [form, setForm] = useState<DemandaFormState>(emptyDemandaForm(defaultData));
  const [novoItem, setNovoItem] = useState('');
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState('');

  useEffect(() => {
    if (!open) return;
    if (editing) {
      setForm({
        titulo: editing.titulo,
        descricao: editing.descricao ?? '',
        tipo_id: editing.tipo_id,
        data_prevista: editing.data_prevista ?? '',
        prioridade: editing.prioridade,
        checklist: editing.checklist?.map((c) => ({ texto: c.texto, concluido: c.concluido })) ?? [],
      });
    } else {
      setForm(emptyDemandaForm(defaultData, tipos[0]?.id ?? ''));
    }
    setNovoItem('');
    setErro('');
  }, [open, editing, defaultData, tipos]);

  const addItem = () => {
    const t = novoItem.trim();
    if (!t) return;
    setForm((f) => ({ ...f, checklist: [...f.checklist, { texto: t }] }));
    setNovoItem('');
  };

  const removeItem = (idx: number) => {
    setForm((f) => ({ ...f, checklist: f.checklist.filter((_, i) => i !== idx) }));
  };

  const salvar = async () => {
    setErro('');
    if (!form.titulo.trim()) {
      setErro('Informe o título.');
      return;
    }
    if (!form.tipo_id) {
      setErro('Selecione o tipo.');
      return;
    }
    setSalvando(true);
    try {
      await onSave(form);
      onClose();
    } catch (e) {
      setErro(e instanceof Error ? e.message : 'Erro ao salvar');
    } finally {
      setSalvando(false);
    }
  };

  return (
    <FinModal open={open} title={editing ? 'Editar demanda' : 'Nova demanda'} onClose={onClose}>
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1.5">Título</label>
          <input
            className="panel-input"
            value={form.titulo}
            onChange={(e) => setForm({ ...form, titulo: e.target.value })}
            placeholder="O que precisa fazer?"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1.5">Descrição (opcional)</label>
          <textarea
            className="panel-input min-h-[72px] py-2"
            value={form.descricao}
            onChange={(e) => setForm({ ...form, descricao: e.target.value })}
            rows={2}
          />
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label className="block text-sm font-medium mb-1.5">Tipo</label>
            <select
              className="panel-input"
              value={form.tipo_id}
              onChange={(e) => setForm({ ...form, tipo_id: Number(e.target.value) })}
            >
              {tipos.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.nome}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium mb-1.5">Prioridade</label>
            <select
              className="panel-input"
              value={form.prioridade}
              onChange={(e) => setForm({ ...form, prioridade: e.target.value as DemandaFormState['prioridade'] })}
            >
              <option value="baixa">Baixa</option>
              <option value="media">Média</option>
              <option value="alta">Alta</option>
            </select>
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1.5">
            Data prevista {allowSemData && <span className="text-slate-400 font-normal">(vazio = backlog)</span>}
          </label>
          <input
            type="date"
            className="panel-input"
            value={form.data_prevista}
            onChange={(e) => setForm({ ...form, data_prevista: e.target.value })}
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-1.5">Checklist (opcional)</label>
          <ul className="space-y-1.5 mb-2">
            {form.checklist.map((item, i) => (
              <li key={i} className="flex items-center gap-2 text-sm bg-slate-50 rounded-lg px-3 py-2">
                <span className="flex-1">{item.texto}</span>
                <button type="button" className="text-slate-400 hover:text-red-600" onClick={() => removeItem(i)}>
                  <X size={14} />
                </button>
              </li>
            ))}
          </ul>
          <div className="flex gap-2">
            <input
              className="panel-input flex-1"
              placeholder="Novo item…"
              value={novoItem}
              onChange={(e) => setNovoItem(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addItem())}
            />
            <button type="button" className="panel-btn-ghost px-3" onClick={addItem}>
              <Plus size={16} />
            </button>
          </div>
        </div>

        {erro && <p className="text-sm text-red-600">{erro}</p>}

        <div className="flex gap-2">
          <button type="button" className="panel-btn-ghost flex-1" onClick={onClose} disabled={salvando}>
            Cancelar
          </button>
          <button type="button" className="panel-btn-primary flex-1" onClick={salvar} disabled={salvando}>
            {salvando ? 'Salvando…' : 'Salvar'}
          </button>
        </div>
      </div>
    </FinModal>
  );
}
