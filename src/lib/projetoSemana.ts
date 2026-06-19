const NOMES_SEMANA = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as const;

export function isoWeekFromDate(date: Date = new Date()): string {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() + 3 - ((d.getDay() + 6) % 7));
  const week1 = new Date(d.getFullYear(), 0, 4);
  const week =
    1 + Math.round(((d.getTime() - week1.getTime()) / 86400000 - 3 + ((week1.getDay() + 6) % 7)) / 7);
  return `${d.getFullYear()}-W${String(week).padStart(2, '0')}`;
}

export function shiftIsoWeek(iso: string, delta: number): string {
  const range = parseIsoWeek(iso);
  const d = new Date(range.de + 'T12:00:00');
  d.setDate(d.getDate() + delta * 7);
  return isoWeekFromDate(d);
}

export function parseIsoWeek(iso: string): { iso: string; de: string; ate: string } {
  const m = iso.match(/^(\d{4})-W(\d{2})$/);
  if (!m) throw new Error('Semana inválida');
  const year = parseInt(m[1], 10);
  const week = parseInt(m[2], 10);
  const jan4 = new Date(year, 0, 4);
  const dayOfWeek = (jan4.getDay() + 6) % 7;
  const monday = new Date(jan4);
  monday.setDate(jan4.getDate() - dayOfWeek + (week - 1) * 7);
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  const fmt = (dt: Date) =>
    `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
  return { iso, de: fmt(monday), ate: fmt(sunday) };
}

export function diasDaSemana(de: string): Array<{ data: string; label: string; nome: string }> {
  const out: Array<{ data: string; label: string; nome: string }> = [];
  const d = new Date(de + 'T12:00:00');
  for (let i = 0; i < 7; i++) {
    const cur = new Date(d);
    cur.setDate(d.getDate() + i);
    const data = `${cur.getFullYear()}-${String(cur.getMonth() + 1).padStart(2, '0')}-${String(cur.getDate()).padStart(2, '0')}`;
    out.push({
      data,
      label: String(cur.getDate()).padStart(2, '0'),
      nome: NOMES_SEMANA[i],
    });
  }
  return out;
}

export function formatSemanaLabel(de: string, ate: string): string {
  const d1 = new Date(de + 'T12:00:00');
  const d2 = new Date(ate + 'T12:00:00');
  const f = (dt: Date) => dt.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
  return `${f(d1)} – ${f(d2)}`;
}

export function isHoje(data: string): boolean {
  const hoje = new Date();
  const h = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
  return data === h;
}
