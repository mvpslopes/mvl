import type { Categoria, Recorrencia, TipoLancamento } from '../../types/financeiro';
import FinCategoriaSelect from './FinCategoriaSelect';

export type RecorrenciaFormState = Omit<Recorrencia, 'id'>;

type Props = {
  form: RecorrenciaFormState;
  valorStr: string;
  semFim: boolean;
  categorias?: Categoria[];
  onCategoriaCriada?: (categoria: Categoria) => void;
  onFormChange: (form: RecorrenciaFormState) => void;
  onValorChange: (value: string) => void;
  onSemFimChange: (value: boolean) => void;
};

export default function FinRecorrenciaForm({
  form,
  valorStr,
  semFim,
  categorias = [],
  onCategoriaCriada,
  onFormChange,
  onValorChange,
  onSemFimChange,
}: Props) {
  return (
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium mb-1.5">Tipo</label>
        <select
          className="panel-input"
          value={form.tipo}
          onChange={(e) => onFormChange({ ...form, tipo: e.target.value as TipoLancamento })}
        >
          <option value="receita">Receita</option>
          <option value="despesa">Despesa</option>
        </select>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1.5">Descrição</label>
        <input
          className="panel-input"
          value={form.descricao}
          onChange={(e) => onFormChange({ ...form, descricao: e.target.value })}
        />
      </div>
      <FinCategoriaSelect
        categorias={categorias}
        tipo={form.tipo}
        value={form.categoria_id ?? ''}
        onChange={(id) => onFormChange({ ...form, categoria_id: id })}
        onCategoriaCriada={onCategoriaCriada}
      />
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label className="block text-sm font-medium mb-1.5">Valor (R$)</label>
          <input
            type="number"
            step="0.01"
            className="panel-input"
            value={valorStr}
            onChange={(e) => onValorChange(e.target.value)}
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1.5">Dia do vencimento</label>
          <input
            type="number"
            min={1}
            max={31}
            className="panel-input"
            value={form.dia_vencimento}
            onChange={(e) => onFormChange({ ...form, dia_vencimento: Number(e.target.value) })}
          />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1.5">Início</label>
        <input
          type="date"
          className="panel-input"
          value={form.data_inicio}
          onChange={(e) => onFormChange({ ...form, data_inicio: e.target.value })}
        />
      </div>
      <label className="flex items-center gap-2 text-sm cursor-pointer">
        <input
          type="checkbox"
          checked={semFim}
          onChange={(e) => onSemFimChange(e.target.checked)}
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
            value={form.data_fim ?? ''}
            onChange={(e) => onFormChange({ ...form, data_fim: e.target.value })}
          />
        </div>
      )}
      <label className="flex items-center gap-2 text-sm cursor-pointer">
        <input
          type="checkbox"
          checked={form.ativa}
          onChange={(e) => onFormChange({ ...form, ativa: e.target.checked })}
          className="rounded border-slate-300"
        />
        Recorrência ativa
      </label>
    </div>
  );
}

export function emptyRecorrenciaForm(): RecorrenciaFormState {
  return {
    tipo: 'despesa',
    descricao: '',
    valor: 0,
    dia_vencimento: 1,
    data_inicio: new Date().toISOString().slice(0, 10),
    data_fim: null,
    categoria_id: null,
    ativa: true,
  };
}

export function recorrenciaToForm(r: Recorrencia): RecorrenciaFormState {
  return {
    tipo: r.tipo,
    descricao: r.descricao,
    valor: r.valor,
    dia_vencimento: r.dia_vencimento,
    data_inicio: r.data_inicio,
    data_fim: r.data_fim,
    categoria_id: r.categoria_id ?? null,
    ativa: r.ativa,
  };
}

export async function saveRecorrencia(
  finFetch: <T>(path: string, opts?: { method?: string; body?: unknown }) => Promise<T>,
  form: RecorrenciaFormState,
  valorStr: string,
  semFim: boolean,
  editId?: number | null
) {
  const body = {
    ...form,
    valor: parseFloat(valorStr),
    data_fim: semFim ? null : form.data_fim || null,
  };
  if (editId) {
    await finFetch('/recorrencias.php', { method: 'PUT', body: { id: editId, ...body } });
  } else {
    await finFetch('/recorrencias.php', { method: 'POST', body });
  }
}
