export function formatBRL(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export function mesLabel(ym: string): string {
  const [y, m] = ym.split('-');
  const d = new Date(Number(y), Number(m) - 1, 1);
  const label = d.toLocaleDateString('pt-BR', { month: 'long' });
  return label.charAt(0).toUpperCase() + label.slice(1);
}

export function mesCurto(ym: string): string {
  const [y, m] = ym.split('-');
  const d = new Date(Number(y), Number(m) - 1, 1);
  return d.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');
}

export function valorRealizadoLancamento(l: { valor: number; valor_realizado?: number | null }): number {
  return l.valor_realizado ?? l.valor;
}

export function lancamentoConcluido(status: string): boolean {
  return status === 'recebida' || status === 'paga';
}

export function lancamentoEstaVencido(l: {
  status: string;
  data_vencimento: string;
}): boolean {
  if (l.status !== 'prevista') return false;
  const hoje = new Date();
  hoje.setHours(12, 0, 0, 0);
  const venc = new Date(`${l.data_vencimento}T12:00:00`);
  return venc < hoje;
}

export function diasAtrasoLancamento(dataVencimento: string): number {
  const hoje = new Date();
  hoje.setHours(12, 0, 0, 0);
  const venc = new Date(`${dataVencimento}T12:00:00`);
  const diff = Math.floor((hoje.getTime() - venc.getTime()) / (1000 * 60 * 60 * 24));
  return Math.max(0, diff);
}

/** Classes de fundo/borda para linha ou card no painel do mês. */
export function classesVisualLancamento(
  l: { tipo: string; status: string; data_vencimento: string; recorrencia_id?: number | null },
  selected: boolean,
  layout: 'row' | 'card' = 'row'
): string {
  if (selected) {
    return layout === 'card'
      ? 'border-sky-300 bg-sky-50 ring-1 ring-sky-200'
      : 'bg-sky-50 ring-1 ring-inset ring-sky-200';
  }

  if (lancamentoConcluido(l.status)) {
    if (l.tipo === 'receita') {
      return layout === 'card'
        ? 'border-emerald-400 bg-emerald-200/80'
        : 'bg-emerald-200/80';
    }
    return layout === 'card' ? 'border-red-400 bg-red-200/80' : 'bg-red-200/80';
  }

  if (lancamentoEstaVencido(l)) {
    return layout === 'card'
      ? 'border-red-300 bg-red-50 ring-1 ring-inset ring-red-200'
      : 'bg-red-50 border-l-4 border-l-red-500';
  }

  if (l.recorrencia_id) {
    return layout === 'card' ? 'border-violet-100 bg-violet-50/40' : 'bg-violet-50/40';
  }

  return layout === 'card' ? 'border-slate-100 bg-white' : '';
}
