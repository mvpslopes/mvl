export type IdeiaStatus = 'raw' | 'usado';

export type IdeiaKeyword = {
  id: number;
  nome: string;
  slug: string;
  count?: number;
};

export type Ideia = {
  id: number;
  texto: string;
  fonte: string | null;
  favorito: boolean;
  status: IdeiaStatus;
  created_at: string;
  updated_at: string | null;
  keywords: IdeiaKeyword[];
};

export type IdeiaGrupoKeyword = {
  keyword: IdeiaKeyword;
  ideias: Ideia[];
};
