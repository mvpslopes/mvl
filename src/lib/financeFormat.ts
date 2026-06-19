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
