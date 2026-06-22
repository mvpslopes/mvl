export type TipoLancamento = 'receita' | 'despesa';
export type StatusLancamento = 'prevista' | 'recebida' | 'paga' | 'cancelada';

export type Categoria = {
  id: number;
  nome: string;
  tipo: 'receita' | 'despesa' | 'ambos';
  cor: string;
  ativa: boolean;
};

export type Lancamento = {
  id: number | null;
  tipo: TipoLancamento;
  descricao: string;
  valor: number;
  valor_realizado: number | null;
  data_vencimento: string;
  data_efetivacao: string | null;
  mes_referencia: string;
  status: StatusLancamento;
  categoria_id: number | null;
  categoria_nome?: string | null;
  categoria_cor?: string | null;
  recorrencia_id: number | null;
  projetado?: boolean;
  alerta_vencimento?: 'vencida' | 'proxima';
};

export type Recorrencia = {
  id: number;
  tipo: TipoLancamento;
  descricao: string;
  valor: number;
  dia_vencimento: number;
  data_inicio: string;
  data_fim: string | null;
  categoria_id?: number | null;
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
  saldo_acumulado_previsto_anterior?: number;
  saldo_acumulado_realizado_anterior?: number;
  mes_anterior_ref?: string;
  mes_anterior?: ResumoMes;
  variacao?: {
    receitas_previstas: number;
    despesas_previstas: number;
    saldo_previsto: number;
    receitas_realizadas: number;
    despesas_realizadas: number;
    saldo_realizado: number;
  };
  por_categoria?: {
    receitas: ResumoCategoriaAno[];
    despesas: ResumoCategoriaAno[];
  };
};

export type FinConfig = {
  saldo_referencia: number;
  data_referencia: string;
};

export type ResumoCategoriaAno = {
  categoria_id: number | null;
  categoria_nome: string;
  categoria_cor: string;
  previsto: number;
  realizado: number;
};

export type ResumoAnoDashboard = {
  ano: number;
  meses: ResumoMes[];
  totais_ano?: {
    receitas_previstas: number;
    despesas_previstas: number;
    saldo_previsto: number;
    receitas_realizadas: number;
    despesas_realizadas: number;
    saldo_realizado: number;
  };
  melhor_mes?: ResumoMes | null;
  pior_mes?: ResumoMes | null;
  por_categoria?: {
    receitas: ResumoCategoriaAno[];
    despesas: ResumoCategoriaAno[];
  };
};
