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

export function statusLabel(status: string, tipo: string): string {
  const map: Record<string, string> = {
    prevista: 'Prevista',
    recebida: 'Recebida',
    paga: 'Paga',
    cancelada: 'Cancelada',
  };
  return map[status] ?? status;
}

export function proximoStatus(tipo: string, status: string): string | null {
  if (status === 'cancelada') return null;
  if (tipo === 'receita') {
    if (status === 'prevista') return 'recebida';
    return null;
  }
  if (status === 'prevista') return 'paga';
  return null;
}
