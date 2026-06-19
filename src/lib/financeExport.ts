import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';
import type { Lancamento } from '../types/financeiro';
import { formatBRL, lancamentoConcluido, mesLabel, valorRealizadoLancamento } from './financeFormat';

function statusLabel(s: string): string {
  if (s === 'recebida' || s === 'paga') return 'Concluído';
  if (s === 'cancelada') return 'Cancelada';
  return 'Prevista';
}

function rowFromLancamento(l: Lancamento): (string | number)[] {
  return [
    l.tipo === 'receita' ? 'Receita' : 'Despesa',
    l.descricao,
    l.categoria_nome ?? '—',
    l.data_vencimento,
    l.data_efetivacao ?? '—',
    l.valor,
    lancamentoConcluido(l.status) ? valorRealizadoLancamento(l) : '—',
    statusLabel(l.status),
  ];
}

const HEADERS = [
  'Tipo',
  'Descrição',
  'Categoria',
  'Vencimento',
  'Efetivação',
  'Previsto (R$)',
  'Real (R$)',
  'Status',
];

export function exportMesXlsx(mes: string, lancamentos: Lancamento[]) {
  const rows = lancamentos.map(rowFromLancamento);
  const ws = XLSX.utils.aoa_to_sheet([HEADERS, ...rows]);
  ws['!cols'] = [{ wch: 10 }, { wch: 28 }, { wch: 16 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }];
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, mesLabel(mes).slice(0, 31));
  XLSX.writeFile(wb, `financas-${mes}.xlsx`);
}

export function exportMesPdf(mes: string, lancamentos: Lancamento[]) {
  const doc = new jsPDF({ orientation: 'landscape' });
  doc.setFontSize(14);
  doc.text(`Finanças — ${mesLabel(mes)}/${mes.slice(0, 4)}`, 14, 16);
  doc.setFontSize(9);
  doc.text(`Gerado em ${new Date().toLocaleString('pt-BR')}`, 14, 22);

  autoTable(doc, {
    head: [HEADERS],
    body: lancamentos.map(rowFromLancamento),
    startY: 28,
    styles: { fontSize: 8 },
    headStyles: { fillColor: [26, 29, 38] },
  });

  doc.save(`financas-${mes}.pdf`);
}

export function exportAnoXlsx(
  ano: number,
  meses: Array<{
    mes: string;
    receitas_previstas: number;
    despesas_previstas: number;
    saldo_previsto: number;
    receitas_realizadas: number;
    despesas_realizadas: number;
    saldo_realizado: number;
  }>
) {
  const headers = [
    'Mês',
    'Rec. previstas',
    'Desp. previstas',
    'Saldo previsto',
    'Rec. realizadas',
    'Desp. realizadas',
    'Saldo realizado',
  ];
  const rows = meses.map((m) => [
    m.mes,
    m.receitas_previstas,
    m.despesas_previstas,
    m.saldo_previsto,
    m.receitas_realizadas,
    m.despesas_realizadas,
    m.saldo_realizado,
  ]);
  const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, String(ano));
  XLSX.writeFile(wb, `financas-ano-${ano}.xlsx`);
}

export function exportAnoPdf(ano: number, meses: Parameters<typeof exportAnoXlsx>[1]) {
  const doc = new jsPDF();
  doc.setFontSize(14);
  doc.text(`Finanças — Ano ${ano}`, 14, 16);
  autoTable(doc, {
    head: [['Mês', 'Saldo previsto', 'Saldo realizado', 'Receitas prev.', 'Despesas prev.']],
    body: meses.map((m) => [
      m.mes,
      formatBRL(m.saldo_previsto),
      formatBRL(m.saldo_realizado),
      formatBRL(m.receitas_previstas),
      formatBRL(m.despesas_previstas),
    ]),
    startY: 24,
    styles: { fontSize: 9 },
    headStyles: { fillColor: [26, 29, 38] },
  });
  doc.save(`financas-ano-${ano}.pdf`);
}
