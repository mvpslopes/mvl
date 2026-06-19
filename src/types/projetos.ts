export type TipoDemanda = {
  id: number;
  nome: string;
  cor: string;
  ordem: number;
};

export type StatusDemanda = 'pendente' | 'em_andamento' | 'concluida' | 'cancelada';
export type PrioridadeDemanda = 'baixa' | 'media' | 'alta';

export type ChecklistItem = {
  id: number;
  texto: string;
  concluido: boolean;
  ordem: number;
};

export type Demanda = {
  id: number;
  titulo: string;
  descricao: string | null;
  tipo_id: number;
  tipo_nome?: string | null;
  tipo_cor?: string | null;
  data_prevista: string | null;
  status: StatusDemanda;
  prioridade: PrioridadeDemanda;
  concluida_em: string | null;
  checklist?: ChecklistItem[];
  checklist_total?: number;
  checklist_concluidos?: number;
};

export type SemanaInfo = {
  iso: string;
  de: string;
  ate: string;
};

export type SemanaResponse = {
  semana: SemanaInfo;
  demandas: Demanda[];
  por_dia: Record<string, Demanda[]>;
};
