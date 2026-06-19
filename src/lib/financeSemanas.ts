import type { Lancamento } from '../types/financeiro';
import { lancamentoConcluido, valorRealizadoLancamento } from './financeFormat';

export type ModoSemana = 'previsto' | 'realizado';

export type SemanaResumo = {
  index: number;
  de: string;
  ate: string;
  label: string;
  receitas: number;
  despesas: number;
  saldoSemana: number;
  saldoAcumulado: number;
};

function ultimoDiaMes(ym: string): number {
  const [y, m] = ym.split('-').map(Number);
  return new Date(y, m, 0).getDate();
}

export function semanasDoMes(ym: string): { de: string; ate: string; label: string }[] {
  const ultimo = ultimoDiaMes(ym);
  const blocos: [number, number][] = [
    [1, 7],
    [8, 14],
    [15, 21],
    [22, 28],
    [29, ultimo],
  ];

  return blocos
    .filter(([ini]) => ini <= ultimo)
    .map(([ini, fim]) => {
      const ate = Math.min(fim, ultimo);
      const de = `${ym}-${String(ini).padStart(2, '0')}`;
      const ateStr = `${ym}-${String(ate).padStart(2, '0')}`;
      const label =
        ini === ate
          ? String(ini).padStart(2, '0')
          : `${String(ini).padStart(2, '0')}–${String(ate).padStart(2, '0')}`;
      return { de, ate: ateStr, label };
    });
}

export function semanaIndexFromDate(data: string): number {
  const day = parseInt(data.slice(8, 10), 10);
  if (day <= 7) return 0;
  if (day <= 14) return 1;
  if (day <= 21) return 2;
  if (day <= 28) return 3;
  return 4;
}

export function lancamentoNaSemana(l: Lancamento, semanaIndex: number, ym: string): boolean {
  const defs = semanasDoMes(ym);
  if (semanaIndex < 0 || semanaIndex >= defs.length) return true;
  const data =
    l.data_efetivacao && lancamentoConcluido(l.status) ? l.data_efetivacao : l.data_vencimento;
  return semanaIndexFromDate(data) === semanaIndex;
}

function dataNoModo(l: Lancamento, modo: ModoSemana): string {
  if (modo === 'realizado' && l.data_efetivacao && lancamentoConcluido(l.status)) {
    return l.data_efetivacao;
  }
  return l.data_vencimento;
}

function valorNoModo(l: Lancamento, modo: ModoSemana): number | null {
  if (l.status === 'cancelada') return null;
  if (modo === 'previsto') return l.valor;
  if (l.tipo === 'receita' && l.status !== 'recebida') return null;
  if (l.tipo === 'despesa' && l.status !== 'paga') return null;
  return valorRealizadoLancamento(l);
}

function round(n: number): number {
  return Math.round(n * 100) / 100;
}

export function calcularSemanas(
  lancamentos: Lancamento[],
  ym: string,
  modo: ModoSemana
): SemanaResumo[] {
  const defs = semanasDoMes(ym);
  const buckets = defs.map((d) => ({ ...d, receitas: 0, despesas: 0 }));

  for (const l of lancamentos) {
    const v = valorNoModo(l, modo);
    if (v === null) continue;
    const data = dataNoModo(l, modo);
    if (!data.startsWith(ym)) continue;
    const idx = semanaIndexFromDate(data);
    if (idx >= buckets.length) continue;
    if (l.tipo === 'receita') buckets[idx].receitas += v;
    else buckets[idx].despesas += v;
  }

  let acum = 0;
  return buckets.map((b, i) => {
    const saldoSemana = b.receitas - b.despesas;
    acum += saldoSemana;
    return {
      index: i,
      de: b.de,
      ate: b.ate,
      label: b.label,
      receitas: round(b.receitas),
      despesas: round(b.despesas),
      saldoSemana: round(saldoSemana),
      saldoAcumulado: round(acum),
    };
  });
}

export function maxAbsSemana(semanas: SemanaResumo[]): number {
  let m = 1;
  for (const s of semanas) {
    m = Math.max(m, Math.abs(s.receitas), Math.abs(s.despesas), Math.abs(s.saldoSemana));
  }
  return m;
}
