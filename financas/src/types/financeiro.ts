export type TipoLancamento = 'receita' | 'despesa';

export type StatusReceita = 'prevista' | 'recebida' | 'cancelada';
export type StatusDespesa = 'prevista' | 'paga' | 'cancelada';
export type StatusLancamento = StatusReceita | StatusDespesa;

export type Lancamento = {
  id: number | null;
  tipo: TipoLancamento;
  descricao: string;
  valor: number;
  data_vencimento: string;
  mes_referencia: string;
  status: StatusLancamento;
  recorrencia_id: number | null;
  projetado?: boolean;
};

export type Recorrencia = {
  id: number;
  tipo: TipoLancamento;
  descricao: string;
  valor: number;
  dia_vencimento: number;
  data_inicio: string;
  data_fim: string | null;
  ativa: boolean;
};

export type ResumoMes = {
  mes: string;
  receitas_previstas: number;
  despesas_previstas: number;
  saldo_previsto: number;
  receitas_realizadas: number;
  despesas_realizadas: number;
  saldo_realizado: number;
  saldo_acumulado_previsto?: number;
  saldo_acumulado_realizado?: number;
};

export type FinConfig = {
  saldo_referencia: number;
  data_referencia: string;
};
