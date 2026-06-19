import { useState } from 'react';
import { Plus } from 'lucide-react';
import { finFetch } from '../../lib/financeApi';
import type { Categoria, TipoLancamento } from '../../types/financeiro';
import FinModal from './FinModal';

export const CORES_CATEGORIA = [
  '#10b981',
  '#ef4444',
  '#f97316',
  '#3b82f6',
  '#8b5cf6',
  '#ec4899',
  '#eab308',
  '#6366f1',
  '#64748b',
];

type Props = {
  categorias: Categoria[];
  tipo: TipoLancamento;
  value: number | '' | null;
  onChange: (id: number | null) => void;
  onCategoriaCriada?: (categoria: Categoria) => void;
  label?: string;
};

export default function FinCategoriaSelect({
  categorias,
  tipo,
  value,
  onChange,
  onCategoriaCriada,
  label = 'Categoria',
}: Props) {
  const [modal, setModal] = useState(false);
  const [nome, setNome] = useState('');
  const [cor, setCor] = useState(CORES_CATEGORIA[0]);
  const [msg, setMsg] = useState('');
  const [salvando, setSalvando] = useState(false);

  const filtradas = categorias.filter((c) => c.ativa && (c.tipo === 'ambos' || c.tipo === tipo));

  const abrirNovo = () => {
    setNome('');
    setCor(CORES_CATEGORIA[0]);
    setMsg('');
    setModal(true);
  };

  const salvar = async () => {
    const nomeTrim = nome.trim();
    if (!nomeTrim) {
      setMsg('Informe o nome da categoria.');
      return;
    }
    setSalvando(true);
    setMsg('');
    try {
      const data = await finFetch<{ categoria: Categoria }>('/categorias.php', {
        method: 'POST',
        body: { nome: nomeTrim, tipo, cor, ativa: true },
      });
      onCategoriaCriada?.(data.categoria);
      onChange(data.categoria.id);
      setModal(false);
    } catch (err) {
      setMsg(err instanceof Error ? err.message : 'Erro ao salvar');
    } finally {
      setSalvando(false);
    }
  };

  const tipoLabel = tipo === 'receita' ? 'receita' : 'despesa';

  return (
    <div>
      <label className="block text-sm font-medium mb-1.5">{label}</label>
      <div className="flex gap-2">
        <select
          className="panel-input flex-1 min-w-0"
          value={value ?? ''}
          onChange={(e) => onChange(e.target.value ? Number(e.target.value) : null)}
        >
          <option value="">Sem categoria</option>
          {filtradas.map((c) => (
            <option key={c.id} value={c.id}>
              {c.nome}
            </option>
          ))}
        </select>
        <button
          type="button"
          className="panel-btn-ghost shrink-0 px-3 border border-slate-200"
          onClick={abrirNovo}
          title={`Nova categoria de ${tipoLabel}`}
          aria-label={`Nova categoria de ${tipoLabel}`}
        >
          <Plus size={18} />
        </button>
      </div>

      <FinModal open={modal} title={`Nova categoria de ${tipoLabel}`} onClose={() => setModal(false)} elevated>
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1.5">Nome</label>
            <input
              className="panel-input"
              placeholder="Ex.: Alimentação, Salário…"
              value={nome}
              onChange={(e) => setNome(e.target.value)}
              autoFocus
            />
          </div>
          <div>
            <p className="text-sm font-medium mb-2">Cor</p>
            <div className="flex flex-wrap gap-2">
              {CORES_CATEGORIA.map((c) => (
                <button
                  key={c}
                  type="button"
                  onClick={() => setCor(c)}
                  className={`w-8 h-8 rounded-full border-2 ${cor === c ? 'border-[#1A1D26] scale-110' : 'border-transparent'}`}
                  style={{ backgroundColor: c }}
                  aria-label={`Cor ${c}`}
                />
              ))}
            </div>
          </div>
          <p className="text-xs text-slate-500">
            Será criada como categoria de <strong>{tipoLabel}</strong> e já selecionada neste lançamento.
          </p>
          {msg && <p className="text-sm text-red-600">{msg}</p>}
          <button type="button" className="panel-btn-primary w-full" onClick={salvar} disabled={salvando}>
            {salvando ? 'Salvando…' : 'Criar categoria'}
          </button>
        </div>
      </FinModal>
    </div>
  );
}
